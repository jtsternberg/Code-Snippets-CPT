<?php
/*
Plugin Name: Dsgnwrks Code Snippets CPT
Description: A code snippet custom post-type and shortcode for displaying your code snippets in your posts or pages.
Plugin URI: http://j.ustin.co/jAHRM3
Author: Jtsternberg
Author URI: http://about.me/jtsternberg
Donate link: http://j.ustin.co/rYL89n
Version: 1.0.1
*/

class CodeSnippitInit {

	protected $plugin_name = 'Code Snippets CPT';
	protected $cpt;
	protected $languages = array( 'Python', 'HTML', 'CSS', 'JavaScript', 'PHP', 'SQL', 'Perl', 'Ruby' );
	const VERSION = '1.0.1';

	function __construct() {

		define( 'DWSNIPPET_PATH', plugin_dir_path( __FILE__ ) );
		define( 'DWSNIPPET_URL', plugins_url('/', __FILE__ ) );

		// Custom Functions
		require_once( DWSNIPPET_PATH .'lib/functions.php' );

		// Snippet Post-Type Setup
		if ( !class_exists( 'CPT_Setup' ) )
			require_once( DWSNIPPET_PATH .'lib/CPT_Setup.php' );
		require_once( DWSNIPPET_PATH .'lib/Snippet_CPT_Setup.php' );
		$this->cpt = new Snippet_CPT_Setup();

		// Custom Taxonomy Setup
		require_once( DWSNIPPET_PATH .'lib/Snippet_Tax_Setup.php' );
		new Snippet_Tax_Setup( 'Snippet Category', 'Snippet Categories', array( $this->cpt->slug ) );
		new Snippet_Tax_Setup( 'Snippet Tag', '', array( $this->cpt->slug ), array( 'hierarchical' => false ) );
		$language = new Snippet_Tax_Setup( 'Language', '', array( $this->cpt->slug ),  array( 'public' => false, 'show_ui' => false ) );
		// Custom metabox for the programming languages taxonomy
		$language->init_select_box();

		// Include our wysiwyg button script
		require_once( DWSNIPPET_PATH .'lib/CodeSnippitButton.php' );
		new CodeSnippitButton( $this->cpt );

		// Snippet Shortcode Setup
		add_shortcode( 'snippet', array( $this, 'shortcode' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'add_languages' ) );

		add_action( 'wp_footer', array( $this, 'run_js' ) );
	}

	public function add_languages() {
		// make sure our default languages exist
		foreach ( $this->languages as $language ) {
			if ( !term_exists( $language, 'languages' ) )
				wp_insert_term( $language, 'languages' );
		}
	}

	public function shortcode( $atts, $context ) {

		$args = array(
			'post_type' => 'code-snippets',
			'showposts' => 1,
			'post_status' => 'published'
		);

		if ( isset( $atts['id'] ) && is_numeric( $atts['id'] ) ) {
			$args['p'] = $atts['id'];
		} elseif ( isset( $atts['slug'] ) && is_string( $atts['slug'] ) ) {
			$args['name'] = $atts['slug'];
		}

		$snippet = new WP_Query( $args );

		$content = get_post_field( 'post_content', $snippet->posts[0]->ID );

		if ( is_wp_error( $content ) || empty( $content ) )
			return;

		wp_enqueue_script( 'prettify' );
		wp_enqueue_style( 'prettify' );
		// wp_enqueue_script( 'syntax-highlighter-php', plugins_url('/lib/js/shBrushPhp.js', __FILE__ ), 'syntax-highlighter', '1.0', true );

		$linenums = isset( $atts['line_numbers'] ) && ( $atts['line_numbers'] === false || $atts['line_numbers'] === 'false' ) ? '' : ' linenums';

		return '<pre class="prettyprint'. $linenums .'">'. htmlentities( $content ) .'</pre>';
	}

	public function run_js() {
		?>
		<script type="text/javascript">
			window.onload = function(){ prettyPrint(); };
		</script>
		<?php
	}

}

new CodeSnippitInit;
