var snippetcpt_ace_viewer = ( function( window, document, $, undefined ){
	'use strict';

	var snippet_viewers = [];

	var new_viewer = function( ) {
		var snippet_viewer   = {},
			editor           = null,
			current_element  = this;

		snippet_viewer.cache = function(){
			snippet_viewer.$snippet = $( current_element );
			snippet_viewer.$toolbar = snippet_viewer.$snippet.siblings( '.snippetcpt_controls' );
			snippet_viewer.$wrapper = snippet_viewer.$snippet.parent();
		};

		snippet_viewer.init = function(){
			snippet_viewer.cache();

			var current_lang = snippet_viewer.get_current_language(),
				linenums = snippet_viewer.show_line_numbers();

			editor = ace.edit( current_element );
			if ( ace_editor_front_end_globals.theme ){
				editor.setTheme( ace_editor_front_end_globals.theme );
			} else {
				editor.setTheme("ace/theme/chrome");
			}

			if( current_lang ){
				editor.getSession().setMode("ace/mode/" + current_lang );
			} else {
				editor.getSession().setMode("ace/mode/text");
			}

			if ( ! linenums ){
				editor.renderer.setShowGutter( false );
			}

			snippet_viewer.$wrapper.mouseenter( snippet_viewer.show_toolbar );
			snippet_viewer.$wrapper.mouseleave( snippet_viewer.hide_toolbar );

		  	editor.setShowPrintMargin( false );
		  	editor.setReadOnly( true );
		};

		snippet_viewer.get_current_language = function(){
			var lang = snippet_viewer.$snippet.data( 'lang' );

			// @TODO: temporary fix, need to provide backwards compatability for some languages.
			if( lang == 'js' ){
				lang = 'javascript';
			}
			return lang ? lang : false;
		};

		snippet_viewer.show_line_numbers = function(){
			var linenums = snippet_viewer.$snippet.data( 'line_nums' );
			return linenums ? true : false;
			//return true
		}

		snippet_viewer.show_toolbar = function(){
			snippet_viewer.$toolbar.slideDown( 150 );
		};

		snippet_viewer.hide_toolbar = function(){
			snippet_viewer.$toolbar.slideUp( 150 );
		};

		snippet_viewers.push( snippet_viewer );
		snippet_viewer.init();
	};

	var create_viewers = function() {
		$('.snippetcpt-ace-viewer').each( new_viewer );
	};

	$(document).ready( create_viewers );

	return snippet_viewers;

})( window, document, jQuery );