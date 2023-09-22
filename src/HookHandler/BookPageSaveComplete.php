<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterUpdater;
use BlueSpice\Bookshelf\Content\BookContent;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Wikitext\ParserFactory;
use Psr\Log\LoggerInterface;
use TitleFactory;
use Wikimedia\Rdbms\LoadBalancer;
use WikiPage;

class BookPageSaveComplete {

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var ParserFactory */
	private $parserFactory = null;

	/** @var LoadBalancer */
	private $loadBalancer = null;

	/** @var BookLookup */
	private $bookLookup = null;

	/** @var LoggerInterface */
	private $logger = null;

	/**
	 * @param TitleFactory $titleFactory
	 * @param ParserFactory $parserFactory
	 * @param LoadBalancer $loadBalancer
	 * @param BookLookup $bookLookup
	 */
	public function __construct(
		TitleFactory $titleFactory, ParserFactory $parserFactory,
		LoadBalancer $loadBalancer, BookLookup $bookLookup
	) {
		$this->titleFactory = $titleFactory;
		$this->parserFactory = $parserFactory;
		$this->loadBalancer = $loadBalancer;
		$this->bookLookup = $bookLookup;
		$this->logger = LoggerFactory::getInstance( 'BSBookshelf' );
	}

	/**
	 * https://www.mediawiki.org/wiki/Manual:Hooks/PageSaveComplete
	 *
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $user
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 */
	public function onPageSaveComplete( WikiPage $wikiPage, UserIdentity $user, string $summary,
		int $flags, RevisionRecord $revisionRecord, EditResult $editResult
	) {
		$content = $wikiPage->getContent();
		if ( $content instanceof BookContent == false ) {
			return;
		}

		$bookSourceParser = new BookSourceParser(
			$revisionRecord,
			$this->parserFactory->getNodeProcessors(),
			$this->titleFactory
		);

		$chapterData = $bookSourceParser->getChapterDataModelArray();
		if ( empty( $chapterData ) ) {
			return;
		}

		$book = $wikiPage->getTitle();
		$status = $this->doDBUpdate( $book, $chapterData );
		if ( !$status ) {
			$this->logger->error( 'onPageSaveComplete: Database update error' );
		}

		$this->updateMeta( $book, $wikiPage );
	}

	/**
	 * @param PageIdentity $book
	 * @param ChapterDataModel[] $chapters
	 * @return bool
	 */
	private function doDBUpdate( PageIdentity $book, $chapters ): bool {
		$updater = new ChapterUpdater( $this->loadBalancer, $this->bookLookup, $this->logger );
		$status = $updater->update( $book, $chapters );

		return $status;
	}

	/**
	 * TODO: Remove again after refactoring Book editor
	 * @param LinkTarget $title
	 * @param WikiPage $wikiPage
	 */
	private function updateMeta( LinkTarget $title, WikiPage $wikiPage ) {
		$bookId = $this->bookLookup->getBookId( $title );
		if ( $bookId === null ) {
			return;
		}

		$db = $this->loadBalancer->getConnection( DB_PRIMARY );
		$db->delete(
			'bs_book_meta',
			[
				'm_book_id' => $bookId
			]
		);

		$content = $wikiPage->getContent();

		if ( $content instanceof \TextContent ) {
			$text = $content->getText();

			$tagMatches = [];
			preg_match( '#<bookmeta (.*?)/>#', $text, $tagMatches );

			if ( count( $tagMatches ) > 0 ) {
				$matches = [];
				preg_match_all( '#(.*?)=\"(.*?)\"#', $tagMatches[1], $matches );

				if ( count( $matches ) > 0 ) {
					for ( $index = 0; $index < count( $matches[0] ); $index++ ) {
						$key = $matches[1][$index];
						$value = $matches[2][$index];

						$meta[$key] = $value;
					}
				}
			}
		}

		foreach ( $meta as $key => $value ) {
			$db->insert(
				'bs_book_meta',
				[
					'm_book_id' => $bookId,
					'm_key' => trim( $key ),
					'm_value' => trim( $value )
				]
			);
		}
	}
}
