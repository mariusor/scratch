var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-110656-5']);
_gaq.push(['_trackPageview']);
(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
$(document).ready( function() {
	var editable = $("#content");
	var waitTime = 3000; // miliseconds
	var start = new Date(); // start of the save request
	var finish = new Date(); // finish of the save request


	var feedBack = $("<div/>").addClass('feedback').insertBefore(editable);
	var a = $("<a/>").addClass('icon').appendTo (feedBack).hide();

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

	var authToken = null;
	var previousContent = editable.html();
	var bStillSaving = false;

	checkForSecrets ();
	editable.fresheditor().keyup(function(e){
		if (isSaveKey(e)) {
			save ();
		}
	});
	var id = setInterval(function () {
		save();
	}, waitTime);

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
			url: '/',
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

	function unsavedChanges (text) {
		return text != previousContent;
	}

	function save () {
//		console.debug ("saving : " + (bStillSaving ? 'yes' : 'no'));
//		console.debug ("changes: " + (unsavedChanges ? 'yes' : 'no'));
		var now = new Date();
//		console.debug ("last save : " + (now.getTime() - finish.getTime()) + 'ms ago');
		if ( !bStillSaving && unsavedChanges(editable.html()) && ((now.getTime() - finish.getTime()) > waitTime)) {
			editable.fresheditor('save', function (id, content) {
				var postData = {
					'action' : 'save',
					'content' : content,
					'auth_token' : authToken
				};
				$.ajax({
					url: '/',
					dataType: 'json',
					type: 'post',
					data: postData,
					beforeSend : function () {
						start = new Date();
						bStillSaving = true;
						previousContent = postData.content;
					},
					success : function (responseData) {
						if (responseData.status != 'ok') {
							console.debug ('Err: ' + responseData.message);
						}
					},
					complete : function (data, status) {
						bStillSaving = false;
						finish = new Date();
						waitTime = (finish.getTime() - start.getTime()) * 2;
					}
				});
			});
		}
	}

	function isSaveKey (e) {
		//var moveKeys	= [33,34,35, 36, 37,38,39,40]; // pg-down, pg-up, end, home, left, up, right, down
		var singleKeys	= [8,9,13,32,46,190]; // bksp, cr, space, tab, del, "." ,
		var ctrlKeys	= [27, 83, 90]; // ctrl-v, ctrl-s, ctrl-z
		var shiftKeys	= [16]; // shift-insert

		if (e.ctrlKey && ctrlKeys.indexOf (e.keyCode) != -1) {
			return true
		}

		if (e.shiftKey && shiftKeys.indexOf (e.keyCode) != -1) {
			return true;
		}

		if (singleKeys.indexOf (e.keyCode) != -1) {
			return true;
		}

		return false;
	}
});