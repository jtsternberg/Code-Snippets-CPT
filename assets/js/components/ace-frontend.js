window.snippetcpt = window.snippetcpt || {};

/* eslint-disable max-params, no-shadow-restricted-names, no-undefined, no-unused-vars */
( function( window, document, $, ace, cpt, undefined ) {
	/* eslint-enable max-params, no-shadow-restricted-names, no-undefined, no-unused-vars */
	'use strict';

	$.extend( cpt, window.snippetcptAce );

	var renderTimeout = null;

	cpt.viewers = [];
	cpt.aceInitComplete = false;
	cpt.aceAfterRenderComplete = false;

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
			var editSession = viewer.editor.getSession();

			viewer.editor.setTheme( cpt.theme || 'ace/theme/chrome' );

			if ( viewer.data.lang ) {
				if ( 'php' === viewer.data.lang && 0 !== editSession.getValue().trim().indexOf( '<?php' ) ) {
					editSession.setMode( { path: 'ace/mode/php', inline: true } );
				} else {
					editSession.setMode( 'ace/mode/' + viewer.data.lang );
				}
			} else {
				editSession.setMode( cpt.language || 'ace/mode/text' );
			}

			if ( ! viewer.data.lineNums ) {
				viewer.editor.renderer.setShowGutter( false );
			}

			viewer.editor.setShowPrintMargin( false );
			viewer.editor.setReadOnly( true );

			viewer.editor.renderer.on( 'afterRender', viewer.triggerRender );

			viewer.$wrap.on( 'click', '.line-numbers', viewer.toggleLineNumbers );
			if ( 'auto' === viewer.data.max_lines ) {
				viewer.$wrap.removeClass( 'scrollable' );
			}

			if ( cpt.features.collapsible ) {
				viewer.$wrap.on( 'click', '.collapse', viewer.toggleCollapse );
			}

			viewer.$snippet.trigger( 'snippetcpt-ace-init' );
		};

		viewer.triggerRender = function() {
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

} )( window, document, jQuery, window.ace, window.snippetcpt );
