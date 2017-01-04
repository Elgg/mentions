<?php

namespace Elgg\Mentions;

class RegexTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Test regex pattern
	 * 
	 * @dataProvider testRegexProvider
	 * 
	 * @param string $content    Source text or html
	 * @param array  $expected Matched username mentions
	 */
	public function testRegex($content, $expected) {

		$actual = [];
		if (preg_match_all(Regex::getRegex(), $content, $matches)) {
			// match against the 2nd index since the first is everything
			foreach ($matches[3] as $username) {
				if (empty($username)) {
					continue;
				}
				$actual[] = $username;
			}
		}

		$this->assertEquals($expected, $actual);
	}

	public function testRegexProvider() {
		return [
			[
				'Mentioned @username',
				['username'],
			],
			[
				'Mentioned <a href="http://example.com/@username">@username</a>',
				[],
			],
			[
				'Some <span data-attr="@username">text</span>',
				[],
			],
			[
				'@username, you rock!',
				['username'],
			],
			[
				'Mentioned @username.',
				['username.'],
			],
			[
				'Mentioned @username!',
				['username'],
			],
			[
				'Mentioned @username-1 and @username.2, as well as @username_3',
				['username-1', 'username.2', 'username_3'],
			],
		];
	}

}
