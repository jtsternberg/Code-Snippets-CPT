<?php
/**
 * Plugin class that registers the Code Snipet CPT.
 */
class Snippet_CPT_Setup {

	public $post_type = 'code-snippets';

	private $singular;
	private $plural;

	function __construct() {

		$this->singular  = __( 'Code Snippet', 'code-snippets-cpt' );
		$this->plural    = __( 'Code Snippets', 'code-snippets-cpt' );

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );
		add_filter( 'manage_edit-'. $this->post_type .'_columns', array( $this, 'columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'columns_display' ) );

		add_filter( 'user_can_richedit', array( $this, 'remove_html' ), 50 );
		add_filter( 'enter_title_here', array( $this, 'title' ) );
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_filter( 'gettext', array( $this, 'text' ), 20, 2 );
		add_action( 'init', array( $this, 'register_scripts_styles' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'ace_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'ace_front_end_scripts' ) );
		add_action( 'wp_ajax_snippetscpt-ace-ajax', array( $this, 'ace_ajax' ) );
		add_filter( 'dsgnwrks_snippet_display', array( $this, 'snippet_controller' ), 10, 3 );

		add_action( 'template_redirect', array( $this, 'remove_filter' ) );

	}

	public function register_post_type() {
		// set default custom post type options
		register_post_type( $this->post_type, apply_filters( 'snippet_cpt_registration_args', array(
			'labels' => array(
				'name'               => $this->plural,
				'singular_name'      => $this->singular,
				'add_new'            => 'Add New ' .$this->singular,
				'add_new_item'       => 'Add New ' .$this->singular,
				'edit_item'          => 'Edit ' .$this->singular,
				'new_item'           => 'New ' .$this->singular,
				'all_items'          => 'All ' .$this->plural,
				'view_item'          => 'View ' .$this->singular,
				'search_items'       => 'Search ' .$this->plural,
				'not_found'          => 'No ' .$this->plural .' found',
				'not_found_in_trash' => 'No ' .$this->plural .' found in Trash',
				'parent_item_colon'  => '',
				'menu_name'          => $this->plural,
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'menu_icon'          => 'dashicons-editor-code',
			'rewrite'            => true,
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'excerpt' ),
		) ) );
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
		switch ( $column ) {
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

	public function remove_filter() {
		if ( $this->post_type != get_post_type() ) {
			return;
		}
		remove_filter( 'the_content', 'wptexturize' );
		remove_filter( 'the_content','wpautop' );
	}

	public function register_scripts_styles() {
		$ace_min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '-min';
		wp_register_style( 'ace_css', DWSNIPPET_URL .'lib/css/ace.css', array( 'dashicons' ), '1.0' );
		wp_register_script( 'ace_editor', DWSNIPPET_URL . "lib/js/ace/src{$ace_min}-noconflict/ace.js", array( 'jquery' ), '1.0', true );
		wp_register_script( 'snippet-cpt-admin-js', DWSNIPPET_URL . 'lib/js/code-snippet-admin.js', array( 'jquery', 'ace_editor' ), '1.0', true );
		wp_register_script( 'snippet-cpt-js', DWSNIPPET_URL . 'lib/js/code-snippet-ace.js', array( 'jquery', 'ace_editor' ), '1.0', true );
	}

	public function ace_scripts(){
		$current_user = wp_get_current_user();
		wp_enqueue_style( 'ace_css' );
		wp_enqueue_script( 'ace_editor' );
		wp_localize_script( 'snippet-cpt-admin-js', 'ace_editor_globals', array(
			'nonce'  => wp_create_nonce( 'ace_editor_nonce' ),
			'labels' => array(
				'default' => __( 'Change Theme:', 'code-snippets-cpt' ),
				'saving'  => __( 'Saving...', 'code-snippets-cpt' ),
			),
			'theme'  => get_user_meta( $current_user->ID, 'snippetscpt-ace-editor-theme', true ),
			'default_lang' => apply_filters( 'snippetcpt_default_ace_lang', 'text' ),
		) );
		wp_enqueue_script( 'snippet-cpt-admin-js' );
	}

	public function ace_front_end_scripts(){
		$current_user = wp_get_current_user();
		wp_enqueue_style( 'ace_css' );
		wp_enqueue_script( 'ace_editor' );
		wp_localize_script( 'snippet-cpt-js', 'ace_editor_front_end_globals', array(
			'theme'  => get_user_meta( $current_user->ID, 'snippetscpt-ace-editor-theme', true ),
			'default_theme' => apply_filters( 'snippetcpt_default_ace_theme', 'ace/theme/monokai' ),
			'default_lang' => apply_filters( 'snippetcpt_default_ace_lang', 'text' ),
		) );
		wp_enqueue_script( 'snippet-cpt-js' );
	}

	public function ace_ajax(){
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'ace_editor_nonce' ) ){
			wp_send_json_error( array(
				'message' => __( 'Security failure', 'code-snippets-cpt' ),
			) );
		}

		$current_user = wp_get_current_user();
		$new_theme = $_POST['theme'];
		$nonce = wp_create_nonce( 'ace_editor_nonce' );
		$result = update_user_meta( $current_user->ID, 'snippetscpt-ace-editor-theme', $new_theme );
		if ( false === $result ){
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

	/**
	 * Snippet Controller
	 * So far will only collapse the snippet and show/hide
	 * line numbers. But should do WAY more.
	 * @param  string 	$output      	HTML Output of original shortcode
	 * @param  array 	$atts        	shortcode attributes
	 * @param  WP_Post	$snippet_obj 	post object similar to get_post
	 * @return string              		HTML output for display.
	 */
	public function snippet_controller( $output, $atts, $snippet_obj ){

		$tmp  = '<div class="snippetcpt-ace-controller">';
		$tmp .= '	<div class="snippetcpt_controls">';

		if ( $atts['title_attr'] && ! in_array( $atts['title_attr'], array( 'no', 'false' ), true ) ) {
			$title_attr = esc_attr( $snippet_obj->post_title );
			$tmp .= '	<div class="snippetcpt_title">' . $title_attr . '</div>';
		}

		$tmp .= '		<a href="#" class="dashicons dashicons-sort collapse"></a>';
		$tmp .= '		<a href="#" class="dashicons dashicons-editor-ol line_numbers"></a>';
		$tmp .= '	</div>';
		$tmp .= $output;
		$tmp .= '</div>';
		return $tmp;
	}

	public function run_js() {
		if ( isset( $this->js_done ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			window.onload = function(){ prettyPrint(); };
		</script>
		<?php

		$this->js_done = true;
	}

	public function remove_html() {
		if ( get_post_type() == $this->post_type ) return false;
		return true;
	}

	public function title( $title ){

		$screen = get_current_screen();
		if ( $screen->post_type == $this->post_type ) {
			$title = 'Snippet Title';
		}

		return $title;
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

		if ( ( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->post_type ) || ( $pagenow == 'post.php' && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == $this->post_type ) || ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->post_type ) ) {

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
		unset( $_wp_post_type_features[$this->post_type]['editor'] );

		add_meta_box( 'snippet_content', __('Snippet'), array( $this, 'content_editor_meta_box' ), $this->post_type, 'normal', 'core' );
	}

	public function content_editor_meta_box( $post ) {
		$content = ! empty( $post->post_content ) ? $post->post_content : '';
		?>
		<div class="ace_editor_settings">
			<label for="ace_theme_settings" id="ace_label"><?php _e( 'Change Theme:', 'code-snippets-cpt' ); ?></label>
			<select id="ace_theme_settings" size="1">          
				<?php echo $this->ace_theme_selector_options(); ?>
			</select>
		</div>
		<div id="snippet-content"><?php echo $content; ?></div>
		<textarea name="content" class="widefat snippet-main-content" class="hidden"><?php echo $content; ?></textarea>
		<?php
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
	public function ace_theme_selector_options(){

		$current_user = wp_get_current_user();
		$theme = get_user_meta( $current_user->ID, 'snippetscpt-ace-editor-theme', true );

		$available_themes = apply_filters( 'snippetcpts_available_ace_themes', array(
			array(
				'label' => __( 'Bright', 'code-snippets-cpt' ),
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
		foreach ( $available_themes as $theme_group ){
			$options = $theme_group['options'];
			$output .= "<optgroup label='{$theme_group['label']}' >";
			foreach ( $options as $value => $name ){
				$selected = selected( $theme, $value, false );
				$output .= "<option value='$value' $selected >$name</option>";
			}
			$output .= "</optgroup>";
		}

		return $output;
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
