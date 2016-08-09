window.snippetcpt = window.snippetcpt || {};

( function( window, document, $, cpt, undefined ) {
	'use strict';

	$.extend( cpt, window.snippetcptAce );

	cpt.viewers = [];
	cpt.aceInitComplete = false;
	cpt.aceAfterRenderComplete = false;
	var renderTimeout = null;

	cpt.newViewer = function( currentEl ) {

		var viewer = {
			toolbarDelay : 500,
			isCollapsed  : null,
			lineNums     : null,
			editor       : null
		};

		viewer.cache = function() {
			viewer.$snippet = $( currentEl );
			viewer.$wrap    = viewer.$snippet.parent();
			viewer.data     = viewer.$snippet.data( 'config' );
		};

		viewer.init = function() {
			viewer.cache();

			viewer.editor = ace.edit( currentEl );

			viewer.editor.setOption( 'maxLines', 'auto' === viewer.data.max_lines ? Infinity : viewer.data.max_lines );
			viewer.editor.setOption( 'minLines', 1 );

			if ( cpt.theme ) {
				viewer.editor.setTheme( cpt.theme );
			} else {
				viewer.editor.setTheme( cpt.default_theme || 'ace/theme/chrome' );
			}


			if ( viewer.data.lang ) {
				viewer.editor.getSession().setMode( 'ace/mode/' + viewer.data.lang );
			} else {
				viewer.editor.getSession().setMode( cpt.default_lang || 'ace/mode/text' );
			}

			if ( ! viewer.data.lineNums ) {
				viewer.editor.renderer.setShowGutter( false );
			}

			viewer.editor.setShowPrintMargin( false );
			viewer.editor.setReadOnly( true );

			viewer.editor.renderer.on( 'afterRender', viewer.triggerRender );

			viewer.$wrap
				.on( 'click', '.line-numbers', viewer.toggleLineNumbers )
				.on( 'click', '.collapse', viewer.toggleCollapse );

			viewer.$snippet.trigger( 'snippetcpt-ace-init' );
		};

		viewer.triggerRender = function( evt ) {
			if ( renderTimeout ) {
				window.clearTimeout( renderTimeout );
			}

			renderTimeout = setTimeout( function() {
				cpt.aceAfterRenderComplete = true;
				$( document.body ).trigger( 'snippetcpt-afterRender' );

				viewer.editor.renderer.off( 'resize', viewer.triggerRender );
			}, 500 );

		};

		viewer.toggleLineNumbers = function( evt ) {
			evt.preventDefault();

			if ( viewer.data.lineNums ) {
				viewer.editor.renderer.setShowGutter( false );
				viewer.data.lineNums = false;
				viewer.$wrap.find( '.snippet-buttons .line-numbers' ).removeClass( 'has-line-numbers' );
			} else {
				viewer.editor.renderer.setShowGutter( true );
				viewer.data.lineNums = true;
				viewer.$wrap.find( '.snippet-buttons .line-numbers' ).addClass( 'has-line-numbers' );
			}
		};

		viewer.toggleCollapse = function( evt ) {
			evt.preventDefault();

			if ( viewer.isCollapsed ) {
				viewer.isCollapsed = false;
				viewer.$snippet.slideDown();
				viewer.$snippet.parent().removeClass( 'snippetcpt-hidden' );
				$( this )
				.addClass( 'dashicons-hidden' )
				.removeClass( 'dashicons-visibility' );
			} else {
				viewer.isCollapsed = true;
				// viewer.$toolbar.slideDown( 150 );
				viewer.$snippet.slideUp();
				viewer.$snippet.parent().addClass( 'snippetcpt-hidden' );
				$( this )
				.addClass( 'dashicons-visibility' )
				.removeClass( 'dashicons-hidden' );
			}
		};

		cpt.viewers.push( viewer );
		viewer.init();
	};

	cpt.init = function() {
		$( '.snippetcpt-ace-viewer' ).each( function() {
			cpt.newViewer( this );
		} );

		cpt.aceInitComplete = true;
		$( document.body ).trigger( 'snippetcpt-ace-init-complete' );
	};

	$( cpt.init );

} )( window, document, jQuery, window.snippetcpt );
