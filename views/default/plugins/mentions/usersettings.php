<?php
/**
 * User setting for mentions
 */

$user = elgg_get_logged_in_user_entity();

// if user has never set this, default it to on
if (false === elgg_get_plugin_user_setting('notify', $user->getGUID(), 'mentions')) {
	elgg_set_plugin_user_setting('notify', 1, $user->getGUID(), 'mentions');
}

$notify_label = elgg_echo('mentions:settings:send_notification');

$notify_field = elgg_view('input/dropdown', array (
	'name' => 'params[notify]',
	'options_values' => array(
		1 => elgg_echo('option:yes'),
		0 => elgg_echo('option:no')
	),
	'value' => elgg_get_plugin_user_setting('notify', $user->getGUID(), 'mentions')
));

echo <<<___END
<div>
	<label>$notify_label $notify_field</label>
</div>
___END;
