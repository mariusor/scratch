$(document).ready( function() {
	var w = $(window);
	var editable = $("body > section:first-child");
	editable.attr("contentEditable", true);

	let lock = $('<svg aria-hidden="true" class="icon"><use xlink:href="/icons.svg#icon-lock"><title>Locked</title></use></svg>')
	let unlock = $('<svg aria-hidden="true" class="icon"><use xlink:href="/icons.svg#icon-unlock"><title>Unlocked</title></use></svg>')

	var icon = unlock;

	var a = $('<a/>').addClass('hidden').append(icon);
	var feedBack = $("<nav/>").addClass('feedback').append(a);
	$("body").prepend(feedBack);

	// lock-unlock icon
	feedBack.mouseenter(function(e) {
		a.toggleClass("hidden");
	}).mouseleave(function (e) {
		a.toggleClass("hidden");
	}).click(function (e) {
		if (a.css('opacity') == 0 || a.css('display') == 'none') {
			a.fadeIn(1200).fadeOut(1200);
		}
	});

	editable.emptyContent = function () {
		var that = $(this);
		if (that.attr('contentEditable') == 'true') {
			var lastModified = $(this).data('modified');

			if (typeof (lastModified) == 'undefined') {
				lastModified = $(this).attr('data-modified');
			}
			var d = new Date(lastModified * 1000);
			if (d.toString() == 'Invalid Date') {
				previousContent = ' ';
				that.html(previousContent);
			}
		}
	};

	editable.height (w.innerHeight()-30);
	editable.width (w.innerWidth()-26);
	w.resize (function (e) {
		editable.height(w.innerHeight()-30);
		editable.width(w.innerWidth()-26);
	});

	var maxHeight = Math.max(w.innerHeight(),$(this).height());

	let waitTime = 3000; // milliseconds
	var start = new Date(); // start of the save request
	var finish = new Date(); // finish of the save request

	var titleText = editable.attr('title');
	var authToken = null;
	var previousContent = editable.html();
	var bStillSaving = false;
	var selection = null;

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

	editable.keyup (function(e){
		if (
			(
				editable.text().trim() == ''
				|| editable.text().trim() == 'Welcome! This page is currently empty. ' +
				'You can edit it and it will be saved automatically.'
			)
			&& editable.children("img").length == 0
		) {
			editable.prop('title', 'Since there is no content, this page will be deleted once you close the tab or browser window.');
			if (typeof ($._data(window, 'events').beforeunload) == 'undefined') {
				// bind delete on window close if there's no content
				$(window).bind ('beforeunload', function (e) {
					var postData = {
						'auth_token' : authToken,
						'action' : 'delete'
					};
					$.ajax({
						url: '/',
						dataType: 'json',
						type: 'post',
						data: postData,
						complete: function (jqXHR, status) {
							console.debug(status)
						}
					});
				});
			}
		} else {
			editable.prop ('title', titleText);
			// remove the delete action if the user wrote something
			if (typeof ($._data(window, 'events').beforeunload) != 'undefined') {
				$(window).unbind ('beforeunload');
			}
		}
	}).bind('click', function(e) {
		editable.emptyContent();
		selection = window.getSelection();
	}).bind('dragover', function(e) {
		editable.emptyContent();
		selection = window.getSelection();
		e.preventDefault();
		e.stopPropagation();
	}).bind('drop', function (e) {
		selection = window.getSelection();//.getRangeAt(0);
		editable.emptyContent();
		handleFileSelect(e);
	});

	// adding click events to make links to work in edit mode
	editable.find('a').mousedown(function(e) {
		var la = $(e.target);
		if (la.is('a') && editable.attr ('contentEditable') == 'true') {
			e.preventDefault();
			e.stopPropagation();
			switch (e.which) {
			case 1:
				location.href = la.attr ('href');
				break;
			case 2:
				window.open (la.attr('href'));
			}
		}
	});

	checkForSecrets ();

	var id = setInterval(function () {
		save();
	}, waitTime);

	function handleFileSelect(e) {
		e.stopPropagation();
		e.preventDefault();

		evt = e.originalEvent;

		var files = evt.dataTransfer.files; // FileList object

		if (files.length != 0) {
			for (var i = 0, f; f = files[i]; i++) {
				if (!f.type.match('image.*')) {
					continue;
				}
				var reader = new FileReader();
				reader.onload = (function (theFile) {
					return function(e) {
						//var img = $('<img src="' + e.target.result + '" data-name="'+theFile.name+'"/>');
						var img = document.createElement ("img");
						img.src = e.target.result;
						img.title = theFile.name;
						img.dataSize= theFile.size;
						img.dataName=theFile.name;

						if (selection.rangeCount > 0 && selection.getRangeAt(0).startContainer != $('body').get(0)) {
							var range = selection.getRangeAt(0);
							var fragment = document.createDocumentFragment();
							fragment.appendChild (img);

							range.deleteContents();
							range.insertNode(fragment);
						} else {
							var elem = $(evt.target);
							elem.append (img);
						}
						save();
					};
				})(f);

				reader.readAsDataURL (f);
			}
		}
	}

	function handleDragOver(e) {
		e.stopPropagation();
		e.preventDefault();
		evt = e.originalEvent;
		evt.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
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
			url: '/',
			dataType: 'json',
			type: 'post',
			data: postData,
			success : function (data) {
				if (data.status == 'ko') {
					// show lock icon
					icon = lock;
					editable.attr("contentEditable", false);
				} else {
					// show unlocked
					icon = unlock;
					editable.attr("contentEditable", true);
					authToken = data.auth_token;
				}
			}
		});
	}

	function unsavedChanges (text) {
		return text != previousContent;
	}

	function save () {
		var now = new Date();
		console.debug ("saving : " + (bStillSaving ? 'yes' : 'no'));
		console.debug ("is editable " + (editable.prop('contentEditable') == "true" ? 'yes' : 'no'));
		console.debug ("changes: " + (unsavedChanges (editable.html()) ? 'yes' : 'no'));
		console.debug ("last save : " + (now.getTime() - finish.getTime()) + 'ms ago');
		console.debug ("will save? : " + (editable.prop("contentEditable") == "true" && !bStillSaving && unsavedChanges(editable.html()) && ((now.getTime() - finish.getTime()) > waitTime) ? 'yes' : 'no'));
		if ( editable.prop("contentEditable") == "true" && !bStillSaving && unsavedChanges(editable.html()) && ((now.getTime() - finish.getTime()) > waitTime)) {
			editable.data('modified', now.getTime());
			editable.fresheditor('save', function (id, content) {
				var postData = {
					'auth_token' : authToken,
					'action' : 'save',
					'content' : content,
					'uri' : $(location).attr('href')
				};

				$.ajax({
					url: '/',
					dataType: 'json',
					type: 'post',
					data: postData,
					beforeSend : function () {
						start = new Date();
						bStillSaving = true;
						previousContent = content;
					},
					success : function (responseData) {
						if (responseData.status != 'ok') {
							console.debug ('Err: ' + responseData.message);
						} else {
							editable.data('modified', responseData.modified);
						}
					},
					complete : function (data, status) {
						bStillSaving = false;
						finish = new Date();
						var lastRun = finish.getTime() - start.getTime();
						var multiplier = 2;
						if (lastRun > 1000) {
							multiplier = 1;
						} else if (lastRun < 400) {
							multiplier = 10;
						} else if (lastRun < 100) {
							multiplier = 20;
						}
						waitTime = lastRun * multiplier;
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

	function pad(n){return n<10 ? '0'+n : n}
});
