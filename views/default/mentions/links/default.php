<?php

$trailing = elgg_extract('trailing', $vars, '');
$user = $vars['user'];
/* @var ElggUser $user */

echo elgg_view('output/url', array(
	'href' => $user->getURL(),
	'text' => $user->name,
	'class' => 'mentions-default-link',
));
echo $trailing;
