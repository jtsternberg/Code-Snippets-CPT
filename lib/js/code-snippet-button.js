var codeSnippetCPTButton = (function(window, document, $, undefined){
	'use strict';

	var btn = {
		$        : {},
		isVisual : false
	};
	var cached     = false,
		adding_new = false;

	btn.cache = function() {
		if ( cached )
			return;

		btn.$.form   = $('#snippet-cpt-form');
		btn.$.select = $('#snippet-cpt-posts');
		btn.$.check  = $('#snippet-cpt-line-nums');
		btn.$.none   = $('#no-snippets-exist');

		/* New UI */
		btn.$.select_snippet  = $( 'fieldset.select_a_snippet' );
		btn.$.add_new_snippet = $( 'fieldset.add_new_snippet' );
		btn.$.add_new                  = $( 'input.add_new_snippet_btn' );
		btn.$.cancel_new               = $( 'input.cancel_new_snippet_btn' );

		cached = true;
	}

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
	}

	btn.close = function() {
		btn.$.select.prop('selectedIndex', 0);
	}

	btn.cancel = function() {
		btn.$.form.dialog( 'close' );
	}

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

			if ( btn.isVisual && codeSnippetCPTVisual ) {
				codeSnippetCPTVisual.execCommand('mceInsertContent', 0, shortcode);
			} else {
				QTags.insertContent(shortcode);
			}
			btn.$.form.dialog( 'close' );
		}
	}

	btn.init = function() {
		btn.cache();

		var buttons = { 'Cancel': btn.cancel };
		if ( ! btn.$.none.length ) {
			buttons['Insert Shortcode'] = btn.insert;
		}
		// btn.btns = codeSnippetCPT.buttons,
		btn.$.form.dialog({
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
		});

		/* New UI Hooks */
		$( 'body' ).on( 'click', 'input.add_new_snippet_btn', btn.show_add_new );
		$( 'body' ).on( 'click', 'input.cancel_new_snippet_btn', btn.cancel_add_new );
	}

	btn.show_add_new = function( evt ){
		evt.preventDefault();
		btn.$.add_new.prop( 'disabled', true );
		btn.$.select_snippet.slideUp( 100, function(){
			btn.$.add_new_snippet.slideDown( 200, function(){
				btn.$.cancel_new.fadeIn();
				adding_new = true;
			} )
		});
	}

	btn.cancel_add_new = function( evt ){
		console.log( 'here' );
		evt.preventDefault();
		btn.$.add_new.prop( 'disabled', false );
		btn.$.select_snippet.slideDown( 200, function(){
			btn.$.add_new_snippet.slideUp( 100, function(){
				btn.$.cancel_new.fadeOut();
				adding_new = false;
			} )
		});
	}

	btn.open = function( isVisual ) {
		btn.cache();

		btn.isVisual = isVisual === true;

		btn.$.form.dialog( 'open' );
	}

	btn.init();

	return btn;

})(window, document, jQuery);

// text editor button
QTags.addButton( 'snippetcpt', window.codeSnippetCPT.button_name, function(el, canvas) {
	codeSnippetCPTButton.open();
});
