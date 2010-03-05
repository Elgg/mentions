<?php
/**
 * Provides links and notifications for using @username mentions
 *
 * @package Mentions
 * @author Curverider Ltd <info@elgg.com>
 * @copyright Curverider Ltd 2008-2010
 * @link http://elgg.com/
 */

$user = get_loggedin_user();

if (FALSE === get_plugin_usersetting('notify', $user->getGUID(), 'mentions')) {
	set_plugin_usersetting('notify', TRUE, $user->getGUID(), 'mentions');
}

$notify_label = elgg_echo('mentions:settings:send_notification');

$notify_field = elgg_view('input/pulldown', array (
	'internalname' => 'params[notify]',
	'options_values' => array(
		1 => elgg_echo('option:yes'),
		0 => elgg_echo('option:no')
	),
	'value' => get_plugin_usersetting('notify', $user->getGUID(), 'mentions')
));

echo <<<___END
<p>
	<label>$notify_label: $notify_field</label>
</p>
___END;
?>