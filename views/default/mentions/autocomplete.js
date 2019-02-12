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
	var Ajax = require('elgg/Ajax');
	var ajax = new Ajax(false);
	var callback;

	/**
	 * Display AJAX response and provide new content for the editor
	 */
	var handleResponse = function (json) {
		var userOptions = '';
		$(json).each(function(key, user) {
			userOptions += '<li data-username="' + user.value + '">' + user.label + "</li>";
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
			newBeforeMention = beforeMention.substring(0, position - current.length);

			// Add the complete @username string and the rest of the original
			// content after the first part
			newContent = newBeforeMention + username + afterMention;

			callback(newContent);

			// Hide the autocomplete popup
			hide();
		});
	};

	var autocomplete = function (content, position, editorCallback) {
		callback = editorCallback;

		beforeMention = content.substring(0, position);
		afterMention = content.substring(position);
		parts = beforeMention.split(' ');
		current = parts[parts.length - 1];

		precurrent = false;
		if (parts.length > 1) {
			precurrent = parts[parts.length - 1];

			if (!current.match(/@/)) {
				if (precurrent.match(/@/)) {
					current = precurrent + ' ' + current;
				}
			}
		}

		if (current.match(/@/) && current.length > 1) {
			current = current.replace('@', '');
			$('#mentions-popup').removeClass('hidden');

			var target_guid = elgg.get_page_owner_guid();
			ajax.path('mentions/search/' + target_guid, {
				data: {
					q: current,
					view: 'json'
				},
			}).done(handleResponse);
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