window.snippetcpt = window.snippetcpt || {};

( function( window, document, $, cpt, undefined ) {
	'use strict';

	function $id( id ) {
		return $( document.getElementById( id ) );
	}

	cpt.editor = null;

	cpt.cache = function() {
		cpt.$content          = $( '.snippet-main-content' );
		cpt.$ace_wrap         = $id( 'snippet-content' );
		cpt.$themeSelector    = $id( 'ace-theme-settings' );
		cpt.$settingTxt       = $id( 'ace-theme-change-label' );
		cpt.$languageSelector = $( '.snippetcpt-language-selector' );
	};

	cpt.init = function() {
		cpt.cache();
		if ( ! cpt.$ace_wrap.length ) {
			return false;
		}
		var currentLang = cpt.getCurrentLanguage();

		cpt.editor = ace.edit('snippet-content');

		if ( cpt.theme ) {
			cpt.editor.setTheme( cpt.theme );
		} else {
			cpt.editor.setTheme( cpt.default_theme || 'ace/theme/chrome' );
		}

		if ( currentLang ) {
			cpt.editor.getSession().setMode( 'ace/mode/' + currentLang );
		} else {
			cpt.editor.getSession().setMode( cpt.default_lang || 'ace/mode/text' );
		}

	  	cpt.editor.setShowPrintMargin( false );
	  	cpt.editor.getSession().on( 'change', cpt.updateTextarea );
	  	cpt.$themeSelector.on( 'change', cpt.changeTheme );
	  	cpt.$languageSelector.on( 'change', cpt.updateLanguage );
	};

	cpt.getCurrentLanguage = function() {
		return cpt.$languageSelector.find(':selected').data('language');
	};

	cpt.updateLanguage = function() {
		var new_lang = cpt.getCurrentLanguage();
		cpt.editor.getSession().setMode( "ace/mode/" + new_lang );
	};

	cpt.updateTextarea = function() {
		cpt.$content.val( cpt.editor.getSession().getValue() );
	};

	cpt.changeTheme = function() {
		cpt.$themeSelector.prop( 'disabled', true );
		cpt.savingMsg();
		var new_theme = cpt.$themeSelector.val();
		var post_data = {
			nonce  : cpt.nonce,
			theme  : new_theme,
			action : 'snippetscpt-ace-ajax',
		};
		$.post( ajaxurl, post_data, cpt.ajaxResponse, 'json' );
	};

	cpt.ajaxResponse = function( resp ) {
		if ( resp.success ) {
			cpt.nonce = resp.data.nonce;
			cpt.editor.setTheme( resp.data.theme );
			cpt.savingMsg( true );
		} else {
			if ( resp.data.nonce ) {
				cpt.nonce = resp.data.nonce;
			}

			if ( resp.data.message ) {
				cpt.savingMsg( true, resp.data.message );
			} else {
				cpt.savingMsg( true );
			}
		}
		cpt.$themeSelector.prop( 'disabled', false );
	};

	cpt.savingMsg = function( reset, message ) {
		var output = null;
		if ( reset ) {
			if ( message ) {
				output = message;
			} else {
				output = cpt.labels.default;
			}
		} else {
			output = cpt.labels.saving;
		}

		cpt.$settingTxt.text( output );
	};

	$( cpt.init );

} )( window, document, jQuery, window.snippetcpt );
