<?php

$plugins = array(
	'ckeditor',
	'tinymce',
	'extended_tinymce'
);

$editor = 'plaintext';
foreach ($plugins as $plugin) {
	if (elgg_is_active_plugin($plugin)) {
		$editor = $plugin;
	}
}
if ($editor == 'extended_tinymce')
	$editor = 'tinymce';

elgg_require_js("mentions/editors/{$editor}");

$vars = array(
	'class' => 'mentions-popup hidden',
	'id' => 'mentions-popup',
);

echo elgg_view_module('popup', '', elgg_view('graphics/ajax_loader', array('hidden' => false)), $vars);
