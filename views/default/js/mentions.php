//<script>

elgg.provide('elgg.mentions');

elgg.mentions.getCursorPosition = function(el) {
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
}

elgg.mentions.handleResponse = function (json) {
	var userOptions = '';
	$(json).each(function(key, user) {
		userOptions += user.label;
	});
	
	if (!userOptions) {
		$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
		$('#mentions-popup').addClass('hidden');
		return;
	}

	$('#mentions-popup > .elgg-body').html(userOptions);
	$('#mentions-popup').removeClass('hidden');

	$('.mentions-popup .elgg-autocomplete-item').bind('click', function(e) {
		e.preventDefault();
		var userUrl = $(this).find('a').first().attr('href');
		var username = userUrl.split('/').pop();

		// Remove the partial @username string from the first part
		newBeforeMention = beforeMention.substring(0, position - current.length);

		// Add the complete @username string and the rest of the original
		// content after the first part
		var newContent = newBeforeMention + username + afterMention;

		// Set new content for the textarea
		if (mentionsEditor == 'ckeditor') {
			textarea.setData(newContent, function() {
				this.checkDirty(); // true
			});
		} else if (mentionsEditor == 'tinymce') {
			tinyMCE.activeEditor.setContent(newContent);
		} else {
			$(textarea).val(newContent);
		}

		// Hide the autocomplete popup
		$('#mentions-popup').addClass('hidden');
	});
}

elgg.mentions.autocomplete = function (content, position) {
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
	
	if (current.match(/@/) && current.length > 1) {
		current = current.replace('@', '');
		$('#mentions-popup').removeClass('hidden');

		var options = {success: elgg.mentions.handleResponse};

		elgg.get(elgg.config.wwwroot + 'livesearch?q=' + current + '&match_on=users', options);
	}
	else {
		$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
		$('#mentions-popup').addClass('hidden');
	}
}

elgg.mentions.init = function() {
	$('textarea').bind('keyup', function(e) {
		
		if (e.which == 8 || e.which == 13) {
			$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
			$('#mentions-popup').addClass('hidden');
		}
		else {
			textarea = $(this);
			content = $(this).val();
			position = elgg.mentions.getCursorPosition(this);
			mentionsEditor = 'textarea';

			elgg.mentions.autocomplete(content, position);
		}
	});

	/*
	 *  @Note - untested
	if (typeof CKEDITOR !== 'undefined') {
		CKEDITOR.on('instanceCreated', function (e) {
			e.editor.on('contentDom', function(ev) {
				ev.editor.document.on('keyup', function(eve) {
					textarea = e.editor;
					mentionsEditor = 'ckeditor';
					position = ev.editor.getSelection().getRanges()[0].startOffset;
					content = e.editor.document.getBody().getText();
					elgg.mentions.autocomplete(content, position);
				});
			})
		});
	}
	*/
   
	setTimeout(function () {
		if (typeof tinyMCE !== 'undefined') {
			for (var i = 0; i < tinymce.editors.length; i++) {
				 tinymce.editors[i].onKeyUp.add(function (ed, e) {
				
					mentionsEditor = 'tinymce';

					// Hide on backspace or enter
					if (e.keyCode == 8 || e.keyCode == 13) {
						$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
						$('#mentions-popup').addClass('hidden');
					} else {
						position = ed.selection.getRng(1).startOffset;
						content = tinyMCE.activeEditor.getContent({format : 'text'});
						
						elgg.mentions.autocomplete(content, position);
					}
				});
            }
		}
	}, 500);
};

elgg.register_hook_handler('init', 'system', elgg.mentions.init, 9999);