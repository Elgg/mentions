<?php
/**
 * Provides links and notifications for using @username mentions
 *
 * @package Mentions
 */

$user = elgg_get_logged_in_user_entity();
$notify_label = elgg_echo('mentions:settings:send_notification');

$notify_field = elgg_view('input/dropdown', array (
	'name' => 'params[notify]',
	'options_values' => array(
		1 => elgg_echo('option:yes'),
		0 => elgg_echo('option:no')
	),
	'value' => (int) elgg_get_plugin_user_setting('notify', $user->getGUID(), 'mentions')
));

echo <<<___END
<p>
	<label>$notify_label: $notify_field</label>
</p>
___END;
?>