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
	}

	public function button_script() {
		wp_register_script( $this->script, DWSNIPPET_URL .'/lib/js/'. $this->script .'.js' , array( 'quicktags', 'wpdialogs' ), CodeSnippitInit::VERSION, true );
		wp_localize_script( $this->script, 'codeSnippetCPT', array(
			'buttons' => array( 'Cancel' => 'cancel', 'Insert Shortcode' => 'insert' ),
			'button_img' => DWSNIPPET_URL .'lib/js/icon.png',
			'button_name' => __( 'Add Snippet', 'code-snippet-cpt' ),
			'button_title' => __( 'Add a Code Snippet', 'code-snippet-cpt' ),
		) );
	}

	public function admin_init() {
		add_filter( 'mce_external_plugins', array( $this, 'add_button' ) );
		add_filter( 'mce_buttons', array( $this, 'register_buttons' ) );
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
			<form>
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
					<label for="snippet-content"><?php _e( 'Snippet', 'code-snippet-cpt' ); ?></label><br />
					<hr />
				<?php
					$settings = array(
						'media_buttons' => false,
						'textarea_name'=>'snippet-content',
						'textarea_rows' => 15,
						'tabindex' => '4',
						'dfw' => true,
						'quicktags' => array( 'buttons' => 'link,ul,ol,li,close,fullscreen' )
					);
					wp_editor( '', 'snippet-content', $settings );
				?>
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
