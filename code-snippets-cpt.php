<?php
/*
Plugin Name:  Code Snippet CPT
Description: A code snippet custom post-type and shortcode for displaying your code snippets in your posts or pages.
Plugin URI: http://j.ustin.co/jAHRM3
Author: Jtsternberg
Author URI: http://about.me/jtsternberg
Donate link: http://j.ustin.co/rYL89n
Version: 1.0
*/

class CodeSnippitInit {

	const NAME = 'Code Snippet CPT';

	function __construct() {

		add_action( 'init', array( $this, 'plugin_init' ) );
		define( 'DWSNIPPET_PATH', plugin_dir_path( __FILE__ ) );
		define( 'DWSNIPPET_URL', plugins_url('/', __FILE__ ) );

		require_once( DWSNIPPET_PATH .'lib/functions.php' );
		require_once( DWSNIPPET_PATH .'lib/tax-setup.php' );
		require_once( DWSNIPPET_PATH .'lib/cpt-setup.php' );
		require_once( DWSNIPPET_PATH .'lib/cmb-setup.php' );

		add_shortcode( 'snippet', array( $this, 'shortcode' ) );
		add_action( 'wp_footer', array( $this, 'run_js' ) );

	}

	public function plugin_init() {

		if ( ! class_exists( 'cmb_Meta_Box' ) ) require_once( DWSNIPPET_PATH .'lib/cmb/init.php' );
	}

	public function shortcode( $atts, $context ) {

		$args = array(
			'post_type' => 'code-snippets',
			'showposts' => 1,
			'post_status' => 'published'
		);

		if ( isset( $atts['id'] ) && is_numeric( $atts['id'] ) ) {
			$args['p'] = $atts['id'];
		} elseif ( isset( $atts['slug'] ) && is_numeric( $atts['slug'] ) ) {
			$args['name'] = $atts['slug'];
		}

		$content = '';
		$snippet = new WP_Query( $args );


		if( $snippet->have_posts() ) : while( $snippet->have_posts() ) : $snippet->the_post();

			$content = get_the_content();

		endwhile; endif;
		wp_reset_query();

		if ( !empty( $content ) ) {
			wp_enqueue_script( 'syntax-highlighter', plugins_url('/lib/src/shCore.js', __FILE__ ), null, '1.0', true );
			wp_enqueue_script( 'syntax-highlighter-php', plugins_url('/lib/js/shBrushPhp.js', __FILE__ ), 'syntax-highlighter', '1.0', true );
			wp_enqueue_style( 'syntax-highlighter', plugins_url('/lib/css/shCore.css', __FILE__ ), null, '1.0', true );
			wp_enqueue_style( 'syntax-highlighter-dark', plugins_url('/lib/css/shThemeRDark.css', __FILE__ ), 'syntax-highlighter', '1.0', true );
		}

		return '<pre class="brush: php">'. $content .'</pre>';

	}

	public function run_js() {
		?>
		<script type="text/javascript">
		     SyntaxHighlighter.all()
		</script>
		<?php
	}

}

new CodeSnippitInit;


// add_action( 'all_admin_notices', 'testing_testing_testing' );
function testing_testing_testing() {
	echo '<div id="message" class="updated"><p>';

		$test = new Snippet_CPT_Setup;
		echo '<pre>'. htmlentities( print_r( $test->slug, true ) ) .'</pre>';

	echo '</p></div>';

}