window.snippetcpt = window.snippetcpt || {};

/* eslint-disable max-params, no-shadow-restricted-names, no-undefined, no-unused-vars */
( function( window, document, $, cpt, undefined ) {
	/* eslint-enable max-params, no-shadow-restricted-names, no-undefined, no-unused-vars */
	'use strict';

	$.extend( cpt, window.snippetcptAce );

	var $id = function( id ) {
		return $( document.getElementById( id ) );
	};

	cpt.editor = null;

	cpt.cache = function() {
		cpt.$content          = $( '.snippet-main-content' );
		cpt.$wrap             = $id( 'snippet-content' );
		cpt.$themeSelector    = $id( 'ace-theme-settings' );
		cpt.$settingTxt       = $id( 'ace-theme-change-label' );
		cpt.$languageSelector = $( '.snippetcpt-language-selector' );
	};

	cpt.init = function() {
		cpt.cache();

		var currentLang = cpt.getCurrentLanguage();

		cpt.$wrap.removeClass( 'hidden' );
		cpt.$content.addClass( 'hidden' );

		cpt.editor = window.ace.edit( 'snippet-content' );

		if ( cpt.theme ) {
			cpt.editor.setTheme( cpt.theme );
		} else {
			cpt.editor.setTheme( cpt.default_theme || 'ace/theme/chrome' );
		}

		if ( currentLang ) {
			cpt.editor.getSession().setMode( 'ace/mode/' + currentLang );
		} else {
			cpt.editor.getSession().setMode( cpt.language || 'ace/mode/text' );
		}

		cpt.editor.setShowPrintMargin( false );
		cpt.editor.getSession().on( 'change', cpt.updateTextarea );
		cpt.$themeSelector.on( 'change', cpt.changeTheme );
		cpt.$languageSelector.on( 'change', cpt.updateLanguage );
	};

	cpt.getCurrentLanguage = function() {
		return cpt.$languageSelector.find( ':selected' ).data( 'language' );
	};

	cpt.updateLanguage = function() {
		cpt.editor.getSession().setMode( 'ace/mode/' + cpt.getCurrentLanguage() );
	};

	cpt.updateTextarea = function() {
		cpt.$content.val( cpt.editor.getSession().getValue() );
	};

	cpt.changeTheme = function() {
		cpt.$themeSelector.prop( 'disabled', true );
		cpt.savingMsg();

		var data = {
			nonce  : cpt.nonce,
			theme  : cpt.$themeSelector.val(),
			action : 'snippetscpt-ace-ajax'
		};

		$.post( window.ajaxurl, data, cpt.ajaxResponse, 'json' );
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
