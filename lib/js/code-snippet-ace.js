var snippetcpt_ace_viewer = ( function( window, document, $, undefined ){
	'use strict';

	var snippet_viewers = [];

	var new_viewer = function( ) {

		var snippet_viewer   = {
				toolbar_delay : 500,
				is_collapsed  : null,
				line_nums 	  : null,
			},
			editor           = null,
			current_element  = this;

		snippet_viewer.cache = function(){
			snippet_viewer.$snippet   = $( current_element );
			snippet_viewer.$wrapper   = snippet_viewer.$snippet.parent();

			snippet_viewer.$toolbar   = snippet_viewer.$snippet.siblings( '.snippetcpt_controls' );
			snippet_viewer.$line_nums = snippet_viewer.$toolbar.find( '.line_numbers' );
			snippet_viewer.$collapse  = snippet_viewer.$toolbar.find( '.collapse' );
		};

		snippet_viewer.init = function(){
			snippet_viewer.cache();

			var current_lang = snippet_viewer.get_current_language(),
				linenums = snippet_viewer.show_line_numbers();

			editor = ace.edit( current_element );

			editor.setOption( "maxLines", 60 );
			editor.setOption( "minLines", 2 );

			if ( ace_editor_front_end_globals.theme ){
				editor.setTheme( ace_editor_front_end_globals.theme );
			} else if ( ace_editor_front_end_globals.default_theme ) {
				editor.setTheme( ace_editor_front_end_globals.default_theme );
			} else {
				editor.setTheme("ace/theme/chrome");
			}

			if( current_lang ){
				editor.getSession().setMode("ace/mode/" + current_lang );
			} else if( ace_editor_front_end_globals.default_lang ) {
				editor.getSession().setMode( ace_editor_front_end_globals.default_lang );
			} else {
				editor.getSession().setMode( "ace/mode/text" );
			}

			if ( ! linenums ){
				editor.renderer.setShowGutter( false );
			}

		  	editor.setShowPrintMargin( false );
		  	editor.setReadOnly( true );

			snippet_viewer.$wrapper.mouseenter( snippet_viewer.show_toolbar );
			snippet_viewer.$wrapper.mouseleave( snippet_viewer.hide_toolbar );
			snippet_viewer.$line_nums.click( snippet_viewer.toggle_line_numbers );
			snippet_viewer.$collapse.click( snippet_viewer.toggle_collapse );

		};

		snippet_viewer.get_current_language = function(){
			var lang = snippet_viewer.$snippet.data( 'lang' );
			return lang ? lang : false;
		};

		snippet_viewer.show_line_numbers = function(){
			var linenums = snippet_viewer.$snippet.data( 'line_nums' );
			if ( linenums ){
				snippet_viewer.line_nums = true;
				return true;
			}

			return false;
		}

		snippet_viewer.show_toolbar = function(){

			if ( snippet_viewer.is_collapsed ){
				return;
			}

			window.clearTimeout( snippet_viewer.timeout );
			snippet_viewer.timeout = window.setTimeout( function(){
				snippet_viewer.$toolbar.slideDown( 150 );	
			}, snippet_viewer.toolbar_delay );
		};

		snippet_viewer.hide_toolbar = function(){

			if ( snippet_viewer.is_collapsed ){
				return;
			}

			window.clearTimeout( snippet_viewer.timeout );
			snippet_viewer.timeout = window.setTimeout( function(){
				snippet_viewer.$toolbar.slideUp( 150 );	
			}, snippet_viewer.toolbar_delay );
		};

		snippet_viewer.toggle_line_numbers = function( evt ){
			evt.preventDefault();

			if( snippet_viewer.line_nums ){
				editor.renderer.setShowGutter( false );
				snippet_viewer.line_nums = null;
			} else {
				editor.renderer.setShowGutter( true );
				snippet_viewer.line_nums = true;
			}
		}

		snippet_viewer.toggle_collapse = function( evt ){
			evt.preventDefault();

			if( snippet_viewer.is_collapsed ){
				snippet_viewer.is_collapsed = null;
				snippet_viewer.$snippet.slideDown();
			} else {
				snippet_viewer.is_collapsed = true;
				snippet_viewer.$toolbar.slideDown( 150 );
				snippet_viewer.$snippet.slideUp();
			}
		}

		snippet_viewers.push( snippet_viewer );
		snippet_viewer.init();
	};

	var create_viewers = function() {
		$('.snippetcpt-ace-viewer').each( new_viewer );
	};

	$(document).ready( create_viewers );

	return snippet_viewers;

})( window, document, jQuery );