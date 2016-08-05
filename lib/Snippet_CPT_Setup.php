<?php
/**
 * Plugin class that registers the Code Snipet CPT.
 */
class Snippet_CPT_Setup {

	protected $singular;
	protected $plural;
	protected $post_type;
	protected $args;
	protected $language;
	protected static $is_full_screen = false;
	protected static $is_singular = false;

	public function __construct() {
		$this->singular  = __( 'Code Snippet', 'code-snippets-cpt' );
		$this->plural    = __( 'Code Snippets', 'code-snippets-cpt' );
		$this->post_type = 'code-snippets';

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'check_for_code_copy_window' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );
		add_filter( 'manage_edit-'. $this->post_type .'_columns', array( $this, 'columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'columns_display' ) );
		add_filter( 'user_can_richedit', array( $this, 'maybe_remove_html' ), 50 );
		add_filter( 'enter_title_here', array( $this, 'title' ) );
		add_filter( 'gettext', array( $this, 'text' ), 20, 2 );
		add_action( 'edit_form_after_title', array( $this, 'shortcode_sample' ) );
		add_action( 'init', array( $this, 'register_scripts_styles' ) );
		add_action( 'template_redirect', array( $this, 'maybe_remove_filter' ) );
		add_filter( 'the_content', array( $this, 'prettify_content' ), 20, 2 );
		add_filter( 'body_class', array( $this, 'maybe_full_screen' ), 20, 2 );
	}

	public function set_language( Snippet_Tax_Setup $language ) {
		$this->language = $language;
	}

	public function register_post_type() {
		// set default custom post type options
		register_post_type( $this->post_type, apply_filters( 'snippet_cpt_registration_args', array(
			'labels' => array(
				'name'               => $this->plural,
				'singular_name'      => $this->singular,
				'add_new'            => __( 'Add New Code Snippet', 'snippet-cpt' ),
				'add_new_item'       => __( 'Add New Code Snippet', 'snippet-cpt' ),
				'edit_item'          => __( 'Edit Code Snippet', 'snippet-cpt' ),
				'new_item'           => __( 'New Code Snippet', 'snippet-cpt' ),
				'all_items'          => __( 'All Code Snippets', 'snippet-cpt' ),
				'view_item'          => __( 'View Code Snippet', 'snippet-cpt' ),
				'search_items'       => __( 'Search Code Snippets', 'snippet-cpt' ),
				'not_found'          => __( 'No Code Snippets found', 'snippet-cpt' ),
				'not_found_in_trash' => __( 'No Code Snippets found in Trash', 'snippet-cpt' ),
				'parent_item_colon'  => '',
				'menu_name'          => $this->plural
			),
			'public'               => true,
			'publicly_queryable'   => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'query_var'            => true,
			'menu_icon'            => 'dashicons-editor-code',
			'rewrite'              => true,
			'capability_type'      => 'post',
			'has_archive'          => true,
			'hierarchical'         => false,
			'menu_position'        => null,
			'register_meta_box_cb' => array( $this, 'meta_boxes' ),
			'supports'             => array( 'title', 'excerpt' )
		) ) );
	}

	public function check_for_code_copy_window() {
		if ( ! isset( $_GET['code-snippets'], $_GET['id'] ) || ! wp_verify_nonce( $_GET['code-snippets'], 'code-snippets-cpt' ) ) {
			return;
		}

		if ( $snippet_post = get_post( absint( $_GET['id'] ) ) ) {
			ob_start();
			include_once( DWSNIPPET_PATH .'lib/views/snippet-window.php' );
			wp_die( ob_get_clean(), __( 'Copy Snippet (cmd/ctrl+c)', 'snippet-cpt' ) );
		}
	}

	public function messages( $messages ) {
		global $post, $post_ID;

		$messages[$this->post_type] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( '%1$s updated. <a href="%2$s">View %1$s</a>' ), $this->singular, esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.' ),
			3 => __( 'Custom field deleted.' ),
			4 => sprintf( __( '%1$s updated.' ), $this->singular ),
			/* translators: %s: date and time of the revision */
			5 => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s' ), $this->singular , wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( '%1$s published. <a href="%2$s">View %1$s</a>' ), $this->singular, esc_url( get_permalink( $post_ID ) ) ),
			7 => sprintf( __( '%1$s saved.' ), $this->singular ),
			8 => sprintf( __( '%1$s submitted. <a target="_blank" href="%2$s">Preview %1$s</a>' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9 => sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %1$s</a>' ), $this->singular,
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %1$s</a>' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;

	}

	public function columns( $columns ) {
		$newcolumns = array(
			'syntax_languages' => 'Syntax Languages',
			'snippet_categories' => 'Snippet Categories',
			'snippet_tags' => 'Snippet Tags',
		);
		$columns = array_merge( $columns, $newcolumns );
		return $columns;
	}

	public function columns_display( $column ) {
		global $post;
		switch ( $column ) {
			case 'syntax_languages':
				return $this->taxonomy_column(
					$post,
					'languages',
					__( 'No Languages Specified', 'snippet-cpt' )
				);
			case 'snippet_categories':
				return $this->taxonomy_column(
					$post,
					'snippet-categories',
					__( 'No Snippet Categories Specified', 'snippet-cpt' )
				);
			case 'snippet_tags':
				return $this->taxonomy_column(
					$post,
					'snippet-tags',
					__( 'No Snippet Tags Specified', 'snippet-cpt' )
				);
		}
	}

	public function maybe_remove_filter() {
		if ( $this->post_type === get_post_type() ) {
			remove_filter( 'the_content', 'wptexturize' );
			remove_filter( 'the_content', 'wpautop' );
		}
	}

	public function register_scripts_styles() {
		wp_register_script( 'prettify', DWSNIPPET_URL .'lib/js/prettify.js', null, '1.1' );

		if ( $this->enabled_features( 'any' ) ) {
			wp_register_script( 'snippet-cpt', DWSNIPPET_URL .'lib/js/snippet-cpt.js', array( 'jquery', 'prettify' ), CodeSnippitInit::VERSION );
		}

		wp_register_style( 'prettify', DWSNIPPET_URL .'lib/css/prettify.css', array( 'dashicons' ), CodeSnippitInit::VERSION );
		wp_register_style( 'prettify-monokai', DWSNIPPET_URL .'lib/css/prettify-monokai.css', array( 'prettify' ), CodeSnippitInit::VERSION );
	}

	public function enqueue_prettify() {
		if ( $this->enabled_features( 'any' ) ) {
			wp_enqueue_script( 'snippet-cpt' );
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

	public static function do_monokai_theme() {
		return apply_filters( 'snippet_cpt_monokai_theme', true );
	}

	public static function localize_js_data() {
		$data = array(
			'show_code_url' => self::show_code_url_base(),
			'features'      => self::enabled_features(),
			'fullscreen'    => self::$is_full_screen,
			'isSnippet'     => self::$is_singular,
			'l10n'          => array(
				'copy'       => esc_attr__( 'Copy Snippet', 'snippet-cpt' ),
				'fullscreen' => esc_html__( 'Expand Snippet', 'snippet-cpt' ),
				'close'      => esc_html__( 'Close Snippet (or hit "escape" key)', 'snippet-cpt' ),
				'edit'       => esc_html__( 'Edit Snippet', 'snippet-cpt' ),
			),
		);
		wp_localize_script( 'snippet-cpt', 'snippetcpt', apply_filters( 'snippet_cpt_js_data', $data ) );
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
				document.getElementsByTagName('body')[0].className += ' cscpt-js-loaded';
				if ( window.jQuery ) {
					jQuery( document ).trigger( 'prettify-loaded' );
				}
			} ); };
		</script>
		<?php
		$js_done = true;
	}

	public function maybe_remove_html( $can_richedit ) {
		if ( $this->post_type === get_post_type() ) {
			$can_richedit = false;
		}

		return $can_richedit;
	}

	public function title( $title ){
		$screen = get_current_screen();

		if ( $this->post_type === $screen->post_type ) {
			$title = __( 'Snippet Title', 'snippet-cpt' );
		}

		return $title;
	}

	public function taxonomy_column( $post, $tax, $oops ) {
		if ( empty( $post ) ) {
			return;
		}

		$categories = get_the_terms( $post->ID, $tax );
		if ( empty( $categories ) ) {
			return print( $oops );
		}

		$out = array();
		foreach ( $categories as $c ) {
			$out[] = sprintf( '<a href="%s">%s</a>',
			esc_url( add_query_arg( array( 'post_type' => $post->post_type, $tax => $c->slug ), 'edit.php' ) ),
			esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, 'category', 'display' ) )
			);
		}

		echo join( ', ', $out );
	}

	public function is_snippet_cpt_admin_page() {
		global $pagenow;

		return (
			( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && $this->post_type === $_GET['post_type'] )
			|| ( $pagenow == 'post.php' && isset( $_GET['post'] ) && $this->post_type === get_post_type( $_GET['post'] ) )
			|| ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $this->post_type === $_GET['post_type'] )
		);
	}

	public function text( $translation, $text ) {
		if ( ! $this->is_snippet_cpt_admin_page() ) {
			return $translation;
		}

		switch ($text) {
			case 'Excerpt';
				return __( 'Snippet Description:', 'snippet-cpt' );
			case 'Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="https://codex.wordpress.org/Excerpt" target="_blank">Learn more about manual excerpts.</a>';
				return '';
			// case 'Permalink:';
			// 	return __( 'Slug will also be used in shortcodes:' );
		}

		return $translation;
	}

	public function shortcode_sample( $post ) {
		if ( ! $this->is_snippet_cpt_admin_page() ) {
			return;
		}

		$lang = '';
		if ( $has_slug = $this->language->language_slug_from_post( $post->ID ) ) {
			$lang = ' lang=' . $has_slug;
		}

		echo '<div style="padding:10px 10px 0 10px;"><strong>'. __( 'Shortcode:', 'snippets-cpt' ) .'</strong> ';
				echo '<code>['. CodeSnippitInit::SHORTCODE_TAG .' slug='. $post->post_name . $lang .']</code></div>';
	}

	public function meta_boxes() {
		add_meta_box( 'snippet_content', __('Snippet'), array( $this, 'content_editor_meta_box' ), $this->post_type, 'normal', 'core' );
	}

	public function content_editor_meta_box( $post ) {
		wp_editor( $post->post_content, 'content', array(
			'media_buttons' => false,
			'textarea_name' =>'content',
			'textarea_rows' => 30,
			'tabindex'      => '4',
			'dfw'           => true,
			'quicktags'     => array( 'buttons' => 'link,ul,ol,li,close,fullscreen' )
		) );
	}

	public function prettify_content( $content ) {
		if ( $this->post_type !== get_post_type() ) {
			return $content;
		}

		global $post;

		$this->enqueue_prettify();

		return CodeSnippitInit::prettyprint_html( $post );
	}

	public function maybe_full_screen( $body_classes ) {

		self::$is_singular = is_singular( $this->post_type );
		if (
			isset( $_GET['full-screen'] )
			&& self::$is_singular
			&& $this->enabled_features( 'enable_full_screen_view' )
		) {
			self::$is_full_screen = true;
			$body_classes[] = 'snippet-full-screen';
			add_action( 'wp_after_admin_bar_render', array( $this, 'output_fullscreen_markup' ) );
		}


		return $body_classes;
	}

	public function output_fullscreen_markup() {
		global $post;
		$output = CodeSnippitInit::prettyprint_html( $post, array(
			'class'           => 'prettyprint linenums',
			'title_attr'      => esc_attr( get_the_title( $post->ID ) ),
		) );

		echo '<div class="footer-prettyprint">'. $output .'</div>';
	}

	public static function enabled_features( $to_check = '' ) {

		/*
		 * To disable:
		 *
		 * add_filter( 'snippet_cpt_do_click_to_copy', '__return_false' );
		 * add_filter( 'snippet_cpt_enable_full_screen_view', '__return_false' );
		 *
		 */

		$features = array(
			'do_click_to_copy'        => apply_filters( 'snippet_cpt_do_click_to_copy', true ),
			'enable_full_screen_view' => apply_filters( 'snippet_cpt_enable_full_screen_view', true ),
		);

		if ( 'all' == $to_check ) {
			foreach ( $features as $feature ) {
				if ( ! $feature ) {
					return false;
				}
			}

			return true;
		}

		if ( 'any' == $to_check ) {
			foreach ( $features as $feature ) {
				if ( $feature ) {
					return true;
				}
			}

			return false;
		}

		if ( $to_check && array_key_exists( $to_check, $features ) ) {
			return (bool) $features[ $to_check ];
		}

		return $features;
	}

	public function __get( $property ) {
		switch( $property ) {
			case 'singular':
			case 'plural':
			case 'post_type':
			case 'args':
				return $this->{$property};
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $property );
		}
	}

}

// add_filter( 'snippet_cpt_do_click_to_copy', '__return_false' );
// add_filter( 'snippet_cpt_enable_full_screen_view', '__return_false' );
// add_filter( 'snippet_cpt_monokai_theme', '__return_false' );
