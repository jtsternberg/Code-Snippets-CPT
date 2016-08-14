<?php
class CodeSnippitButton {

	private $min = '';

	function __construct( $cpt, $language ) {
		$this->cpt      = $cpt;
		$this->language = $language;

		$is_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$this->min      = $is_debug ? '' : '.min';

		if ( $this->cpt->is_snippet_cpt_admin_page() ) {
			return;
		}

		// Add button for snippet lookup
		add_filter( 'the_editor_content', array( $this, 'enqueue_button_script' ) );
		// script for button handler
		add_action( 'admin_enqueue_scripts', array( $this, 'button_script' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'wp_ajax_snippetcpt_insert_snippet', array( $this, 'ajax_insert_snippet' ) );
		add_action( 'wp_ajax_snippet_parse_shortcode', array( $this, 'ajax_parse_shortcode_callback' ) );
	}

	public function button_script() {
		wp_register_script( 'code-snippet-button', DWSNIPPET_URL ."/assets/js/code-snippet-admin-button{$this->min}.js" , array( 'jquery', 'quicktags', 'wpdialogs' ), CodeSnippitInit::VERSION, true );

		wp_localize_script( 'code-snippet-button', 'codeSnippetCPTButton', array(
			'snippet_nonce' => wp_create_nonce( 'insert_snippet_post' ),
			'button_img'    => DWSNIPPET_URL .'assets/js/icon.png',
			'version'       => CodeSnippitInit::VERSION,
			'l10n'          => array(
				'button_name'      => __( 'Add Snippet', 'code-snippets-cpt' ),
				'button_title'     => __( 'Add a Code Snippet', 'code-snippets-cpt' ),
				'btn_cancel'       => __( 'Cancel', 'code-snippets-cpt' ),
				'btn_insert'       => __( 'Insert Shortcode', 'code-snippets-cpt' ),
				'btn_update'       => __( 'Update Shortcode', 'code-snippets-cpt' ),
				'btn_edit'         => __( 'Edit this Snippet', 'code-snippets-cpt' ),
				'missing_required' => __( 'If you are creating a new snippet, you are required to have at minimum a title and content for the snippet.', 'code-snippets-cpt' ),
				'general_error'    => __( 'There has been an error processing your request, please close the dialog and try again.', 'code-snippets-cpt' ),
				'no_snippets'      => __( "Silly rabbit, there are no snippets, you cannot add what doesn't exist.  Try the adding a snippet first.", 'code-snippets-cpt' ),
				'select_snippet'   => __( 'You must select a snippet to add to the shortcode.', 'code-snippets-cpt' ),
			),
		) );
	}

	public function admin_init() {
		add_filter( 'mce_external_plugins', array( $this, 'add_button' ) );
		add_filter( 'mce_buttons', array( $this, 'register_buttons' ) );
	}

	public function ajax_insert_snippet() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'insert_snippet_post' ) ) {
			wp_send_json_error( __( 'Security Failure', 'code-snippets-cpt' ) );
		}

		$form_data = array();
		parse_str( $_POST['data'], $form_data );

		$language = get_term( absint( $form_data['snippet-language'] ), 'languages' );

		if ( is_wp_error( $language ) ) {
			wp_send_json_error( __( 'Make sure you select a programming language for this snippet.', 'code-snippets-cpt' ) );
		}

		$args = array(
			'post_title'   => sanitize_text_field( $form_data['new-snippet-title'] ),
			'post_content' => $form_data['new-snippet-content'], // Not sure there's any sanitizing we can do here.
			'post_type'    => $this->cpt->post_type,
			'post_status'  => 'publish',
			'tax_input'    => array(
				'snippet-categories' => array_map( 'absint', $form_data['tax_input']['snippet-categories'] ),
				'snippet-tags'       => sanitize_text_field( $form_data['tax_input']['snippet-tags'] )	,
				'languages'          => $language->term_id,
			),
		);

		if ( isset( $form_data['edit-snippet-id'] ) ) {
			$args['ID'] = absint( $form_data['edit-snippet-id'] );
		}

		$post_id = wp_insert_post( $args, true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( $post_id->get_error_message() );
		}

		// Need to create a new nonce.
		$output = array(
			'nonce'        => wp_create_nonce( 'insert_snippet_post' ),
			'line_numbers' => ! empty( $form_data['snippet-cpt-line-nums-2'] ),
			'post_name'    => '',
			'lang'         => '',
		);

		$snippet = $this->get_snippet_for_ajax( $post_id );

		if ( $snippet ) {
			$output['post_name'] = $snippet->post_name;
			$output['lang'] = ! empty( $snippet->language->slug ) ? $snippet->language->slug : '';
		}

		// Finally end it all
		wp_send_json_success( $output );
	}

	public function add_button( $plugin_array ) {
		$plugin_array['snippetcpt'] = DWSNIPPET_URL ."/assets/js/code-snippet-admin-button-mce{$this->min}.js";
		return $plugin_array;
	}

	public function register_buttons( $buttons ) {
		array_push( $buttons, 'snippetcpt' );
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
		wp_enqueue_script( 'code-snippet-button' );

		// Get CPT posts
		$snippets = get_posts( array(
			'post_type'      => $this->cpt->post_type,
			'posts_per_page' => 30,
		) );

		// Category box config options.
		$cat_box_config = array(
			'snippet-categories',
			__( 'Snippet Categories', 'code-snippets-cpt' ),
			'args' => array(
				'taxonomy' => 'snippet-categories',
			),
		);

		// Tag box config options.
		$tag_box_config = array(
			'id'    => 'snippet-tags',
			'title' => __( 'Snippet Tags', 'code-snippets-cpt' ),
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
	 * Parse the snippet shortcode for display within a TinyMCE view, and send it back to JS.
	 */
	public function ajax_parse_shortcode_callback() {
		if ( empty( $_POST['shortcode'] ) ) {
			wp_send_json_error();
		}

		$response = $this->ajax_parse_shortcode( wp_unslash( $_POST['shortcode'] ), $_POST['slug'] );

		if ( isset( $response['error'] ) ) {
			wp_send_json_error( $response['error'] );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Parse the snippet shortcode for display within a TinyMCE view.
	 */
	public function ajax_parse_shortcode( $shortcode, $snippet ) {
		global $wp_scripts;

		$response = array();
		$shortcode = do_shortcode( $shortcode );

		if ( empty( $shortcode ) ) {
			$response['error'] = array(
				'type' => 'no-items',
				'message' => __( 'No items found.' ),
			);

			return $response;
		}

		if ( ! empty( $wp_scripts ) ) {
			$wp_scripts->done = array();
		}

		$do_ace = CodeSnippitInit::get_option( 'ace' );

		ob_start();

		if ( ! $do_ace ) {
			Snippet_CPT_Frontend::run_js();
		}

		$styles = array();
		$scripts = array( 'code-snippets-cpt' );

		if ( $do_ace ) {
			$styles[] = 'ace-css';
			$scripts[] = 'ace-editor';
		} else {
			$styles[] = 'prettify';
			if ( 'ace/theme/monokai' === CodeSnippitInit::get_option( 'theme', 'ace/theme/monokai' ) ) {
				$styles[] = 'prettify-monokai';
			}
		}

		add_action( 'wp_print_scripts', array( 'Snippet_CPT_Frontend', 'localize_js_data' ) );

		wp_print_scripts( $scripts );
		wp_print_styles( $styles );
		$head = ob_get_clean();

		ob_start();
		echo $shortcode;

		return array(
			'head'    => '',
			'body'    => $head . ob_get_clean(),
			'snippet' => $this->get_snippet_for_ajax( $snippet ),
		);
	}

	public function get_snippet_for_ajax( $snippet_id ) {
		if ( is_object( $snippet_id ) ) {
			return $snippet_id;
		}

		$snippet = is_numeric( $snippet_id )
			? get_post( absint( $snippet_id ) )
			: $this->cpt->get_snippet_by_id_or_slug( array(
				'slug' => sanitize_text_field( sanitize_text_field( $snippet_id ) ),
			) );

		if ( ! $snippet ) {
			return false;
		}

		$snippet->language = array();
		if ( $lang = $this->language->get_lang( $snippet->ID ) ) {
			$snippet->language = $lang;
		}

		foreach( array( 'snippet-categories', 'snippet-tags' ) as $tax ) {
			$prop = str_replace( 'snippet-', '', $tax );
			$snippet->{$prop} = get_the_terms( $snippet->ID, $tax );
		}

		if ( $snippet->categories ) {
			$snippet->categories = wp_list_pluck( $snippet->categories, 'term_id' );
		}

		if ( $snippet->tags ) {
			$snippet->tags = wp_list_pluck( $snippet->tags, 'name' );
		}

		return $snippet;
	}

}
