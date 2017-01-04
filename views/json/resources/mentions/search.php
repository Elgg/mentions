<?php

elgg_set_http_header('Content-type: application/json');

// Use defaulf viewtype so we can render user listings
elgg_set_viewtype('default');

$q = get_input('q', '');

$q = sanitise_string($q);
// replace mysql vars with escaped strings
$q = str_replace(array('_', '%'), array('\_', '\%'), $q);

$target_guid = elgg_extract('target_guid', $vars);
$target = get_entity($target_guid);

$user_guid = elgg_get_logged_in_user_guid();

$dbprefix = elgg_get_config('dbprefix');

$options = [
	'type' => 'user',
	'joins' => [
		"JOIN {$dbprefix}users_entity ue ON e.guid = ue.guid",
	],
	'order_by' => 'ue.name ASC',
	'wheres' => [
		"ue.banned = 'no'",
		"e.guid != $user_guid",
	],
	'batch' => true,
];

if ($q) {
	$options['wheres'][] = "(ue.name LIKE '$q%' OR ue.name LIKE '% $q%' OR ue.username LIKE '$q%')";
	if (strlen($q) >= 3) {
		$options['limit'] = 0;
	}
}

if ($target instanceof ElggGroup && elgg_get_plugin_setting('restrict_group_search', 'mentions')) {
	$options['wheres'][] = "
		EXISTS (SELECT 1
				FROM {$dbprefix}entity_relationships
				WHERE guid_one = e.guid
				AND relationship = 'member'
				AND guid_two = $target->guid)
		";
} else if (elgg_get_plugin_setting('friends_only_search', 'mentions') && !elgg_check_access_overrides()) {
	$options['wheres'][] = "
		EXISTS (SELECT 1
				FROM {$dbprefix}entity_relationships
				WHERE guid_one = $user_guid
				AND relationship = 'friend'
				AND guid_two = e.guid)
		";
}

$users = elgg_get_entities($options);

$results = [];

foreach ($users as $user) {

	$icon = elgg_view('output/img', [
		'src' => $user->getIconURL('small'),
		'alt' => $user->getDisplayName(),
	]);

	$output = elgg_view('object/elements/summary', [
		'icon' => $icon,
		'title' => $user->name,
		'subtitle' => "@{$user->username}",
		'tags' => false,
		'metadata' => '',
		'content' => '',
		'class' => 'elgg-autocomplete-item',
		'entity' => $user,
	]);

	$results[] = [
		'type' => 'user',
		'name' => $user->name,
		'desc' => $user->username,
		'guid' => $user->guid,
		'label' => $output,
		'value' => $user->username,
	];
}

echo json_encode($results);
