<?php
/*
Plugin Name: Code Snippets CPT
Description: A code snippet custom post-type and shortcode for elegantly managing and displaying your code snippets.
elegantly managing and displaying code snippets
Plugin URI: http://j.ustin.co/jAHRM3
Author: Jtsternberg
Author URI: http://dsgnwrks.pro
Donate link: http://j.ustin.co/2aKL1Iu
Version: 2.0.3
Text Domain: code-snippets-cpt
*/

/**
 * Plugin setup master class.
 * @todo shortcode parameters for description/taxonomy data
 * @todo Minify/concatenate CSS
 * @todo Make line-number button optional
 */
class CodeSnippitInit {

	const VERSION = '2.0.3';

	/**
	 * The name of the shortcode tag
	 * @var string
	 */
	const SHORTCODE_TAG = 'snippet';

	protected $cpt;
	protected $language;
	protected $frontend;
	protected $settings;

	public static $single_instance = null;

	protected function __construct() {

		define( 'DWSNIPPET_PATH', plugin_dir_path( __FILE__ ) );
		define( 'DWSNIPPET_URL', plugins_url( '/', __FILE__ ) );

		// Snippet Post-Type Setup
		require_once( DWSNIPPET_PATH . 'lib/Snippet_CPT_Setup.php' );
		$this->cpt = new Snippet_CPT_Setup();

		// Custom Taxonomy Setup
		require_once( DWSNIPPET_PATH . 'lib/Snippet_Tax_Setup.php' );
		new Snippet_Tax_Setup( 'Snippet Category', 'Snippet Categories', array( $this->cpt->post_type ) );
		new Snippet_Tax_Setup( 'Snippet Tag', '', array( $this->cpt->post_type ), array( 'hierarchical' => false ) );

		$this->language = new Snippet_Tax_Setup( 'Language', '', array( $this->cpt->post_type ), array( 'public' => false, 'show_ui' => false ) );
		// Custom metabox for the programming languages taxonomy
		$this->language->init_select_box();
		$this->cpt->set_language( $this->language );

		// Include our wysiwyg button script
		require_once( DWSNIPPET_PATH . 'lib/CodeSnippitButton.php' );
		new CodeSnippitButton( $this->cpt, $this->language );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'update_check' ) );
		add_filter( 'content_save_pre', array( $this, 'allow_unfiltered' ), 5 );

		// Snippet Post-Type Front-end stuff.
		require_once( DWSNIPPET_PATH . 'lib/Snippet_CPT_Frontend.php' );
		$this->frontend = new Snippet_CPT_Frontend( $this->cpt );

		add_action( 'admin_menu', array( $this, 'load_admin_settings' ) );
	}

	public function load_admin_settings() {
		require_once( DWSNIPPET_PATH . 'lib/Snippet_CPT_Settings.php' );
		$this->settings = new Snippet_CPT_Settings( $this->cpt );
	}

	public function update_check() {
		$current_version = get_option( 'dsgnwrks_snippet_version', '1.0.5' );

		if ( version_compare( $current_version, self::VERSION, '<' ) ) {
			$taxonomy  = $this->language->slug;
			$to_create = ( include DWSNIPPET_PATH . 'lib/languages.php' );
			$migrate = array(
				'c_cpp'  => array(
					'key' => 'name',
					'value' => 'C',
				),
				'csharp' => array(
					'key' => 'slug',
					'value' => 'c-sharp',
				),
			);

			// Migrate possibly-existing terms.
			foreach ( $migrate as $slug => $to_migrate ) {

				$exists = get_term_by( $to_migrate['key'], $to_migrate['value'], $taxonomy );
				$new_name = $to_create[ $slug ];

				if ( ! empty( $exists ) ) {
					$updated = wp_update_term( $exists->term_id, $taxonomy, array(
						'name' => $new_name,
						'slug' => $slug,
					) );
				} else {
					wp_insert_term( $new_name, $taxonomy, array( 'slug' => $slug ) );
				}

				// Mark this done.
				unset( $to_create[ $slug ] );
			}

			// make sure our default languages exist
			foreach ( $to_create as $slug => $language ) {
				$exists = get_term_by( 'slug', $slug, $taxonomy );
				if ( empty( $exists ) ) {
					wp_insert_term( $language, $taxonomy, array( 'slug' => $slug ) );
				}
			}

			update_option( 'dsgnwrks_snippet_version', self::VERSION );
		}
	}

	public function allow_unfiltered( $value ) {
		global $post;

		if ( isset( $post->post_type ) && $this->cpt->post_type === $post->post_type && current_user_can( 'edit_posts' ) ) {
			kses_remove_filters();
		}

		return $value;
	}

	public static function enabled_features( $to_check = '' ) {

		/*
		 * To disable:
		 *
		 * add_filter( 'dsgnwrks_snippet_do_click_to_copy', '__return_false' );
		 * add_filter( 'dsgnwrks_snippet_enable_full_screen_view', '__return_false' );
		 * add_filter( 'dsgnwrks_snippet_ace_frontend', '__return_false' );
		 *
		 */

		$features = array(
			// Determines whether we should enable the code-copy window/button.
			'do_click_to_copy' => apply_filters( 'dsgnwrks_snippet_do_click_to_copy', true ),
			// Determines whether we should enable full-screen view.
			'enable_full_screen_view' => apply_filters( 'dsgnwrks_snippet_enable_full_screen_view', true ),
			// Determines whether snippets are collapsible.
			'collapsible' => apply_filters( 'dsgnwrks_snippet_enable_ace_collapsible_snippets', true ),
		);

		if ( 'all' == $to_check ) {
			foreach ( $features as $feature ) {
				if ( ! $feature ) {
					return false;
				}
			}

			return true;
		}

		if ( 'any' == $to_check ) {
			foreach ( $features as $feature ) {
				if ( $feature ) {
					return true;
				}
			}

			return false;
		}

		if ( $to_check && array_key_exists( $to_check, $features ) ) {
			return (bool) $features[ $to_check ];
		}

		return $features;
	}

	public static function get_option( $setting_key = null, $default = null ) {
		$options = (array) get_option( 'code-snippets-cpt', array() );
		if ( null !== $setting_key ) {
			return isset( $options[ $setting_key ] ) ? $options[ $setting_key ] : $default;
		}

		return $options;
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
			case 'cpt':
			case 'language':
			case 'frontend':
			case 'settings':
				return $this->{$field};
			case 'shortcode_tag':
				return self::SHORTCODE_TAG;
			case 'version':
				return self::VERSION;
			case 'languages':
				return ( include DWSNIPPET_PATH . 'lib/languages.php' );
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}
}

CodeSnippitInit::get_instance();

/**
 * Flush rewrite rules when the plugin activates
 */
function dsgnwrks_snippet_activate() {
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dsgnwrks_snippet_activate' );

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
