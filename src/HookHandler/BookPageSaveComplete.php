<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterUpdater;
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
		$bookSourceParser = new BookSourceParser(
			$revisionRecord,
			$this->parserFactory->getNodeProcessors(),
			$this->titleFactory
		);

		$chapterData = $bookSourceParser->getChapterDataModelArray();

		$book = $wikiPage->getTitle();
		$status = $this->doDBUpdate( $book, $chapterData );
		if ( !$status ) {
			$this->logger->error( 'onPageSaveComplete: Database update error' );
		}
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

}
