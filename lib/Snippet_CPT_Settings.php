<?php
/**
 * Plugin class that registers the Code Snipet CPT.
 */
class Snippet_CPT_Settings {

	protected $cpt;
	protected $defaults = array();

	function __construct( $cpt ) {
		$this->cpt = $cpt;
		$this->add_page();
	}

	/**
	 * Add the options menu page for this plugin.
	 */
	public function add_page() {
		$this->defaults =  array(
			'theme' => 'ace/theme/monokai',
			'ace'   => false,
		);

		add_submenu_page(
			'edit.php?post_type='. $this->cpt->post_type,
			__( 'Settings', 'code-snippets-cpt' ),
			__( 'Settings', 'code-snippets-cpt' ),
			'manage_options',
			'code-snippets-cpt',
			array( $this, 'options_page' )
		);

		register_setting( 'code-snippets-cpt', 'code-snippets-cpt', array( $this, 'sanitize' ) );
	}

	/**
	 * Render the options page.
	 */
	public function options_page() {
		// Get the current settings
		$options = wp_parse_args( CodeSnippitInit::get_option(), $this->defaults );

		$ace_theme_options = $this->cpt->ace_theme_selector_options( $options['theme'] );

		include_once( DWSNIPPET_PATH .'lib/views/settings-page.php' );
	}

	public function sanitize( $options ) {
		$clean = array();

		if ( empty( $options ) ) {
			return $clean;
		}

		foreach ( $this->defaults as $key => $value ) {
			$clean[ $key ] = isset( $options[ $key ] ) && $options[ $key ]
				? sanitize_text_field( $options[ $key ] )
				: $value;
		}

		return $clean;
	}
}
