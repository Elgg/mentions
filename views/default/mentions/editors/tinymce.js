/**
 * Mentions support for the TinyMCE editor
 */
define(function(require) {
	var mentions = require('mentions/autocomplete');

	// Give some time for the TinyMCE to load
	setTimeout(function () {
		for (var i = 0; i < tinymce.editors.length; i++) {
			var editor = tinymce.editors[i];

			 editor.on('keyup', function (e) {
				// Skip keycodes that cannot be used for entering a username
			 	if (!mentions.isValidKey(e.keyCode)) {
			 		return;
			 	}

				position = editor.selection.getRng(1).startOffset;
				content = tinyMCE.activeEditor.getContent({format : 'text'});

				mentions.autocomplete(content, position, function(content) {
					tinyMCE.activeEditor.setContent(content);
				});
			});
		}
	}, 500);
});
