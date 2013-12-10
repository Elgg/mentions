<?php

$trailing = elgg_extract('trailing', $vars, '');
$user = $vars['user'];
/* @var ElggUser $user */

$icon = elgg_view('output/img', array(
	'src' => $user->getIconURL('topbar'),
	'class' => 'pas mentions-user-icon',
));

echo elgg_view('output/url', array(
	'href' => $user->getURL(),
	'text' => $icon . $user->name,
	'class' => 'mentions-user-link mentions-fancy-link',
));
echo $trailing;
