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
	cpt.isPHP = null;
	cpt.hasPHPtag = null;

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

		cpt.setLanguage( currentLang || cpt.language );

		cpt.editor.setShowPrintMargin( false );
		cpt.editor.getSession().on( 'change', cpt.updateTextarea );
		cpt.$themeSelector.on( 'change', cpt.changeTheme );
		cpt.$languageSelector.on( 'change', cpt.updateLanguage );

		$( document.body ).trigger( 'code-snippet-ace-init', cpt );
	};

	cpt.setLanguage = function( language ) {
		var editSession = cpt.editor.getSession();

		cpt.isPHP = 'php' === language;
		cpt.hasPHPtag = null === cpt.hasPHPtag ? 0 === editSession.getValue().trim().indexOf( '<?php' ) : cpt.hasPHPtag;

		if ( cpt.isPHP && ! cpt.hasPHPtag ) {
			editSession.setMode( { path: 'ace/mode/php', inline: true } );
		} else {
			editSession.setMode( 'ace/mode/' + language );
		}
	};

	cpt.getCurrentLanguage = function() {
		return cpt.$languageSelector.find( ':selected' ).data( 'language' );
	};

	cpt.updateLanguage = function() {
		cpt.setLanguage( cpt.getCurrentLanguage() );
	};

	cpt.updateTextarea = function() {
		var txt = cpt.editor.getSession().getValue();

		if ( cpt.isPHP ) {
			if ( ( 0 === txt.trim().indexOf( '<?php' ) ) !== cpt.hasPHPtag ) {
				cpt.hasPHPtag = ! cpt.hasPHPtag;
				cpt.updateLanguage();
			}
		}

		cpt.$content.val( txt );
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
