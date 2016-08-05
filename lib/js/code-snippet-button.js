window.wp = window.wp || {};
window.codeSnippetCPTButton = window.codeSnippetCPTButton || {};

( function( window, document, $, wp, QTags, btn, undefined ) {
	'use strict';

	btn.$        = {};
	btn.isVisual = false;
	btn.postID   = 0;

	var cached = false;

	btn.cache = function() {
		if ( cached ) {
			return;
		}

		btn.$.form   = $('#snippet-cpt-form');
		btn.$.select = $('#snippet-cpt-posts');
		btn.$.check  = $('#snippet-cpt-line-nums');
		btn.$.none   = $('#no-snippets-exist');
		btn.postID   = $( '#post_ID' ).val() || 0;

		cached = true;
	};

	btn.create = function() {
		var $this = $(this);
		// focus first button and bind enter to it
		$this.parent().find('.ui-dialog-buttonpane button:last-child').focus();
		$this.keypress(function(e) {
			if( e.keyCode == 13 ) {
				e.preventDefault();
				$this.parent().find('.ui-dialog-buttonpane button:last-child').click();
			}
		});
	};

	btn.close = function() {
		btn.$.select.prop('selectedIndex', 0);
	};

	btn.cancel = function() {
		btn.$.form.dialog( 'close' );
	};

	btn.insert = function () {
		var post_slug = btn.$.select.val();
		var lang = btn.$.select.find(':selected').data('lang');

		if ( post_slug ) {
			var shortcode = '[snippet slug='+ post_slug;

			if ( ! btn.$.check.is(':checked') ) {
				shortcode += ' line_numbers=false';
			}

			if ( lang ) {
				shortcode += ' lang=' + lang;
			}

			shortcode += ']';

			if ( 'function' === typeof( btn.isVisual ) ) {
				btn.isVisual( shortcode );
			} else if ( btn.isVisual && codeSnippetCPTVisual ) {
				codeSnippetCPTVisual.execCommand('mceInsertContent', 0, shortcode);
			} else {
				QTags.insertContent(shortcode);
			}

			btn.$.form.dialog( 'close' );

			btn.isVisual = false;

			if ( btn.$.btn ) {
				btn.$.btn.text( btn.l10n.btn_insert );
				btn.$.editBtn.hide();
			}
		}
	};

	btn.open = function( isVisual ) {
		btn.cache();

		btn.isVisual = isVisual;

		btn.$.form.dialog( 'open' );
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

			wp.ajax.post( that.action, {
				post_ID   : btn.postID,
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
			btn.$.btn = btn.$.form.next().find( '.ui-button-text:contains('+ btn.l10n.btn_insert +')' );

			// update its text
			btn.$.btn.text( btn.l10n.btn_update );

			// Add the edit button (if it's not there)
			btn.editBtn( $option.data( 'editlink' ) ).show();

			btn.open( update );
		}

	};

	btn.init = function() {
		btn.cache();

		var buttons = {};
		buttons[ btn.l10n.btn_cancel ] = btn.cancel;
		if ( ! btn.$.none.length ) {
			buttons[ btn.l10n.btn_insert ] = btn.insert;
		}

		btn.$.form.dialog( {
			'dialogClass'   : 'wp-dialog',
			'modal'         : true,
			'autoOpen'      : false,
			'draggable'     : false,
			'height'        : 'auto',
			'width'         : 395,
			'closeOnEscape' : true,
			'buttons'       : buttons,
			'create'        : btn.create,
			'close'         : btn.close
		} );

		wp.mce.views.register( 'snippet', btn.mce_view );

		// text editor button
		QTags.addButton( 'snippetcpt', btn.l10n.button_name, function(el, canvas) {
			codeSnippetCPTButton.open();
		});

	};

	$( btn.init );

} )( window, document, jQuery, window.wp, window.QTags, window.codeSnippetCPTButton );
