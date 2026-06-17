<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterDataModel;
use BlueSpice\Bookshelf\MenuEditor\NodeProcessor\ChapterPlainTextProcessor;
use BlueSpice\Bookshelf\MenuEditor\NodeProcessor\ChapterWikiLinkWithAliasProcessor;
use MediaWikiIntegrationTestCase;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 * @group Database
 */
class BookSourceParserTest extends MediaWikiIntegrationTestCase {

	/** @var string */
	protected $dummyBookContent = <<<HERE
* [[A|]]
** [[A/A|aa]]
*** [[A/A/A|aaa]]
*** [[A/A/B|aab]]
**** [[A/A/B/A|aaba]]
* Hello
** World
** [[A/B|ab]]
*** [[Template:A/B/A|aba]]
* [[B]]
** [[B/A]]
HERE;

	/**
	 * @covers \BlueSpice\Bookshelf\BookSourceParser::getChapterDataModelArray
	 */
	public function testGetChapterDataModelArray() {
		$title = $this->insertPage( 'BookPage', $this->dummyBookContent )['title'];

		$expected = $this->getExpecedOutput();
		$parser = $this->newParserForTitle( $title );

		$actual = $parser->getChapterDataModelArray();

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @covers \BlueSpice\Bookshelf\BookSourceParser::addNodeAfter
	 */
	public function testAddNodeAfterByChapterNumber() {
		$title = $this->insertPage( 'BookPageInsert', "* [[A|A]]\n* [[B|B]]\n* [[C|C]]" )['title'];
		$parser = $this->newParserForTitle( $title );
		$node = $parser->getNodeFromData( [
			'type' => 'bs-bookshelf-chapter-wikilink-with-alias',
			'label' => 'D',
			'level' => 1,
			'target' => 'D',
		] );

		$this->assertNotNull( $node );
		$parser->addNodeAfter( $node, '2' );
		$actual = $parser->getChapterDataModelArray();

		$this->assertSame( [ 'A', 'B', 'D', 'C' ], array_map(
			static function ( ChapterDataModel $chapter ) {
				return $chapter->getName();
			},
			$actual
		) );
		$this->assertSame( [ '1', '2', '3', '4' ], array_map(
			static function ( ChapterDataModel $chapter ) {
				return $chapter->getNumber();
			},
			$actual
		) );
	}

	private function newParserForTitle( $title ): BookSourceParser {
		$services = $this->getServiceContainer();
		$titleFactory = $services->getTitleFactory();
		$revisionRecord = $services->getRevisionLookup()->getRevisionByTitle( $title );

		return new BookSourceParser(
			$revisionRecord,
			[
				new ChapterPlainTextProcessor(),
				new ChapterWikiLinkWithAliasProcessor( $titleFactory ),
			],
			$titleFactory
		);
	}

	private function getExpecedOutput(): array {
		$data = [
			new ChapterDataModel( NS_MAIN, 'A', 'A', '1', 'wikilink-with-alias' ),
			new ChapterDataModel( NS_MAIN, 'A/A', 'aa', '1.1', 'wikilink-with-alias' ),
			new ChapterDataModel( NS_MAIN, 'A/A/A', 'aaa', '1.1.1', 'wikilink-with-alias' ),
			new ChapterDataModel( NS_MAIN, 'A/A/B', 'aab', '1.1.2', 'wikilink-with-alias' ),
			new ChapterDataModel( NS_MAIN, 'A/A/B/A', 'aaba', '1.1.2.1', 'wikilink-with-alias' ),
			new ChapterDataModel( null, null, 'Hello', '2', 'plain-text' ),
			new ChapterDataModel( null, null, 'World', '2.1', 'plain-text' ),
			new ChapterDataModel( NS_MAIN, 'A/B', 'ab', '2.2', 'wikilink-with-alias' ),
			new ChapterDataModel( NS_TEMPLATE, 'A/B/A', 'aba', '2.2.1', 'wikilink-with-alias' ),
			new ChapterDataModel( NS_MAIN, 'B', 'B', '3', 'wikilink-with-alias' ),
			new ChapterDataModel( NS_MAIN, 'B/A', 'B/A', '3.1', 'wikilink-with-alias' ),
		];

		return $data;
	}

}
