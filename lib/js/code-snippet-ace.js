window.snippetcpt = window.snippetcpt || {};

( function( window, document, $, cpt, undefined ) {
	'use strict';

	cpt.viewers = [];

	var newViewer = function() {

		var snippetViewer = {
			toolbarDelay : 500,
			isCollapsed  : null,
			lineNums     : null,
			editor       : null
		};
		var currentEl = this;

		snippetViewer.cache = function() {
			snippetViewer.$snippet  = $( currentEl );
			snippetViewer.$wrapper  = snippetViewer.$snippet.parent();
			snippetViewer.$toolbar  = snippetViewer.$snippet.siblings( '.snippet-controls' );
			snippetViewer.$lineNums = snippetViewer.$toolbar.find( '.line-numbers' );
			snippetViewer.$collapse = snippetViewer.$toolbar.find( '.collapse' );
			snippetViewer.data      = snippetViewer.$snippet.data( 'config' );
		};

		snippetViewer.init = function() {
			snippetViewer.cache();

			snippetViewer.editor = ace.edit( currentEl );

			snippetViewer.editor.setOption( 'maxLines', 'auto' === snippetViewer.data.max_lines ? Infinity : snippetViewer.data.max_lines );
			snippetViewer.editor.setOption( 'minLines', 1 );

			if ( 5 > snippetViewer.editor.session.getLength() ) {
				snippetViewer.$snippet.parent().addClass( 'no-toolbar' );
				snippetViewer.$toolbar.remove();
			}

			if ( cpt.theme ) {
				snippetViewer.editor.setTheme( cpt.theme );
			} else {
				snippetViewer.editor.setTheme( cpt.default_theme || 'ace/theme/chrome' );
			}


			if ( snippetViewer.data.lang ) {
				snippetViewer.editor.getSession().setMode( 'ace/mode/' + snippetViewer.data.lang );
			} else {
				snippetViewer.editor.getSession().setMode( cpt.default_lang || 'ace/mode/text' );
			}

			if ( ! snippetViewer.data.lineNums ) {
				snippetViewer.editor.renderer.setShowGutter( false );
			}

		  	snippetViewer.editor.setShowPrintMargin( false );
		  	snippetViewer.editor.setReadOnly( true );

			snippetViewer.$lineNums.click( snippetViewer.toggleLineNumbers );
			snippetViewer.$collapse.click( snippetViewer.toggleCollapse );

		};

		snippetViewer.showToolbar = function() {

			if ( snippetViewer.isCollapsed ) {
				return;
			}

			window.clearTimeout( snippetViewer.timeout );
			snippetViewer.timeout = window.setTimeout( function() {
				snippetViewer.$toolbar.slideDown( 150 );
			}, snippetViewer.toolbarDelay );
		};

		snippetViewer.hideToolbar = function() {

			if ( snippetViewer.isCollapsed ) {
				return;
			}

			window.clearTimeout( snippetViewer.timeout );
			snippetViewer.timeout = window.setTimeout( function() {
				snippetViewer.$toolbar.slideUp( 150 );
			}, snippetViewer.toolbarDelay );
		};

		snippetViewer.toggleLineNumbers = function( evt ) {
			evt.preventDefault();

			if ( snippetViewer.data.lineNums ) {
				snippetViewer.editor.renderer.setShowGutter( false );
				snippetViewer.data.lineNums = false;
				snippetViewer.$lineNums.removeClass( 'has-line-numbers' );
			} else {
				snippetViewer.editor.renderer.setShowGutter( true );
				snippetViewer.data.lineNums = true;
				snippetViewer.$lineNums.addClass( 'has-line-numbers' );
			}
		};

		snippetViewer.toggleCollapse = function( evt ) {
			evt.preventDefault();

			if ( snippetViewer.isCollapsed ) {
				snippetViewer.isCollapsed = false;
				snippetViewer.$snippet.slideDown();
				snippetViewer.$snippet.parent().removeClass( 'snippetcpt-hidden' );
				snippetViewer.$collapse
					.addClass( 'dashicons-hidden' )
					.removeClass( 'dashicons-visibility' );
			} else {
				snippetViewer.isCollapsed = true;
				snippetViewer.$toolbar.slideDown( 150 );
				snippetViewer.$snippet.slideUp();
				snippetViewer.$snippet.parent().addClass( 'snippetcpt-hidden' );
				snippetViewer.$collapse
					.addClass( 'dashicons-visibility' )
					.removeClass( 'dashicons-hidden' );
			}
		};

		cpt.viewers.push( snippetViewer );
		snippetViewer.init();
	};

	cpt.init = function() {
		$( '.snippetcpt-ace-viewer' ).each( newViewer );
	};

	$( cpt.init );

} )( window, document, jQuery, window.snippetcpt );
