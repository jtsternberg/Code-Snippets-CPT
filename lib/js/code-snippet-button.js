/* global codeSnippetCPTButton */
/* global codeSnippetCPTVisual */

window.wp = window.wp || {};
window.codeSnippetCPTButton = window.codeSnippetCPTButton || {};

( function( window, document, $, wp, QTags, btn, undefined ) {
	'use strict';

	btn.$        = {};
	btn.isVisual = false;
	btn.postID   = 0;

	var cached    = false;
	var addingNew = false;

	function $id( id ) {
		return $( document.getElementById( id ) );
	}

	btn.cache = function() {
		if ( cached ) {
			return;
		}

		btn.$.htmlBody = $( 'html, body' );
		btn.$.formWrap = $id( 'snippet-cpt-form' );
		btn.$.select   = $id( 'snippet-cpt-posts' );
		btn.$.check    = $id( 'snippet-cpt-line-nums' );
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
		btn.$.snippetTitle      = btn.$.form.find( '.new-snippet-title' );
		btn.$.snippetContent    = btn.$.form.find( '.new-snippet-content' );

		cached = true;
	};

	btn.create = function() {
		var $this = $(this);
		// focus first button and bind enter to it
		$this.parent().find('.ui-dialog-buttonpane button:last-child').focus();
		$this.keypress(function(e) {
			if ( e.keyCode == 13 ) {
				e.preventDefault();
				$this.parent().find('.ui-dialog-buttonpane button:last-child').click();
			}
		});
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
		btn.$.form.trigger('reset');
	};

	btn.resetErrorBlock = function() {
		btn.$.errorBlock.text('').hide();
	};

	btn.cancel = function() {
		btn.$.formWrap.dialog( 'close' );
	};

	btn.insert = function () {
		var postSlug    = btn.$.select.val();
		var lang        = btn.$.select.find(':selected').data('lang');
		var lineNumbers = btn.$.check.is(':checked') ? true : false ;

		// Reset it just in case.
		btn.resetErrorBlock();

		if ( addingNew ) {

			if ( ! btn.$.snippetTitle.val() || ! btn.$.snippetContent.val() ) {
				btn.displayErrorMessage( btn.l10n.missing_required );
				return false;
			}

			var ajaxData = {
				action    : 'snippetcpt_insert_snippet',
				nonce     : btn.snippet_nonce,
				form_data : btn.$.form.serialize(),
			};

			btn.$.overlay.show();

			$.post( ajaxurl, ajaxData, function( response ) {
				btn.$.overlay.hide();
				// May be a better way of doing this, but I'm using an anonymous function since I want to
				// be able to reset the above variables if necessary without globalizing them. - Jay
				if ( response.success ) {
					postSlug    = response.data.slug;
					lang        = response.data.language;
					lineNumbers = response.data.line_numbers;

					btn.insertShortcode( postSlug, lineNumbers, lang );
				} else {
					if ( response.data.message ) {
						btn.displayErrorMessage( response.data.message );
					} else {
						btn.displayErrorMessage();
					}
					return false;
				}

			}, 'json' );
		} else {
			btn.insertShortcode( postSlug, lineNumbers, lang );
		}
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

	btn.insertShortcode = function( postSlug, lineNumbers, lang ) {

		if ( 0 < btn.$.none.length && ! addingNew ) {
			btn.displayErrorMessage( btn.l10n.no_snippets );
			btn.$.overlay.hide();
			return false;
		}

		if ( postSlug ) {
			var shortcode = '[snippet slug='+ postSlug;

			if ( ! lineNumbers ) {
				shortcode += ' line_numbers=false';
			}

			if ( lang ) {
				shortcode += ' lang=' + lang;
			}

			shortcode += ']';

			if ( 'function' === typeof( btn.isVisual ) ) {
				btn.isVisual( shortcode );
			} else if ( btn.isVisual && codeSnippetCPTVisual ) {
				codeSnippetCPTVisual.execCommand( 'mceInsertContent', 0, shortcode );
			} else {
				QTags.insertContent(shortcode);
			}

			btn.$.formWrap.dialog( 'close' );

			btn.isVisual = false;

			if ( btn.$.btn ) {
				btn.$.btn.text( btn.l10n.btn_insert );
				btn.$.editBtn.hide();
			}

		} else {
			btn.$.overlay.hide();
			if ( addingNew ) {
				btn.displayErrorMessage( btn.l10n.missing_required );
			} else {
				btn.displayErrorMessage( btn.l10n.select_snippet );
			}
		}
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
		});
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
		});
	};

	btn.open = function( isVisual ) {
		btn.cache();

		btn.isVisual = isVisual;

		btn.$.formWrap.dialog( 'open' );
	};

	btn.editBtn = function( editlink ) {
		if ( ! btn.$.editBtn ) {
			btn.$.btn.parent().before( '<a class="button-secondary ui-dialog-buttonpane ui-button" id="snippet-edit-link" href="'+ editlink +'">'+ btn.l10n.btn_edit +'</a>' );

			btn.$.editBtn = $( document.getElementById( 'snippet-edit-link' ) );
		} else {
			btn.$.editBtn.attr( 'href', editlink );
		}

		return btn.$.editBtn;
	};

	btn.mce_view = {
		action: 'snippet_parse_shortcode',
		state: [],

		initialize: function() {
			var that = this;

			if ( that.url ) {
				that.loader    = false;
				that.shortcode = wp.media.embed.shortcode( {
					url: that.text
				} );
			}

			var slug = '';
			if ( that.shortcode.attrs && that.shortcode.attrs.named && that.shortcode.attrs.named.slug ) {
				slug = that.shortcode.attrs.named.slug;
			}
			wp.ajax.post( that.action, {
				post_ID   : btn.postID,
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

			if ( ! attrs.slug && ! attrs.id ) {
				return;
			}

			var has_line_numbers = attrs.line_numbers && 'false' !== attrs.line_numbers;

			btn.$.check.prop( 'checked', has_line_numbers );

			if ( ! attrs.slug ) {
				$option = btn.$.select.find( '[data-id="'+ attrs.id +'"]' );
				attrs.slug = $option.attr( 'value' );
			} else {
				$option = btn.$.select.find( '[value="'+ attrs.slug +'"]' );
			}

			btn.$.select.val( attrs.slug );

			// Get submit button
			btn.$.btn = btn.$.formWrap.next().find( '.ui-button-text:contains('+ btn.l10n.btn_insert +')' );

			// update its text
			btn.$.btn.text( btn.l10n.btn_update );

			// Add the edit button (if it's not there)
			btn.editBtn( $option.data( 'editlink' ) ).show();

			btn.open( update );

			if ( this.content.snippet && this.content.snippet.post_content ) {
				btn.$.snippetContent.val( this.content.snippet.post_content );
				btn.$.snippetTitle.val( this.content.snippet.post_title );
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

		btn.$.formWrap.dialog({
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
		});

		wp.mce.views.register( 'snippet', btn.mce_view );

		// text editor button
		QTags.addButton( 'snippetcpt', btn.l10n.button_name, function() {
			codeSnippetCPTButton.open();
		});

		// New UI Hooks
		$( document.body )
			.on( 'click', 'input.add-new-snippet-btn', btn.showAddNew )
			.on( 'click', 'input.cancel-new-snippet-btn', btn.cancelAddNew );
	};

	$( btn.init );

} )( window, document, jQuery, window.wp, window.QTags, window.codeSnippetCPTButton );
