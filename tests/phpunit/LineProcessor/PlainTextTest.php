<?php

namespace BlueSpice\Bookshelf\Tests\LineProcessor;

use BlueSpice\Bookshelf\LineProcessor\PlainText;
use BlueSpice\Bookshelf\TreeNode;
use PHPUnit\Framework\TestCase;

/**
 * @group Database
 * @group BlueSpice
 * @group BlueSpiceBookshelf
 */
class PlainTextTest extends TestCase {

	/**
	 * @param string $line
	 * @param string $message
	 * @covers \BlueSpice\Bookshelf\LineProcessor\PlainText::applies
	 * @dataProvider provideTestAppliesData
	 */
	public function testApplies( $line, $message ) {
		$processor = new PlainText();

		$this->assertTrue( $processor->applies( $line ), $message );
	}

	/**
	 *
	 * @return array
	 */
	public function provideTestAppliesData() {
		return [
			[ '', 'Should process emtpy lines' ],
			[ 'Any Value ', 'Should process any value' ],
			[ '[[Some link]]', 'Should process even a wikitext link' ]
		];
	}

	/**
	 *
	 * @param string $line
	 * @covers \BlueSpice\Bookshelf\LineProcessor\PlainText::process
	 * @dataProvider provideTestProcessData
	 */
	public function testProcess( $line ) {
		$parser = new PlainText();
		$node = $parser->process( $line );

		$this->assertInstanceOf( TreeNode::class, $node );
		$this->assertEquals( 'plain-text', $node['type'], "Should have proper `type` set" );
		$this->assertEquals( $node['title'], $line, "Should have proper `title` set" );
	}

	/**
	 *
	 * @return array
	 */
	public function provideTestProcessData() {
		return [
			[ '' ],
			[ 'Any Value' ],
			[ '[[Some link]]' ]
		];
	}
}
