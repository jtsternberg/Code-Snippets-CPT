var codeSnippetCPTButton = (function(window, document, $, undefined){
	'use strict';

	var btn = {
		$        : {},
		isVisual : false
	};
	var cached = false;

	btn.cache = function() {
		if ( cached )
			return;

		btn.$.form   = $('#snippet-cpt-form');
		btn.$.select = $('#snippet-cpt-posts');
		btn.$.check  = $('#snippet-cpt-line-nums');
		btn.$.none   = $('#no-snippets-exist');
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
		var post_id = btn.$.select.val();
		var lang = btn.$.select.find(':selected').data('lang');

		if ( post_id ) {
			var shortcode = '[snippet id='+ post_id;

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
