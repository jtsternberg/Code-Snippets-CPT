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
		wp_register_script( $this->script, DWSNIPPET_URL .'/lib/js/'. $this->script .'.js' , array( 'jquery', 'quicktags', 'wpdialogs' ), CodeSnippitInit::VERSION, true );

		wp_localize_script( $this->script, 'codeSnippetCPT', array(
			'button_img'   => DWSNIPPET_URL .'lib/js/icon.png',
			'button_name'  => __( 'Add Snippet', 'code-snippet-cpt' ),
			'button_title' => __( 'Add a Code Snippet', 'code-snippet-cpt' ),
			'btn_cancel'   => __( 'Cancel', 'snippet-cpt' ),
			'btn_insert'   => __( 'Insert Shortcode', 'snippet-cpt' ),
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
								?>
									<option value="<?php echo $snippet->post_name;?>" data-lang="<?php echo $lang_slug; ?>"><?php echo $snippet->post_title; ?></option>
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

}
