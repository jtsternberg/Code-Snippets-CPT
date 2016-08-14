window.wp = window.wp || {};
window.codeSnippetCPTButton = window.codeSnippetCPTButton || {};

/* eslint-disable max-params, no-shadow-restricted-names, no-undefined, no-unused-vars */
( function( window, document, $, wp, QTags, btn, undefined ) {
	/* eslint-enable max-params, no-shadow-restricted-names, no-undefined, no-unused-vars */
	'use strict';

	btn.$        = {};
	btn.isVisual = false;
	btn.postID   = 0;

	var cached    = false;
	var addingNew = false;
	var ENTER_KEY = 13;

	var $id = function( id ) {
		return $( document.getElementById( id ) );
	};

	btn.cache = function() {
		if ( cached ) {
			return;
		}

		btn.$.htmlBody = $( 'html, body' );
		btn.$.formWrap = $id( 'snippet-cpt-form' );
		btn.$.select   = $id( 'snippet-cpt-posts' );
		btn.$.check    = $id( 'snippet-cpt-line-nums' ).add( $id( 'snippet-cpt-line-nums-2' ) );
		btn.$.none     = $id( 'no-snippets-exist' );
		btn.postID     = $id( 'post_ID' ).val() || 0;

		// New UI
		btn.$.errorBlock        = $( '.snippet-cpt-errors p' );
		btn.$.overlay           = $( '.snippet-overlay' );
		btn.$.form              = $id( 'cpt-snippet-form' );
		btn.$.selectSnippet     = btn.$.form.find( 'fieldset.select-a-snippet' );
		btn.$.addSnippetSection = btn.$.form.find( 'fieldset.add-new-snippet' );
		btn.$.addNew            = btn.$.form.find( '.add-new-snippet-btn' );
		btn.$.cancelNew         = btn.$.form.find( '.cancel-new-snippet-btn' );
		btn.$.snippetTitle      = btn.$.form.find( '#new-snippet-title' );
		btn.$.snippetID         = btn.$.form.find( '#edit-snippet-id' );
		btn.$.snippetContent    = btn.$.form.find( '#new-snippet-content' );

		cached = true;
	};

	btn.create = function() {
		var $this = $( this );

		// focus first button and bind enter to it
		$this.parent().find( '.ui-dialog-buttonpane button:last-child' ).focus();
		$this.keypress( function( evt ) {
			var isEditable = evt.target.tagName in { INPUT: 1, TEXTAREA: 1 };

			if ( ! isEditable && ENTER_KEY === evt.keyCode ) {
				evt.preventDefault();
				$this.parent().find( '.ui-dialog-buttonpane button:last-child' ).click();
			}
		} );
	};

	btn.close = function() {
		btn.$.select.prop( 'selectedIndex', 0 );
		btn.resetErrorBlock();

		// New UI
		if ( addingNew ) {
			btn.$.addNew.prop( 'disabled', false );
			btn.$.addSnippetSection.hide();
			btn.$.selectSnippet.show();
			btn.$.errorBlock.hide();
			btn.$.overlay.hide();
			addingNew = false;
		}

		// Reset the form now
		btn.$.form.trigger( 'reset' );
		btn.mainBtn().text( btn.l10n.btn_insert );
	};

	btn.resetErrorBlock = function() {
		btn.$.errorBlock.text( '' ).hide();
	};

	btn.cancel = function() {
		btn.$.formWrap.dialog( 'close' );
	};

	btn.insert = function() {

		// Reset it just in case.
		btn.resetErrorBlock();

		if ( ! addingNew ) {
			return btn.insertShortcode( btn.buildShortcode(
				btn.$.select.val(),
				btn.$.check.is( ':checked' ),
				btn.$.select.find( ':selected' ).data( 'lang' )
			) );
		}

		if ( ! btn.$.snippetTitle.val() || ! btn.$.snippetContent.val() ) {
			return btn.displayErrorMessage( btn.l10n.missing_required );
		}

		var ajaxData = {
			action : 'snippetcpt_insert_snippet',
			nonce  : btn.snippet_nonce,
			data   : btn.$.form.serialize()
		};

		btn.$.overlay.show();

		$.post( window.ajaxurl, ajaxData, function( response ) {
			btn.$.overlay.hide();
			if ( response.success && response.data ) {
				var r = response.data;

				btn.insertShortcode( btn.buildShortcode( r.post_name, r.line_numbers, r.lang ) );

			} else {
				btn.displayErrorMessage( response.data || '' );
			}

		}, 'json' ).fail( function() {
			btn.displayErrorMessage();
		} );

		return true;
	};

	btn.displayErrorMessage = function( message ) {
		if ( ! message ) {
			// Default show general message if no message is set.
			message = btn.l10n.general_error;
		}

		btn.$.errorBlock.slideUp( 100, function() {
			btn.$.errorBlock.text( message ).slideDown( 100 );
		} );

		// Scroll back to error.
		btn.$.htmlBody.animate( {
			scrollTop: btn.$.errorBlock.offset().top - 50
		} );
	};

	btn.buildShortcode = function( postSlug, lineNumbers, lang ) {
		if ( ! postSlug ) {
			return '';
		}

		var shortcode = '[snippet slug=' + postSlug;

		if ( ! lineNumbers ) {
			shortcode += ' line_numbers=false';
		}

		if ( lang ) {
			shortcode += ' lang=' + lang;
		}

		shortcode += ']';

		return shortcode;
	};

	btn.insertShortcode = function( shortcode ) {
		if ( 0 < btn.$.none.length && ! addingNew ) {
			btn.displayErrorMessage( btn.l10n.no_snippets );
			btn.$.overlay.hide();
			return false;
		}

		if ( shortcode ) {

			if ( 'function' === typeof( btn.isVisual ) ) {
				btn.isVisual( shortcode, true );
			} else if ( btn.isVisual && window.codeSnippetCPTVisual ) {
				window.codeSnippetCPTVisual.execCommand( 'mceInsertContent', 0, shortcode );
			} else {
				QTags.insertContent( shortcode );
			}

			btn.$.formWrap.dialog( 'close' );

			btn.isVisual = false;

		} else {
			btn.$.overlay.hide();
			if ( addingNew ) {
				btn.displayErrorMessage( btn.l10n.missing_required );
			} else {
				btn.displayErrorMessage( btn.l10n.select_snippet );
			}
			return false;
		}

		return true;
	};

	btn.showAddNew = function( evt ) {
		if ( evt && evt.preventDefault ) {
			evt.preventDefault();
		}

		btn.resetErrorBlock();

		btn.$.addNew.prop( 'disabled', true );
		btn.$.selectSnippet.slideUp( 100, function() {
			btn.$.addSnippetSection.slideDown( 200, function() {
				btn.$.cancelNew.fadeIn();
				addingNew = true;
			} );
		} );
	};

	btn.cancelAddNew = function( evt ) {
		evt.preventDefault();
		btn.resetErrorBlock();

		btn.$.addNew.prop( 'disabled', false );
		btn.$.selectSnippet.slideDown( 200, function() {
			btn.$.addSnippetSection.slideUp( 100, function() {
				btn.$.cancelNew.fadeOut();
				addingNew = false;
			} );
		} );
	};

	btn.open = function( isVisual ) {
		btn.cache();

		btn.isVisual = isVisual;

		btn.$.formWrap.dialog( 'open' );
	};

	btn.mainBtn = function() {
		btn.$.btn = btn.$.btn || btn.$.formWrap.next().find( '.ui-button-text:contains(' + btn.l10n.btn_insert + ')' ).parent().addClass( 'button-primary' );

		return btn.$.btn;
	};

	btn.mceView = {
		action: 'snippet_parse_shortcode',
		state: [],

		initialize: function() {
			var that = this;
			var slug = '';

			if ( that.url ) {
				that.loader    = false;
				that.shortcode = wp.media.embed.shortcode( {
					url: that.text
				} );
			}

			if ( that.shortcode.attrs && that.shortcode.attrs.named && that.shortcode.attrs.named.slug ) {
				slug = that.shortcode.attrs.named.slug;
			}
			wp.ajax.post( that.action, {
				postID    : btn.postID,
				slug      : slug,
				type      : that.shortcode.tag,
				shortcode : that.shortcode.string()
			} )
			.done( function( response ) {
				that.render( response );
			} )
			.fail( function( response ) {
				if ( that.url ) {
					that.removeMarkers();
				} else {
					that.setError( response.message || response.statusText, 'admin-media' );
				}
			} );

		},

		edit: function( text, update ) {
			if ( ! this.shortcode.attrs || ! this.shortcode.attrs.named ) {
				return;
			}

			var attrs = this.shortcode.attrs.named;
			var $option = null;
			var i, snippet;

			if ( ! attrs.slug && ! attrs.id ) {
				return;
			}

			btn.$.check.prop( 'checked', attrs.line_numbers && 'false' !== attrs.line_numbers );

			if ( ! attrs.slug ) {
				$option = btn.$.select.find( '[data-id="' + attrs.id + '"]' );
				attrs.slug = $option.attr( 'value' );
			}

			btn.$.select.val( attrs.slug );

			// update btn text
			btn.mainBtn().text( btn.l10n.btn_update );

			btn.open( update );
			// Update snippet-editing fields.
			if ( this.content.snippet && this.content.snippet.post_content ) {
				snippet = this.content.snippet;
				btn.$.snippetContent.val( snippet.post_content );
				btn.$.snippetTitle.val( snippet.post_title );
				btn.$.snippetID.val( snippet.ID );

				if ( snippet.categories.length ) {
					for ( i = snippet.categories.length - 1; i >= 0; i-- ) {
						$id( 'in-snippet-categories-' + snippet.categories[i] ).prop( 'checked', true );
					}
				}

				if ( snippet.tags.length ) {
					$id( 'new-tag-snippet-tags' ).val( snippet.tags.join( ',' ) ).next().click();
				}

				if ( snippet.language.term_id ) {
					$id( 'snippet-language' ).val( snippet.language.term_id );
				}

				btn.showAddNew();
			}

		}

	};

	btn.init = function() {
		btn.cache();

		// Localize the buttons
		var buttons = {};

		buttons[ btn.l10n.btn_cancel ] = btn.cancel;
		if ( ! btn.$.none.length ) {
			buttons[ btn.l10n.btn_insert ] = btn.insert;
		}

		btn.$.formWrap.dialog( {
			resizable     : true,
			dialogClass   : 'wp-dialog snippet-cpt-dialog',
			modal         : true,
			autoOpen      : false,
			draggable     : true,
			height        : 'auto',
			// width      : 495,
			width         : 'calc( 100% - 100px )',
			closeOnEscape : true,
			buttons       : buttons,
			create        : btn.create,
			close         : btn.close
		} );

		wp.mce.views.register( 'snippet', btn.mceView );

		// text editor button
		QTags.addButton( 'snippetcpt', btn.l10n.button_name, function() {
			window.codeSnippetCPTButton.open();
		} );

		// New UI Hooks
		$( document.body )
			.on( 'click', 'input.add-new-snippet-btn', btn.showAddNew )
			.on( 'click', 'input.cancel-new-snippet-btn', btn.cancelAddNew );
	};

	$( btn.init );

} )( window, document, jQuery, window.wp, window.QTags, window.codeSnippetCPTButton );
