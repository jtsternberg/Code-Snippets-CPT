<?php

/*
Plugin Name: Code Snippets CPT
Description: A code snippet custom post-type and shortcode for displaying your code snippets in your posts or pages.
Plugin URI: http://j.ustin.co/jAHRM3
Author: Jtsternberg
Author URI: http://about.me/jtsternberg
Donate link: http://j.ustin.co/rYL89n
Version: 1.0.7
*/

class CodeSnippitInit {

	const VERSION = '1.0.7';

	public        $cpt;
	public static $single_instance = null;

	protected $languages = array(
		'abap'         => 'ABAP',
		'actionscript' => 'ActionScript',
		'ada'          => 'ADA',
		'apache_conf'  => 'Apache Conf',
		'asciidoc'     => 'AsciiDoc',
		'assembly_x86' => 'Assembly x86',
		'autohotkey'   => 'AutoHotKey',
		'batchfile'    => 'BatchFile',
		'c9search'     => 'C9Search',
		'c_cpp'        => 'C and C++',
		'cirru'        => 'Cirru',
		'clojure'      => 'Clojure',
		'cobol'        => 'Cobol',
		'coffee'       => 'CoffeeScript',
		'coldfusion'   => 'ColdFusion',
		'csharp'       => 'C#',
		'css'          => 'CSS',
		'curly'        => 'Curly',
		'd'            => 'D',
		'dart'         => 'Dart',
		'diff'         => 'Diff',
		'dockerfile'   => 'Dockerfile',
		'dot'          => 'Dot',
		'dummy'        => 'Dummy',
		'dummysyntax'  => 'DummySyntax',
		'eiffel'       => 'Eiffel',
		'ejs'          => 'EJS',
		'elixir'       => 'Elixir',
		'elm'          => 'Elm',
		'erlang'       => 'Erlang',
		'forth'        => 'Forth',
		'ftl'          => 'FreeMarker',
		'gcode'        => 'Gcode',
		'gherkin'      => 'Gherkin',
		'gitignore'    => 'Gitignore',
		'glsl'         => 'Glsl',
		'golang'       => 'Go',
		'groovy'       => 'Groovy',
		'haml'         => 'HAML',
		'handlebars'   => 'Handlebars',
		'haskell'      => 'Haskell',
		'haxe'         => 'haXe',
		'html'         => 'HTML',
		'html_ruby'    => 'HTML (Ruby)',
		'ini'          => 'INI',
		'io'           => 'Io',
		'jack'         => 'Jack',
		'jade'         => 'Jade',
		'java'         => 'Java',
		'javascript'   => 'JavaScript',
		'json'         => 'JSON',
		'jsoniq'       => 'JSONiq',
		'jsp'          => 'JSP',
		'jsx'          => 'JSX',
		'julia'        => 'Julia',
		'latex'        => 'LaTeX',
		'less'         => 'LESS',
		'liquid'       => 'Liquid',
		'lisp'         => 'Lisp',
		'livescript'   => 'LiveScript',
		'logiql'       => 'LogiQL',
		'lsl'          => 'LSL',
		'lua'          => 'Lua',
		'luapage'      => 'LuaPage',
		'lucene'       => 'Lucene',
		'makefile'     => 'Makefile',
		'markdown'     => 'Markdown',
		'mask'         => 'Mask',
		'matlab'       => 'MATLAB',
		'mel'          => 'MEL',
		'mushcode'     => 'MUSHCode',
		'mysql'        => 'MySQL',
		'nix'          => 'Nix',
		'objectivec'   => 'Objective-C',
		'ocaml'        => 'OCaml',
		'pascal'       => 'Pascal',
		'perl'         => 'Perl',
		'pgsql'        => 'pgSQL',
		'php'          => 'PHP',
		'powershell'   => 'Powershell',
		'praat'        => 'Praat',
		'prolog'       => 'Prolog',
		'properties'   => 'Properties',
		'protobuf'     => 'Protobuf',
		'python'       => 'Python',
		'r'            => 'R',
		'rdoc'         => 'RDoc',
		'rhtml'        => 'RHTML',
		'ruby'         => 'Ruby',
		'rust'         => 'Rust',
		'sass'         => 'SASS',
		'scad'         => 'SCAD',
		'scala'        => 'Scala',
		'scheme'       => 'Scheme',
		'scss'         => 'SCSS',
		'sh'           => 'SH',
		'sjs'          => 'SJS',
		'smarty'       => 'Smarty',
		'snippets'     => 'snippets',
		'soy_template' => 'Soy Template',
		'space'        => 'Space',
		'sql'          => 'SQL',
		'stylus'       => 'Stylus',
		'svg'          => 'SVG',
		'tcl'          => 'Tcl',
		'tex'          => 'Tex',
		'text'         => 'Text',
		'textile'      => 'Textile',
		'toml'         => 'Toml',
		'twig'         => 'Twig',
		'typescript'   => 'Typescript',
		'vala'         => 'Vala',
		'vbscript'     => 'VBScript',
		'velocity'     => 'Velocity',
		'verilog'      => 'Verilog',
		'vhdl'         => 'VHDL',
		'xml'          => 'XML',
		'xquery'       => 'XQuery',
		'yaml'         => 'YAML',
	);

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return CodeSnippitInit A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	private function __construct() {

		define( 'DWSNIPPET_PATH', plugin_dir_path( __FILE__ ) );
		define( 'DWSNIPPET_URL', plugins_url( '/', __FILE__ ) );

		register_activation_hook( __FILE__, array( $this, '_activate' ) );

		// Custom Functions
		require_once( DWSNIPPET_PATH . 'lib/functions.php' );

		// Snippet Post-Type Setup
		require_once( DWSNIPPET_PATH . 'lib/Snippet_CPT_Setup.php' );
		$this->cpt = new Snippet_CPT_Setup();

		// Custom Taxonomy Setup
		require_once( DWSNIPPET_PATH . 'lib/Snippet_Tax_Setup.php' );
		new Snippet_Tax_Setup( 'Snippet Category', 'Snippet Categories', array( $this->cpt->post_type ) );
		new Snippet_Tax_Setup( 'Snippet Tag', '', array( $this->cpt->post_type ), array( 'hierarchical' => false ) );
		$this->language = new Snippet_Tax_Setup( 'Language', '', array( $this->cpt->post_type ), array( 'public' => false, 'show_ui' => false ) );
		// Custom metabox for the programming languages taxonomy
		$this->language->init_select_box();

		// Include our wysiwyg button script
		require_once( DWSNIPPET_PATH . 'lib/CodeSnippitButton.php' );
		new CodeSnippitButton( $this->cpt, $this->language );

		// Snippet Shortcode Setup
		add_shortcode( 'snippet', array( $this, 'shortcode' ) );

		// Set default programming language taxonomy terms
		add_action( 'admin_init', array( $this, 'add_languages' ) );
		add_filter( 'content_save_pre', array( $this, 'allow_unfiltered' ), 5 );


	}

	/**
	 * Flush rewrite rules when the plugin activates
	 */
	function _activate() {
		flush_rewrite_rules();
	}


	public function add_languages() {
		$this->version_check();
		// make sure our default languages exist
		foreach ( $this->languages as $key => $language ) {
			$exists = is_numeric( $key )
				? get_term_by( 'name', $language, 'languages' )
				: get_term_by( 'slug', $key, 'languages' );
			if ( empty( $exists ) ) {
				$args = ! is_numeric( $key ) ? array( 'slug' => $key ) : array();
				wp_insert_term( $language, 'languages', $args );
			}
		}
	}

	public function version_check() {
		$current_version = get_option( 'dsgnwrks_snippetcpt_version' );
		if ( self::VERSION !== $current_version ) {
			$this->update();
		}
	}

	function update() {
		$terms = get_terms( 'languages', array(
			'fields'     => 'ids',
			'hide_empty' => false,
		) );

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, 'languages' );
			}
		}

		update_option( 'dsgnwrks_snippetcpt_version', self::VERSION );
	}

	public function allow_unfiltered( $value ) {
		global $post;

		if ( isset( $post->post_type ) && $this->cpt->post_type == $post->post_type && current_user_can( 'edit_posts' ) ) {
			kses_remove_filters();
		}

		return $value;
	}

	public function shortcode( $atts ) {

		$atts = shortcode_atts( array(
			'id'           => false,
			'slug'         => '',
			'line_numbers' => true,
			'lang'         => '',
			'title_attr'   => true,
		), $atts, 'snippet' );

		$args = array(
			'post_type'   => 'code-snippets',
			'showposts'   => 1,
			'post_status' => 'published',
		);

		if ( $atts['id'] && is_numeric( $atts['id'] ) ) {
			$args['p'] = $atts['id'];
		} elseif ( $atts['slug'] && is_string( $atts['slug'] ) ) {
			$args['name'] = $atts['slug'];
		}

		$snippet = get_posts( $args );
		if ( is_wp_error( $snippet ) || empty( $snippet ) ) {
			return '';
		}

		$snippet    = $snippet[0];
		$snippet_id = $snippet->ID;

		if ( empty( $snippet->post_content ) ) {
			return '';
		}

		$line_nums = ! $atts['line_numbers'] || false === $atts['line_numbers'] || 'false' === $atts['line_numbers'] ? false : $atts['line_numbers'];
		$snippet_content = apply_filters( 'dsgnwrks_snippet_content', htmlentities( $snippet->post_content, ENT_COMPAT, 'UTF-8' ), $atts, $snippet );

		if ( $atts['title_attr'] && ! in_array( $atts['title_attr'], array( 'no', 'false' ), true ) ) {
			$title_attr = sprintf( ' title="%s"', esc_attr( $snippet->post_title ) );
		} else {
			$title_attr = '';
		}

		if ( apply_filters( 'snippets-cpt-ace-frontend', false ) ) {
			$output = $this->get_ace_output( $title_attr, $snippet_content, $snippet_id, $line_nums );
		} else {
			$output = $this->get_old_output( $title_attr, $snippet_content, $snippet_id, $line_nums );
		}

		return apply_filters( 'dsgnwrks_snippet_display', $output, $atts, $snippet );
	}

	/**
	 * Gets the Legacy output as to not break the old code/display
	 *
	 * @param $title_attr
	 * @param $snippet_content
	 * @param $snippet_id
	 * @param $line_nums
	 *
	 * @return string
	 */
	public function get_old_output( $title_attr, $snippet_content, $snippet_id, $line_nums ) {
		$this->cpt->enqueue_prettify();
		$class = 'prettyprint';
		if ( $line_nums ) {
			$class .= ' linenums';
			if ( is_numeric( $line_nums ) && 0 !== absint( $line_nums ) ) {
				$class .= ':' . absint( $line_nums );
			}
		}

		if ( ! empty( $atts['lang'] ) ) {
			$class .= ' lang-'. sanitize_html_class( $atts['lang'] );
		} elseif ( $lang_slug = $this->language->language_slug_from_post( $snippet_id ) ) {
			$class .= ' lang-'. $lang_slug;
		}

		return sprintf( '<pre class="%1$s"%2$s>%3$s</pre>', $class, $title_attr, $snippet_content );
	}

	/**
	 * Gets the output for the ACE front-end display
	 *
	 * @param $title_attr
	 * @param $snippet_content
	 * @param $snippet_id
	 * @param $line_nums
	 *
	 * @return string
	 */
	public function get_ace_output( $title_attr, $snippet_content, $snippet_id, $line_nums ) {
		// Let's use data sets instead?
		// This is just personal preference, and that I like to access the .data method in JS instead
		// of jumping through all classes.
		$data_sets = array();
		if ( $line_nums ) {
			$data_sets['line_nums'] = is_numeric( $line_nums ) && 0 !== absint( $line_nums ) ? absint( $line_nums ) : true;
		}

		$data_sets['lang'] = apply_filters( 'snippetcpt_default_ace_lang', 'text' );
		if ( ! empty( $atts['lang'] ) ) {
			// Need this for backwards compatibility
			$maybe_old_language = sanitize_html_class( $atts['lang'] );
			$data_sets['lang']  = $this->language->get_ace_slug( $maybe_old_language );
		} elseif ( $lang_slug = $this->language->language_slug_from_post( $snippet_id ) ) {
			// Get the language linked to the current post id
			$data_sets['lang'] = $lang_slug;
		}

		// Set the snippet ID, for use in the controller
		$data_sets['snippet-id'] = $snippet_id;

		$data = '';
		if ( ! empty( $data_sets ) ) {
			foreach ( $data_sets as $data_key => $value ) {
				$data .= " data-{$data_key}='{$value}'";
			}
		}

		return sprintf( '<pre class="%1$s" %2$s %3$s>%4$s</pre>', 'snippetcpt-ace-viewer', $title_attr, $data, $snippet_content );
	}

	/**
	 * Magic getter for our object.
	 *
	 * @param string $field
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 *
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'languages':
				return $this->{$field};
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}
}

CodeSnippitInit::get_instance();

function dsgnwrks_snippet_content_replace_tabs( $snippet_content ) {
	// Replace tabs w/ spaces as it is more readable
	$snippet_content = str_replace( '\t', '    ', $snippet_content );

	return $snippet_content;
}

add_filter( 'dsgnwrks_snippet_content', 'dsgnwrks_snippet_content_replace_tabs' );
