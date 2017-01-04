<?php

$plugins = [
	'ckeditor',
	'tinymce',
];

foreach ($plugins as $plugin) {
	if (elgg_is_active_plugin($plugin)) {
		elgg_require_js("mentions/editors/$plugin");
	}
}