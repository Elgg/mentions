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
	var callback;

 /**
  *  positionMentionPopup()
	*
	*  get position in pixels of the editor's cursor and place the mention popup relative to it.
	**/

	var positionMentionPopup = function() {
		if (typeof tinyMCE.activeEditor != "undefined")
		{
			var editor = tinyMCE.activeEditor;
			//get position of tinyMCE widgets
			var tinymcePosition = $(editor.getContainer()).position();
			var toolbarPosition = $(editor.getContainer()).find(".mce-toolbar").first();

			// get position of HTML node being edited

			var nodePosition = $(editor.selection.getNode()).position();
			var textareaTop = 0;
			var textareaLeft = 0;

			//We have Y-axis position (via nodePosition.top), it's time to get X:

			if (editor.selection.getRng().getClientRects().length > 0) {
	    	textareaTop = editor.selection.getRng().getClientRects()[0].top + 	editor.selection.getRng().getClientRects()[0].height;
	    	textareaLeft = editor.selection.getRng().getClientRects()[0].left;
			} else {
	    	textareaTop = parseInt($($this.selection.getNode()).css("font-size")) * 1.3 + 	nodePosition.top;
	    	textareaLeft = nodePosition.left;
			}
			//We have in textareaTop && textareaLeft positions of caret relative to the TinyMCE editor Window (textarea). Now it's time to get position relative to the whole page (browser window):

			var position = $(editor.getContainer()).offset();
			var caretPosition = {
	    	top:  tinymcePosition.top + toolbarPosition.innerHeight() + textareaTop,
	    	left: tinymcePosition.left + textareaLeft + position.left
			}

			$('#mentions-popup').css('top', caretPosition.top);
			$('#mentions-popup').css('left', caretPosition.left);
		}
		return;
	}

	/**
	 * Display AJAX response and provide new content for the editor
	 */
	var handleResponse = function (json) {
		var userOptions = '';
		$(json).each(function(key, user) {
			userOptions += '<li data-username="' + user.desc + '">' + user.label + "</li>";
		});

		if (!userOptions) {
			hide();
			return;
		}

		$('#mentions-popup > .elgg-body').html('<ul class="mentions-autocomplete">' + userOptions + "</ul>");
		$('#mentions-popup').removeClass('hidden');

		$('.mentions-autocomplete > li').bind('click', function(e) {
			e.preventDefault();

			var username = $(this).data('username');

			// Remove the partial @username string from the first part
			newBeforeMention = beforeMention.substring(0, sharedPosition - current.length);

			// Add the complete @username string and the rest of the original
			// content after the first part
			newContent = newBeforeMention + username + afterMention;
			// put line breaks back into text
			newContent = newContent.replace(/\r?\n|\r/g, '<br>');
			callback(newContent);

			// Hide the autocomplete popup
			hide();
		});
	};

	// check if the last word in the text is a username and if it is then open the autocomplete input for users
	var autocomplete = function (node, position, editorCallback) {
		if (node.data)
		{
			callback = editorCallback;
			var words;
			// declare a variable that can be accessed by other functions
			sharedPosition = position;
			beforeMention = node.data.substring(0,position);
			// duplicate this variable as we need to process it and also keep the original for use when processing the ajax response
			stringCount = beforeMention;
			afterMention = node.data.substring(position);

			// strip line breaks
			stringCount = stringCount.replace(/\r?\n|\r/g, ' ');

			// split the text into words, previous to the current character
			words = stringCount.split(' ');
			// remove empty values
			words = jQuery.grep(words, function(value) {
			  return value != "";
			});

			// grab the current word and get nothing if no words exist
			if (words.length > 0)
				current = words[words.length - 1];
			else
				current = '';

			// if the current word contains a @ symbol
			if (current.match(/@/) && current.length > 1) {
				// remove @ symbol from current to allow us to use current in a search query
				current = current.replace('@', '');

				var options = {success: handleResponse};

				// replace period characters with html encoded equivalent to prevent hanging
				current = encodeURIComponent(current.replace(/\./g, '&#46;'));

				// reposition mention popup next to cursor
				positionMentionPopup();

				// show mention popup
				$('#mentions-popup').removeClass('hidden');
				// search for a username matching the current word
				elgg.get('livesearch?q=' + current + '&match_on=users', options);
			}
		}
	};

	/**
	 * Check if entered key represents a valid character for a username
	 *
	 * 8  = backspace
	 * 13 = enter
	 * 32 = space
	 *
	 * @param {String} keyCode
	 * @return {Boolean}
	 */
	var isValidKey = function(keyCode) {
		var keyCodes = [8, 13, 32];

		if (keyCodes.indexOf(keyCode) == -1) {
			return true;
		} else {
			hide();
			return;
		}
	};

	/**
	 * Hide the autocomplete results
	 */
	var hide = function() {
		$('#mentions-popup > .elgg-body').html('<div class="elgg-ajax-loader"></div>');
		$('#mentions-popup').addClass('hidden');
	};

	return {
		autocomplete: autocomplete,
		isValidKey: isValidKey
	};
});
