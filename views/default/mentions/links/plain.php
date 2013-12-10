<?php

$trailing = elgg_extract('trailing', $vars, '');
$user = $vars['user'];
/* @var ElggUser $user */

echo elgg_view('output/url', array(
	'href' => $user->getURL(),
	'text' => "@{$user->username}",
	'encode_text' => true,
	'class' => 'mentions-plain-link',
));
echo $trailing;
