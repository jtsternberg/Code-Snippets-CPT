<?php
/*
Plugin Name: Code Snippets CPT
Description: A code snippet custom post-type and shortcode for displaying your code snippets in your posts or pages.
Plugin URI: http://j.ustin.co/jAHRM3
Author: Jtsternberg
Author URI: http://about.me/jtsternberg
Donate link: http://j.ustin.co/rYL89n
Version: 1.0.5
*/

class CodeSnippitInit {

	protected $plugin_name = 'Code Snippets CPT';
	protected $cpt;
	protected $languages = array(
		'Python',
		'HTML',
		'CSS',
		'JavaScript',
		'PHP',
		'SQL',
		'Perl',
		'Ruby',
		'Bash',
		'C',
		'c-sharp' => 'C#',
		'HTML',
		'Java',
		'XHTML',
		'XML',
	);
	const VERSION = '1.0.4';

	public static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return CodeSnippitInit A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	private function __construct() {

		define( 'DWSNIPPET_PATH', plugin_dir_path( __FILE__ ) );
		define( 'DWSNIPPET_URL', plugins_url( '/', __FILE__ ) );

		// Custom Functions
		require_once( DWSNIPPET_PATH .'lib/functions.php' );

		// Snippet Post-Type Setup
		require_once( DWSNIPPET_PATH .'lib/Snippet_CPT_Setup.php' );
		$this->cpt = new Snippet_CPT_Setup();

		// Custom Taxonomy Setup
		require_once( DWSNIPPET_PATH .'lib/Snippet_Tax_Setup.php' );
		new Snippet_Tax_Setup( 'Snippet Category', 'Snippet Categories', array( $this->cpt->post_type ) );
		new Snippet_Tax_Setup( 'Snippet Tag', '', array( $this->cpt->post_type ), array( 'hierarchical' => false ) );
		$this->language = new Snippet_Tax_Setup( 'Language', '', array( $this->cpt->post_type ),  array( 'public' => false, 'show_ui' => false ) );
		// Custom metabox for the programming languages taxonomy
		$this->language->init_select_box();

		// Include our wysiwyg button script
		require_once( DWSNIPPET_PATH .'lib/CodeSnippitButton.php' );
		new CodeSnippitButton( $this->cpt, $this->language );

		// Snippet Shortcode Setup
		add_shortcode( 'snippet', array( $this, 'shortcode' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'add_languages' ) );
		add_filter( 'content_save_pre', array( $this, 'allow_unfiltered' ), 5 );
	}

	public function add_languages() {
		// make sure our default languages exist
		foreach ( $this->languages as $key => $language ) {
			$exists = is_numeric( $key )
				? get_term_by( 'name', $language, 'languages' )
				: get_term_by( 'slug', $key, 'languages' );
			if ( empty( $exists ) ) {
				$args = ! is_numeric( $key ) ? array( 'slug' => $key ) : array();
				wp_insert_term( $language, 'languages', $args );
			}
		}
	}

	public function allow_unfiltered( $value ) {
		global $post;

		if ( isset( $post->post_type ) && $this->cpt->post_type == $post->post_type && current_user_can( 'edit_posts' ) ) {
			kses_remove_filters();
		}

		return $value;
	}

	public function shortcode( $atts, $context ) {

		$atts = shortcode_atts( array(
			'id'           => false,
			'slug'         => '',
			'line_numbers' => true,
			'lang'         => '',
			'title_attr'   => true,
		), $atts, 'snippet' );

		$args = array(
			'post_type'   => 'code-snippets',
			'showposts'   => 1,
			'post_status' => 'published',
		);

		if ( $atts['id'] && is_numeric( $atts['id'] ) ) {
			$args['p'] = $atts['id'];
		} elseif ( $atts['slug'] && is_string( $atts['slug'] ) ) {
			$args['name'] = $atts['slug'];
		}

		$snippet = get_posts( $args );
		if ( is_wp_error( $snippet ) || empty( $snippet ) ) {
			return;
		}

		$snippet = $snippet[0];
		$snippet_id = $snippet->ID;

		if ( empty( $snippet->post_content ) ) {
			return;
		}

		$this->cpt->enqueue_prettify();

		$class = 'prettyprint';

		$line_nums = ! $atts['line_numbers'] || false === $atts['line_numbers'] || $atts['line_numbers'] === 'false' ? false : $atts['line_numbers'];

		if ( $line_nums ) {
			$class .= ' linenums';
			if ( is_numeric( $line_nums ) && 0 !== absint( $line_nums ) ) {
				$class .= ':' . absint( $line_nums );
			}
		}

		if ( ! empty( $atts['lang'] ) ) {
			$class .= ' lang-'. sanitize_html_class( $atts['lang'] );
		}
		elseif ( $lang_slug = $this->language->language_slug_from_post( $snippet_id ) ) {
			$class .= ' lang-'. $lang_slug;
		}

		$snippet_content = apply_filters( 'dsgnwrks_snippet_content', htmlentities( $snippet->post_content, ENT_COMPAT, 'UTF-8' ), $atts, $snippet );

		if ( $atts['title_attr'] && ! in_array( $atts['title_attr'], array( 'no', 'false' ), true ) ) {
			$title_attr = sprintf( ' title="%s"', esc_attr( $snippet->post_title ) );
		}

		return apply_filters( 'dsgnwrks_snippet_display', sprintf( '<pre class="%1$s"%2$s>%3$s</pre>', $class, $title_attr, $snippet_content ), $atts, $snippet );
	}

	/**
	 * Magic getter for our object.
	 *
	 * @param string $field
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 *
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'plugin_name':
			case 'cpt':
			case 'languages':
				return $this->{$field};
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}

}

CodeSnippitInit::get_instance();

function dsgnwrks_snippet_content_replace_tabs( $snippet_content ) {
	// Replace tabs w/ spaces as it is more readable
	$snippet_content = str_replace( "\t", "    ", $snippet_content );

	return $snippet_content;
}
add_filter( 'dsgnwrks_snippet_content', 'dsgnwrks_snippet_content_replace_tabs' );
