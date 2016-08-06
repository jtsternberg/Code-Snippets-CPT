<?php
class CodeSnippitButton {

	private $script = 'code-snippet-button';
	private $btn = 'snippetcpt';

	function __construct( $cpt, $language ) {
		$this->cpt = $cpt;
		$this->language = $language;
		// Add button for snippet lookup
		add_filter( 'the_editor_content', array( $this, '_enqueue_button_script' ) );
		// script for button handler
		add_action( 'admin_enqueue_scripts', array( $this, 'button_script' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'wp_ajax_snippetcpt_insert_snippet', array( $this, 'ajax_insert_snippet' ) );
	}

	public function button_script() {
		wp_register_script( $this->script, DWSNIPPET_URL .'/lib/js/'. $this->script .'.js' , array( 'quicktags', 'wpdialogs' ), CodeSnippitInit::VERSION, true );
		wp_localize_script( $this->script, 'codeSnippetCPTButton', array(
			'snippet_nonce' => wp_create_nonce( 'insert_snippet_post' ),
			'button_img'    => DWSNIPPET_URL .'lib/js/icon.png',
			'version'       => CodeSnippitInit::VERSION,
			'l10n'          => array(
				'button_img'       => DWSNIPPET_URL .'lib/js/icon.png',
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

		// Need to create a new nonce.
		$output = array( 'nonce' => wp_create_nonce( 'insert_snippet_post' ) );

		$form_data = array();
		parse_str( $_POST['form_data'], $form_data );

		$title         = $form_data['new-snippet-title'];
		$content       = $form_data['new-snippet-content'];
		$categories    = $form_data['tax_input']['snippet-categories'];
		$tags          = $form_data['tax_input']['snippet-tags'];
		$language      = $form_data['snippet-language'];
		$language_data = get_term( $language, 'languages' );

		if ( is_wp_error( $language_data ) ) {
			$output['message'] = __( 'Make sure you select a language for this snippet.', 'code-snippet-cpt' );
			wp_send_json_error( $output );
		}

		$post_result = wp_insert_post( array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'code-snippets',
			'tax_input'    => array(
				'snippet-categories' => $categories,
				'snippet-tags'       => $tags,
				'languages'			 => $language,
			),
		), true );

		if ( is_wp_error( $post_result ) ) {
			$output['message'] = $post_result->get_error_message();
			wp_send_json_error( $output );
		}
		$post_data = get_post( $post_result );

		$output['line_numbers'] = $form_data['snippet-cpt-line-nums-2'];
		$output['language']     = $language_data->slug;
		$output['slug']         = $post_data->post_name;

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

	public function _enqueue_button_script( $content ) {
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
}
