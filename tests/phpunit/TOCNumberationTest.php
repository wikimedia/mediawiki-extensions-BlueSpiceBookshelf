<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\TOCNumberation;
use PHPUnit\Framework\TestCase;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 *
 * @covers \BlueSpice\Bookshelf\TOCNumberation
 */
class TOCNumberationTest extends TestCase {

	/**
	 * @covers TOCNumberation::execute
	 */
	public function testExecute() {
		$input = file_get_contents( __DIR__ . '/data/toc_numberation_input.html' );
		$expected = file_get_contents( __DIR__ . '/data/toc_numberation_output.html' );

		$tocNumberation = new TOCNumberation();
		$actual = $tocNumberation->execute( 5, $input );

		$this->assertEquals( $expected, $actual );
	}
}
