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
 * @covers \BlueSpice\Bookshelf\BookHierarchyBuilder
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
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_3',
			'chapter_name' => 'My display title 3',
			'chapter_number' => '3',
			'chapter_type' => 'wikilink-with-alias',
		],
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_4',
			'chapter_name' => 'My display title 4',
			'chapter_number' => '4',
			'chapter_type' => 'wikilink-with-alias',
		],
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_5',
			'chapter_name' => 'My display title 5',
			'chapter_number' => '5',
			'chapter_type' => 'wikilink-with-alias',
		],
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_6',
			'chapter_name' => 'My display title 6',
			'chapter_number' => '6',
			'chapter_type' => 'wikilink-with-alias',
		],
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_7',
			'chapter_name' => 'My display title 7',
			'chapter_number' => '7',
			'chapter_type' => 'wikilink-with-alias',
		],
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_8',
			'chapter_name' => 'My display title 8',
			'chapter_number' => '8',
			'chapter_type' => 'wikilink-with-alias',
		],
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_9',
			'chapter_name' => 'My display title 9',
			'chapter_number' => '9',
			'chapter_type' => 'wikilink-with-alias',
		],
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_10',
			'chapter_name' => 'My display title 10',
			'chapter_number' => '10',
			'chapter_type' => 'wikilink-with-alias',
		],
		[ 'chapter_namespace' => '0',
			'chapter_title' => 'Test_chap_A_11',
			'chapter_name' => 'My display title 11',
			'chapter_number' => '11',
			'chapter_type' => 'wikilink-with-alias',
		]
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
			),new ChapterDataModel(
				'0',
				'Test_chap_A_3',
				'My display title 3',
				'3',
				'wikilink-with-alias',
			),new ChapterDataModel(
				'0',
				'Test_chap_A_4',
				'My display title 4',
				'4',
				'wikilink-with-alias',
			),new ChapterDataModel(
				'0',
				'Test_chap_A_5',
				'My display title 5',
				'5',
				'wikilink-with-alias',
			),new ChapterDataModel(
				'0',
				'Test_chap_A_6',
				'My display title 6',
				'6',
				'wikilink-with-alias',
			),new ChapterDataModel(
				'0',
				'Test_chap_A_7',
				'My display title 7',
				'7',
				'wikilink-with-alias',
			),new ChapterDataModel(
				'0',
				'Test_chap_A_8',
				'My display title 8',
				'8',
				'wikilink-with-alias',
			),new ChapterDataModel(
				'0',
				'Test_chap_A_9',
				'My display title 9',
				'9',
				'wikilink-with-alias',
			),new ChapterDataModel(
				'0',
				'Test_chap_A_10',
				'My display title 10',
				'10',
				'wikilink-with-alias',
			),new ChapterDataModel(
				'0',
				'Test_chap_A_11',
				'My display title 11',
				'11',
				'wikilink-with-alias',
			)
		];
	}
}
