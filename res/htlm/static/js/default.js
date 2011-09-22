$(document).ready( function() {
	var editable = $("#content");

	var feedBack = $("<div/>").addClass('feedback').insertBefore(editable);
	var a = $("<a/>").addClass('icon').appendTo (feedBack).hide();

	var authToken = null;

	function checkForSecrets (key, action) {
		var postData = {};
		postData.uri = $(location).attr('href');

		if (typeof(key) != 'undefined' && key != null) {
			postData.key = key;
		}
		if (typeof(action) != 'undefined' && action == 'update') {
			postData.action = action;
		} else {
			postData.action = 'check';
		}

		if (authToken != null) {
			postData.auth_token = authToken;
		}

		$.ajax({
			url: '/htlm/check/',
			dataType: 'json',
			type: 'post',
			data: postData,
			success : function (data) {
				if (data.status == 'nok') {
					// show lock icon
					a.addClass ('locked').fadeIn('slow').fadeOut('slow');
					editable.stopEditing();
				} else {
					// show unlocked
					a.addClass ('unlocked');
					editable.startEditing();
					authToken = data.auth_token;
				}
			}
		});
	}

	$('.feedback').mouseenter(function(e){
		$(this).children('a').fadeIn('slow');
	}).mouseleave(function (e) {
		$(this).children('a').fadeOut('slow');
	});

	checkForSecrets ();

	a.click (function (e) {
		var message = 'Please enter the secret key for this page.';
		var key = prompt (message, '');

		if (key != null && $(this).hasClass('unlocked')) {
			checkForSecrets(key, 'update');
		} else {
			checkForSecrets(key, 'check');
		}
	});

	editable
		.startEditing()
		.keyup(function(e) {
			var whiteSpaces = [9,13,32,190]; // cr, space, tab, "." ,
			var ctrlKeys = [27]; // ctrl-v
			var shiftKeys = [16]; // shift-insert
			if (

				whiteSpaces.indexOf (e.keyCode) != -1 ||
				(e.ctrlKey &&  ctrlKeys.indexOf (e.keyCode) != -1) ||
				(e.shiftKey &&  shiftKeys.indexOf (e.keyCode) != -1)
			) {
				var postData = {
					'content' : $(this).html(),
					'auth_token' : authToken
				};
				$.ajax({
					url: '/htlm/save/',
					dataType: 'json',
					type: 'post',
					data: postData
				});
			}
		});
});
