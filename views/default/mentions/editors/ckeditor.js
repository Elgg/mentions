/**
 * CKEditor support for the mentions plugins
 */
define(function (require) {

	var elgg = require('elgg');
	elgg.register_hook_handler('prepare', 'ckeditor', function (hook, type, params, CKEDITOR) {
		var mentions = require('mentions/autocomplete');

		CKEDITOR.on('instanceCreated', function (e) {
			e.editor.on('contentDom', function (ev) {
				var editable = ev.editor.editable();

				editable.attachListener(editable, 'keyup', function (eve) {
					// Skip keycodes that cannot be used for entering a username
					if (!mentions.isValidKey(eve.data.$.keyCode)) {
						return;
					}

					content = e.editor.document.getBody().getText();
					position = ev.editor.getSelection().getRanges()[0].startOffset;

					mentions.autocomplete(content, position, function (newContent) {
						if (newContent == undefined) {
							return;
						}

						// Replace current content with one that
						// has the autocompleted username
						e.editor.setData(newContent, function () {
							this.checkDirty(); // true
						});
					});
				});
			});
		});
	});
});
