<?php

namespace Elgg\Mentions;

class Regex {

	/**
	 * Returns regex pattern for matching a @mention
	 * @return string
	 */
	public static function getRegex() {
		
		// match anchor tag with all attributes and wrapped html
		// we want to exclude matches that have already been wrapped in an anchor
		$match_anchor = "<a[^>]*?>.*?<\/a>";

		// match tag name and attributes
		// we want to exclude matches that found within tag attributes
		$match_attr = "<.*?>";

		// match username followed by @
		$match_username = "(@([\p{L}\p{Nd}._-]+))";

		// match at least one space or punctuation char before a match
		$match_preceding_char = "(^|\s|\!|\.|\?|>|\G)+";

		return "/{$match_anchor}|{$match_attr}|{$match_preceding_char}{$match_username}/i";
	}

}
