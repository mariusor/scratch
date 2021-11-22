const warning = 'Since there is no content, this page will be deleted once you close the tab or browser window.';
$(document).ready( function() {
	const w = $(window);
	const uri = $(location).attr('href');

	let editable = $("body > section:first-child");
	editable.attr("contentEditable", true);

	const lock = $('<svg aria-hidden="true" class="icon"><use xlink:href="/icons.svg#icon-lock"><title>Locked</title></use></svg>')
	const unlock = $('<svg aria-hidden="true" class="icon"><use xlink:href="/icons.svg#icon-unlock"><title>Unlocked</title></use></svg>')

	let icon = unlock;
	const a = $('<a/>').addClass('hidden').append(icon);
	const feedBack = $("<nav/>").addClass('feedback').append(a);
	$("body").prepend(feedBack);

	// lock-unlock icon
	feedBack.mouseenter(function(e) {
		a.fadeIn(1200, () => { a.addClass("hidden"); });
	}).mouseleave(function (e) {
		a.fadeOut(1200, () => { a.removeClass("hidden"); });
	}).click(function (e) {
		if (a.css('opacity') == 0 || a.css('display') == 'none') {
			a.fadeIn(1200).fadeOut(1200);
		}
	});

	editable.emptyContent = function () {
		const that = $(this);
		if (that.attr('contentEditable') == 'true') {
			let lastModified = $(this).data('modified');

			if (typeof (lastModified) == 'undefined') {
				lastModified = $(this).attr('data-modified');
			}
			const d = new Date(lastModified);
			if (d.toString() == 'Invalid Date') {
				previousContent = ' ';
				that.html(previousContent);
			}
		}
	};

	const titleText = editable.attr('title');

	let waitTime = 3000; // milliseconds
	let start = new Date(); // start of the save request
	let finish = new Date(); // finish of the save request
	let authToken = null;
	let previousContent = editable.html();
	let bStillSaving = false;
	let selection = null;

	a.click (function (e) {
		const message = 'Please enter the secret key for this page.';
		const key = prompt (message, '');
		if (key != null) {
			if ($(this).hasClass('unlocked')) {
				checkForSecrets(key, 'update');
			} else {
				checkForSecrets(key, 'check');
			}
		}
	});

	function removeOnClose(e) {
		$.ajax({
			url: uri,
			type: 'delete',
			beforeSend: setAuthorizationToken,
			complete: function (jqXHR, status) {
				console.debug("deleted %s: %s", uri, status)
			}
		});
		return warning;
	}

	editable.keyup (function(e) {
		if (editable.text().trim().length == 0 && editable.children("img").length == 0) {
			editable.prop('title', warning);
			// bind delete on window close if there's no content
			console.debug("preparing to delete %s", uri)
			window.addEventListener ('beforeunload', removeOnClose);
		} else {
			editable.prop ('title', titleText);
			// remove the delete action if the user wrote something
			window.removeEventListener('beforeunload', removeOnClose);
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
		const la = $(e.target);
		if (la.is('a') && editable.attr ('contentEditable') == 'true') {
			e.preventDefault();
			e.stopPropagation();
			switch (e.detail) {
			case 1:
				location.href = la.attr('href');
				break;
			case 2:
				window.open(la.attr('href'));
			}
		}
	});

	checkForSecrets();

	const id = setInterval(() => { save(); }, waitTime);

	function handleFileSelect(e) {
		e.stopPropagation();
		e.preventDefault();

		const evt = e.originalEvent;
		const files = evt.dataTransfer.files; // FileList object

		if (files.length != 0) {
			for (let i = 0, f; f = files[i]; i++) {
				if (!f.type.match('image.*')) {
					continue;
				}
				const reader = new FileReader();
				reader.onload = (function (theFile) {
					return function(e) {
						//const img = $('<img src="' + e.target.result + '" data-name="'+theFile.name+'"/>');
						const img = document.createElement ("img");
						img.src = e.target.result;
						img.title = theFile.name;
						img.dataSize= theFile.size;
						img.dataName=theFile.name;

						if (selection.rangeCount > 0 && selection.getRangeAt(0).startContainer != $('body').get(0)) {
							const range = selection.getRangeAt(0);
							const fragment = document.createDocumentFragment();
							fragment.appendChild (img);

							range.deleteContents();
							range.insertNode(fragment);
						} else {
							const elem = $(evt.target);
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
		const evt = e.originalEvent;
		evt.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
	}

	function setAuthorizationToken(xhr) {
		if (authToken != null) {
			xhr.setRequestHeader("Authorization", authToken);
		}
	};

	function checkForSecrets(key, action) {
		const postData = {};
		if (typeof(key) != 'undefined' && key != null) {
			postData.key = key;
		}
		if (typeof(action) != 'undefined' && action == 'update') {
			postData.action = action;
		} else {
			postData.action = 'check';
		}

		console.debug("check secrets")
		$.ajax({
			url: uri,
			type: 'post',
			data: postData,
			beforeSend: setAuthorizationToken,
			success: function (data) {
				if (data.status == 'ko') {
					// show lock icon
					icon = lock;
					editable.attr("contentEditable", false);
					console.debug("locked");
				} else {
					// show unlocked
					icon = unlock;
					editable.attr("contentEditable", true);
					authToken = data.auth_token;
					console.debug("unlocked");
				}
			},
			error: function (data) {
				// show lock icon
				icon = lock;
				editable.attr("contentEditable", false);
				console.debug("locked");
			}
		});
	}

	function unsavedChanges (text) {
		return text != previousContent;
	}

	function save () {
		let now = new Date();
		console.debug ("saving: " + (bStillSaving ? 'yes' : 'no'));
		console.debug ("is editable " + (editable.prop('contentEditable') == "true" ? 'yes' : 'no'));
		console.debug ("changes: " + (unsavedChanges (editable.html()) ? 'yes' : 'no'));
		console.debug ("last save: %dms ago", (now.getTime() - finish.getTime()));
		console.debug ("last modified: " + new Date(editable.data("modified")));
		console.debug ("will save?:" + (editable.prop("contentEditable") == "true" && !bStillSaving && unsavedChanges(editable.html()) && ((now.getTime() - finish.getTime()) > waitTime) ? 'yes' : 'no'));
		console.debug ("next check: %dms", waitTime);
		if ( editable.prop("contentEditable") == "true" && !bStillSaving && unsavedChanges(editable.html()) && ((now.getTime() - finish.getTime()) > waitTime)) {
			editable.data('modified', now.getTime());
			editable.fresheditor('save', function (id, content) {
				const postData = {
					'action' : 'save',
					'content' : content,
				};

				$.ajax({
					url: uri,
					type: 'post',
					data: postData,
					beforeSend: function (xhr) {
						start = new Date();
						bStillSaving = true;
						previousContent = content;
						setAuthorizationToken(xhr);
					},
					success: function (responseData) {
						if (responseData.status != 'ok') {
							console.debug(responseData.message);
						} else {
							editable.data('modified', responseData.modified);
						}
					},
					complete: function (data, status) {
						bStillSaving = false;
						finish = new Date();
						const lastRun = finish.getTime() - start.getTime();
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
		const singleKeys	= [8,9,13,32,46,190]; // bksp, cr, space, tab, del, "." ,
		const ctrlKeys	= [27, 83, 90]; // ctrl-v, ctrl-s, ctrl-z
		const shiftKeys	= [16]; // shift-insert

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

	function pad(n) {return n<10 ? '0'+n : n}
});
