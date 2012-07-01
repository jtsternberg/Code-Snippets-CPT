<?php
/**
 * Plugin class for Code Snipet CPT and it's associated taxonomies.
 *
 */
class Snippet_Tax_Setup {

	public $taxonomy = 'Language';
	public $plural = 'Languages';
	public $slug;
	public $cpt;

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

		$this->cpt = new Snippet_CPT_Setup;

		add_action( 'init', array( &$this, 'tax_loop' ) );
		add_action( 'admin_menu', array( &$this, 'add_select_box' ) );

	}

	public function tax_loop( $singular = '', $plural = '', $post_types = array(), $custom_args = array() ) {

		$post_types = !empty( $post_types ) ? $post_types : array( 'code-snippets' );
		$singular = !empty( $singular ) ? $singular : $this->taxonomy;
		if ( empty( $plural ) ) $plural = $singular .'s';

		$labels = array(
			'name' => $plural,
			'singular_name' => $singular,
			'search_items' =>  'Search '.$plural,
			'all_items' => 'All '.$plural,
			'parent_item' => 'Parent '.$singular,
			'parent_item_colon' => 'Parent '.$singular.':',
			'edit_item' => 'Edit '.$singular,
			'update_item' => 'Update '.$singular,
			'add_new_item' => 'Add New '.$singular,
			'new_item_name' => 'New '.$singular.' Name',
		);
		$defaults = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => sanitize_title( $plural ) ),
		);

		$args = wp_parse_args( $custom_args, $defaults );

		register_taxonomy( sanitize_title( $plural ), $post_types, $args );

	}

	public function add_select_box() {
		//remove default
		remove_meta_box( $this->slug .'div', $this->cpt->slug, 'core' );
		// add custom
		add_meta_box( $this->slug .'_dropdown', $this->plural, array( &$this, 'select_box' ), $this->cpt->slug, 'side', 'high' );
	}

	public function select_box() {

		echo '<input type="hidden" name="taxonomy_noncename" id="taxonomy_noncename" value="' .
		wp_create_nonce( 'taxonomy_'. $this->slug ) . '" />';

		$checked = $editor_picks_checked = "";
		// Get all blog taxonomy terms
		$terms = get_terms( $this->slug, 'hide_empty=0');
		$names = wp_get_object_terms( get_the_ID(), $this->slug);

		$existing = array();
		if ( !is_wp_error( $names ) && !empty( $names ) ) {
			foreach ( $names as $name ) {
				$existing[] = $name->term_id;
			}
		}

		foreach ( $terms as $term ) {

			echo "<div style='margin-bottom: 5px;'><input style='margin-right: 5px;' type='radio' name='editorial_option[]' id='option-".  $term->slug ."' value='" . $term->slug . "'";
			if ( !empty( $existing ) && in_array( $term->term_id, $existing ) ) {
				echo " checked";
			}
			echo "/><label for='option-". $term->slug ."'>". $term->name ."</label></div>\n";
		}

	}

}

new Snippet_Tax_Setup;