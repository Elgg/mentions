<?php

/**
 * Provides links and notifications for using @username mentions
 */
elgg_register_event_handler('init', 'system', 'mentions_init');

/**
 * Initialize
 * @return void
 */
function mentions_init() {
	elgg_extend_view('elgg.css', 'mentions/mentions.css');

	elgg_extend_view('input/longtext', 'mentions/popup');
	elgg_extend_view('input/plaintext', 'mentions/popup');

	elgg_extend_view('input/plaintext', 'mentions/input/plaintext');
	elgg_extend_view('input/longtext', 'mentions/input/longtext');
	
	elgg_register_event_handler('pagesetup', 'system', 'mentions_get_views');

	// can't use notification hooks here because of many reasons
	elgg_register_event_handler('create', 'object', 'mentions_notification_handler');
	elgg_register_event_handler('create', 'annotation', 'mentions_notification_handler');

	// @todo This will result in multiple notifications for an edited entity so we don't do this
	//register_elgg_event_handler('update', 'all', 'mentions_notification_handler');
	// add option to the personal notifications form
	elgg_extend_view('notifications/subscriptions/personal', 'mentions/notification_settings');
	elgg_register_plugin_hook_handler('action', 'notificationsettings/save', 'mentions_save_settings');

	elgg_register_page_handler('mentions', 'mentions_page_handler');
}

/**
 * Handler for /mentions
 * 
 * @param array $segments URL segments
 * @return bool
 */
function mentions_page_handler($segments) {

	$page = array_shift($segments);

	switch ($page) {
		case 'search' :
			$target_guid = array_shift($segments);
			echo elgg_view_resource('mentions/search', [
				'target_guid' => $target_guid,
			]);
			return true;
	}

	return false;
}

/**
 * Returns regex pattern for matching a @mention
 * @return string
 */
function mentions_get_regex() {
	return \Elgg\Mentions\Regex::getRegex();
}

/**
 * Registers hooks to replace @mentions with anchor tags in view output
 * @return void
 */
function mentions_get_views() {
	// allow plugins to add additional views to be processed for usernames
	$views = [
		'output/longtext',
		'object/elements/summary/content',
		'object/elements/full/body',

	];
	$views = elgg_trigger_plugin_hook('get_views', 'mentions', null, $views);
	foreach ($views as $view) {
		elgg_register_plugin_hook_handler('view', $view, 'mentions_rewrite');
	}

	elgg_register_plugin_hook_handler('view_vars', 'river/elements/body', 'mentions_rewrite_river_message');
}

/**
 * Rewrites a view for @username mentions.
 *
 * @param string $hook    "view"
 * @param string $type    View name
 * @param string $content View output
 * @return string
 */
function mentions_rewrite($hook, $type, $content) {

	$regexp = mentions_get_regex();
	$content = preg_replace_callback($regexp, 'mentions_preg_callback', $content);
	return $content;
}

/**
 * Rewrite mentions in river message
 * 
 * @param string $hook      "view_vars"
 * @param string $type      "river/elements/body"
 * @param array  $view_vars View vars
 * @param array  $params    Hook params
 * @return array
 */
function mentions_rewrite_river_message($hook, $type, $view_vars, $params) {

	$message = elgg_extract('message', $view_vars);
	if (!$message) {
		return;
	}

	$regexp = mentions_get_regex();
	$view_vars['message'] = preg_replace_callback($regexp, 'mentions_preg_callback', $message);

	return $view_vars;
}

/**
 * Used as a callback for the preg_replace in mentions_rewrite()
 *
 * @param array $matches Regex matches
 * @return string
 */
function mentions_preg_callback($matches) {

	$source = $matches[0];
	$preceding_char = $matches[1];
	$mention = $matches[2];
	$username = $matches[3];

	if (empty($username)) {
		return $source;
	}

	$user = get_user_by_username($username);

	// Catch the trailing period when used as punctuation and not a username.
	$period = '';
	if (!$user && substr($username, -1) == '.') {
		$user = get_user_by_username(rtrim($username, '.'));
		$period = '.';
	}

	if (!$user) {
		return $source;
	}

	if (elgg_get_plugin_setting('named_links', 'mentions', true)) {
		$label = $user->getDisplayName();
	} else {
		$label = $mention;
	}

	$icon = '';
	if (elgg_get_plugin_setting('fancy_links', 'mentions')) {
		$icon = elgg_view('output/img', array(
			'src' => $user->getIconURL('topbar'),
			'class' => 'pas mentions-user-icon'
		));
	}

	$replacement = elgg_view('output/url', array(
		'href' => $user->getURL(),
		'text' => $icon . $label,
		'class' => 'mentions-user-link',
	));

	return $preceding_char . $replacement . $period;
}

/**
 * Catch all create events and scan for @username tags to notify user.
 *
 * @param string                    $event      "create"
 * @param string                    $event_type "object"|"annotation"
 * @param ElggObject|ElggAnnotation $object     Created object or annotation
 * @return void
 */
function mentions_notification_handler($event, $event_type, $object) {

	$type = $object->getType();
	$subtype = $object->getSubtype();
	$owner = $object->getOwnerEntity();

	$type_key = "mentions:notification_types:$type:$subtype";
	if (!elgg_language_key_exists($type_key)) {
		// plugins can add to the list of mention objects by defining
		// the language string 'mentions:notification_types:<type>:<subtype>'
		return;
	}
	$type_str = elgg_echo($type_key);

	if ($object instanceof ElggAnnotation) {
		$fields = ['value'];
		$entity = $object->getEntity();
	} else {
		$fields = ['title', 'description'];
		$fields = elgg_trigger_plugin_hook('get_fields', 'mentions', ['entity' => $object], $fields);
		$entity = $object;
	}

	if (empty($fields)) {
		return;
	}

	$usernames = [];

	foreach ($fields as $field) {
		$content = $object->$field;
		if (is_array($content)) {
			$content = implode(' ', $content);
		}

		// it's ok in this case if 0 matches == FALSE
		if (preg_match_all(mentions_get_regex(), $content, $matches)) {
			// match against the 2nd index since the first is everything
			foreach ($matches[3] as $username) {
				if (empty($username)) {
					continue;
				}
				$usernames[] = $username;
			}
		}
	}

	$notified_guids = [];

	foreach ($usernames as $username) {
		$user = get_user_by_username($username);

		// check for trailing punctuation caught by the regex
		if (!$user && substr($username, -1) == '.') {
			$user = get_user_by_username(rtrim($username, '.'));
		}

		if (!$user) {
			continue;
		}

		if (in_array($user->guid, $notified_guids)) {
			continue;
		}

		$notified_guids[] = $user->guid;

		// if they haven't set the notification status default to sending.
		// Private settings are stored as strings so we check against "0"
		$notification_setting = elgg_get_plugin_user_setting('notify', $user->guid, 'mentions');
		if ($notification_setting === "0") {
			continue;
		}

		// user must have access to view object/annotation
		if (!has_access_to_entity($entity, $user)) {
			continue;
		}

		if ($user->language) {
			$language = $user->language;
		} else {
			$language = elgg_get_config('language');
		}

		$link = $object->getURL();

		$localized_type_str = elgg_echo($type_key, [], $language);
		$subject = elgg_echo('mentions:notification:subject', array($owner->name, $localized_type_str), $language);

		$body = elgg_echo('mentions:notification:body', array(
			$owner->name,
			$localized_type_str,
			$link,
				), $language);

		$params = array(
			'object' => $object,
			'action' => 'mention',
		);

		notify_user($user->guid, $owner->guid, $subject, $body, $params);
	}
}

/**
 * Saves notifications preferences for mentions from the settings form
 *
 * @param string $hook   "action"
 * @param string $type   "notificationsettings/save"
 * @param bool   $value  Proceed with action?
 * @param array  $params Hook params
 * @return void
 */
function mentions_save_settings($hook, $type, $value, $params) {
	$notify = (bool) get_input('mentions_notify');
	$user = get_entity(get_input('guid'));

	if (!elgg_set_plugin_user_setting('notify', $notify, $user->getGUID(), 'mentions')) {
		register_error(elgg_echo('mentions:settings:failed'));
	}
}
