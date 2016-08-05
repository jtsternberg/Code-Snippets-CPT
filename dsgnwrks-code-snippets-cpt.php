<?php
/*
Plugin Name: Code Snippets CPT
Description: A code snippet custom post-type and shortcode for elegantly managing and displaying your code snippets.
elegantly managing and displaying code snippets
Plugin URI: http://j.ustin.co/jAHRM3
Author: Jtsternberg
Author URI: http://about.me/jtsternberg
Donate link: http://j.ustin.co/rYL89n
Version: 2.0.0
*/

/**
 * Plugin setup master class.
 * @todo shortcode parameters for description/taxonomy data
 * @todo Minify/concatenate CSS/JS
 */
class CodeSnippitInit {

	const VERSION = '2.0.0';

	/**
	 * The name of the shortcode tag
	 * @var string
	 */
	const SHORTCODE_TAG = 'snippet';

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
		'Swift',
		'CFML',
	);
	public static $single_instance = null;

	private function __construct() {

		define( 'DWSNIPPET_PATH', plugin_dir_path( __FILE__ ) );
		define( 'DWSNIPPET_URL', plugins_url( '/', __FILE__ ) );

		register_activation_hook( __FILE__, array( $this, '_activate' ) );

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
		$this->cpt->set_language( $this->language );

		// Include our wysiwyg button script
		require_once( DWSNIPPET_PATH .'lib/CodeSnippitButton.php' );
		new CodeSnippitButton( $this->cpt, $this->language );

		// Snippet Shortcode Setup
		add_shortcode( self::SHORTCODE_TAG, array( $this, 'shortcode' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'add_languages' ) );
		add_filter( 'content_save_pre', array( $this, 'allow_unfiltered' ), 5 );
	}

	/**
	 * Flush rewrite rules when the plugin activates
	 */
	function _activate() {
		flush_rewrite_rules();
	}

	public function add_languages() {
		if ( ! get_option( 'code_snippets_cpt_languages_installed' ) ) {

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

			update_option( 'code_snippets_cpt_languages_installed', self::VERSION, false );
		}
	}

	public function allow_unfiltered( $value ) {
		global $post;

		if ( isset( $post->post_type ) && $this->cpt->post_type == $post->post_type && current_user_can( 'edit_posts' ) ) {
			kses_remove_filters();
		}

		return $value;
	}

	public function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id'           => false,
			'slug'         => '',
			'line_numbers' => true,
			'lang'         => '',
			'title_attr'   => true,
			// @todo Offer to output snippet description/taxonomies
		), $atts, 'snippet' );

		$args = array(
			'post_type'      => 'code-snippets',
			'posts_per_page' => 1,
			'post_status'    => 'published',
		);

		if ( $atts['id'] && is_numeric( $atts['id'] ) ) {
			$args['p'] = $atts['id'];
		} elseif ( $atts['slug'] && is_string( $atts['slug'] ) ) {
			$args['name'] = $atts['slug'];
		}

		$snippets = new WP_Query( $args );
		if ( ! $snippets->have_posts() ) {
			return;
		}

		$snippet = $snippets->posts[0];

		if ( empty( $snippet->post_content ) ) {
			return;
		}

		$this->cpt->enqueue_prettify();

		$atts['class'] = 'prettyprint';

		$line_nums = ! $atts['line_numbers'] || false === $atts['line_numbers'] || $atts['line_numbers'] === 'false' ? false : $atts['line_numbers'];

		if ( $line_nums ) {
			$atts['class'] .= ' linenums';
			if ( is_numeric( $line_nums ) && 0 !== absint( $line_nums ) ) {
				$atts['class'] .= ':' . absint( $line_nums );
			}
		}

		if ( ! empty( $atts['lang'] ) ) {
			$atts['class'] .= ' lang-'. sanitize_html_class( $atts['lang'] );
		}
		elseif ( $lang_slug = $this->language->language_slug_from_post( $snippet->ID ) ) {
			$atts['class'] .= ' lang-'. $lang_slug;
		}

		if ( is_string( $atts['title_attr'] ) && ! in_array( $atts['title_attr'], array( 'true', 'yes', '1' ), true ) ) {
			$atts['title_attr'] = esc_attr( $atts['title_attr'] );
		} else {
			$atts['title_attr'] = in_array( $atts['title_attr'], array( 'no', 'false', '' ), true ) || ! $atts['title_attr'] ? '' : esc_attr( $snippet->post_title );
		}

		return self::prettyprint_html( $snippet, $atts );
	}

	public static function prettyprint_html( $snippet, $args = array() ) {
		$edit_link = '';
		if ( is_user_logged_in() && current_user_can( get_post_type_object( $snippet->post_type )->cap->edit_post,  $snippet->ID ) ) {
			$edit_link = get_edit_post_link( $snippet->ID );
		}

		$args = wp_parse_args( $args, array(
			'class'           => 'prettyprint linenums',
			'title_attr'      => esc_attr( get_the_title( $snippet->ID ) ),
			'snippet_content' => apply_filters( 'dsgnwrks_snippet_content', htmlentities( $snippet->post_content, ENT_COMPAT, 'UTF-8' ), $args, $snippet ),
			'edit_link'       => $edit_link,
			'copy_link'       => Snippet_CPT_Setup::show_code_url_base( array( 'id' => $snippet->ID ) ),
			'fullscreen_link' => add_query_arg( 'full-screen', 1, get_permalink( $snippet->ID ) ),
		) );

		$html = sprintf(
			'<div class="pretty-print-wrap" id="snippet-%4$s" data-id="%4$s" data-edit="%5$s" data-copy="%6$s" data-fullscreen="%7$s"><pre class="%1$s" title="%2$s">%3$s</pre></div>',
			$args['class'],
			$args['title_attr'],
			$args['snippet_content'],
			$snippet->ID,
			esc_url( $args['edit_link'] ),
			esc_url( $args['copy_link'] ),
			esc_url( $args['fullscreen_link'] )
		);

		return apply_filters( 'dsgnwrks_snippet_display', $html, $args, $snippet );
	}

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
			case 'shortcode_tag':
				return self::SHORTCODE_TAG;
			case 'version':
				return self::VERSION;
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}

}

CodeSnippitInit::get_instance();

/**
 * Replace snippet content tabs with spaces as they are generally
 * more readable in blog-posts.
 *
 * To remove:
 * remove_filter( 'dsgnwrks_snippet_content', 'dsgnwrks_snippet_content_replace_tabs' );
 *
 * @since  1.0.5
 *
 * @param  string  $snippet_content The snippet content.
 *
 * @return string                   Modified snippet content.
 */
function dsgnwrks_snippet_content_replace_tabs( $snippet_content ) {
	// Replace tabs w/ spaces as it is more readable
	$snippet_content = str_replace( "\t", "    ", $snippet_content );

	return $snippet_content;
}
add_filter( 'dsgnwrks_snippet_content', 'dsgnwrks_snippet_content_replace_tabs' );
