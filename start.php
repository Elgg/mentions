<?php
/**
 * Provides links and notifications for using @username mentions
 *
 * @package Mentions
 * @link http://elgg.com/
 */

/**
 * Init
 */
function mentions_init() {
	elgg_extend_view('css/elgg', 'css/mentions');

	// @todo this won't work for usernames that must be html encoded.
	// get all chars with unicode 'letter' or 'mark' properties or a number _ or .,
	// preceeded by @, and possibly surrounded by word boundaries.
	elgg_set_config('mentions_match_regexp', '/[\b]?@([\p{L}\p{M}_\.0-9]+)[\b]?/iu');

	// Register our post processing hook
	elgg_register_plugin_hook_handler('output', 'page', 'mentions_rewrite');

	// can't use notification hooks here because of many reasons
	// only check against annotations:generic_comment and entity:object
	elgg_register_event_handler('create', 'object', 'mentions_entity_notification_handler');
	elgg_register_event_handler('create', 'annotation', 'mentions_entity_notification_handler');

	// @todo This will result in multiple notifications for an edited entity
	// could put "guids notified" metadata on the entity to avoid this.
	//register_elgg_event_handler('update', 'all', 'mentions_entity_notification_handler');

	elgg_register_event_handler('annotate', 'all', 'mentions_annotation_notification_handler');
}

/**
 * Rewrites the view content for @username mentions.
 *
 * @param unknown_type $hook
 * @param unknown_type $entity_type
 * @param unknown_type $returnvalue
 * @param unknown_type $params
 * @return unknown_type
 */
function mentions_rewrite($hook, $entity_type, $returnvalue, $params) {

	$regexp = elgg_get_config('mentions_match_regexp');
	$returnvalue =  preg_replace_callback($regexp, 'mentions_preg_callback', $returnvalue);
	
	return $returnvalue;
}

/**
 * Used as a callback fro the preg_replace in mentions_rewrite()
 *
 * @param type $matches
 * @return type str
 */
function mentions_preg_callback($matches) {
	$user = get_user_by_username($matches[1]);

	// Catch the trailing period when used as punctuation and not a username.
	if (!$user && substr($matches[1], -1) == '.') {
		$user = get_user_by_username(rtrim($matches[1], '.'));
	}

	if ($user) {
		if (elgg_get_plugin_setting('fancy_links', 'mentions')) {
			$icon = "<img class='pas mentions-user-icon' src='" . $user->getIcon('topbar') ."' />";
			return "<a class='mentions-user-link' href=\"{$user->getURL()}\">$icon{$user->name}</a>";
		} else {
			return "<a href=\"{$user->getURL()}\">{$matches[0]}</a>";
		}
	} else {
		return $matches[0];
	}
}

/**
 * Catch all create events and scan for @username tags to notify user.
 *
 * @param unknown_type $event
 * @param unknown_type $type
 * @param unknown_type $object
 * @return unknown_type
 */
function mentions_entity_notification_handler($event, $type, $object) {
	$regexp = elgg_get_config('mentions_match_regexp');

	if ($type == 'annotation' && $object->name != 'generic_comment') {
		return NULL;
	}

	// excludes messages - otherwise an endless loop of notifications occur!
	if ($object->getSubtype() == "messages") {
		return NULL;
	}

	$fields = array(
		'name', 'title', 'description', 'value'
	);

	// store the guids of notified users so they only get one notification per creation event
	$notified_guids = array();

	foreach ($fields as $field) {
		$content = $object->$field;
		// it's ok in in this case if 0 matches == FALSE
		if (preg_match_all($regexp, $content, $matches)) {
			foreach ($matches[1] as $username) {

				$user = get_user_by_username($username);

				// check for trailing punctuation caught by the regex
				if (!$user && substr($username, -1) == '.') {
					$user = get_user_by_username(rtrim($username, '.'));
				}

				if (!$user) {
					continue;
				}

				if ($type == 'annotation') {
					$parent = get_entity($object->entity_guid);
					if ($parent) {
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
							$parent = get_entity($object->entity_guid);
							if ($parent) {
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
					$subtype = $object->getSubtype();
					if ($subtype) {
						$type_key .= ":$subtype";
					}

					$type_str = elgg_echo($type_key);
					$subject = elgg_echo('mentions:notification:subject', array($owner->name, $type_str));

					// use the search function to pull out relevant parts of the content
					//$content = search_get_highlighted_relevant_substrings($content, "@{$user->username}");

					$body = elgg_echo('mentions:notification:body', array($owner->name, $type_str, $content, $link));

					$site = elgg_get_config('site');
					if (notify_user($user->getGUID(), $site->getGUID(), $subject, $body)) {
						$notified_guids[] = $user->getGUID();
					}
				}
			}
		}
	}
}

elgg_register_event_handler('init', 'system', 'mentions_init');