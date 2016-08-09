<?php
/**
 * Plugin class to manage code-snippet front-end display.
 */
class Snippet_CPT_Frontend {

	protected $cpt;

	public function __construct( $cpt ) {
		$this->cpt = $cpt;

		// Snippet Shortcode Setup
		add_shortcode( CodeSnippitInit::SHORTCODE_TAG, array( $this, 'shortcode' ) );


		add_action( 'template_redirect', array( $this, 'remove_filter' ) );
		add_filter( 'the_content', array( $this, 'modify_snippet_singular_content' ), 20, 2 );
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

		$output = $this->cpt->is_ace_enabled()
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
		$this->cpt->enqueue_prettify();
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

		// '<pre class="prettyprint linenums lang-php" title="Large Network &#039;My Sites&#039; menu replacement">'

		return sprintf(
			'<div class="snippetcpt-wrap"><pre class="%1$s" title="%2$s">%3$s</pre></div>',
			$class,
			$atts['title_attr'],
			$atts['content']
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

	public function remove_filter() {
		if ( get_post_type() != $this->cpt->post_type ) {
			return;
		}
		remove_filter( 'the_content', 'wptexturize' );
		remove_filter( 'the_content','wpautop' );
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

}
