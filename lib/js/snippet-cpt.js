window.snippetcpt = window.snippetcpt || {};

( function( window, document, $, cpt, undefined ) {
	'use strict';

	cpt.$ = cpt.$ || {};

	var $c = cpt.$;
 	var ESCAPE = 27;
 	var btnTemplate = '<span class="snippet-button dashicons {{ data.class }}" title="{{ data.title }}"></span>';
 	var linkTemplate = '<a href="{{ data.link }}" class="snippet-button dashicons {{ data.class }}"  title="{{ data.title }}"></a>';
	var icons = {
		copy : {
			'class'    : 'dashicons-editor-code',
			'title'    : cpt.l10n.copy,
		},
		fullscreen : {
			'class'    : 'dashicons-editor-expand',
			'title'    : cpt.l10n.fullscreen,
		},
		close : {
			'class'    : 'dashicons-no',
			'title'    : cpt.l10n.close,
		},
		edit : {
			'class'    : 'dashicons-edit',
			'title'    : cpt.l10n.edit,
		},
		collapse : {
			'class'    : 'dashicons-hidden collapse',
			'title'    : cpt.l10n.collapse,
		},
		numbers : {
			'class'    : 'dashicons-editor-ol line-numbers',
			'title'    : cpt.l10n.numbers,
		},
	};
	var iconSet = ['edit'];

	cpt.init = function() {
		$c.wrap = $( '.snippetcpt-wrap' );
		$c.body = $( document.body );

		$( document ).on( 'prettify-loaded', cpt.prettifyLoaded );

		if ( cpt.features.do_click_to_copy ) {
			cpt.clickToCopyInit();
		}

		if ( cpt.features.enable_full_screen_view ) {
			cpt.fullScreenInit();
		}

		if ( cpt.features.enable_ace ) {
			iconSet.push( 'collapse' );
			iconSet.push( 'numbers' );
		}

		cpt.addIcons( iconSet );

		if ( cpt.features.enable_full_screen_view && cpt.fullscreen ) {
			$c.body.one( 'snippetcpt-afterRender', function() {
				$c.body.find( '.snippet-button.dashicons-editor-expand' ).first().trigger( 'click' );
			} );
		}

	};

	cpt.prettifyLoaded = function() {
		$c.wrap.each( function() {
			var $this = $( this );
			var rows  = $this.find( '.linenums li' ).length;
			if ( rows > 1000 ) {
				$this.addClass( 'gt1000' );
			}
			if ( rows > 100 ) {
				$this.addClass( 'gt100' );
			}
			if ( rows > 10 ) {
				$this.addClass( 'gt10' );
			}
		} );
	};

	cpt.addIcons = function( icons ) {
		var added = false;
		$c.wrap.each( function() {
			var $this = $( this );
			var html = '';

			for ( var i = 0; i < icons.length; i++ ) {
				if ( 'fullscreen' === icons[i] && $this.parent( '.snippetcpt-footer' ).length ) {
					icons[i] = 'close';
				}

				html += cpt.getIcon( icons[i], $this.data( icons[i] ) );
			}

			if ( html ) {
				added = true;
				if ( ! $this.find( '.snippet-buttons' ).length ) {
					$this.append( '<div class="snippet-buttons"></div>' );
				}
			}

			$( this ).find( '.snippet-buttons' ).append( html );
		} );

		return added;
	};

	cpt.getIcon = function( icon, link ) {
		var html = '';

		switch ( icon ) {
			case 'close':
			case 'collapse':
			case 'numbers':
				html = cpt.template( icons[ icon ], btnTemplate );
				break;
			default:
				if ( link ) {
					html = cpt.template( $.extend( icons[ icon ], { link: link } ), linkTemplate );
				}
				break;
		}

		return html;
	};

	cpt.template = function( data, template ) {
		$.each( data, function( key, value ) {
			template = template.replace( new RegExp( '{{ data.'+ key +' }}', 'gi' ), value );
		} );
		return template;
	};


	/*
	 * Feature: Click to copy
	 */

	cpt.clickToCopyInit = function() {
		iconSet.push( 'copy' );

		$c.body.on( 'click', '.snippet-button.dashicons-editor-code', cpt.openSnippetCopy );
	};

	cpt.openSnippetCopy = function( evt ) {
		// Pop open a window (else fall through to opening link in same window)
		if ( cpt.windowPop( $( this ).attr( 'href' ) ) ) {
			evt.preventDefault();
		}
	};

	cpt.windowPop = function( url, w, h ) {
		w = w || 925;
		h = h || 950;
		var left = (window.innerWidth/2)-(w/2); var top = (window.innerHeight/2)-(h/2);
		return window.open( url, cpt.l10n.copy_code, 'toolbar=no,resizable=yes,width='+w+',height='+h+',top='+top+',left='+left );
	};

	/*
	 * Feature: Expand snippet view to full screen
	 */

	cpt.fullScreenInit = function() {
		iconSet.push( 'fullscreen' );

		$c.footer = $( '.snippetcpt-footer' );
		if ( ! $c.footer.length ) {
			$c.footer = $( '<div class="snippetcpt-footer snippet-hidden"></div>' ).appendTo( $c.body );
		}

		$c.body
			.on( 'click', '.snippet-button.dashicons-no', cpt.closeSnippet )
			.on( 'click', '.snippet-button.dashicons-editor-expand', cpt.openSnippet );

		$( document ).on( 'keyup', function ( evt ) {
			if ( ESCAPE === evt.keyCode ) {
				cpt.closeSnippet();
			}
		} );

		if ( cpt.isSnippet ) {
			cpt.url = window.location.pathname;
			cpt.isFull = false;

			window.onpopstate = function( evt ) {
				var goTo = evt.state && evt.state.was ? evt.state.was : ( cpt.fullscreen ? 'closed' : 'open' );

				cpt.isSnippet = false;
				if ( 'closed' === goTo ) {
					$c.body.find( '.snippet-button.dashicons-editor-expand' ).first().trigger( 'click' );
				} else {
					$c.body.find( '.snippet-button.dashicons-no' ).first().trigger( 'click' );
				}
				cpt.isSnippet = true;
			};
		}
	};

	cpt.closeSnippet = function() {
		cpt.isFull = false;

		if ( cpt.isSnippet ) {
			window.history.pushState( { was : 'open' }, '', cpt.url );
		}

		$c.body.removeClass( 'snippet-full-screen' );
		$c.footer.html('').addClass( 'snippet-hidden' );
	};

	cpt.openSnippet = function( evt ) {
		evt.preventDefault();
		cpt.isFull = true;

		if ( cpt.isSnippet ) {
			window.history.pushState( { was : 'closed' }, '', '?full-screen' );
		}

		var $snippet = $(this).parents( '.snippetcpt-wrap' ).clone();
		$c.body.addClass( 'snippet-full-screen' );
		$snippet.find( '.dashicons-editor-expand' ).replaceWith( cpt.template( icons.close, btnTemplate ) );
		$snippet.find( 'pre' ).show();
		$c.footer.html( $snippet ).removeClass( 'snippet-hidden' );
		$( document.body ).trigger( 'snippet-full-screen' );
	};

	$( cpt.init );

} )( window, document, jQuery, window.snippetcpt );
