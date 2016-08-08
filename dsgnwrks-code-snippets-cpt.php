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

	protected $cpt;
	protected $language;

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

		// Snippet Shortcode Setup
		add_shortcode( self::SHORTCODE_TAG, array( $this, 'shortcode' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'update_check' ) );
		add_filter( 'content_save_pre', array( $this, 'allow_unfiltered' ), 5 );
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

	public function shortcode( $atts ) {

		$atts = shortcode_atts( array(
			'id'           => false,
			'slug'         => '',
			'line_numbers' => true,
			'lang'         => '',
			'title_attr'   => true,
		), $atts, 'snippet' );

		$snippet = $this->cpt->get_snippet_by_id_or_slug( $atts );
		if ( ! $snippet ) {
			return '';
		}

		$snippet_id = $snippet->ID;

		if ( empty( $snippet->post_content ) ) {
			return '';
		}

		$line_nums = ! $atts['line_numbers'] || false === $atts['line_numbers'] || 'false' === $atts['line_numbers'] ? false : $atts['line_numbers'];
		$snippet_content = apply_filters( 'dsgnwrks_snippet_content', htmlentities( $snippet->post_content, ENT_COMPAT, 'UTF-8' ), $atts, $snippet );

		if ( $atts['title_attr'] && ! in_array( $atts['title_attr'], array( 'no', 'false' ), true ) ) {
			$title_attr = sprintf( 'title="%s"', esc_attr( $snippet->post_title ) );
		} else {
			$title_attr = '';
		}

		if ( $this->cpt->is_ace_enabled() ) {
			$output = $this->get_ace_output( $title_attr, $snippet_content, $snippet_id, $line_nums );
		} else {
			$output = $this->get_legacy_output( $title_attr, $snippet_content, $snippet_id, $line_nums );
		}

		return apply_filters( 'dsgnwrks_snippet_display', $output, $atts, $snippet );
	}

	/**
	 * Gets the Legacy output as to not break the old code/display
	 *
	 * @param $title_attr
	 * @param $snippet_content
	 * @param $snippet_id
	 * @param $line_nums
	 *
	 * @return string
	 */
	public function get_legacy_output( $title_attr, $snippet_content, $snippet_id, $line_nums ) {
		$this->cpt->enqueue_prettify();
		$class = 'prettyprint';
		if ( $line_nums ) {
			$class .= ' linenums';
			if ( is_numeric( $line_nums ) && 0 !== absint( $line_nums ) ) {
				$class .= ':' . absint( $line_nums );
			}
		}

		if ( ! empty( $atts['lang'] ) ) {
			$class .= ' lang-'. sanitize_html_class( $atts['lang'] );
		} elseif ( $lang_slug = $this->language->language_slug_from_post( $snippet_id ) ) {
			$class .= ' lang-'. $lang_slug;
		}

		return sprintf( '<pre class="%1$s" %2$s>%3$s</pre>', $class, $title_attr, $snippet_content );
	}

	/**
	 * Gets the output for the ACE front-end display
	 *
	 * @param $title_attr
	 * @param $snippet_content
	 * @param $snippet_id
	 * @param $line_nums
	 *
	 * @return string
	 */
	public function get_ace_output( $title_attr, $snippet_content, $snippet_id, $line_nums ) {
		// Let's use data sets instead?
		// This is just personal preference, and that I like to access the .data method in JS instead
		// of jumping through all classes.
		$data_sets = array();
		if ( $line_nums ) {
			$data_sets['line_nums'] = is_numeric( $line_nums ) && 0 !== absint( $line_nums ) ? absint( $line_nums ) : true;
		}

		$data_sets['lang'] = apply_filters( 'dsgnwrks_snippet_default_ace_lang', 'text' );
		if ( ! empty( $atts['lang'] ) ) {
			// Need this for backwards compatibility
			$maybe_old_language = sanitize_html_class( $atts['lang'] );
			$data_sets['lang']  = $this->language->get_ace_slug( $maybe_old_language );
		} elseif ( $lang_slug = $this->language->language_slug_from_post( $snippet_id ) ) {
			// Get the language linked to the current post id
			$data_sets['lang'] = $lang_slug;
		}

		// Set the snippet ID, for use in the controller
		$data_sets['snippet-id'] = $snippet_id;

		$data = '';
		if ( ! empty( $data_sets ) ) {
			foreach ( $data_sets as $data_key => $value ) {
				$data .= " data-{$data_key}='{$value}'";
			}
		}

		return sprintf( '<pre class="%1$s" %2$s %3$s>%4$s</pre>', 'snippetcpt-ace-viewer', $title_attr, $data, $snippet_content );
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
