<?php
/**
 * Plugin class for Code Snipet CPT and it's associated taxonomies.
 *
 */
class Snippet_Tax_Setup {

	public $taxonomy = 'Language';

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
		add_action( 'init', array( &$this, 'tax_loop' ) );
	}

	function tax_loop( $singular = '', $plural = '', $post_types = array(), $custom_args = array() ) {

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

}

new Snippet_Tax_Setup;