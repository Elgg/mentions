//<script>

/**
 * Autocomplete @mentions
 *
 * Fetch and display a list of matching users when writing a @mention and
 * autocomplete the selected user.
 */
define(function(require) {
	var $ = require('jquery');
	var elgg = require('elgg');

	if (require.specified('ckeditor')) {
		require(['ckeditor'], function(CKEDITOR) {
			CKEDITOR.on('instanceLoaded', function (e) {
			//CKEDITOR.on('instanceCreated', function (e) {
				e.editor.on('contentDom', function(ev) {
					var editable = ev.editor.editable();

					editable.attachListener(editable, 'keyup', function() {
						textarea = e.editor;
						mentionsEditor = 'ckeditor';
						content = e.editor.document.getBody().getText();
						html_content = e.editor.getData();
						autocomplete(content, html_content);
					});
				});
			});
		});
	};

	var getCursorPosition = function(el) {
		var pos = 0;

		if ('selectionStart' in el) {
			pos = el.selectionStart;
		} else if ('selection' in document) {
			el.focus();
			var Sel = document.selection.createRange();
			var SelLength = document.selection.createRange().text.length;
			Sel.moveStart('character', - el.value.length);
			pos = Sel.text.length - SelLength;
		}

		return pos;
	};

	var handleResponse = function (json) {
		var userOptions = '';
		$(json).each(function(key, user) {
			userOptions += '<li data-username="' + user.desc + '">' + user.label + "</li>";
		});

		if (!userOptions) {
			$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
			$('#mentions-popup').addClass('hidden');
			return;
		}

		$('#mentions-popup > .elgg-body').html('<ul class="mentions-autocomplete">' + userOptions + "</ul>");
		$('#mentions-popup').removeClass('hidden');

		$('.mentions-autocomplete > li').bind('click', function(e) {
			e.preventDefault();

			var username = $(this).data('username');

			// Set new content for the textarea
			if (mentionsEditor == 'ckeditor') {
				var textBefore=html_content.substring(0,html_content.lastIndexOf(' @'+current))
				var newContent = textBefore+' @'+username;
				textarea.setData(newContent, function() {
				this.checkDirty(); // true
				});
			} else if (mentionsEditor == 'tinymce') {
				// Remove the partial @username string from the first part
				newBeforeMention = beforeMention.substring(0, position - current.length);

				// Add the complete @username string and the rest of the original
				// content after the first part
				var newContent = newBeforeMention + username + afterMention;
				tinyMCE.activeEditor.setContent(newContent);
			} else {
				// Remove the partial @username string from the first part
				newBeforeMention = beforeMention.substring(0, position - current.length);

				// Add the complete @username string and the rest of the original
				// content after the first part
				var newContent = newBeforeMention + username + afterMention;
				$(textarea).val(newContent);
			}

			// Hide the autocomplete popup
			$('#mentions-popup').addClass('hidden');
		});
	};

	var autocomplete = function (content, position_or_data) {
		if (mentionsEditor == 'ckeditor') {
			edata = position_or_data;
			current = "";
			if (content.length != 0) {
				//Split to get last item
				var words = content.split(/ +/);
				var html_words = edata.split(/ +|\r\n|\n/);
				var last_word = words[words.length -1];
				var last_html_word = html_words[html_words.length -2];
				if (last_word.match(/^@\w{3,}/)&& (last_html_word == last_word+'</p>' || last_html_word == '<p>'+last_word+'</p>')) {
					var search_word = last_word.replace('@','');
					if (! search_word.match(/\W/)) {
						current = last_word;
					};
				};
			};
		} else {
			position = position_or_data;
			beforeMention = content.substring(0, position);
			afterMention = content.substring(position);
			parts = beforeMention.split(' ');
			current = parts[parts.length - 1];

			precurrent = false;
			if (parts.length > 1) {
				precurrent = parts[parts.length - 2];
				if (!current.match(/@/)) {
					if (precurrent.match(/@/)) {
						current = precurrent + ' ' + current;
					}
				}
			}
		}

		if (current.match(/@/) && current.length > 4) {
			current = current.replace('@', '');
			$('#mentions-popup').removeClass('hidden');

			var options = {success: handleResponse};

			elgg.get(elgg.config.wwwroot + 'livesearch?q=' + current + '&match_on=users', options);
		} else {
			$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
			$('#mentions-popup').addClass('hidden');
		}
	};

	var init = function() {
		$('textarea').bind('keyup', function(e) {

			// Hide on backspace and enter
			if (e.which == 8 || e.which == 13) {
				$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
				$('#mentions-popup').addClass('hidden');
			} else {
				textarea = $(this);
				content = $(this).val();
				position = getCursorPosition(this);
				mentionsEditor = 'textarea';

				autocomplete(content, position);
			}
		});

		setTimeout(function () {
			if (typeof tinyMCE !== 'undefined') {
				for (var i = 0; i < tinymce.editors.length; i++) {
					 tinymce.editors[i].onKeyUp.add(function (ed, e) {

						mentionsEditor = 'tinymce';

						// Hide on backspace and enter
						if (e.keyCode == 8 || e.keyCode == 13) {
							$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
							$('#mentions-popup').addClass('hidden');
						} else {
							position = ed.selection.getRng(1).startOffset;
							content = tinyMCE.activeEditor.getContent({format : 'text'});

							autocomplete(content, position);
						}
					});
				}
			}
		}, 500);
	};

	elgg.register_hook_handler('init', 'system', init, 9999);

	return {
		autocomplete: autocomplete
	};
});
