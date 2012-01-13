<?php
/**
 * English language translation.
 */

$english = array(
	'mentions:send_notification' => 'Send a notification when someone mentions you in a post?',
	'mentions:notification:subject' => '%s mentioned you in %s!',
	'mentions:notification:body' => '%s mentioned you in %s:

"%s"

To see the full post, click on the link below:
%s
',
	'mentions:notification_types:object:blog' => 'a blog post',
	'mentions:notification_types:object:conversations' => 'a conversation',
	'mentions:notification_types:annotation:generic_comment' => 'a comment',
	'mentions:notification_types:annotation:wire_reply' => 'a conversation reply',

	'mentions:settings:send_notification' => 'Send a notification when someone mentions you in a post?',
);

add_translation("en", $english);