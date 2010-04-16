<?php
/**
 * Provides links and notifications for using @username mentions
 *
 * @package Mentions
 * @author Curverider Ltd <info@elgg.com>
 * @copyright Curverider Ltd 2008-2010
 * @link http://elgg.com/
 */
function mentions_init() {
	global $CONFIG;

	$CONFIG->mentions_match_regexp = '/[\b]?@([^\/\\"\'\*&\?#&\^\(\){}\[\]~<>;|@\-\+= \.,]+)[\b]?/i';

	// Register our post processing hook
	register_plugin_hook('display', 'view', 'mentions_rewrite');

	// a list of language key / view path arrays to accept for rewriting
	// @todo define a list of views to scan so we don't scan them all.
//	$CONFIG->mentions_available_objects = array(
//		'blog' => array(),
//		'group' => array(),
//		'group_discussion' => array(),
//		'thewire' => array(),
//		'pages' => array(),
//		'files' => array(),
//		'bookmarks' => array()
//	);


	// can't use notification hooks here because of many reasons
	// registering all/create to do the scanning
	register_elgg_event_handler('create', 'all', 'mentions_entity_notification_handler');

	register_elgg_event_handler('annotate', 'all', 'mentions_annotation_notification_handler');
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
	global $CONFIG;

	$view = $params['view'];

	//return $returnvalue;
	if ($view) {
		$returnvalue =  preg_replace_callback($CONFIG->mentions_match_regexp,
			create_function(
				'$matches',
				'
					global $CONFIG;
					if ($user = get_user_by_username($matches[1])) {
						return "<a href=\"{$user->getURL()}\">{$matches[0]}</a>";
					} else {
						return $matches[0];
					}
				'
		), $returnvalue);

		return $returnvalue;
	}
}

/**
 * Catch all create events and scan for @username tags to notify user.
 *
 * @param unknown_type $event
 * @param unknown_type $object_type
 * @param unknown_type $object
 * @return unknown_type
 */
function mentions_entity_notification_handler($event, $object_type, $object) {
	global $CONFIG;
	$fields = array(
		'title', 'description', 'value'
	);

	// store the guids of notified users so they only get one notification per creation event
	$notified_guids = array();

	foreach ($fields as $field) {
		$content = $object->$field;
		// it's ok in in this case if 0 matches == FALSE
		if (preg_match_all($CONFIG->mentions_match_regexp, $content, $matches)) {
			// match against the 2nd index since the first is everything
			foreach ($matches[1] as $username) {

				if ($object_type == 'annotation') {
					if ($parent = get_entity($object->entity_guid)) {
						$access = has_access_to_entity($parent, $user);
					} else {
						continue;
					}
				} else {
					$access = has_access_to_entity($object, $user);
				}

				if (($user = get_user_by_username($username)) && !in_array($user->getGUID(), $notified_guids)
				&& $access) {
					// if they haven't set the notification status default to sending.
					$notification_setting = get_plugin_usersetting('notify', $user->getGUID(), 'mentions');

					if (!$notification_setting && $notification_setting !== FALSE) {
						$notified_guids[] = $user->getGUID();
						continue;
					}

					// figure out the link
					switch($object_type) {
						case 'annotation':
							if ($parent = get_entity($object->entity_guid)) {
								$link = $object->getURL();
							} else {
								$link = 'Unavailable';
							}
							break;
						default:
							$link = $object->getURL();
							break;
					}

					$owner = get_entity($object->owner_guid);
					$type_key = "mentions:notification_types:$object_type";
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

register_elgg_event_handler('init', 'system', 'mentions_init');