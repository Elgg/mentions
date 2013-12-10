<?php
/**
 * Plugin settings for mentions
 */

$label = elgg_echo('mentions:link_style_label') . ' ';

$options_values = array();
foreach (array('default', 'fancy', 'plain') as $style) {
	$options_values[$style] = elgg_echo("mentions:link_style:$style");
}

$select_view = elgg_view_exists('input/select') ? 'input/select' : 'input/dropdown';

$input = elgg_view($select_view, array(
	'name' => 'params[link_style]',
	'options_values' => $options_values,
	'value' => mentions_get_link_style(),
));

?>
<label>
	<?php
	echo $label;
	echo $input;
	?>
</label>