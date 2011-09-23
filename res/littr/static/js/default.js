$(document).ready( function() {
	var editable = $("#content");

	var feedBack = $("<div/>").addClass('feedback').insertBefore(editable);
	var a = $("<a/>").addClass('icon').appendTo (feedBack).hide();

	var authToken = null;

	function blinkError (message) {
		var err = $('a.icon.error');
		if (err.length == 0) {
			var err = $('<a/>').addClass('icon');
			err.insertAfter (a);
		}
		var keepClasses = ['icon'];
		var allClasses = err.prop('class').split(/\s+/);
		var oldClasses = []; 
		
		for (var i = 0 ; i < allClasses.length ; i++ ) {
			var currClass = allClasses[i]; 
			if (keepClasses.indexOf (currClass) == -1) {
				oldClasses.push (currClass);
				err.removeClass (currClass);
			}
		}
				
		err.addClass ("error").prop("title", message).fadeIn(200).fadeOut(200).fadeIn(400).fadeOut(3000);
		
		for (var i = 0; i < oldClasses.length ; i++ ) {
			err.addClass(oldClasses[i]);
		}
	}
	
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
			url: '/s/',
			dataType: 'json',
			type: 'post',
			data: postData,
			success : function (data) {
				if (data.status == 'ko') {
					// show lock icon
					a.addClass ('locked').blink({
						speed: 'slow',
						times: 2
					});
					editable.stopEditing();
				} else {
					// show unlocked
					a.addClass ('unlocked').blink('slow');
					editable.startEditing();
					authToken = data.auth_token;
					$('a.error').detach();
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
		console.debug (key);

		if (key != null) {
			if ($(this).hasClass('unlocked')) {
				checkForSecrets(key, 'update');
			} else {
				checkForSecrets(key, 'check');
			}
			if ($(this).hasClass('locked')) {
				blinkError('Wrong key entered.');
			}
		}		
		
	});

	editable
		.startEditing()
		.keyup(function(e) {
			var moveCommands = [37,38,39,40]; // left, up, right, down 
			var whiteSpaces = [8,9,13,32,46,190]; // bksp, cr, space, tab, del, "." ,
			var ctrlKeys = [27, 83, 90]; // ctrl-v, ctrl-s, ctrl-z
			var shiftKeys = [16]; // shift-insert
			if (

				whiteSpaces.indexOf (e.keyCode) != -1 ||
				(e.ctrlKey &&  ctrlKeys.indexOf (e.keyCode) != -1) ||
				(e.shiftKey &&  shiftKeys.indexOf (e.keyCode) != -1)
			) {
				var postData = {
					'action' : 'save',
					'content' : $(this).html(),
					'auth_token' : authToken
				};
				$.ajax({
					url: '/s/',
					dataType: 'json',
					type: 'post',
					data: postData,
					success : function (responseData) {
						if (responseData.status != 'ok') {
							blinkError (responseData.message);
						}
					}
				});
			} else {
				console.debug (e.keyCode);
			}
		});
});
