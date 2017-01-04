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