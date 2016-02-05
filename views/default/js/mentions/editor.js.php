<?php
/**
 * Provides different @mentions module depending on the user editor
 *
 * Checks which editor plugin (if any) is enabled and provides the
 * AMD module that takes care of autocompleting @mentions for that
 * specific editor.
 */

$plugins = array(
	'ckeditor',
	'tinymce',
);

$editor = 'plaintext';
foreach ($plugins as $plugin) {
	if (elgg_is_active_plugin($plugin)) {
		$editor = $plugin;
	}
}

echo elgg_view("js/mentions/editors/{$editor}");
