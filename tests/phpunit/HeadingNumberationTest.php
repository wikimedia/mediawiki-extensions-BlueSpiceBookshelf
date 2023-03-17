<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\HeadingNumberation;
use PHPUnit\Framework\TestCase;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 *
 * @covers \BlueSpice\Bookshelf\HeadingNumberation
 */
class HeadingNumberationTest extends TestCase {

	/**
	 * @covers HeadingNumberation::execute
	 */
	public function testExecute() {
		$input = file_get_contents( __DIR__ . '/data/heading_numberation_input.html' );
		$expected = file_get_contents( __DIR__ . '/data/heading_numberation_output.html' );

		$headingNumberation = new HeadingNumberation();
		$actual = $headingNumberation->execute( 5, $input );

		$this->assertEquals( $expected, $actual );
	}
}
