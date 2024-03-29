const warning = 'Since there is no content, this page will be deleted once you close the tab or browser window.';
const fadeTime = 1200;

$(document).ready( function() {
	const uri = $(location).attr('href');

	let editable = $("body > section:first-child");

	editable.fresheditor();

	editable.unlocked = function() {
		return editable.prop('contentEditable') == "true"
	};
	editable.lock = function () {
		editable.attr("contentEditable", false);
	};
	editable.unlock = function () {
		editable.attr("contentEditable", true);
	};
	editable.emptyContent = function () {
		const that = $(this);
		if (editable.unlocked()) {
			let lastModified = that.data('modified');

			if (typeof (lastModified) == 'undefined') {
				lastModified = that.attr('data-modified');
			}
			const d = new Date(lastModified);
			if (d.toString() == 'Invalid Date') {
				previousContent = ' ';
				that.html(previousContent);
			}
		}
	};

	const lock = $('<svg aria-hidden="true" class="icon"><use xlink:href="/icons.svg#icon-lock"><title>Locked</title></use></svg>')
	const unlock = $('<svg aria-hidden="true" class="icon"><use xlink:href="/icons.svg#icon-unlock"><title>Unlocked</title></use></svg>')

	const a = $('<a/>').addClass('hidden');
	a.click (function (e) {
		const message = 'Please enter the secret key for this page.';
		const key = prompt (message, '');
		if (key == null) {
			return;
		}
		let icon = a.find("svg");
		let isLocked = icon.find("title").text() == "Locked";
		console.debug("is Locked: %s %s", icon.find("title").text(), isLocked);
		if (isLocked) {
			console.debug("checking auth token: %s", authToken);
			checkSecret(key);
		} else {
			console.debug("saving auth token: %s", authToken);
			checkSecret(key);
		}
	});

	function hideLock (e) {
		a.fadeIn(fadeTime, () => { a.addClass("hidden"); });
	};

	function showLock(e) {
		a.fadeOut(fadeTime, () => { a.removeClass("hidden"); });
	};

	function blinkLock(e) {
		if (a.css('opacity') == 0 || a.css('display') == 'none') {
			a.fadeIn(fadeTime).fadeOut(fadeTime);
		}
	}
	const feedBack = $("<nav/>").addClass('feedback').append(a);
	feedBack.mouseenter(hideLock)
		.mouseleave(showLock)
		.click(blinkLock);
	$("body").prepend(feedBack);

	editable.unlock();

	const titleText = editable.attr('title');

	let waitTime = 3000; // milliseconds
	let start = new Date(); // start of the save request
	let finish = new Date(); // finish of the save request
	let authToken = null;
	let previousContent = editable.html();
	let bStillSaving = false;
	let selection = null;


	function removeOnClose(e) {
		let request = $.ajax({
			url: uri,
			type: 'delete',
			beforeSend: setAuthorizationToken,
		});
		request.always((jqXHR, status) => {
			console.debug("deleted %s: %s", uri, status)
		});
		return warning;
	}

	editable.keyup (function(e) {
		if (
			editable.prop("contentEditable") == "true" &&
			editable.text().trim().length == 0 &&
			editable.children("img").length == 0
		) {
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
	});

	// adding click events to make links to work in edit mode
	editable.find('a').mousedown(function(e) {
		const la = $(e.target);
		if (la.is('a') && editable.unlocked()) {
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

	checkLocked();

	setInterval(() => { save(); }, waitTime);

	function setAuthorizationToken(xhr) {
		if (authToken != null) {
			xhr.setRequestHeader("Authorization", authToken);
		}
	};

	function makeEditable() {
		a.empty();
		a.append(unlock);
		editable.attr("contentEditable", true);
	};

	function makeReadOnly() {
		a.empty();
		a.append(lock);
		editable.attr("contentEditable", false);
	};

	function checkLocked() {
		let request = $.ajax({
			url: uri,
			type: 'post',
		});
		request.done((o, tw, xhr) => {
			makeEditable();
			console.debug("unlocked");
		});
		request.fail((xhr) => {
			if (xhr.status == 401) {
				makeReadOnly();
				console.debug("locked");
			}
		});
		request.always(blinkLock());
	}

	function checkSecret(key) {
		console.debug("check or update secrets: %s", key)
		const postData = {'_': key};
		let request = $.ajax({
			url: uri,
			type: 'patch',
			data: postData,
			beforeSend: function (xhr) {
				bStillSaving = true;
				setAuthorizationToken(xhr);
			},
		});

		request.done((o, tw, xhr) => {
			makeEditable();
			console.debug("unlocked");
			if (xhr != null) {
				authToken = xhr.getResponseHeader('Authentication-Info')
			}
		});
		request.fail((xhr) => {
			if (xhr.status == 401) {
				makeReadOnly();
				authToken = null;
				console.error("failed to update key:", xhr);
			}
		});
		request.always(blinkLock(), resetTimer);
	};

	function unsavedChanges (text) {
		return text != previousContent;
	};

	function resetTimer (xhr, status) {
		bStillSaving = false;
		finish = new Date();
		const lastRun = finish.getTime() - start.getTime();
		let multiplier = 2;
		if (lastRun > 1000) {
			multiplier = 1;
		} else if (lastRun < 400) {
			multiplier = 10;
		} else if (lastRun < 100) {
			multiplier = 20;
		}
		waitTime = lastRun * multiplier;
	};

	function save () {
		console.debug ("next check: in %dms", waitTime);
		console.debug ("is editable: %s", editable.unlocked());
		if (!editable.unlocked()) {
			return;
		}
		const changes = unsavedChanges (editable.html());
		console.debug ("changes: %s", changes);
		if (!changes) {
			return;
		}
		console.debug ("still saving: %s", bStillSaving);
		if (bStillSaving) {
			return;
		}
		const now = new Date();
		const overTheWaitTime = ((now.getTime() - finish.getTime()) > waitTime);
		console.debug ("over the wait time: %s", overTheWaitTime);
		if (!overTheWaitTime) {
			return;
		}
		const modifiedLast = new Date(editable.data("modified"));
		console.debug ("last modified: %sms ago", (now.getTime() - modifiedLast));
		console.debug ("last save: %dms ago", (now.getTime() - finish.getTime()));

		const content = editable.html();
		const postData = {'_': content};
		let request = $.ajax({
			url: uri,
			type: 'post',
			data: postData,
			beforeSend: function (xhr) {
				start = new Date();
				bStillSaving = true;
				previousContent = content;
				setAuthorizationToken(xhr);
			},
		});

		request.done(() => {
			console.debug(request);
			if (request.status == 200) {
				editable.data('modified', now.valueOf());
			}
		});
		request.fail((xhr) => {
			console.error("failed to update: %d", xhr.status, xhr);
		});
		request.always(resetTimer);
	};
});
