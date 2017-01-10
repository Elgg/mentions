<?php
/**
 * Plugin settings for mentions
 */
$entity = elgg_extract('entity', $vars);
?>
<div>
	<label>
		<?php
		echo elgg_view('input/checkbox', array(
			'name' => 'params[named_links]',
			'value' => 1,
			'checked' => (bool) $entity->named_links,
		));
		echo elgg_echo('mentions:named_links');
		?>
	</label>
</div>

<div>
	<label>
		<?php
		echo elgg_view('input/checkbox', array(
			'name' => 'params[fancy_links]',
			'value' => 1,
			'checked' => (bool) $entity->fancy_links,
		));
		echo elgg_echo('mentions:fancy_links');
		?>
	</label>
</div>

<?php
echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('mentions:restrict_group_search'),
	'name' => 'params[restrict_group_search]',
	'value' => 1,
	'checked' => (bool) $entity->restrict_group_search,
]);

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('mentions:friends_only_search'),
	'name' => 'params[friends_only_search]',
	'value' => 1,
	'checked' => (bool) $entity->friends_only_search,
]);
