<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\BookViewTreeDataBuilder;
use BlueSpice\Bookshelf\ChapterDataModel;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use TitleFactory;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 *
 * @covers \BlueSpice\Bookshelf\BookHierarchyJsonBuilder
 */
class BookViewTreeDataBuilderTest extends TestCase {

	/**
	 * @covers BookHierarchyBilderTest::build
	 */
	public function testBuild() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		$book = $titleFactory->newFromText( "Book:Testbook" );
		$query = "book=" . $book->getPrefixedText();

		$input = $this->getInput();
		$expected = $this->getExpected( $titleFactory, $query );

		$builder = new BookViewTreeDataBuilder( $titleFactory );

		$actual = $builder->build( $book, $input );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @param TitleFactory $titleFactory
	 * @param string $query
	 * @return array
	 */
	private function getExpected( TitleFactory $titleFactory, string $query = '' ): array {
	  return [
			[
				'label' => 'Testbook',
				'name' => 'Testbook',
				'chapter' => [
					'number' => '',
					'name' => 'Testbook',
					'type' => 'plain-text',
				],
				'children' => [
					[
						'label' => '1 My display title',
						'name' => 'Test chap A',
						'href' => $this->makeURL( $titleFactory, 'Test chap A', $query ),
						'class' => 'new',
						'chapter' => [
							'namespace' => '0',
							'title' => 'Test_chap_A',
							'number' => '1',
							'name' => 'My display title',
							'type' => 'wikilink-with-alias',
						],
						'children' => [
							[
								'label' => '1.1 Test chap B',
								'name' => 'Test chap B',
								'href' => $this->makeURL( $titleFactory, 'Test chap B', $query ),
								'class' => 'new',
								'chapter' => [
									'namespace' => '0',
									'title' => 'Test_chap_B',
									'number' => '1.1',
									'name' => 'Test chap B',
									'type' => 'wikilink-with-alias',
								],
								'children' => [
									[
										'label' => '1.1.1 Test chap C',
										'name' => 'Test chap C',
										'href' => $this->makeURL( $titleFactory, 'Test chap C', $query ),
										'class' => 'new',
										'chapter' => [
											'namespace' => '0',
											'title' => 'Test_chap_C',
											'number' => '1.1.1',
											'name' => 'Test chap C',
											'type' => 'wikilink-with-alias',
										],
										'children' => [
											[
												'label' => '1.1.1.1 My plain text label 1',
												'name' => 'My plain text label 1',
												'chapter' => [
													'number' => '1.1.1.1',
													'name' => 'My plain text label 1',
													'type' => 'plain-text',
												],
												'children' => [
													[
														'label' => '1.1.1.1.1 Test chap D',
														'name' => 'Test chap D',
														'href' =>
															$this->makeURL( $titleFactory, 'Test chap D', $query ),
														'class' => 'new',
														'chapter' => [
															'namespace' => '0',
															'title' => 'Test_chap_D',
															'number' => '1.1.1.1.1',
															'name' => 'Test chap D',
															'type' => 'wikilink-with-alias',
														],
													],
												]
											],
										]
									],
								]
							],
							[
								'label' => '1.2 Test chap G',
								'name' => 'Test chap G',
								'href' => $this->makeURL( $titleFactory, 'Test chap G', $query ),
								'class' => 'new',
								'chapter' => [
									'namespace' => '0',
									'title' => 'Test_chap_G',
									'number' => '1.2',
									'name' => 'Test chap G',
									'type' => 'wikilink-with-alias',
								],
							],
						]
					],
					[
						'label' => '2 My plain text label 2',
						'name' => 'My plain text label 2',
						'chapter' => [
							'number' => '2',
							'name' => 'My plain text label 2',
							'type' => 'plain-text',
						],
						'children' => [
							[
								'label' => '2.1 Test chap E',
								'name' => 'Test chap E',
								'href' => $this->makeURL( $titleFactory, 'Test chap E', $query ),
								'class' => 'new',
								'chapter' => [
									'namespace' => '0',
									'title' => 'Test_chap_E',
									'number' => '2.1',
									'name' => 'Test chap E',
									'type' => 'wikilink-with-alias',
								],
								'children' => [
									[
										'label' => '2.1.1 Test chap F',
										'name' => 'Template:Test chap F',
										'href' => $this->makeURL( $titleFactory, 'Test chap F', $query, 10 ),
										'class' => 'new',
										'chapter' => [
											'namespace' => '10',
											'title' => 'Test_chap_F',
											'number' => '2.1.1',
											'name' => 'Test chap F',
											'type' => 'wikilink-with-alias',
										],
									],
								]
							]
						]
					],
				]
			]
		];
	}

	/**
	 * @param TitleFactory $titleFactory
	 * @param string $title
	 * @param string $query
	 * @param string $namespace
	 * @return string
	 */
	private function makeURL( $titleFactory, $title, $query, $namespace = 0 ) {
		$title = $titleFactory->makeTitle(
			$namespace,
			$title
		);
		if ( !$title ) {
			return '';
		}

		return $title->getLocalURL( $query );
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
				'Test_chap_B',
				'Test chap B',
				'1.1',
				'wikilink-with-alias',
			),
			new ChapterDataModel(
				'0',
				'Test_chap_C',
				'Test chap C',
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
