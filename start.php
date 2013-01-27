<?php
/**
 * Provides links and notifications for using @username mentions
 *
 */

elgg_register_event_handler('init', 'system', 'mentions_init');

function mentions_init() {

	// Register our post processing hook
	elgg_register_plugin_hook_handler('output', 'page', 'mentions_rewrite');

	// can't use notification hooks here because of many reasons
	// only check against annotations:generic_comment and entity:object
	elgg_register_event_handler('create', 'object', 'mentions_notification_handler');
	elgg_register_event_handler('create', 'annotation', 'mentions_notification_handler');

	// @todo This will result in multiple notifications for an edited entity so we don't do this
	//register_elgg_event_handler('update', 'all', 'mentions_notification_handler');
}

function mentions_get_regex() {
	// @todo this won't work for usernames that must be html encoded.
	// get all chars with unicode 'letter' or 'mark' properties or a number _ or .,
	// preceeded by @, and possibly surrounded by word boundaries.
	return '/[\b]?@([\p{L}\p{M}_\.0-9]+)[\b]?/iu';
}

/**
 * Rewrites the page content for @username mentions.
 *
 * @param string $hook    The name of the hook
 * @param string $type    The type of the hook
 * @param string $content The content of the page
 * @return string
 */
function mentions_rewrite($hook, $type, $content) {
	return preg_replace_callback(
		mentions_get_regex(),
		create_function(
			'$matches',
			'
				if ($user = get_user_by_username($matches[1])) {
					return "<a href=\"{$user->getURL()}\">{$matches[0]}</a>";
				} else {
					return $matches[0];
				}
			'
		),
		$content
	);
}

/**
 * Catch all create events and scan for @username tags to notify user.
 *
 * @param string   $event
 * @param string   $type
 * @param ElggData $object
 * @return void
 */
function mentions_notification_handler($event, $type, $object) {
	global $CONFIG;

	if ($type == 'annotation' && $object->name != 'generic_comment') {
		return;
	}

	// excludes messages - otherwise an endless loop of notifications occur!
	if (elgg_instanceof($object, 'object', 'messages')) {
		return;
	}

	$fields = array(
		'title', 'description', 'value'
	);

	// store the guids of notified users so they only get one notification per creation event
	$notified_guids = array();

	foreach ($fields as $field) {
		$content = $object->$field;
		// it's ok in this case if 0 matches == FALSE
		if (preg_match_all(mentions_get_regex(), $content, $matches)) {
			// match against the 2nd index since the first is everything
			foreach ($matches[1] as $username) {

				if (!$user = get_user_by_username($username)) {
					continue;
				}

				if ($type == 'annotation') {
					if ($parent = get_entity($object->entity_guid)) {
						$access = has_access_to_entity($parent, $user);
					} else {
						continue;
					}
				} else {
					$access = has_access_to_entity($object, $user);
				}

				if ($user && $access && !in_array($user->getGUID(), $notified_guids)) {
					// if they haven't set the notification status default to sending.
					$notification_setting = elgg_get_plugin_user_setting('notify', $user->getGUID(), 'mentions');

					if (!$notification_setting && $notification_setting !== FALSE) {
						$notified_guids[] = $user->getGUID();
						continue;
					}

					// figure out the link
					switch($type) {
						case 'annotation':
							//@todo permalinks for comments?
							if ($parent = get_entity($object->entity_guid)) {
								$link = $parent->getURL();
							} else {
								$link = 'Unavailable';
							}
							break;
						default:
							$link = $object->getURL();
							break;
					}

					$owner = get_entity($object->owner_guid);
					$type_key = "mentions:notification_types:$type";
					if ($subtype = $object->getSubtype()) {
						$type_key .= ":$subtype";
					}

					$type_str = elgg_echo($type_key);
					$subject = sprintf(elgg_echo('mentions:notification:subject'), $owner->name, $type_str);

					// use the search function to pull out relevant parts of the content
					//$content = search_get_highlighted_relevant_substrings($content, "@{$user->username}");

					$body = sprintf(elgg_echo('mentions:notification:body'), $owner->name, $type_str, $content, $link);

					if (notify_user($user->getGUID(), $CONFIG->site->getGUID(), $subject, $body)) {
						$notified_guids[] = $user->getGUID();
					}
				}
			}
		}
	}
}
