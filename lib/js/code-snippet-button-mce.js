var codeSnippetCPTVisual = false;

(function() {
    if ( ! window.codeSnippetCPT )
        return;
    tinymce.create('tinymce.plugins.Snippet_CPT_Button', {
        init : function(ed, url) {
            ed.addButton('snippetcpt', {
                title : window.codeSnippetCPT.button_title,
                cmd : 'snippetcpt',
                image : window.codeSnippetCPT.button_img
            });

            ed.addCommand( 'snippetcpt', function() {
                codeSnippetCPTVisual = ed;
                codeSnippetCPTVisual.focus();
                codeSnippetCPTButton.open(true);
            });
        },

        createControl : function(n, cm) {
            return null;
        },

        getInfo : function() {
            return {
                longname : window.codeSnippetCPT.button_title,
                author : 'Justin Sternberg',
                authorurl : 'http://dsgnwrks.pro',
                infourl : 'http://dsgnwrks.pro',
                version : '1.0.1'
            };
        }
    });

    // Visual editor button
    tinymce.PluginManager.add( 'snippetcpt', tinymce.plugins.Snippet_CPT_Button );

})();
