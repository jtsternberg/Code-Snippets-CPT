var codeSnippetCPTVisual = false;
window.codeSnippetCPTButton = window.codeSnippetCPTButton || {};

( function( window, document, tinymce, btn, undefined ) {
    'use strict';

    tinymce.create( 'tinymce.plugins.Snippet_CPT_Button', {
        init : function( ed, url ) {
            ed.addButton( 'snippetcpt', {
                title : btn.l10n.button_title,
                cmd   : 'snippetcpt',
                image : btn.button_img
            } );

            ed.addCommand( 'snippetcpt', function() {
                codeSnippetCPTVisual = ed;
                codeSnippetCPTVisual.focus();
                codeSnippetCPTButton.open( true );
            } );
        },

        createControl : function() { return null; },

        getInfo : function() {
            return {
                longname  : btn.l10n.button_title,
                author    : 'Justin Sternberg',
                authorurl : 'http://dsgnwrks.pro',
                infourl   : 'http://dsgnwrks.pro',
                version   : btn.version
            };
        }
    });

    // Visual editor button
    tinymce.PluginManager.add( 'snippetcpt', tinymce.plugins.Snippet_CPT_Button );

} )( window, document, window.tinymce, window.codeSnippetCPTButton );

