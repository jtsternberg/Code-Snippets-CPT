<?php
class CodeSnippitButton {

	private $script = 'code-snippet-button';
	private $btn = 'snippetcpt';

	function __construct( $cpt, $language ) {
		$this->cpt = $cpt;
		$this->language = $language;

		// Add button for snippet lookup
		add_filter( 'the_editor_content', array( $this, 'enqueue_button_script' ) );
		// script for button handler
		add_action( 'admin_enqueue_scripts', array( $this, 'button_script' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'wp_ajax_snippetcpt_insert_snippet', array( $this, 'ajax_insert_snippet' ) );
		add_action( 'wp_ajax_snippet_parse_shortcode', array( $this, 'ajax_parse_shortcode' ) );
	}

	public function button_script() {
		wp_register_script( $this->script, DWSNIPPET_URL .'/lib/js/'. $this->script .'.js' , array( 'jquery', 'quicktags', 'wpdialogs' ), CodeSnippitInit::VERSION, true );

		wp_localize_script( $this->script, 'codeSnippetCPTButton', array(
			'snippet_nonce' => wp_create_nonce( 'insert_snippet_post' ),
			'button_img'    => DWSNIPPET_URL .'lib/js/icon.png',
			'version'       => CodeSnippitInit::VERSION,
			'l10n'          => array(
				'button_name'      => __( 'Add Snippet', 'code-snippet-cpt' ),
				'button_title'     => __( 'Add a Code Snippet', 'code-snippet-cpt' ),
				'btn_cancel'       => __( 'Cancel', 'snippet-cpt' ),
				'btn_insert'       => __( 'Insert Shortcode', 'snippet-cpt' ),
				'btn_update'       => __( 'Update Shortcode', 'snippet-cpt' ),
				'btn_edit'         => __( 'Edit this Snippet', 'snippet-cpt' ),
				'missing_required' => __( 'If you are creating a new snippet, you are required to have at minimum a title and content for the snippet.', 'code-snippet-cpt' ),
				'general_error'    => __( 'There has been an error processing your request, please close the dialog and try again.', 'code-snippet-cpt' ),
				'no_snippets'      => __( "Silly rabbit, there are no snippets, you cannot add what doesn't exist.  Try the adding a snippet first.", 'code-snippet-cpt' ),
				'select_snippet'   => __( 'You must select a snippet to add to the shortcode.', 'code-snippet-cpt' ),
			),
		) );
	}

	public function admin_init() {
		add_filter( 'mce_external_plugins', array( $this, 'add_button' ) );
		add_filter( 'mce_buttons', array( $this, 'register_buttons' ) );
	}

	public function ajax_insert_snippet() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'insert_snippet_post' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security Failure', 'code-snippet-cpt' ) ) );
		}

		$form_data = array();
		parse_str( $_POST['form_data'], $form_data );

		// Need to create a new nonce.
		$output = array( 'nonce' => wp_create_nonce( 'insert_snippet_post' ) );

		$language = get_term( absint( $form_data['snippet-language'] ), 'languages' );

		if ( is_wp_error( $language ) ) {
			$output['message'] = __( 'Make sure you select a programming language for this snippet.', 'code-snippet-cpt' );
			wp_send_json_error( $output );
		}

		$post_id = wp_insert_post( array(
			'post_title'   => sanitize_text_field( $form_data['new-snippet-title'] ),
			'post_content' => $form_data['new-snippet-content'], // Not sure there's any sanitizing we can do here.
			'post_type'    => $this->cpt->post_type,
			'post_status'  => 'publish',
			'tax_input'    => array(
				'snippet-categories' => array_map( 'absint', $form_data['tax_input']['snippet-categories'] ),
				'snippet-tags'       => sanitize_text_field( $form_data['tax_input']['snippet-tags'] )	,
				'languages'          => $language->term_id,
			),
		), true );

		if ( is_wp_error( $post_id ) ) {
			$output['message'] = $post_id->get_error_message();
			wp_send_json_error( $output );
		}

		$output['line_numbers'] = $form_data['snippet-cpt-line-nums-2'];
		$output['language']     = $language->slug;
		$output['slug']         = get_post( $post_id )->post_name;

		// Finally end it all
		wp_send_json_success( $output );
	}

	public function add_button( $plugin_array ) {
		$plugin_array[ $this->btn ] = DWSNIPPET_URL .'/lib/js/'. $this->script .'-mce.js';
		return $plugin_array;
	}

	public function register_buttons( $buttons ) {
		array_push( $buttons, $this->btn );
		return $buttons;
	}

	public function enqueue_button_script( $content ) {
		// We know wp_editor was called, so add our CSS/JS to the footer
		add_action( 'admin_footer', array( $this, 'quicktag_button_script' ) );
		return $content;
	}

	public function quicktag_button_script() {
		global $post;

		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( $this->script );

		// Get CPT posts
		$snippets = get_posts( array(
			'post_type'      => $this->cpt->post_type,
			'posts_per_page' => 30,
		) );

		// Category box config options.
		$cat_box_config = array(
			'snippet-categories',
			__( 'Snippet Categories', 'code-snippet-cpt' ),
			'args' => array(
				'taxonomy' => 'snippet-categories',
			),
		);

		// Tag box config options.
		$tag_box_config = array(
			'id'    => 'snippet-tags',
			'title' => __( 'Snippet Tags', 'code-snippet-cpt' ),
			'args'  => array(
				'taxonomy' => 'snippet-tags',
			),
		);

		// Languages terms.
		$languages = get_terms( 'languages', array(
			'hide_empty' => 0,
		) );

		// Shortcode button popup form
		include_once( DWSNIPPET_PATH .'lib/views/shortcode-button-form.php' );
	}

	/**
	 * Parse the snippet shortcode for display within a TinyMCE view.
	 */
	public function ajax_parse_shortcode() {
		global $wp_scripts;

		if ( empty( $_POST['shortcode'] ) ) {
			wp_send_json_error();
		}

		$shortcode = do_shortcode( wp_unslash( $_POST['shortcode'] ) );

		if ( empty( $shortcode ) ) {
			wp_send_json_error( array(
				'type' => 'no-items',
				'message' => __( 'No items found.' ),
			) );
		}

		$head  = '';
		$styles = wpview_media_sandbox_styles();

		foreach ( $styles as $style ) {
			$head .= '<link type="text/css" rel="stylesheet" href="' . $style . '">';
		}

		$head .= '<link rel="stylesheet" href="' . DWSNIPPET_URL .'lib/css/prettify.css?v=' . CodeSnippitInit::VERSION . '">';
		$head .= '<link rel="stylesheet" href="' . DWSNIPPET_URL .'lib/css/prettify-monokai.css?v=' . CodeSnippitInit::VERSION . '">';

		if ( ! empty( $wp_scripts ) ) {
			$wp_scripts->done = array();
		}

		$snippet = $this->cpt->get_snippet_by_id_or_slug( array(
			'slug' => sanitize_text_field( $_POST['slug'] ),
		) );

		ob_start();
		echo $shortcode;

		add_action( 'wp_print_scripts', array( $this->cpt, 'run_js' ) );

		wp_print_scripts( 'prettify' );

		wp_send_json_success( array(
			'head'    => $head,
			'body'    => ob_get_clean(),
			'snippet' => $snippet,
		) );
	}

}
