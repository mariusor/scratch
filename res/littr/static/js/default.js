$(document).ready( function() {
	var editable = $("#content");

	var feedBack = $("<div/>").addClass('feedback').insertBefore(editable);
	var a = $("<a/>").addClass('icon').appendTo (feedBack).hide();

	var authToken = null;
	var unsavedChanges = false;
	
	checkForSecrets ();
	
	editable.fresheditor().keyup (function (e) {
		if (isSaveKey (e) && unsavedChanges) {
			editable.fresheditor('save', function (id, content) {
				var postData = {
					'action' : 'save',
					'content' : content,
					'auth_token' : authToken
				};
				$.ajax({
					url: '/s/',
					dataType: 'json',
					type: 'post',
					data: postData,
					success : function (responseData) {
						if (responseData.status != 'ok') {
							console.debug ('Err: ' + responseData.message);
						} else {
							unsavedChanges = false;
						}
					}
				});
			});
		}
	});
	
	$('.feedback').mouseenter(function(e){
		$(this).children('a').fadeIn('slow');
	}).mouseleave(function (e) {
		$(this).children('a').fadeOut('slow');
	});
	
	a.click (function (e) {
		var message = 'Please enter the secret key for this page.';
		var key = prompt (message, '');
		
		if (key != null) {
			if ($(this).hasClass('unlocked')) {
				checkForSecrets(key, 'update');
			} else {
				checkForSecrets(key, 'check');
			}
		}		
	});
	
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
					a.addClass ('locked').fadeIn('slow').fadeOut('slow');
					editable.fresheditor("edit", false);
				} else {
					// show unlocked
					a.addClass ('unlocked').fadeIn('slow').fadeOut('slow');
					editable.fresheditor("edit", true);
					authToken = data.auth_token;
				}
			}
		});
	}
	
	function isSaveKey (e) {
		var moveKeys = [33,34,35, 36, 37,38,39,40]; // pg-down, pg-up, end, home, left, up, right, down 
		var singleKeys = [8,9,13,32,46,190]; // bksp, cr, space, tab, del, "." ,
		var ctrlKeys = [27, 83, 90]; // ctrl-v, ctrl-s, ctrl-z
		var shiftKeys = [16]; // shift-insert

		if (moveKeys.indexOf (e.keyCode) == -1) {
			unsavedChanges = true;
		}
		
		if (e.ctrlKey) {
			if (ctrlKeys.indexOf (e.keyCode) != -1 || moveKeys.indexOf(e.keyCode)) {
				return true
			}
		}
		
		if (e.shiftKey) {
			if (shiftKeys.indexOf (e.keyCode) != -1)  { 
				return true;
			}
		}
		
		if (singleKeys.indexOf (e.keyCode) != -1 || moveKeys.indexOf (e.keyCode) != -1) {
			return true;
		}
		
		
		return false;
	}

	/*/
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
	/**/
	/*/
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
	/**/
});
