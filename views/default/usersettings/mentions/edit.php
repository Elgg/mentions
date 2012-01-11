<?php
/**
 * Provides links and notifications for using @username mentions
 *
 * @package Mentions
 * @author Curverider Ltd <info@elgg.com>
 * @copyright Curverider Ltd 2008-2010
 * @link http://elgg.com/
 */

$user = elgg_get_logged_in_user_entity();

if (FALSE === elgg_get_plugin_user_setting('notify', $user->getGUID(), 'mentions')) {
	elgg_set_plugin_user_setting('notify', TRUE, $user->getGUID(), 'mentions');
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
<p>
	<label>$notify_label: $notify_field</label>
</p>
___END;
?>