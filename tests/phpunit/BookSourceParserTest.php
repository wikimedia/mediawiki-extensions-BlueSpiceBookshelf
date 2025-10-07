<?php

namespace BlueSpice\Bookshelf\Tests;

use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterDataModel;
use MediaWikiIntegrationTestCase;
use MWStake\MediaWiki\Component\Wikitext\ParserFactory;

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

		$services = $this->getServiceContainer();
		$revisionLookup = $services->getRevisionLookup();
		/** @var ParserFactory */
		$parserFactory = $services->get( 'MWStakeWikitextParserFactory' );
		$nodeProcessors = $parserFactory->getNodeProcessors();
		$titleFactory = $services->getTitleFactory();
		$revisionRecord = $revisionLookup->getRevisionByTitle( $title );

		$parser = new BookSourceParser(
			$revisionRecord,
			$nodeProcessors,
			$titleFactory
		);

		$actual = $parser->getChapterDataModelArray();

		$this->assertEquals( $expected, $actual );
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
