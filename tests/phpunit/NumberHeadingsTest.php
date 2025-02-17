<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\NumberHeadings;
use PHPUnit\Framework\TestCase;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 *
 * @covers \BlueSpice\Bookshelf\NumberHeadings
 */
class NumberHeadingsTest extends TestCase {

	/**
	 * @covers \BlueSpice\Bookshelf\NumberHeadings::execute
	 */
	public function testExecute() {
		$input = file_get_contents( __DIR__ . '/data/number_heading_input.html' );
		$expected = file_get_contents( __DIR__ . '/data/number_heading_output.html' );

		$numberHeadings = new NumberHeadings();
		$actual = $numberHeadings->execute( 5, $input );

		$this->assertEquals( $expected, $actual );
	}
}
