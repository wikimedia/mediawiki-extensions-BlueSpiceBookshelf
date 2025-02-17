<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\NumberTOC;
use PHPUnit\Framework\TestCase;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 *
 * @covers \BlueSpice\Bookshelf\NumberTOC
 */
class NumberTOCTest extends TestCase {

	/**
	 * @covers \BlueSpice\Bookshelf\NumberTOC::execute
	 */
	public function testExecute() {
		$input = file_get_contents( __DIR__ . '/data/number_toc_input.html' );
		$expected = file_get_contents( __DIR__ . '/data/number_toc_output.html' );

		$numberToc = new NumberTOC();
		$actual = $numberToc->execute( 5, $input );

		$this->assertEquals( $expected, $actual );
	}
}
