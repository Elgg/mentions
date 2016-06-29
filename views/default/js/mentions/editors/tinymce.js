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
				var sel = tinymce.activeEditor.getWin().getSelection(), // current selection
						position = sel.anchorOffset, // get caret start position
						node = sel.anchorNode; // get the current #text node

				mentions.autocomplete(node, position, function(content) {
					tinymce.activeEditor.selection.select(tinymce.activeEditor.selection.getNode(),true);
					tinymce.activeEditor.selection.setContent(content, {format : 'raw'});
				});
			});
		}
	}, 5000);
});
