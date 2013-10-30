<?php
/**
 * Plugin class that registers the Code Snipet CPT.
 *
 */
class Snippet_CPT_Setup extends CPT_Setup {

	/**
	 * Holds copy of instance, so other plugins can remove our hooks.
	 *
	 * @since 1.0
	 * @link http://core.trac.wordpress.org/attachment/ticket/16149/query-standard-format-posts.php
	 * @link http://twitter.com/#!/markjaquith/status/66862769030438912
	 *
	 */
	static $instance;

	function __construct() {

		self::$instance = $this;

		parent::__construct( 'Code Snippet' );

		add_filter( 'user_can_richedit', array( &$this, 'remove_html' ), 50 );
		add_filter( 'enter_title_here', array( &$this, 'title' ) );
		add_action( 'add_meta_boxes', array( &$this, 'meta_boxes' ) );
		add_filter( 'gettext', array( &$this, 'text' ), 20, 2 );
		add_action( 'init', array( &$this, 'register_scripts_styles' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue' ) );
		add_action( 'template_redirect', array( &$this, 'remove_filter' ) );
		add_filter( 'the_content', array( &$this, 'prettify_content' ), 20, 2 );
	}

	public function remove_filter() {
		if ( get_post_type() != $this->slug ) return;
		remove_filter('the_content', 'wptexturize');
		remove_filter ('the_content','wpautop');
	}

	public function register_scripts_styles() {
		wp_register_script( 'prettify', DWSNIPPET_URL .'lib/js/prettify.js', null, '1.0' );
		wp_register_style( 'prettify', DWSNIPPET_URL .'lib/css/prettify.css', null, '1.0' );
		wp_register_style( 'prettify-plus', DWSNIPPET_URL .'lib/css/prettify-plus.css', null, '1.0' );
	}

	public function enqueue() {
		wp_enqueue_script( 'prettify' );
		wp_enqueue_style( 'prettify' );
		wp_enqueue_style( 'prettify-plus' );
	}

	public function remove_html() {
		if ( get_post_type() == $this->slug ) return false;
		return true;
	}

	public function title( $title ){

		$screen = get_current_screen();
		if ( $screen->post_type == $this->slug ) {
			$title = 'Snippet Title';
		}

		return $title;
	}

	public function columns( $columns ) {
		$newcolumns = array(
			'syntax_languages' => 'Syntax Languages',
			'snippet_categories' => 'Snippet Categories',
			'snippet_tags' => 'Snippet Tags',
		);
		$columns = array_merge( $columns, $newcolumns );
		return $columns;
		// $this->taxonomy_column( $post, 'uses', 'Uses' );
	}

	public function columns_display( $column ) {
		global $post;
		switch ($column) {
			case 'syntax_languages':
				$this->taxonomy_column( $post, 'languages', 'Languages' );
			break;
			case 'snippet_categories':
				$this->taxonomy_column( $post, 'snippet-categories', 'Snippet Categories' );
			break;
			case 'snippet_tags':
				$this->taxonomy_column( $post, 'snippet-tags', 'Snippet Tags' );
			break;
		}
	}

	public function taxonomy_column( $post = '', $tax = '', $name = '' ) {
		if ( empty( $post ) ) return;
		$id = $post->ID;
		$categories = get_the_terms( $id, $tax );
		if ( !empty( $categories ) ) {
			$out = array();
			foreach ( $categories as $c ) {
				$out[] = sprintf( '<a href="%s">%s</a>',
				esc_url( add_query_arg( array( 'post_type' => $post->post_type, $tax => $c->slug ), 'edit.php' ) ),
				esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, 'category', 'display' ) )
				);
			}
			echo join( ', ', $out );
		} else {
			_e( 'No '. $name .' Specified' );
		}

	}

	public function text( $translation, $text ) {
		global $pagenow;

		if ( ( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->slug ) || ( $pagenow == 'post.php' && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == $this->slug ) || ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->slug ) ) {

			switch ($text) {
				case 'Excerpt';
					return 'Snippet Description:';
				break;
				case 'Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="http://codex.wordpress.org/Excerpt" target="_blank">Learn more about manual excerpts.</a>';
					return '';
				break;
				case 'Permalink:';
					return 'Choose a slug that\'s easy to remember for the shortcode:';
				break;
			}
		}
		return $translation;
	}

	public function meta_boxes() {

		global $_wp_post_type_features;
		unset( $_wp_post_type_features[$this->slug]['editor'] );

		add_meta_box( 'snippet_content', __('Snippet'), array( &$this, 'content_editor_meta_box' ), $this->slug, 'normal', 'core' );
	}

	public function content_editor_meta_box( $post ) {
		$settings = array(
			'media_buttons' => false,
			'textarea_name'=>'content',
			'textarea_rows' => 30,
			'tabindex' => '4',
			'dfw' => true,
			'quicktags' => array( 'buttons' => 'link,ul,ol,li,close,fullscreen' )
		);
		wp_editor( $post->post_content, 'content', $settings );

	}

	public function prettify_content( $content ) {
		if ( get_post_type() != $this->slug ) return $content;

		return '<pre class="prettyprint linenums">'. htmlentities( $content ) .'</pre>';
	}

}
