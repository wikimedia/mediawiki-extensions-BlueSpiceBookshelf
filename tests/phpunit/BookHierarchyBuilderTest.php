<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\BookHierarchyBuilder;
use BlueSpice\Bookshelf\ChapterDataModel;
use PHPUnit\Framework\TestCase;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 *
 * @covers \BlueSpice\Bookshelf\BookHierarchyBilder
 */
class BookHierarchyBuilderTest extends TestCase {

	/**
	 * @covers BookHierarchyBilderTest::build
	 */
	public function testBuild() {
		$input = $this->getInput();
		$expected = $this->getExpected();

		$builder = new BookHierarchyBuilder();

		$actual = $builder->build( $input );
		$this->assertEquals( $expected, $actual );

		# $test = $builder->isChild( '2.1.1', '2.1' );
		#$this->assertEquals( true, $test );
	}

	/**
	 * @return array
	 */
	private function getExpected(): array {
		return [
		[
		  'chapter_namespace' => '0',
		  'chapter_title' => 'Test_chap_A',
		  'chapter_name' => 'My display title',
		  'chapter_number' => '1',
		  'chapter_type' => 'wikilink-with-alias',
		  'chapter_children' => [
			[
			  'chapter_namespace' => '0',
			  'chapter_title' => 'Test_chap B',
			  'chapter_name' => 'Test chap B',
			  'chapter_number' => '1.1',
			  'chapter_type' => 'wikilink-with-alias',
			  'chapter_children' => [
				[
				  'chapter_namespace' => '0',
				  'chapter_title' => 'Test_chap_C',
				  'chapter_name' => 'Test_chap_C',
				  'chapter_number' => '1.1.1',
				  'chapter_type' => 'wikilink-with-alias',
				  'chapter_children' => [
					[
					  'chapter_namespace' => null,
					  'chapter_title' => null,
					  'chapter_name' => 'My plain text label 1',
					  'chapter_number' => '1.1.1.1',
					  'chapter_type' => 'plain-text',
					  'chapter_children' => [
						[
						  'chapter_namespace' => '0',
						  'chapter_title' => 'Test_chap_D',
						  'chapter_name' => 'Test chap D',
						  'chapter_number' => '1.1.1.1.1',
						  'chapter_type' => 'wikilink-with-alias',
						],
					  ]
					],
				  ]
				],
			  ]
			],
			[
			  'chapter_namespace' => '0',
			  'chapter_title' => 'Test_chap_G',
			  'chapter_name' => 'Test chap G',
			  'chapter_number' => '1.2',
			  'chapter_type' => 'wikilink-with-alias',
			],
		  ]
		],
		[
		  'chapter_namespace' => null,
		  'chapter_title' => null,
		  'chapter_name' => 'My plain text label 2',
		  'chapter_number' => '2',
		  'chapter_type' => 'plain-text',
		  'chapter_children' => [
			[
			  'chapter_namespace' => '0',
			  'chapter_title' => 'Test_chap_E',
			  'chapter_name' => 'Test chap E',
			  'chapter_number' => '2.1',
			  'chapter_type' => 'wikilink-with-alias',
			  'chapter_children' => [
				[
				  'chapter_namespace' => '10',
				  'chapter_title' => 'Test_chap_F',
				  'chapter_name' => 'Test chap F',
				  'chapter_number' => '2.1.1',
				  'chapter_type' => 'wikilink-with-alias',
				],
			  ]
			]
		  ]
		],
		];
	}

	/**
	 * @return array
	 */
	private function getInput(): array {
		return [
			new ChapterDataModel(
				'0',
				'Test_chap_A',
				'My display title',
				'1',
				'wikilink-with-alias',
			),
			new ChapterDataModel(
				'0',
				'Test_chap B',
				'Test chap B',
				'1.1',
				'wikilink-with-alias',
			),
			new ChapterDataModel(
				'0',
				'Test_chap_C',
				'Test_chap_C',
				'1.1.1',
				'wikilink-with-alias',
			),
			new ChapterDataModel(
				null,
				null,
				'My plain text label 1',
				'1.1.1.1',
				'plain-text',
			),
			new ChapterDataModel(
				'0',
				'Test_chap_D',
				'Test chap D',
				'1.1.1.1.1',
				'wikilink-with-alias',
			),
			new ChapterDataModel(
				'0',
				'Test_chap_G',
				'Test chap G',
				'1.2',
				'wikilink-with-alias',
			),
			new ChapterDataModel(
				null,
				null,
				'My plain text label 2',
				'2',
				'plain-text',
			),
			new ChapterDataModel(
				'0',
				'Test_chap_E',
				'Test chap E',
				'2.1',
				'wikilink-with-alias',
			),
			new ChapterDataModel(
				'10',
				'Test_chap_F',
				'Test chap F',
				'2.1.1',
				'wikilink-with-alias',
			)
		];
	}
}
