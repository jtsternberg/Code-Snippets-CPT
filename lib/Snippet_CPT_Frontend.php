<?php
/**
 * Plugin class to manage code-snippet front-end display.
 */
class Snippet_CPT_Frontend {

	protected $cpt;
	protected static $is_full_screen = false;
	protected static $is_singular = false;

	public function __construct( $cpt ) {
		$this->cpt = $cpt;

		// Snippet Shortcode Setup
		add_shortcode( CodeSnippitInit::SHORTCODE_TAG, array( $this, 'shortcode' ) );

		add_action( 'init', array( $this, 'check_for_code_copy_window' ) );
		add_filter( 'body_class', array( $this, 'maybe_full_screen' ), 20, 2 );
		add_action( 'template_redirect', array( $this, 'maybe_remove_filters' ) );
		add_filter( 'the_content', array( $this, 'modify_snippet_singular_content' ), 20, 2 );
	}

	public function check_for_code_copy_window() {
		if ( ! isset( $_GET['code-snippets'], $_GET['id'] ) || ! wp_verify_nonce( $_GET['code-snippets'], 'code-snippets-cpt' ) ) {
			return;
		}

		if ( $snippet_post = get_post( absint( $_GET['id'] ) ) ) {
			ob_start();
			include_once( DWSNIPPET_PATH .'lib/views/snippet-window.php' );
			wp_die( ob_get_clean(), __( 'Copy Snippet (cmd/ctrl+c)', 'code-snippets-cpt' ) );
		}
	}

	public function maybe_full_screen( $body_classes ) {

		self::$is_singular = is_singular( $this->cpt->post_type );
		if (
			isset( $_GET['full-screen'] )
			&& self::$is_singular
			&& CodeSnippitInit::enabled_features( 'enable_full_screen_view' )
		) {
			self::$is_full_screen = true;
			$body_classes[] = 'snippet-full-screen';
			add_action( 'wp_after_admin_bar_render', array( $this, 'output_fullscreen_markup' ) );
		}


		return $body_classes;
	}

	public function output_fullscreen_markup() {
		global $post;

		$output = $this->shortcode( array(
			'id'           => $post->ID,
			'line_numbers' => true,
			'max_lines'    => false,
		) );

		echo '<div class="footer-prettyprint">'. $output .'</div>';
	}

	public function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id'           => false,
			'slug'         => '',
			'line_numbers' => true,
			'lang'         => '',
			'title_attr'   => true,
			'max_lines'    => 60,
			'classes'      => '',
			// @todo Offer to output snippet description/taxonomies
		), $atts, 'snippet' );

		$snippet = $this->cpt->get_snippet_by_id_or_slug( $atts );
		if ( ! $snippet || empty( $snippet->post_content ) ) {
			return '';
		}

		$atts['line_numbers'] = ! $atts['line_numbers'] || false === $atts['line_numbers'] || 'false' === $atts['line_numbers'] ? false : $atts['line_numbers'];

		$atts['content'] = apply_filters( 'dsgnwrks_snippet_content', htmlentities( $snippet->post_content, ENT_COMPAT, 'UTF-8' ), $atts, $snippet );
		$atts['id'] = $snippet->ID;

		if ( is_string( $atts['title_attr'] ) && ! in_array( $atts['title_attr'], array( 'true', 'yes', '1' ), true ) ) {
			$atts['title_attr'] = esc_attr( $atts['title_attr'] );
		} else {
			$atts['title_attr'] = in_array( $atts['title_attr'], array( 'no', 'false', '' ), true ) || ! $atts['title_attr'] ? '' : esc_attr( $snippet->post_title );
		}

		$output = CodeSnippitInit::enabled_features( 'enable_ace' )
			? $this->get_ace_output( $atts )
			: $this->get_legacy_output( $atts, $snippet );

		return apply_filters( 'dsgnwrks_snippet_display', $output, $atts, $snippet );
	}

	/**
	 * Gets the Legacy output as to not break the old code/display
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function get_legacy_output( $atts, $snippet ) {
		$this->enqueue_prettify();
		$class = 'prettyprint';
		if ( $atts['line_numbers'] ) {
			$class .= ' linenums';
			if ( is_numeric( $atts['line_numbers'] ) && 0 !== absint( $atts['line_numbers'] ) ) {
				$class .= ':' . absint( $atts['line_numbers'] );
			}
		}

		if ( ! empty( $atts['lang'] ) ) {
			$class .= ' lang-'. sanitize_html_class( $atts['lang'] );
		} elseif ( $lang_slug = $this->cpt->language->language_slug_from_post( $atts['id'] ) ) {
			$class .= ' lang-'. $lang_slug;
		}

		if ( $atts['classes'] ) {
			$class .= ' '. sanitize_text_field( $atts['classes'] );
		}

		$edit_link = '';
		if ( is_user_logged_in() && current_user_can( get_post_type_object( $snippet->post_type )->cap->edit_post,  $snippet->ID ) ) {
			$edit_link = get_edit_post_link( $snippet->ID );
		}

		$copy_link = self::show_code_url_base( array( 'id' => $snippet->ID ) );
		$fullscreen_link = add_query_arg( 'full-screen', 1, get_permalink( $snippet->ID ) );

		return sprintf(
			'<div class="snippetcpt-wrap" id="snippet-%4$s" data-id="%4$s" data-edit="%5$s" data-copy="%6$s" data-fullscreen="%7$s"><pre class="%1$s" title="%2$s">%3$s</pre></div>',
			$class,
			$atts['title_attr'],
			$atts['content'],
			$snippet->ID,
			esc_url( $edit_link ),
			esc_url( $copy_link ),
			esc_url( $fullscreen_link )
		);
	}

	/**
	 * Gets the output for the ACE front-end display
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function get_ace_output( $atts ) {
		static $scripts_enqueued = false;

		$data_attrs = array();

		if ( $atts['line_numbers'] ) {
			$data_attrs['line_nums'] = is_numeric( $atts['line_numbers'] ) && 0 !== absint( $atts['line_numbers'] )
				? absint( $atts['line_numbers'] )
				: true;
		}

		$data_attrs['max_lines'] = absint( $atts['max_lines'] );
		$data_attrs['max_lines'] = $data_attrs['max_lines'] > 2 ? $data_attrs['max_lines'] : 'auto';

		$data_attrs['lang'] = apply_filters( 'dsgnwrks_snippet_default_ace_lang', 'text' );
		if ( ! empty( $atts['lang'] ) ) {
			// Need this for backwards compatibility
			$maybe_old_language = sanitize_html_class( $atts['lang'] );
			$data_attrs['lang']  = $this->cpt->language->get_ace_slug( $maybe_old_language );
		} elseif ( $lang_slug = $this->cpt->language->language_slug_from_post( $atts['id'] ) ) {
			// Get the language linked to the current post id
			$data_attrs['lang'] = $lang_slug;
		}

		// Set the snippet ID, for use in the controller
		$data_attrs['snippet-id'] = $atts['id'];

		$data = '';
		if ( ! empty( $data_attrs ) ) {
			$value = wp_json_encode( $data_attrs );
			$data .= " data-config='{$value}'";
		}

		if ( $atts['classes'] ) {
			$atts['classes'] = ' '. sanitize_text_field( $atts['classes'] );
		}

		if ( ! $scripts_enqueued ) {
			$this->cpt->ace_scripts( 'snippet-cpt-js' );
			$scripts_enqueued = true;
		}

		// '<pre class="snippetcpt-ace-viewer" title="Large Network &#039;My Sites&#039; menu replacement"  data-line_nums=\'1\' data-lang=\'php\' data-snippet-id=\'15904\'>'

		return sprintf(
			'<div class="snippetcpt-wrap">
				<pre class="snippetcpt-ace-viewer %1$s" %2$s title="%3$s">%4$s</pre>
				<div class="snippet-controls" title="%3$s">
					<a href="#" class="dashicons dashicons-hidden collapse"></a>
					<a href="#" class="dashicons dashicons-editor-ol line-numbers"></a>
				</div>
			</div>',
			$atts['classes'],
			$data,
			$atts['title_attr'],
			$atts['content']
		);
	}

	public function maybe_remove_filters() {
		if ( $this->cpt->post_type === get_post_type() ) {
			remove_filter( 'the_content', 'wptexturize' );
			remove_filter( 'the_content', 'wpautop' );
		}
	}

	public function modify_snippet_singular_content( $content ) {
		if ( get_post_type() != $this->cpt->post_type ) {
			return $content;
		}

		return $this->shortcode( array(
			'id'           => get_the_id(),
			'line_numbers' => true,
			'max_lines'    => false,
			'classes'      => 'singular-snippet',
		) );
	}

	public static function do_monokai_theme() {
		return apply_filters( 'dsgnwrks_snippet_monokai_theme', true );
	}

	public function enqueue_prettify() {
		if ( CodeSnippitInit::enabled_features( 'any' ) ) {
			wp_enqueue_script( 'code-snippets-cpt' );
			add_action( 'wp_footer', array( __CLASS__, 'localize_js_data' ), 5 );
		} else {
			wp_enqueue_script( 'prettify' );
		}

		wp_enqueue_style( 'prettify' );

		if ( $this->do_monokai_theme() ) {
			wp_enqueue_style( 'prettify-monokai' );
		}

		add_action( 'wp_footer', array( __CLASS__, 'run_js' ), 9999 );
	}

	public static function localize_js_data() {
		$data = array(
			'show_code_url' => self::show_code_url_base(),
			'features'      => CodeSnippitInit::enabled_features(),
			'fullscreen'    => self::$is_full_screen,
			'isSnippet'     => self::$is_singular,
			'l10n'          => array(
				'copy'       => esc_attr__( 'Copy Snippet', 'code-snippets-cpt' ),
				'fullscreen' => esc_html__( 'Expand Snippet', 'code-snippets-cpt' ),
				'close'      => esc_html__( 'Close Snippet (or hit "escape" key)', 'code-snippets-cpt' ),
				'edit'       => esc_html__( 'Edit Snippet', 'code-snippets-cpt' ),
			),
		);
		wp_localize_script( 'code-snippets-cpt', 'snippetcpt', apply_filters( 'dsgnwrks_snippet_js_data', $data ) );
	}

	public static function show_code_url_base( $args = false ) {
		static $show_code_url_base = false;
		if ( ! $show_code_url_base  ) {
			$show_code_url_base = wp_nonce_url( add_query_arg( 'code-snippets', 'show' ), 'code-snippets-cpt', 'code-snippets' );
		}

		return esc_url( $args ? add_query_arg( $args, $show_code_url_base ) : $show_code_url_base );
	}

	public static function run_js() {
		static $js_done = false;
		if ( $js_done ) {
			return;
		}
		?>
		<script type="text/javascript">
			window.onload = function(){ prettyPrint( function() {
				document.getElementsByTagName('body')[0].className += ' snippetcpt-js-loaded';
				if ( window.jQuery ) {
					jQuery( document ).trigger( 'prettify-loaded' );
				}
			} ); };
		</script>
		<?php
		$js_done = true;
	}

}
