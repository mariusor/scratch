$(document).ready(function() {
	let editable = $("body > section:first-child");
	editable.bind('dragover', function(e) {
		//editable.emptyContent();
		//selection = window.getSelection();
		e.preventDefault();
		e.stopPropagation();
	}).bind('drop', function (e) {
		selection = window.getSelection();//.getRangeAt(0);
		console.debug("selection:", selection);
		//editable.emptyContent();
		handleFileSelect(e);
	});

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

	function handleDragOver(e) {
		e.stopPropagation();
		e.preventDefault();
		const evt = e.originalEvent;
		evt.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
	};

	function handleFileSelect(e) {
		e.stopPropagation();
		e.preventDefault();

		const evt = e.originalEvent;
		const files = evt.dataTransfer.files; // FileList object

		if (files.length == 0) {
			return false;
		}
		for (let i = 0, f; f = files[i]; i++) {
			if (!f.type.match('image.*')) {
				continue;
			}

			const reader = new FileReader();
			reader.onload = (function (theFile) {
				return function(e) {
					const img = $('<img src="' + e.target.result + '" data-name="'+theFile.name+'" alt="'+theFile.name+'"/>');
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

			reader.readAsDataURL(f);
		}
	};
});
