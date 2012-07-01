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
		require_once( DWSNIPPET_PATH .'lib/cpt-setup.php' );
		require_once( DWSNIPPET_PATH .'lib/tax-setup.php' );
		require_once( DWSNIPPET_PATH .'lib/cmb-setup.php' );

	}

	public function plugin_init() {

		if ( ! class_exists( 'cmb_Meta_Box' ) ) require_once( DWSNIPPET_PATH .'lib/cmb/init.php' );
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