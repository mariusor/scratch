/**
 * Copyright (c) 2011 Marius Orcsik, http://habarnam.ro
 * Licensed under the GPL2 license
 */
(function(){
	/**
	 * The dollar sign could be overwritten globally,
	 * but jQuery should always stay accesible
	 */
	var $ = jQuery;
	/**
	 * Extending jQuery namespace, we
	 * could add public methods here
	 */

	$.fn.startEditing = function (options) {
		$(this).each(function() {
			if (typeof($(this).prop('prevValue')) == 'undefined'){
				$(this).prop('prevValue' , $(this).html());
			}

			$(this).prop('contentEditable', true);
			$(this).focus();
		});
		return $(this);
	};

	$.fn.stopEditing = function (options) {
		$(this).each(function() {
			$(this).prop('contentEditable', false);
		});
		return $(this);
	};

	$.fn.cancelEditing = function (options) {
		$(this).each(function() {
			$(this).prop('contentEditable', false);
			$(this).html($(this).prop('prevValue'));
		});
		return $(this);
	};

	$.fn.ctrlKeyDown = function (key, callback) {
		console.debug (key, callback);
		$(this).keydown(function(e) {
			if(e.keyCode == key.charCodeAt(0) && e.ctrlKey) {
				callback.apply(this);
				e.preventDefault();
			}
		});
	};

	$.getSelected = function(){
		var t = '';
		if(window.getSelection){
			t = window.getSelection();
		}else if(document.getSelection){
			t = document.getSelection();
		}else if(document.selection){
			t = document.selection.createRange().text;
		}
		return t;
	};
})(jQuery);