<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterDataModel;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Title;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 * @group Database
 */
class BookSourceParserTest extends MediaWikiIntegrationTestCase {
	protected $tablesUsed = [ 'page' ];

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

	public function addDBDataOnce(): void {
		$this->insertPage(
			$this->getBookTitle()->getPrefixedText(),
			$this->dummyBookContent,
		);
	}

	/**
	 * @covers \BlueSpice\Bookshelf\BookSourceParser::getChapterDataModelArray
	 */
	public function testGetChapterDataModelArray() {
		$expected = $this->getExpecedOutput();

		$services = MediaWikiServices::getInstance();
		$revisionLookup = $services->getRevisionLookup();
		$parserFactory = $services->get( 'MWStakeWikitextParserFactory' );
		$titleFactory = $services->getTitleFactory();
		$revisionRecord = $revisionLookup->getRevisionByTitle(
			$this->getBookTitle()
		);

		$parser = new BookSourceParser(
			$revisionRecord,
			$parserFactory->getNodeProcessors(),
			$titleFactory
		);

		$actual = $parser->getChapterDataModelArray();

		$this->assertEquals( $expected, $actual );
	}

	private function getExpecedOutput(): array {
		$data = [
			new ChapterDataModel( null, null, 'A', '1', 'plain-text' ),
			new ChapterDataModel( 0, 'A/A', 'aa', '1.1', 'wikilink-with-alias' ),
			new ChapterDataModel( 0, 'A/A/A', 'aaa', '1.1.1', 'wikilink-with-alias' ),
			new ChapterDataModel( 0, 'A/A/B', 'aab', '1.1.2', 'wikilink-with-alias' ),
			new ChapterDataModel( 0, 'A/A/B/A', 'aaba', '1.1.2.1', 'wikilink-with-alias' ),
			new ChapterDataModel( null, null, 'Hello', '2', 'plain-text' ),
			new ChapterDataModel( null, null, 'World', '2.1', 'plain-text' ),
			new ChapterDataModel( 0, 'A/B', 'ab', '2.2', 'wikilink-with-alias' ),
			new ChapterDataModel( 10, 'A/B/A', 'aba', '2.2.1', 'wikilink-with-alias' ),
			new ChapterDataModel( 0, 'B', 'B', '3', 'wikilink-with-alias' ),
			new ChapterDataModel( 0, 'B/A', 'B/A', '3.1', 'wikilink-with-alias' ),
		];

		return $data;
	}

	private function getBookTitle(): Title {
		return Title::makeTitle( NS_BOOK, 'UNIT TEST BOOK' );
	}
}
