<?php
/**
 * Provides information which editor plugin is used on the site
 *
 * Checks which editor plugin (if any) is enabled and provides
 * the information as an AMD module.
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

$json = json_encode(array('editor' => $editor));

echo "define($json)";
