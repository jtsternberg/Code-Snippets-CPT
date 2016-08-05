<?php
class CodeSnippitButton {

	private $script = 'code-snippet-button';
	private $btn = 'snippetcpt';

	function __construct( $cpt, $language ) {
		$this->cpt = $cpt;
		$this->language = $language;

		if ( $this->cpt->is_snippet_cpt_admin_page() ) {
			return;
		}

		// Add button for snippet lookup
		add_filter( 'the_editor_content', array( $this, '_enqueue_button_script' ) );
		// script for button handler
		add_action( 'admin_enqueue_scripts', array( $this, 'button_script' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'wp_ajax_snippet_parse_shortcode', array( $this, 'ajax_parse_shortcode' ) );
	}

	public function button_script() {
		wp_register_script( $this->script, DWSNIPPET_URL .'/lib/js/'. $this->script .'.js' , array( 'jquery', 'quicktags', 'wpdialogs' ), CodeSnippitInit::VERSION, true );

		wp_localize_script( $this->script, 'codeSnippetCPTButton', array(
			'l10n' => array(
				'button_img'   => DWSNIPPET_URL .'lib/js/icon.png',
				'button_name'  => __( 'Add Snippet', 'code-snippet-cpt' ),
				'button_title' => __( 'Add a Code Snippet', 'code-snippet-cpt' ),
				'btn_cancel'   => __( 'Cancel', 'snippet-cpt' ),
				'btn_insert'   => __( 'Insert Shortcode', 'snippet-cpt' ),
				'btn_update'   => __( 'Update Shortcode', 'snippet-cpt' ),
				'btn_edit'     => __( 'Edit this Snippet', 'snippet-cpt' ),
			),
			'version' => CodeSnippitInit::VERSION,
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

		</style>
		<div style="display: none;" id="snippet-cpt-form" title="<?php esc_attr_e( 'Code Snippets', 'code-snippet-cpt' ); ?>">
			<div class="snippet-cpt-errors"><p></p></div>
			<form>
			<fieldset>
				<table>
					<?php if ( ! empty( $snippets ) ) : ?>
					<tr>
						<th colspan="2"><label for="snippet-cpt-posts"><?php _e( 'Choose a Snippet', 'code-snippet-cpt' ); ?></label></th>
					</tr>
					<tr>
						<td colspan="2">
							<select name="snippet-cpt-posts" id="snippet-cpt-posts" value="left" class="text ui-widget-content ui-corner-all">
								<?php foreach ( $snippets as $snippet ) :
									$lang_slug	= '';
									if ( $has_slug = $this->language->language_slug_from_post( $snippet->ID ) ) {
										$lang_slug = $has_slug;
									}
									$edit_link = get_edit_post_link( $snippet->ID );
								?>
									<option value="<?php echo $snippet->post_name;?>" data-lang="<?php echo $lang_slug; ?>" data-id="<?php echo $snippet->ID; ?>" data-editlink="<?php echo esc_url( $edit_link ); ?>"><?php echo $snippet->post_title; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="th"><label for="snippet-cpt-line-nums"><?php _e( 'Display Line Numbers?', 'tv-msnbc' ); ?></label></td>
						<td><input type="checkbox" name="snippet-cpt-line-nums" id="snippet-cpt-line-nums" value="1" checked="checked" class="text ui-widget-content ui-corner-all" /></td>
					</tr>

					<?php else : ?>
					<tr>
						<th><label id="no-snippets-exist"><?php _e( 'No Snippets Yet!', 'code-snippet-cpt' ); ?></label></th>
					</tr>
					<?php endif; ?>
				</table>
			</fieldset>
			</form>
		</div>
		<?php
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

		ob_start();
		echo $shortcode;

		add_action( 'wp_print_scripts', array( $this->cpt, 'run_js' ) );

		wp_print_scripts( 'prettify' );

		wp_send_json_success( array(
			'head' => $head,
			'body' => ob_get_clean(),
		) );
	}
}
