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

		add_action( 'wp_ajax_insert_snippet', array( $this, 'ajax_insert_snippet' ) );
	}

	public function button_script() {
		wp_register_script( $this->script, DWSNIPPET_URL .'/lib/js/'. $this->script .'.js' , array( 'quicktags', 'wpdialogs' ), CodeSnippitInit::VERSION, true );
		wp_localize_script( $this->script, 'codeSnippetCPT', array(
			'buttons'         => array( 'cancel' => __( 'Cancel', 'code-snippet-cpt' ), 'insert' => __( 'Insert Shortcode', 'code-snippet-cpt' ) ),
			'button_img'      => DWSNIPPET_URL .'lib/js/icon.png',
			'button_name'     => __( 'Add Snippet', 'code-snippet-cpt' ),
			'button_title'    => __( 'Add a Code Snippet', 'code-snippet-cpt' ),
			'snippet_nonce'   => wp_create_nonce( 'insert_snippet_post' ),
			'error_messages'  => array(
				'no_title_or_content' => __( 'If you are creating a new snippet, you are required to have at minimum a title and content for the snippet.', 'code-snippet-cpt' ),
				'general'             => __( 'There has been an error processing your request, please close the dialog and try again.', 'code-snippet-cpt' ),
				'no_snippets'		  => __( "Silly rabbit, there are no snippets, you cannot add what doesn't exist.  Try the adding a snippet first.", 'code-snippet-cpt' ),
				'select_a_snippet'    => __( 'You must select a snippet to add to the shortcode.', 'code-snippet-cpt' ),
			),
		) );
	}

	public function admin_init() {
		add_filter( 'mce_external_plugins', array( $this, 'add_button' ) );
		add_filter( 'mce_buttons', array( $this, 'register_buttons' ) );
	}

	public function ajax_insert_snippet(){
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'insert_snippet_post' ) ){
			wp_send_json_error( array( 'message' => __( 'Security Failure', 'code-snippet-cpt') ) );
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

		if ( is_wp_error( $language_data ) ){
			$output['message'] = $language_data->get_error_message();
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

		if( is_wp_error( $post_result ) ){
			$output['message'] = $post_result->get_error_message();
			wp_send_json_error( $output );
		}
		$post_data = get_post( $post_result );

		$output['line_numbers'] = $form_data['line_numbers'];
		$output['language']     = $this->language->language_slug( $language_data->name );
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
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( $this->script );

		// wp_die( '<xmp style="padding: 50px; background: #eee; color: #000;">$this: '. print_r( $this, true ) .'</xmp>' );

		// Get CPT posts
		$snippets = get_posts( array(
			'post_type' => $this->cpt->post_type,
			'posts_per_page' => 30,
		) );
		// Shortcode button popup form
		?>
		<style type="text/css">
			#snippet-cpt-form {
				padding: 0 10px 20px;
			}
			#snippet-cpt-form table {
				width: 100%;
			}
			#snippet-cpt-form select {
				width: 100%;
				max-width: 100%;
			}
			#snippet-cpt-form .th {
				width: 50%;
			}
			#snippet-cpt-form .th label {
				padding-right: 9px;
				text-align: right;
			}

			.snippet-cpt-errors p{
				display: none;
				background: rgba( 255, 0, 0, 0.5 );
				padding: 5px;
				border: 1px solid #F00;
			}

			.add_new_snippet{
				display:none;
			}

			.add_new_snippet > div{
				margin-top: 0.5em;
			}

			.add_new_snippet label{
				font-weight: bold;
			}
		</style>
		<div style="display: none;" id="snippet-cpt-form" title="<?php esc_attr_e( 'Code Snippets', 'code-snippet-cpt' ); ?>">
			<div class="snippet-cpt-errors"><p></p></div>
			<form id="snippet_form">
			<fieldset class="select_a_snippet">
				<table>
					<?php if ( ! empty( $snippets ) ) : ?>
					<tr>
						<th colspan="2"><label for="snippet-cpt-posts"><?php _e( 'Choose a Snippet', 'code-snippet-cpt' ); ?></label></th>
					</tr>
					<tr>
						<td colspan="2">
							<select name="snippet-cpt-posts" id="snippet-cpt-posts" value="left" class="text ui-widget-content ui-corner-all">
								<option value=""><?php _e( 'Select a Snippet', 'code-snippet-cpt' ); ?></option>
								<?php foreach ( $snippets as $snippet ) :
									$lang_slug	= '';
									if ( $has_slug = $this->language->language_slug_from_post( $snippet->ID ) ) {
										$lang_slug = $has_slug;
									}
								?>
									<option value="<?php echo $snippet->post_name;?>" data-lang="<?php echo $lang_slug; ?>"><?php echo $snippet->post_title; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="th"><label for="snippet-cpt-line-nums"><?php _e( 'Display Line Numbers?', 'code-snippet-cpt' ); ?></label></td>
						<td><input type="checkbox" name="snippet-cpt-line-nums" id="snippet-cpt-line-nums" value="1" checked="checked" class="text ui-widget-content ui-corner-all" /></td>
					</tr>

					<?php else : ?>
					<tr>
						<th><label id="no-snippets-exist"><?php _e( 'No Snippets Yet!', 'code-snippet-cpt' ); ?></label></th>
					</tr>
					<?php endif; ?>
				</table>
				<hr />
			</fieldset>
			<fieldset>
				<div style="text-align:right;">
					<input type='button' class="add_new_snippet_btn button button-secondary" value="<?php _e( 'Add New', 'code-snippet-cpt' ); ?> " />
					<input type='button' class="cancel_new_snippet_btn button button-secondary hidden" value="<?php _e( 'Cancel', 'code-snippet-cpt' ); ?>">
				</div>
			</fieldset>
			<fieldset class="add_new_snippet">
				<div>
					<label for="snippet-title"><?php _e( 'Snippet Title', 'code-snippet-cpt' ); ?></label><br />
					<input type="text" name="new-snippet-title" class="new-snippet-title widefat">
				</div>

				<div>
					<label for="line_numbers"><input type="checkbox" name="line_numbers" id="line_numbers" value="1" checked="checked" class="text ui-widget-content ui-corner-all" /><span><?php _e( 'Display Line Numbers?', 'code-snippet-cpt' ); ?></span></label>
				</div>

				<div>
					<label for="new-snippet-content"><?php _e( 'Snippet', 'code-snippet-cpt' ); ?></label><br />
					<textarea name="new-snippet-content" id="new-snippet-content" class="widefat new-snippet-content" rows="15"></textarea>
				</div>
				<hr />
				<div>
					<label for="snippet-categories"><?php _e( 'Snippet Categories', 'code-snippet-cpt' ); ?></label><br />
					<?php
						global $post;
						$cat_box_config = array(
							'snippet-categories',
							__( 'Snippet Categories', 'code-snippet-cpt' ),
							'args' => array(
								'taxonomy' => 'snippet-categories',
							),
						);
						post_categories_meta_box( $post, $cat_box_config );
					?>
				</div>
				<hr />
				<div>
					<label for="snippet-categories"><?php _e( 'Snippet Tags', 'code-snippet-cpt' ); ?></label><br />
					<?php
						global $post;
						$cat_box_config = array(
							'id' => 'snippet-tags',
							'title' => __( 'Snippet Tags', 'code-snippet-cpt' ),
							'args' => array(
								'taxonomy' => 'snippet-tags',
							),
						);
						post_tags_meta_box( $post, $cat_box_config );
					?>
				</div>
				<hr />
				<div>
					<label for="snippet-language"><?php _e( 'Programming Language', 'code-snippet-cpt' ); ?></label>
					<?php
						$languages = get_terms( 'languages', array(
							'hide_empty' => 0,
						) );
					?>
					<select name="snippet-language" id="snippet-language">
						<option value=""><?php _e( 'Select One', 'code-snippet-cpt' ); ?></option>
					<?php if ( ! empty( $languages ) ) : ?>
						<?php foreach ( $languages as $language ): ?>
							<option value="<?php echo $language->term_id; ?>" data-slug="<?php echo $language->slug; ?>"><?php echo $language->name; ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
					</select>
				</div>

			</fieldset>
			</form>
		</div>
		<?php
	}

}
