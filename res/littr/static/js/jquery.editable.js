/* jQuery.contentEditable Plugin
Copyright © 2011 FreshCode
http://www.freshcode.co.za/

DHTML text editor jQuery plugin that uses contentEditable attribute in modern browsers for in-place editing.

Dependencies
------------
 - jQuery core
 - shortcut.js for keyboard hotkeys

Issues
------
 - no <code> or <blockquote> buttons (use Tab key for quotes)
 - no text alignment support

To Do
-----
 - let plugin build the toolbar
 - moves hard-coded IDs to options

License
-------
Let's keep it simple:
 1. You may use this code however you wish, including for commercial projects.
 2. You may not sell it or charge for it without my written permission.
 3. You muse retain the license information in this file.
 4. You are encouraged to contribute to the plugin on bitbucket (https://bitbucket.org/freshcode/jquery.contenteditable)
 5. You are encouraged to link back to www.freshcode.co.za if you publish something about it so everyone can benefit from future updates.

Best regards
Petrus Theron
contenteditable@freshcode.co.za
FreshCode Software Development

*/
/**
* http://www.openjs.com/scripts/events/keyboard_shortcuts/
* Version : 2.01.B
* By Binny V A
* License : BSD
*/
var shortcut = {
	'all_shortcuts': {}, //All the shortcuts are stored in this array
	'add': function (shortcut_combination, callback, opt) {
		//Provide a set of default options
		var default_options = {
			'type': 'keydown',
			'propagate': false,
			'disable_in_input': false,
			'target': document,
			'keycode': false
		};
		if (!opt) {
			opt = default_options;
		}
		else {
			for (var dfo in default_options) {
				if (typeof opt[dfo] == 'undefined') opt[dfo] = default_options[dfo];
			}
		}

		var ele = opt.target;
		if (typeof opt.target == 'string') ele = document.getElementById(opt.target);
		var ths = this;
		shortcut_combination = shortcut_combination.toLowerCase();

		//The function to be called at keypress
		var func = function (e) {
			e = e || window.event;

			if (opt['disable_in_input']) { //Don't enable shortcut keys in Input, Textarea fields
				var element;
				if (e.target) element = e.target;
				else if (e.srcElement) element = e.srcElement;
				if (element.nodeType == 3) element = element.parentNode;

				if (element.tagName == 'INPUT' || element.tagName == 'TEXTAREA') return;
			}

			//Find Which key is pressed
			if (e.which) code = e.which;
			else if (e.keyCode) code = e.keyCode;

			var character = String.fromCharCode(code).toLowerCase();

			if (code == 188) character = ","; //If the user presses , when the type is onkeydown
			if (code == 190) character = "."; //If the user presses . when the type is onkeydown

			var keys = shortcut_combination.split("+");
			//Key Pressed - counts the number of valid keypresses - if it is same as the number of keys, the shortcut function is invoked
			var kp = 0;

			//Work around for stupid Shift key bug created by using lowercase - as a result the shift+num combination was broken
			var shift_nums = {
				"`": "~",
				"1": "!",
				"2": "@",
				"3": "#",
				"4": "$",
				"5": "%",
				"6": "^",
				"7": "&",
				"8": "*",
				"9": "(",
				"0": ")",
				"-": "_",
				"=": "+",
				";": ":",
				"'": "\"",
				",": "<",
				".": ">",
				"/": "?",
				"\\": "|"
			};
			//Special Keys - and their codes
			var special_keys = {
				'esc': 27,
				'escape': 27,
				'tab': 9,
				'space': 32,
				'return': 13,
				'enter': 13,
				'backspace': 8,

				'scrolllock': 145,
				'scroll_lock': 145,
				'scroll': 145,
				'capslock': 20,
				'caps_lock': 20,
				'caps': 20,
				'numlock': 144,
				'num_lock': 144,
				'num': 144,

				'pause': 19,
				'break': 19,

				'insert': 45,
				'home': 36,
				'delete': 46,
				'end': 35,

				'pageup': 33,
				'page_up': 33,
				'pu': 33,

				'pagedown': 34,
				'page_down': 34,
				'pd': 34,

				'left': 37,
				'up': 38,
				'right': 39,
				'down': 40,

				'f1': 112,
				'f2': 113,
				'f3': 114,
				'f4': 115,
				'f5': 116,
				'f6': 117,
				'f7': 118,
				'f8': 119,
				'f9': 120,
				'f10': 121,
				'f11': 122,
				'f12': 123
			};

			var modifiers = {
				shift: { wanted: false, pressed: false },
				ctrl: { wanted: false, pressed: false },
				alt: { wanted: false, pressed: false },
				meta: { wanted: false, pressed: false}	//Meta is Mac specific
			};

			if (e.ctrlKey) modifiers.ctrl.pressed = true;
			if (e.shiftKey) modifiers.shift.pressed = true;
			if (e.altKey) modifiers.alt.pressed = true;
			if (e.metaKey) modifiers.meta.pressed = true;

			for (var i = 0; k = keys[i], i < keys.length; i++) {
				//Modifiers
				if (k == 'ctrl' || k == 'control') {
					kp++;
					modifiers.ctrl.wanted = true;

				} else if (k == 'shift') {
					kp++;
					modifiers.shift.wanted = true;

				} else if (k == 'alt') {
					kp++;
					modifiers.alt.wanted = true;
				} else if (k == 'meta') {
					kp++;
					modifiers.meta.wanted = true;
				} else if (k.length > 1) { //If it is a special key
					if (special_keys[k] == code) kp++;

				} else if (opt['keycode']) {
					if (opt['keycode'] == code) kp++;

				} else { //The special keys did not match
					if (character == k) {
						kp++;
					} else {
						if (shift_nums[character] && e.shiftKey) { //Stupid Shift key bug created by using lowercase
							character = shift_nums[character];
							if (character == k) kp++;
						}
					}
				}
			}

			if (kp == keys.length &&
						modifiers.ctrl.pressed == modifiers.ctrl.wanted &&
						modifiers.shift.pressed == modifiers.shift.wanted &&
						modifiers.alt.pressed == modifiers.alt.wanted &&
						modifiers.meta.pressed == modifiers.meta.wanted) {
				callback(e);

				if (!opt['propagate']) { //Stop the event
					//e.cancelBubble is supported by IE - this will kill the bubbling process.
					e.cancelBubble = true;
					e.returnValue = false;

					//e.stopPropagation works in Firefox.
					if (e.stopPropagation) {
						e.stopPropagation();
						e.preventDefault();
					}
//					return false;
				}
			}
		};
		this.all_shortcuts[shortcut_combination] = {
			'callback': func,
			'target': ele,
			'event': opt['type']
		};
		//Attach the function with the event
		if (ele.addEventListener) ele.addEventListener(opt['type'], func, false);
		else if (ele.attachEvent) ele.attachEvent('on' + opt['type'], func);
		else ele['on' + opt['type']] = func;
	},

	//Remove the shortcut - just specify the shortcut and I will remove the binding
	'remove': function (shortcut_combination) {
		shortcut_combination = shortcut_combination.toLowerCase();
		var binding = this.all_shortcuts[shortcut_combination];
		delete (this.all_shortcuts[shortcut_combination])
		if (!binding) return;
		var type = binding['event'];
		var ele = binding['target'];
		var callback = binding['callback'];

		if (ele.detachEvent) ele.detachEvent('on' + type, callback);
		else if (ele.removeEventListener) ele.removeEventListener(type, callback, false);
		else ele['on' + type] = false;
	}
};

/**
 * Copyright (c) 2010 by Gabriel Birke
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the 'Software'), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

function Sanitize()
{
  var i, e, options;
  options = arguments[0] || {};
  this.config = {};
  this.config.elements = options.elements ? options.elements : [];
  this.config.attributes = options.attributes ? options.attributes : {};
  this.config.attributes[Sanitize.ALL] = this.config.attributes[Sanitize.ALL] ? this.config.attributes[Sanitize.ALL] : [];
  this.config.allow_comments = options.allow_comments ? options.allow_comments : false;
  this.allowed_elements = {};
  this.config.protocols = options.protocols ? options.protocols : {};
  this.config.add_attributes = options.add_attributes ? options.add_attributes  : {};
  this.dom = options.dom ? options.dom : document;
  for(i=0;i<this.config.elements.length;i++) {
	this.allowed_elements[this.config.elements[i]] = true;
  }
  this.config.remove_element_contents = {};
  this.config.remove_all_contents = false;
  if(options.remove_contents) {

	if(options.remove_contents instanceof Array) {
	  for(i=0;i<options.remove_contents.length;i++) {
		this.config.remove_element_contents[options.remove_contents[i]] = true;
	  }
	}
	else {
	  this.config.remove_all_contents = true;
	}
  }
  this.transformers = options.transformers ? options.transformers : [];
};

Sanitize.REGEX_PROTOCOL = /^([A-Za-z0-9\+\-\.\&\;\*\s]*?)(?:\:|&*0*58|&*x0*3a)/i;
Sanitize.RELATIVE = '__relative__'; // emulate Ruby symbol with string constant

Sanitize.prototype.clean_node = function (container) {

	var fragment = this.dom.createDocumentFragment();
	this.current_element = fragment;
	this.whitelist_nodes = [];

	/**
	* Utility function to check if an element exists in an array
	*/
	function _array_index(needle, haystack) {
		var i;
		for (i = 0; i < haystack.length; i++) {
			if (haystack[i] == needle) {
				return i;
			}
		}
		return -1;
	}

	function _merge_arrays_uniq() {
		var result = [];
		var uniq_hash = {};
		var i, j;
		for (i = 0; i < arguments.length; i++) {
			if (!arguments[i] || !arguments[i].length) {
				continue;
			}

			for (j = 0; j < arguments[i].length; j++) {
				if (uniq_hash[arguments[i][j]]) {
					continue;
				}

				uniq_hash[arguments[i][j]] = true;
				result.push(arguments[i][j]);
			}
		}
		return result;
	}

	/**
	* Clean function that checks the different node types and cleans them up accordingly
	* @param elem DOM Node to clean
	*/
	function _clean(elem) {
		var clone;
		switch (elem.nodeType) {
			// Element
			case 1:
				_clean_element.call(this, elem);
				break;
			// Text
			case 3:
				var clone = elem.cloneNode(false);
				this.current_element.appendChild(clone);
				break;
			// Entity-Reference (normally not used)
			case 5:
				var clone = elem.cloneNode(false);
				this.current_element.appendChild(clone);
				break;
			// Comment
			case 8:
				if (this.config.allow_comments) {
					var clone = elem.cloneNode(false);
					this.current_element.appendChild(clone);
				}
			default:
				console.log("unknown node type", elem.nodeType);
				break;
		}
	}

	function _clean_element(elem) {
		var i, j, clone, parent_element, name, allowed_attributes, attr, attr_name, attr_node, protocols, del, attr_ok;
		var transform = _transform_element.call(this, elem);

		elem = transform.node;
		name = elem.nodeName.toLowerCase();

		// check if element itself is allowed
		parent_element = this.current_element;

		if (this.allowed_elements[name] || transform.whitelist) {
			this.current_element = this.dom.createElement(elem.nodeName);
			parent_element.appendChild(this.current_element);

			// clean attributes
			allowed_attributes = _merge_arrays_uniq(
				this.config.attributes[name],
				this.config.attributes['__ALL__'],
				transform.attr_whitelist
			);

			for (i = 0; i < allowed_attributes.length; i++) {
				attr_name = allowed_attributes[i];
				attr = elem.attributes[attr_name];
				if (attr) {
					attr_ok = true;
					// Check protocol attributes for valid protocol
					if (this.config.protocols[name] && this.config.protocols[name][attr_name]) {
						protocols = this.config.protocols[name][attr_name];
						del = attr.nodeValue.toLowerCase().match(Sanitize.REGEX_PROTOCOL);
						if (del) {
							attr_ok = (_array_index(del[1], protocols) != -1);
						}
						else {
							attr_ok = (_array_index(Sanitize.RELATIVE, protocols) != -1);
						}
					}
					if (attr_ok) {
						attr_node = document.createAttribute(attr_name);
						attr_node.value = attr.nodeValue;
						this.current_element.setAttributeNode(attr_node);
					}
				}
			}

			// Add attributes
			if (this.config.add_attributes[name]) {
				for (attr_name in this.config.add_attributes[name]) {
					attr_node = document.createAttribute(attr_name);
					attr_node.value = this.config.add_attributes[name][attr_name];
					this.current_element.setAttributeNode(attr_node);
				}
			}
		} // End checking if element is allowed
		// If this node is in the dynamic whitelist array (built at runtime by
		// transformers), let it live with all of its attributes intact.
		else if (_array_index(elem, this.whitelist_nodes) != -1) {
			this.current_element = elem.cloneNode(true);
			// Remove child nodes, they will be sanitiazied and added by other code
			while (this.current_element.childNodes.length > 0) {
				this.current_element.removeChild(this.current_element.firstChild);
			}
			parent_element.appendChild(this.current_element);
		}

		// iterate over child nodes
		if (!this.config.remove_all_contents && !this.config.remove_element_contents[name]) {
			for (i = 0; i < elem.childNodes.length; i++) {
				_clean.call(this, elem.childNodes[i]);
			}
		}

		// some versions of IE don't support normalize.
		if (this.current_element.normalize) {
			this.current_element.normalize();
		}
		this.current_element = parent_element;
	} // END clean_element function

	function _transform_element(node) {
		var output = {
			attr_whitelist: [],
			node: node,
			whitelist: false
		};
		var i, j, transform;
		for (i = 0; i < this.transformers.length; i++) {
			transform = this.transformers[i]({
				allowed_elements: this.allowed_elements,
				config: this.config,
				node: node,
				node_name: node.nodeName.toLowerCase(),
				whitelist_nodes: this.whitelist_nodes,
				dom: this.dom
			});

			if (transform == null) {
				continue;
			} else {
				if (typeof transform == 'object') {
					if (transform.whitelist_nodes && transform.whitelist_nodes instanceof Array) {
						for (j = 0; j < transform.whitelist_nodes.length; j++) {
							if (_array_index(transform.whitelist_nodes[j], this.whitelist_nodes) === -1) {
								this.whitelist_nodes.push(transform.whitelist_nodes[j]);
							}
						}
					}
					output.whitelist = transform.whitelist ? true : false;
					if (transform.attr_whitelist) {
						output.attr_whitelist = _merge_arrays_uniq(output.attr_whitelist, transform.attr_whitelist);
					}
					output.node = transform.node ? transform.node : output.node;
				}
				else {
					throw new Error("transformer output must be an object or null");
				}
			}
		}
		return output;
	} // end _transform_element function

	for (var i = 0; i < container.childNodes.length; i++) {
		_clean.call(this, container.childNodes[i]);
	}

	if (fragment.normalize) {
		fragment.normalize();
	}

	return fragment;
};
(function ($) {
	var inited = false,
			// helper function that builds the toolbar
			toolbar = function(options) {
					var bar = $('<div />').addClass('fresheditor-toolbar').css('display', 'block');
					bar.append($('<div />').addClass('buttons'));
					$.each(options.enabledCommands, function(groupName, group)
					{
							var groupEl = $('<ul />').addClass('toolbarSection').addClass(groupName);
							$.each(group, function(v, command) {
									groupEl.append(
											$('<li />').append(
													$('<a />')
															.addClass('toolbar_' + command)
															.attr({ title: options.i18n[command] + " (" + options.commands[command].shortcut + ")", href: "#" })
															.html(options.commands[command].toolbarHtml ? options.commands[command].toolbarHtml : "&nbsp;")
											)
									);
							});
							bar.find('.buttons').append(groupEl);
					});
					return bar;
			};
	var methods = {
			edit: function (isEditing) {
					var focus = function() {
							var t = $(this);
							t.data('before', t.html());
							return t;
					}, blur = function() {
							var t = $(this);
							if (t.data('before') != t.html())
							{
									t.data('before', t.html());
									t.trigger('change');
							}
					};
					if (isEditing === true)
					{
							$(this).bind('focus', focus);
							$(this).bind('blur keyup paste', blur);
					}
					else
					{
							$(this).unbind('focus', focus);
							$(this).unbind('blur keyup paste', blur);
					}

					return this.each(function () {
							$(this).attr("contentEditable", (isEditing === true) ? true : false);
					});
			},
			copy: function () {
					document.execCommand("Copy", false, null);
			},
			paste: function () {
					document.execCommand("Paste", false, null);
			},
			save: function (callback) {
					return this.each(function () {
							(callback)($(this).attr("id"), $(this).html());
					});
			},
			init: function (options) {
					options = $.extend({}, $.fn.fresheditor.defaults, options);
					if (typeof options === 'object' && typeof options.onchange == 'function')
					{
							$(this).bind('change', options.onchange);
					}
					if (options.toolbarEnabled) {
						var $toolbar = toolbar(options),
						on_scroll = function () {
								var docTop = $(window).scrollTop();

								var toolbarTop = $toolbar.offset().top;
								if (docTop > toolbarTop) {
										$("div.buttons", $toolbar).css({ "position": "fixed", "top": "0" });
								} else {
										$("div.buttons", $toolbar).css("position", "relative");
								}
						}, toolbar_reset = function() {
								$(window).unbind('scroll', on_scroll);
								$("div.buttons", $toolbar).css("position", "relative");
						}, toolbar_scroll = function() {
								$(window).bind('scroll', on_scroll);
								on_scroll();
						};

						$(this).first().before($toolbar);
					}
					/* Bind Toolbar Clicks */

					var that = this;
					$.each(options.commands, function(command, opts) {
							// use self-invoking function to keep command and opts in scope after loop ends
							methods[command] = (function(command, opts) {
									return function() {
										if (options.showToolbar) {
											var $toolbar = $(this).data('fresheditor').toolbar;
											// prevent triggering items that are not enabled
											if ($toolbar.find('a.toolbar_' + command).size() == 0) {
													return false;
											}
										}

										// we allow multiple commands to be executed as one action
										// typically, this only happens with removeFormat + unlink
										if (typeof opts.execCommand == 'string') {
												opts.execCommand = [opts.execCommand];
												opts.execCommandValue = [opts.execCommandValue]
										}

										// since execCommand is now an array, loop
										$.each(opts.execCommand, function(i, execCommand) {
												var execCommandValue = typeof opts.execCommandValue == 'object' ? opts.execCommandValue[i] : undefined;
												// if the command wants to provide a value to the execCommand, allow
												// it to using a callback
												if (typeof execCommandValue === "function") {
													if (that.prop("contentEditable") == "true") {
														execCommandValue(function(value) {
																// allow command to be aborted by returning false
																if (value === false) {
																		return false;
																}
																	document.execCommand(execCommand, false, value);

														});
													}
												}
												else
												{
														document.execCommand(execCommand, false, execCommandValue);
												}
										});
										return false;
								};
							})(command, opts);

							// put contenteditable as this when commands are run, so commands can
							// examine the plugin if it wants to
							var scopedThis = function() { return methods[command].apply(that); };

							if (options.showToolbar) {
								$toolbar.find('a.toolbar_' + command).click(scopedThis);
							}

							if (!inited)
							{
									if (typeof opts.shortcut === 'string')
									{
											opts.shortcut = [opts.shortcut];
									}
									$.each(opts.shortcut || [], function(i, key) {
											shortcut.add(key, scopedThis, { 'type': 'keydown', 'propagate': false });
									});
							}
					});

					inited = true;

					return this.each(function () {

							var $this = $(this), data = $this.data('fresheditor'),
									tooltip = $('<div />', {
											text: $this.attr('title')
									});
							if (options.showToolbar) {
								$this.blur(toolbar_reset);
								$this.focus(toolbar_scroll);
							}

							// If the plugin hasn't been initialized yet
							if (!data) {
									/* Do more setup stuff here */

									$(this).data('fresheditor', {
											target: $this,
											tooltip: tooltip,
											options: options,
											toolbar: options.showToolbar ? $toolbar : null
									});
							}
					});
			}
	};

	$.fn.fresheditor = function (method) {
			// Method calling logic
			if (methods[method]) {
					return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
			} else if (typeof method === 'object' || !method) {
					methods.init.apply(this, arguments);
			} else {
					$.error('Method ' + method + ' does not exist on jQuery.contentEditable');
			}

			return this;
	};

	$.fn.fresheditor.defaults = {
			/*
			 * Change this option to remove some options. Note that the shortcut keys
			 * will still be registered, but disabled for the options not present.
			 * The sub-arrays are groups of buttons, useful for styling.
			 */
			enabledCommands: {
					// character formatting
					character: ["bold", "italic", "removeFormat", "sup", "sub", "increaseFontSize", "decreaseFontSize"],
					// external stuff
					external: ["createLink", "insertImage"],
					// multiple-paragraph formatting
					multipleParagrah: ["ol", "ul", "blockquote", "code"],
					// headers and paragraphs
					paragraph: ["p", "h1", "h2", "h3", "h4"]
			},
			/*
			 * Strings for translation
			 */
			i18n: {
					bold: "Bold",
					italic: "Italic",
					underline: "Underline",
					removeFormat: "Remove formatting",
					createLink: "Link to web page",
					insertImage: "Insert image",
					blockquote: "Blockquote",
					code: "Code",
					ol: "Numbered list",
					ul: "Bullet list",
					sup: "Superscript",
					sub: "Subscript",
					p: "Paragraph",
					h1: "Heading 1",
					h2: "Heading 2",
					h3: "Heading 3",
					h4: "Heading 4",
					h5: "Heading 5",
					h6: "Heading 6",
					indent: "Indent",
					outdent: "Outdent"
			},
			/*
			 * Actual commands to run. You probably shouldn't override this completely,
			 * but you can safely change the shortcut and toolbarHtml values.
			 *
			 * shortcut: the shortcut combination (can also be an array of multiple shortcuts)
			 * execCommand: the command to send to document.execCommand
			 * execCommandValue: optional value to send to document.execCommand, but can
			 *	  also be a function that calls the provided callback when the value is
			 *	  ready.
			 * toolbarHtml: HTML to put inside the buttons (very short)
			 */
			commands: {
					bold: { shortcut: "Ctrl+b", execCommand: "bold", toolbarHtml: "B" },
					italic: { shortcut: "Ctrl+i", execCommand: "italic", toolbarHtml: "I" },
					underline: { shortcut: "Ctrl+u", execCommand: "underline", toolbarHtml: "U" },
					removeFormat: { shortcut: "Ctrl+m", execCommand: ["removeFormat", "unlink", "formatBlock"],
							execCommandValue: [null, null, ["<P>"]],
							toolbarHtml: "&minus;" },
					createLink: {
							shortcut: "Ctrl+l",
							execCommand: "createLink",
							execCommandValue: function(callback) {
									callback(prompt("Enter URL:", "http://"));
							},
							toolbarHtml: "@"
					},
					insertImage: {
							shortcut: "Ctrl+g",
							execCommand: "insertImage",
							execCommandValue: function(callback) {
									callback(prompt("Enter image URL:", "http://"));
							}
					},
					inserthorizontalrule: {shortcut: "Ctrl+Alt+h", execCommand: "inserthorizontalrule"},
					strikethrough: {shortcut: "Ctrl+Alt+t", execCommand: "strikethrough"},
					increaseFontSize: {shortcut: "Ctrl+Alt+=", execCommand: "increasefontsize"},
					decreaseFontSize: {shortcut: "Ctrl+Alt+m", execCommand: "decreasefontsize"}, // keyCode for - seems to be interpreted as M
					blockquote: { shortcut: "Ctrl+q", execCommand: "formatBlock", execCommandValue: ["<BLOCKQUOTE>"], toolbarHtml: "&ldquo;&bdquo;" },
					code: { shortcut: "Ctrl+Alt+c", execCommand: "formatBlock", execCommandValue: ["<PRE>"], toolbarHtml: "{&nbsp;}" },
					ol: { shortcut: "Ctrl+Alt+o", execCommand: "insertorderedlist" },
					ul: { shortcut: "Ctrl+Alt+u", execCommand: "insertunorderedlist" },
					sup: { shortcut: "Ctrl+.", execCommand: "superscript", toolbarHtml: "x<sup>2</sup>" },
					sub: { shortcut: "Ctrl+Shift+.", execCommand: "subscript", toolbarHtml: "x<sub>2</sub>" },
					p: { shortcut: "Ctrl+Alt+0", execCommand: "formatBlock", execCommandValue: ["<P>"], toolbarHtml: "P" },
					h1: { shortcut: "Ctrl+Alt+1", execCommand: "formatBlock", execCommandValue: ["<H1>"], toolbarHtml: "H<sub>1</sub>" },
					h2: { shortcut: "Ctrl+Alt+2", execCommand: "formatBlock", execCommandValue: ["<H2>"], toolbarHtml: "H<sub>2</sub>" },
					h3: { shortcut: "Ctrl+Alt+3", execCommand: "formatBlock", execCommandValue: ["<H3>"], toolbarHtml: "H<sub>3</sub>" },
					h4: { shortcut: "Ctrl+Alt+4", execCommand: "formatBlock", execCommandValue: ["<H4>"], toolbarHtml: "H<sub>4</sub>" },
					h5: { shortcut: "Ctrl+Alt+5", execCommand: "formatBlock", execCommandValue: ["<H5>"], toolbarHtml: "H<sub>5</sub>" },
					h6: { shortcut: "Ctrl+Alt+6", execCommand: "formatBlock", execCommandValue: ["<H6>"], toolbarHtml: "H<sub>6</sub>" },
					indent: { shortcut: "Tab", execCommand: "indent", toolbarHtml: "&rArr;" },
					outdent: { shortcut: ["Ctrl+Tab", "Shift+Tab"], execCommand: "outdent", toolbarHtml: "&lArr;" }
			},
			brOnReturn: false,
			showToolbar : false
	};

})(jQuery);
