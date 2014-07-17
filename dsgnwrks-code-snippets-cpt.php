<?php
/*
Plugin Name: Dsgnwrks Code Snippets CPT
Description: A code snippet custom post-type and shortcode for displaying your code snippets in your posts or pages.
Plugin URI: http://j.ustin.co/jAHRM3
Author: Jtsternberg
Author URI: http://about.me/jtsternberg
Donate link: http://j.ustin.co/rYL89n
Version: 1.0.2
*/

class CodeSnippitInit {

	protected $plugin_name = 'Code Snippets CPT';
	protected $cpt;
	protected $languages = array( 'Python', 'HTML', 'CSS', 'JavaScript', 'PHP', 'SQL', 'Perl', 'Ruby', 'Bash', 'C', 'HTML', 'Java', 'XHTML', 'XML', );
	const VERSION = '1.0.2';

	function __construct() {

		define( 'DWSNIPPET_PATH', plugin_dir_path( __FILE__ ) );
		define( 'DWSNIPPET_URL', plugins_url('/', __FILE__ ) );

		// Custom Functions
		require_once( DWSNIPPET_PATH .'lib/functions.php' );

		// Snippet Post-Type Setup
		if ( !class_exists( 'CPT_Setup' ) )
			require_once( DWSNIPPET_PATH .'lib/CPT_Setup.php' );
		require_once( DWSNIPPET_PATH .'lib/Snippet_CPT_Setup.php' );
		$this->cpt = new Snippet_CPT_Setup();

		// Custom Taxonomy Setup
		require_once( DWSNIPPET_PATH .'lib/Snippet_Tax_Setup.php' );
		new Snippet_Tax_Setup( 'Snippet Category', 'Snippet Categories', array( $this->cpt->slug ) );
		new Snippet_Tax_Setup( 'Snippet Tag', '', array( $this->cpt->slug ), array( 'hierarchical' => false ) );
		$this->language = new Snippet_Tax_Setup( 'Language', '', array( $this->cpt->slug ),  array( 'public' => false, 'show_ui' => false ) );
		// Custom metabox for the programming languages taxonomy
		$this->language->init_select_box();

		// Include our wysiwyg button script
		require_once( DWSNIPPET_PATH .'lib/CodeSnippitButton.php' );
		new CodeSnippitButton( $this->cpt, $this->language );

		// Snippet Shortcode Setup
		add_shortcode( 'snippet', array( $this, 'shortcode' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'add_languages' ) );

	}

	public function add_languages() {
		// make sure our default languages exist
		foreach ( $this->languages as $language ) {
			if ( !term_exists( $language, 'languages' ) )
				wp_insert_term( $language, 'languages' );
		}
	}

	public function shortcode( $atts, $context ) {

		$atts = shortcode_atts( array(
			'id'           => false,
			'slug'         => '',
			'line_numbers' => true,
			'lang'         => '',
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
		if ( is_wp_error( $snippet ) || empty( $snippet ) )
			return;

		$snippet_id = $snippet[0]->ID;
		$content = get_post_field( 'post_content', $snippet_id );

		if ( is_wp_error( $content ) || empty( $content ) )
			return;

		$this->cpt->enqueue_prettify();

		$class = 'prettyprint';

		$line_nums = !$atts['line_numbers'] || false === $atts['line_numbers'] || $atts['line_numbers'] === 'false' ? false : $atts['line_numbers'];

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

		$snippet_content = apply_filters( 'dsgnwrks_snippet_content', htmlentities( $content, ENT_COMPAT, 'UTF-8' ), $atts );

		return sprintf( '<pre class="%1$s">%2$s</pre>', $class, $snippet_content );
	}

}

new CodeSnippitInit;
