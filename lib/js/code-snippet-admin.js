var snippet_admin_editor_js = ( function( window, document, $, undefined ){
	'use strict';
	
	var snippet_editor = {},
		editor = null;

	snippet_editor.cache = function(){
		snippet_editor.$content           = $( '.snippet-main-content' );
		snippet_editor.$ace_wrap          = $( '#snippet-content' );
		snippet_editor.$theme_selector    = $( '#ace_theme_settings' );
		snippet_editor.$setting_txt       = $( '#ace_label' );
		snippet_editor.$language_selector = $( '.snippetcpt-language-selector' );
	};

	snippet_editor.init = function(){
		snippet_editor.cache();
		if ( 0 == snippet_editor.$ace_wrap.length ){
			return false;
		}
		var current_lang = snippet_editor.get_current_language();

		editor = ace.edit('snippet-content');
		if ( ace_editor_globals.theme ){
			editor.setTheme( ace_editor_globals.theme );
		} else {
			editor.setTheme("ace/theme/chrome");
		}

		if( current_lang ){
			editor.getSession().setMode("ace/mode/" + current_lang );	
		} else {
			editor.getSession().setMode("ace/mode/text");
		}

	  	editor.setShowPrintMargin( false );
	  	editor.getSession().on( 'change', snippet_editor.update_textarea );
	  	snippet_editor.$theme_selector.on( 'change', snippet_editor.change_theme );
	  	snippet_editor.$language_selector.on( 'change', snippet_editor.update_language );
	};

	snippet_editor.get_current_language = function(){
		return snippet_editor.$language_selector.find(':selected').data('language');
	};

	snippet_editor.update_language = function(){
		var new_lang = snippet_editor.get_current_language();
		editor.getSession().setMode( "ace/mode/" + new_lang );
	};

	snippet_editor.update_textarea = function(){
		snippet_editor.$content.val( editor.getSession().getValue() );
	};

	snippet_editor.change_theme = function(){
		snippet_editor.$theme_selector.prop( 'disabled', true );
		snippet_editor.saving_msg();
		var new_theme = snippet_editor.$theme_selector.val(),
			post_data = {
				nonce: ace_editor_globals.nonce,
				theme: new_theme,
				action: 'snippetscpt-ace-ajax',
			};
		$.post( ajaxurl, post_data, snippet_editor.ajax_response, 'json' );
	};

	snippet_editor.ajax_response = function( resp ){
		if( resp.success ){
			ace_editor_globals.nonce = resp.data.nonce;
			editor.setTheme( resp.data.theme );
			snippet_editor.saving_msg( true );
			snippet_editor.$theme_selector.prop( 'disabled', false );
		} else {
			if ( resp.data.nonce ){
				ace_editor_globals.nonce = resp.data.nonce;
			}

			if( resp.data.message ){
				snippet_editor.saving_msg( true, resp.data.message );
			} else {
				snippet_editor.saving_msg( true );
			}
			snippet_editor.$theme_selector.prop( 'disabled', false );
			return false;
		}
	};

	snippet_editor.saving_msg = function( reset, message ){
		var output = null;
		if( reset ){
			if ( message ){
				output = message;
			} else {
				output = ace_editor_globals.labels.default;
			}
		} else {
			output = ace_editor_globals.labels.saving;
		}

		snippet_editor.$setting_txt.text( output );
	};

	$(document).ready( snippet_editor.init );

	return snippet_editor;

})( window, document, jQuery );