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

		// Super early to limit how much of WP needs to run.
		add_action( 'plugins_loaded', array( $this, 'check_for_code_copy_window' ), 5 );
		add_filter( 'body_class', array( $this, 'maybe_full_screen' ), 20, 2 );
		add_action( 'template_redirect', array( $this, 'maybe_remove_filters' ) );
		add_filter( 'the_content', array( $this, 'modify_snippet_singular_content' ), 20, 2 );
	}

	public function check_for_code_copy_window() {
		if (
			is_admin()
			|| ! isset( $_GET['code-snippets'], $_GET['id'] )
			|| ! wp_verify_nonce( $_GET['code-snippets'], 'code-snippets-cpt' )
			|| ! ( $snippet_post = get_post( absint( $_GET['id'] ) ) )
		) {
			return;
		}

		if ( isset( $_GET['json'] ) ) {
			return wp_send_json_success( $snippet_post->post_content );
		}
		ob_start();
		include_once( DWSNIPPET_PATH .'lib/views/snippet-window.php' );
		wp_die( ob_get_clean(), __( 'Copy Snippet (cmd/ctrl+c)', 'code-snippets-cpt' ) );
	}

	public function maybe_full_screen( $body_classes ) {

		self::$is_singular = is_singular( $this->cpt->post_type );
		if (
			isset( $_GET['full-screen'] )
			&& self::$is_singular
			&& CodeSnippitInit::enabled_features( 'enable_full_screen_view' )
		) {
			self::$is_full_screen = true;
			if ( ! CodeSnippitInit::get_option( 'ace' ) ) {
				$body_classes[] = 'snippet-full-screen';
				add_action( 'wp_after_admin_bar_render', array( $this, 'output_fullscreen_markup' ) );
			}
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

		echo '<div class="snippetcpt-footer">'. $output .'</div>';
	}

	public function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id'           => false,
			'slug'         => '',
			'line_numbers' => true,
			'lang'         => '',
			'title_attr'   => true,
			'max_lines'    => 'auto',
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

		$atts = array_merge( $atts, self::get_output_args( $snippet ) );

		$output = CodeSnippitInit::get_option( 'ace' )
			? $this->get_ace_output( $atts )
			: $this->get_legacy_output( $atts );

		return apply_filters( 'dsgnwrks_snippet_display', $output, $atts, $snippet );
	}

	/**
	 * Gets the Legacy output as to not break the old code/display
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function get_legacy_output( $atts ) {
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

		return sprintf(
			'<div class="snippetcpt-wrap" id="snippet-%4$s" data-id="%4$s" data-edit="%5$s" data-copy="%6$s" data-fullscreen="%7$s">
				<pre class="%1$s" title="%2$s">%3$s</pre>
			</div>',
			$class,
			$atts['title_attr'],
			$atts['content'],
			$atts['snippet_id'],
			$atts['edit_link'],
			$atts['copy_link'],
			$atts['fullscreen_link']
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
			$data_attrs['lang'] = $this->cpt->language->get_ace_slug( $maybe_old_language );
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
			$this->enqueue_ace();
			$scripts_enqueued = true;
		}

		return sprintf(
			'<div class="snippetcpt-wrap scrollable" id="snippet-%5$s" data-id="%5$s" data-edit="%6$s" data-copy="%7$s" data-fullscreen="%8$s">
				<pre class="snippetcpt-ace-viewer %1$s" %2$s title="%3$s">%4$s</pre>
				<div class="snippet-buttons" title="%3$s"></div>
			</div>',
			$atts['classes'],
			$data,
			$atts['title_attr'],
			$atts['content'],
			$atts['snippet_id'],
			$atts['edit_link'],
			$atts['copy_link'],
			$atts['fullscreen_link']
		);

	}

	public static function get_output_args( $snippet ) {
		$snippet_id = $snippet->ID;

		$edit_link = '';
		if ( is_user_logged_in() && current_user_can( get_post_type_object( $snippet->post_type )->cap->edit_post,  $snippet_id ) ) {
			$edit_link = get_edit_post_link( $snippet_id );
		}

		$copy_link = self::show_code_url_base( array( 'id' => $snippet_id ) );
		$fullscreen_link = add_query_arg( 'full-screen', 1, get_permalink( $snippet_id ) );

		return compact( 'edit_link', 'copy_link', 'fullscreen_link', 'snippet_id' );
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

	public function enqueue_ace() {
		$this->cpt->ace_scripts( 'code-snippets-cpt' );

		add_action( 'wp_footer', array( __CLASS__, 'localize_js_data' ), 5 );
	}

	public function enqueue_prettify() {
		wp_enqueue_script( 'code-snippets-cpt' );
		add_action( 'wp_footer', array( __CLASS__, 'localize_js_data' ), 5 );

		wp_enqueue_style( 'prettify' );

		if ( 'ace/theme/monokai' === CodeSnippitInit::get_option( 'theme', 'ace/theme/monokai' ) ) {
			wp_enqueue_style( 'prettify-monokai' );
		}

		add_action( 'wp_footer', array( __CLASS__, 'run_js' ), 9999 );
	}

	public static function localize_js_data() {
		$features = array();
		if ( ! is_admin() ) {
			$features = CodeSnippitInit::enabled_features();
			$features['enable_ace'] = (bool) CodeSnippitInit::get_option( 'ace' );
			$features['edit'] = true;
		}
		$data = array(
			'features'   => $features,
			'fullscreen' => self::$is_full_screen,
			'isSnippet'  => self::$is_singular,
			'l10n'       => array(
				'copy'       => esc_attr__( 'Copy Snippet', 'code-snippets-cpt' ),
				'fullscreen' => esc_html__( 'Expand Snippet', 'code-snippets-cpt' ),
				'close'      => esc_html__( 'Close Snippet (or hit "escape" key)', 'code-snippets-cpt' ),
				'edit'       => esc_html__( 'Edit Snippet', 'code-snippets-cpt' ),
				'collapse'   => esc_html__( 'Collapse Snippet', 'code-snippets-cpt' ),
				'numbers'    => esc_html__( 'Toggle Line Numbers', 'code-snippets-cpt' ),
			),
		);
		wp_localize_script( 'code-snippets-cpt', 'snippetcptl10n', apply_filters( 'dsgnwrks_snippet_js_data', $data ) );
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
