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
	elgg_extend_view('css/elgg', 'css/mentions');

	elgg_register_simplecache_view('js/mentions/editor');
	elgg_require_js('mentions/editor');

	elgg_extend_view('input/longtext', 'mentions/popup');
	elgg_extend_view('input/plaintext', 'mentions/popup');

	elgg_register_event_handler('pagesetup', 'system', 'mentions_get_views');

	// can't use notification hooks here because of many reasons
	elgg_register_event_handler('create', 'object', 'mentions_notification_handler');
	elgg_register_event_handler('create', 'annotation', 'mentions_notification_handler');

	// @todo This will result in multiple notifications for an edited entity so we don't do this
	//register_elgg_event_handler('update', 'all', 'mentions_notification_handler');

	// add option to the personal notifications form
	elgg_extend_view('notifications/subscriptions/personal', 'mentions/notification_settings');
	elgg_register_plugin_hook_handler('action', 'notificationsettings/save', 'mentions_save_settings');
}

/**
 * Returns regex pattern for matching a @mention
 * @return string
 */
function mentions_get_regex() {
	// @todo this won't work for usernames that must be html encoded.
	// get all chars with unicode 'letter' or 'mark' properties or a number _ or .,
	// preceeded by @, and possibly surrounded by word boundaries.
	return '/[\b]?@([\p{L}\p{M}_\.0-9]+)[\b]?/iu';
}

/**
 * Registers hooks to replace @mentions with anchor tags in view output
 * @return void
 */
function mentions_get_views() {
	// allow plugins to add additional views to be processed for usernames
	$views = array('output/longtext');
	$views = elgg_trigger_plugin_hook('get_views', 'mentions', null, $views);
	foreach ($views as $view) {
		elgg_register_plugin_hook_handler('view', $view, 'mentions_rewrite');
	}
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
	$text =  preg_replace_callback($regexp, 'mentions_preg_callback', $text);

	return $text;
}

/**
 * Used as a callback for the preg_replace in mentions_rewrite()
 *
 * @param array $matches Regex matches
 * @return string
 */
function mentions_preg_callback($matches) {
	$user = get_user_by_username($matches[1]);
	$period = '';
	$icon = '';

	// Catch the trailing period when used as punctuation and not a username.
	if (!$user && substr($matches[1], -1) == '.') {
		$user = get_user_by_username(rtrim($matches[1], '.'));
		$period = '.';
	}

	if ($user) {
		if (elgg_get_plugin_setting('fancy_links', 'mentions')) {
			$icon = elgg_view('output/img', array(
				'src' => $user->getIconURL('topbar'),
				'class' => 'pas mentions-user-icon'
			));
			$replace = elgg_view('output/url', array(
				'href' => $user->getURL(),
				'text' => $icon . $user->name,
				'class' => 'mentions-user-link'
			));
		} else {
			$replace = elgg_view('output/url', array(
				'href' => $user->getURL(),
				'text' => $user->name,
			));
		}

		return $replace .= $period;
	} else {
		return $matches[0];
	}
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
			foreach ($matches[1] as $username) {
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