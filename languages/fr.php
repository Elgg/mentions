<?php
/**
 * French language translation.
 */

$french = array(
	'mentions:notification:subject' => "%s vous a mentionné dans %s",
	'mentions:notification:body' => "%s vous a mentionné dans %s.

Pour voir la publication complète, cliquez sur le lien suivant :
%s
",
	'mentions:notification_types:object:blog' => "un article de blog",
	'mentions:notification_types:object:bookmarks' => "un lien web",
	'mentions:notification_types:object:groupforumtopic' => "un sujet de forum",
	'mentions:notification_types:object:discussion_reply' => "une réponse à un sujet de forum",
	'mentions:notification_types:object:thewire' => "un message du Fil",
	'mentions:notification_types:object:comment' => "un commentaire",
	'mentions:settings:send_notification' => "Envoyer une notification quand quelqu'un vous @mentionne dans une publication ?",

	// admin
	'mentions:fancy_links' => "Remplacer les @mentions par une vignette de l'utilisateur en plus de son nom",

	'mentions:settings:failed' => "Impossible d'enregigtrer les paramètres des mentions.",
);

add_translation("fr", $french);
