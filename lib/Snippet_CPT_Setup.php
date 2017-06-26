<?php
/**
 * Plugin class that registers the Code Snipet CPT.
 */
class Snippet_CPT_Setup {

	protected $post_type = 'code-snippets';

	protected $singular;
	protected $plural;
	protected $args;
	protected $language;

	function __construct() {

		$this->singular  = __( 'Code Snippet', 'code-snippets-cpt' );
		$this->plural    = __( 'Code Snippets', 'code-snippets-cpt' );

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );
		add_filter( 'manage_edit-'. $this->post_type .'_columns', array( $this, 'columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'columns_display' ) );

		add_filter( 'user_can_richedit', array( $this, 'remove_html' ), 50 );
		add_filter( 'enter_title_here', array( $this, 'title' ) );
		add_filter( 'gettext', array( $this, 'text' ), 20, 2 );
		add_action( 'edit_form_after_title', array( $this, 'shortcode_sample' ) );
		add_action( 'init', array( $this, 'register_scripts_styles' ) );

		// ACE Scripts
		add_action( 'wp_ajax_snippetscpt-ace-ajax', array( $this, 'ace_ajax' ) );
	}

	public function set_language( Snippet_Tax_Setup $language ) {
		$this->language = $language;
	}

	public function register_post_type() {
		$args = array(
			'labels' => array(
				'name'               => $this->plural,
				'singular_name'      => $this->singular,
				'add_new'            => __( 'Add New Snippet', 'code-snippets-cpt' ),
				'add_new_item'       => __( 'Add New Code Snippet', 'code-snippets-cpt' ),
				'edit_item'          => __( 'Edit Code Snippet', 'code-snippets-cpt' ),
				'new_item'           => __( 'New Code Snippet', 'code-snippets-cpt' ),
				'all_items'          => __( 'All Code Snippets', 'code-snippets-cpt' ),
				'view_item'          => __( 'View Code Snippet', 'code-snippets-cpt' ),
				'search_items'       => __( 'Search Code Snippets', 'code-snippets-cpt' ),
				'not_found'          => __( 'No Code Snippets found', 'code-snippets-cpt' ),
				'not_found_in_trash' => __( 'No Code Snippets found in Trash', 'code-snippets-cpt' ),
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
			'supports'             => array( 'title', 'excerpt', 'author' )
		);

		// This filter is deprecated, but left for back-compatibility.
		$args = apply_filters( 'snippet_cpt_registration_args', $args );

		// Filter the CPT args.
		$args = apply_filters( 'dsgnwrks_snippet_cpt_registration_args', $args );

		// set default custom post type options
		register_post_type( $this->post_type, $args );
	}

	public function messages( $messages ) {
		global $post, $post_ID;

		$messages[ $this->post_type ] = array(
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
			'syntax_languages'   => __( 'Syntax Languages', 'code-snippets-cpt' ),
			'snippet_categories' => __( 'Snippet Categories', 'code-snippets-cpt' ),
			'snippet_tags'       => __( 'Snippet Tags', 'code-snippets-cpt' ),
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
					__( 'No Languages Specified', 'code-snippets-cpt' )
				);
			case 'snippet_categories':
				return $this->taxonomy_column(
					$post,
					'snippet-categories',
					__( 'No Snippet Categories Specified', 'code-snippets-cpt' )
				);
			case 'snippet_tags':
				return $this->taxonomy_column(
					$post,
					'snippet-tags',
					__( 'No Snippet Tags Specified', 'code-snippets-cpt' )
				);
		}
	}

	public function register_scripts_styles() {
		$is_debug     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$ace_min      = $is_debug ? '' : '-min';
		$min          = $is_debug ? '' : '.min';
		$dependencies = array( 'jquery', 'wp-a11y' );

		wp_register_script( 'ace-editor', DWSNIPPET_URL . "assets/js/vendor/ace/src{$ace_min}-noconflict/ace.js", array( 'jquery' ), '1.0', true );
		wp_register_script( 'snippet-cpt-admin-js', DWSNIPPET_URL . "assets/js/code-snippet-admin-ace{$min}.js", array( 'jquery', 'ace-editor' ), '1.0', true );

		wp_register_style( 'ace-css', DWSNIPPET_URL . "assets/css/code-snippet-cpt-ace{$min}.css", array( 'dashicons' ), '1.0' );

		if ( CodeSnippitInit::get_option( 'ace' ) ) {
			$src = 'code-snippet-cpt-ace';
			$dependencies[] = 'ace-editor';
		} else {
			$src = 'code-snippet-cpt-prettify';
			wp_register_style( 'prettify', DWSNIPPET_URL ."assets/css/code-snippet-cpt-prettify{$min}.css", null, '1.0' );

			if ( 'ace/theme/monokai' === CodeSnippitInit::get_option( 'theme', 'ace/theme/monokai' ) ) {
				wp_register_style( 'prettify-monokai', DWSNIPPET_URL ."assets/css/code-snippet-cpt-prettify-monokai{$min}.css", null, '1.0' );
			}
		}

		wp_register_script( 'code-snippets-cpt', DWSNIPPET_URL ."assets/js/{$src}{$min}.js", $dependencies, CodeSnippitInit::VERSION, true );
	}

	public function ace_scripts( $handle, $args = array() ) {
		wp_enqueue_style( 'ace-css' );
		wp_enqueue_script( 'ace-editor' );
		wp_enqueue_script( $handle );

		$args = wp_parse_args( $args, array(
			'theme'    => CodeSnippitInit::get_option( 'theme', 'ace/theme/monokai' ),
			'language' => apply_filters( 'dsgnwrks_snippet_default_ace_lang', 'text' ),
			'features' => CodeSnippitInit::enabled_features(),
		) );
		wp_localize_script( $handle, 'snippetcptAce', $args );
	}

	public function ace_ajax() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'ace_editor_nonce' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security failure', 'code-snippets-cpt' ),
			) );
		}

		$new_theme = $_POST['theme'];
		$nonce = wp_create_nonce( 'ace_editor_nonce' );
		$result = update_user_meta( get_current_user_id(), 'snippetscpt-ace-editor-theme', $new_theme );
		if ( false === $result ) {
			wp_send_json_error( array(
				'nonce' => $nonce,
				'message' => __( 'Error inserting user data', 'code-snippets-cpt' ),
			) );
		}

		wp_send_json_success( array(
			'nonce' => $nonce,
			'theme' => $new_theme,
		) );
	}

	public function remove_html() {
		return get_post_type() !== $this->post_type;
	}

	public function title( $title ) {
		$screen = get_current_screen();

		if ( $this->post_type === $screen->post_type ) {
			$title = __( 'Snippet Title', 'code-snippets-cpt' );
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
				return __( 'Snippet Description:', 'code-snippets-cpt' );
			case 'Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="%s">Learn more about manual excerpts</a>.';
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
		add_meta_box( 'snippet_content', __( 'Snippet', 'code-snippets-cpt' ), array( $this, 'content_editor_meta_box' ), $this->post_type, 'normal', 'core' );
	}

	public function content_editor_meta_box( $post ) {
		$content = ! empty( $post->post_content ) ? $post->post_content : '';
		$theme   = get_user_meta( get_current_user_id(), 'snippetscpt-ace-editor-theme', true );

		?>
		<div class="ace-editor-settings">
			<label for="ace-theme-settings" id="ace-theme-change-label"><?php _e( 'Change Theme:', 'code-snippets-cpt' ); ?></label>
			<select id="ace-theme-settings" size="1">
				<?php echo $this->ace_theme_selector_options( $theme ); ?>
			</select>
		</div>
		<pre id="snippet-content" class="hidden"><?php echo $content; ?></pre>
		<pre><textarea name="content" class="widefat snippet-main-content"><?php echo $content; ?></textarea></pre>
		<?php

		$args = array(
			'nonce'  => wp_create_nonce( 'ace_editor_nonce' ),
			'labels' => array(
				'default' => __( 'Change Theme:', 'code-snippets-cpt' ),
				'saving'  => __( 'Saving...', 'code-snippets-cpt' ),
			),
		);

		if ( $theme ) {
			$args['theme'] = $theme;
		}

		$this->ace_scripts( 'snippet-cpt-admin-js', $args );
	}

	/**
	 * Ace theme selector options
	 *
	 * Put this in it's own method so we can add/remove themes more easily should
	 * the ACE devs decide to include more.  Also added a filter so others can hook
	 * into the available themes to add/remove them on a user by user basis.
	 *
	 * @return string HTML Option Selectors
	 */
	public function ace_theme_selector_options( $selected ) {

		$available_themes = apply_filters( 'dsgnwrks_snippet_available_ace_themes', array(
			array(
				'label'   => __( 'Bright', 'code-snippets-cpt' ),
				'options' => array(
					'ace/theme/chrome'          => __( 'Chrome', 'code-snippets-cpt' ),
					'ace/theme/clouds'          => __( 'Clouds', 'code-snippets-cpt' ),
					'ace/theme/crimson_editor'  => __( 'Crimson Editor', 'code-snippets-cpt' ),
					'ace/theme/dawn'            => __( 'Dawn', 'code-snippets-cpt' ),
					'ace/theme/dreamweaver'     => __( 'Dreamweaver', 'code-snippets-cpt' ),
					'ace/theme/eclipse'         => __( 'Eclipse', 'code-snippets-cpt' ),
					'ace/theme/github'          => __( 'GitHub', 'code-snippets-cpt' ),
					'ace/theme/solarized_light' => __( 'Solarized Light', 'code-snippets-cpt' ),
					'ace/theme/textmate'        => __( 'TextMate', 'code-snippets-cpt' ),
					'ace/theme/tomorrow'        => __( 'Tomorrow', 'code-snippets-cpt' ),
					'ace/theme/xcode'           => __( 'XCode', 'code-snippets-cpt' ),
					'ace/theme/kuroir'          => __( 'Kuroir', 'code-snippets-cpt' ),
					'ace/theme/katzenmilch'     => __( 'KatzenMilch', 'code-snippets-cpt' ),
				),
			),
			array(
				'label' => __( 'Dark', 'code-snippets-cpt' ),
				'options' => array(
					'ace/theme/ambiance'                => __( 'Ambiance', 'code-snippets-cpt' ),
					'ace/theme/chaos'                   => __( 'Chaos', 'code-snippets-cpt' ),
					'ace/theme/clouds_midnight'         => __( 'Clouds Midnight', 'code-snippets-cpt' ),
					'ace/theme/cobalt'                  => __( 'Cobalt', 'code-snippets-cpt' ),
					'ace/theme/idle_fingers'            => __( 'idle Fingers', 'code-snippets-cpt' ),
					'ace/theme/kr_theme'                => __( 'krTheme', 'code-snippets-cpt' ),
					'ace/theme/merbivore'               => __( 'Merbivore', 'code-snippets-cpt' ),
					'ace/theme/merbivore_soft'          => __( 'Merbivore Soft', 'code-snippets-cpt' ),
					'ace/theme/mono_industrial'         => __( 'Mono Industrial', 'code-snippets-cpt' ),
					'ace/theme/monokai'                 => __( 'Monokai', 'code-snippets-cpt' ),
					'ace/theme/pastel_on_dark'          => __( 'Pastel on dark', 'code-snippets-cpt' ),
					'ace/theme/solarized_dark'          => __( 'Solarized Dark', 'code-snippets-cpt' ),
					'ace/theme/terminal'                => __( 'Terminal', 'code-snippets-cpt' ),
					'ace/theme/tomorrow_night'          => __( 'Tomorrow Night', 'code-snippets-cpt' ),
					'ace/theme/tomorrow_night_blue'     => __( 'Tomorrow Night Blue', 'code-snippets-cpt' ),
					'ace/theme/tomorrow_night_bright'   => __( 'Tomorrow Night Bright', 'code-snippets-cpt' ),
					'ace/theme/tomorrow_night_eighties' => __( 'Tomorrow Night 80s', 'code-snippets-cpt' ),
					'ace/theme/twilight'                => __( 'Twilight', 'code-snippets-cpt' ),
					'ace/theme/vibrant_ink'             => __( 'Vibrant Ink', 'code-snippets-cpt' ),
				),
			),
		) );

		$output = '';
		foreach ( $available_themes as $theme_group ) {
			$options = $theme_group['options'];
			$output .= "<optgroup label='{$theme_group['label']}' >";
			foreach ( $options as $value => $name ) {
				$output .= '<option value="'. $value .'" '. selected( $selected, $value, false ) .' >'. $name .'</option>';
			}
			$output .= '</optgroup>';
		}

		return $output;
	}

	public function get_snippet_by_id_or_slug( $atts ) {
		$args = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => 1,
			'post_status'    => 'published',
		);

		if ( isset( $atts['id'] ) && is_numeric( $atts['id'] ) ) {
			$args['p'] = $atts['id'];
		} elseif ( isset( $atts['slug'] ) && is_string( $atts['slug'] ) ) {
			$args['name'] = sanitize_text_field( $atts['slug'] );
		} else {
			return false;
		}

		$snippets = new WP_Query( $args );

		return $snippets->have_posts()
			? $snippets->posts[0]
			: false;
	}

	public function __get( $property ) {
		switch ( $property ) {
			case 'singular':
			case 'plural':
			case 'post_type':
			case 'args':
			case 'language':
				return $this->{$property};
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $property );
		}
	}
}
