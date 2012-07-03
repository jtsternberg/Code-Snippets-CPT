<?php
/**
 * Plugin class for Code Snipet CPT and it's associated taxonomies.
 *
 */
class Snippet_CPT_Setup {

	public $post_type = 'Code Snippet';
	public $plural = 'Code Snippets';
	public $registered = 'code-snippets';
	public $slug;

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

		$this->slug = sanitize_title( $this->plural );

		$cats = new Snippet_Tax_Setup( 'Snippet Category', 'Snippet Categories', array( $this->slug ) );
		$args = array(
			'hierarchical' => false,
		);
		new Snippet_Tax_Setup( 'Snippet Tag', '', array( $this->slug ), $args );
		$args = array(
			'public' => false,
			'show_ui' => false,
		);
		$language = new Snippet_Tax_Setup( 'Language', '', array( $this->slug ), $args );
		$language->init_select_box();

		add_action( 'init', array( &$this, 'cpt_loop' ) );
		add_filter( 'post_updated_messages', array( &$this, 'messages' ) );
		add_filter( 'user_can_richedit', array( &$this, 'remove_html' ), 50 );
		add_filter( 'enter_title_here', array( &$this, 'title' ) );
		add_filter( 'manage_edit-'. $this->slug .'_columns', array( &$this, 'columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'columns_display' ) );
		add_action( 'add_meta_boxes', array( &$this, 'meta_boxes' ) );
		add_filter( 'gettext', array( &$this, 'text' ), 20, 2 );
		add_filter( 'the_content', array( &$this, 'content' ) );
		register_activation_hook( DWSNIPPET_PATH .'code-snippets-cpt.php', array( &$this, 'add_languages_event' ) );
		add_action( 'snippet_add_languages', array( &$this, 'add_languages' ) );

	}

	public function add_languages_event() {
		wp_schedule_single_event( ( time() + 2 ), 'snippet_add_languages' );
	}

	public function add_languages() {
		$languages = array( 'Python', 'HTML', 'CSS', 'JavaScript', 'PHP', 'SQL', 'Perl', 'Ruby' );
		foreach ( $languages as $language ) {
			wp_insert_term( $language, 'languages' );
		}
	}

	public function cpt_loop( $custom_args = array() ) {

		//set default custom post type options
		$defaults = array(
			'labels' => array(
				'name' => $this->plural,
				'singular_name' => $this->post_type,
				'add_new' => 'Add New ' .$this->post_type,
				'add_new_item' => 'Add New ' .$this->post_type,
				'edit_item' => 'Edit ' .$this->post_type,
				'new_item' => 'New ' .$this->post_type,
				'all_items' => 'All ' .$this->plural,
				'view_item' => 'View ' .$this->post_type,
				'search_items' => 'Search ' .$this->plural,
				'not_found' =>  'No ' .$this->plural .' found',
				'not_found_in_trash' => 'No ' .$this->plural .' found in Trash',
				'parent_item_colon' => '',
				'menu_name' => $this->plural
			),
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'excerpt' )
		);

		$args = wp_parse_args( $custom_args, $defaults );
		register_post_type( $this->slug, $args );
	}

	public function messages( $messages ) {
		global $post, $post_ID;

		$messages[$this->post_type] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( '%1$s updated. <a href="%2$s">View %1$s</a>' ), $this->post_type, esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.' ),
			3 => __( 'Custom field deleted.' ),
			4 => sprintf( __( '%1$s updated.' ), $this->post_type ),
			/* translators: %s: date and time of the revision */
			5 => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s' ), $this->post_type , wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( '%1$s published. <a href="%2$s">View %1$s</a>' ), $this->post_type, esc_url( get_permalink( $post_ID ) ) ),
			7 => sprintf( __( '%1$s saved.' ), $this->post_type ),
			8 => sprintf( __( '%1$s submitted. <a target="_blank" href="%2$s">Preview %1$s</a>' ), $this->post_type, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9 => sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %1$s</a>' ), $this->post_type,
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %1$s</a>' ), $this->post_type, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);
		return $messages;

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

	public function content( $content ) {
		// if ( get_post_type() == $this->slug )
		// 	$content = '<pre class="brush: php; title: ; notranslate" title="">'. $content .'</pre>';
		return $content;

	}


}

new Snippet_CPT_Setup;