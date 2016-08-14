window.codeSnippetCPTVisual = false;
window.codeSnippetCPTButton = window.codeSnippetCPTButton || {};

/* eslint-disable max-params, no-shadow-restricted-names, no-undefined, no-unused-vars */
( function( window, document, tinymce, btn, visual, undefined ) {
	/* eslint-enable max-params, no-shadow-restricted-names, no-undefined, no-unused-vars */
	'use strict';

	tinymce.create( 'tinymce.plugins.Snippet_CPT_Button', {
		init : function( ed ) {
			ed.addButton( 'snippetcpt', {
				title : btn.l10n.button_title,
				cmd   : 'snippetcpt',
				image : btn.button_img
			} );

			ed.addCommand( 'snippetcpt', function() {
				visual = ed;
				visual.focus();
				btn.open( true );
			} );
		},

		createControl : function() {
			return null;
		},

		getInfo : function() {
			return {
				longname  : btn.l10n.button_title,
				author    : 'Justin Sternberg',
				authorurl : 'http://dsgnwrks.pro',
				infourl   : 'http://dsgnwrks.pro',
				version   : btn.version
			};
		}
	} );

	// Visual editor button
	tinymce.PluginManager.add( 'snippetcpt', tinymce.plugins.Snippet_CPT_Button );

} )( window, document, window.tinymce, window.codeSnippetCPTButton, window.codeSnippetCPTVisual );
