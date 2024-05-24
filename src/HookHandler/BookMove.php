<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\BookInfo;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterUpdater;
use BlueSpice\Bookshelf\Content\BookContent;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Wikitext\ParserFactory;
use Psr\Log\LoggerInterface;
use Title;
use TitleFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;

class BookMove {

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
	 * @param LinkTarget $old
	 * @param LinkTarget $new
	 * @param UserIdentity $userIdentity
	 * @param int $pageid
	 * @param int $redirid
	 * @param string $reason
	 * @param RevisionRecord $revision
	 * @return bool
	 */
	public function onPageMoveComplete(
		LinkTarget $old, LinkTarget $new, UserIdentity $userIdentity,
		int $pageid, int $redirid, string $reason, RevisionRecord $revision
	) {
		$oldBook = $this->titleFactory->newFromLinkTarget( $old );
		$newBook = $this->titleFactory->newFromLinkTarget( $new );

		if ( $newBook->getNamespace() !== NS_BOOK ) {
			return;
		}

		$db = $this->loadBalancer->getConnection( DB_PRIMARY );

		/** @var BookInfo */
		$oldBookInfo = $this->bookLookup->getBookInfo( $oldBook );
		if ( !$oldBookInfo ) {
			$this->createBook( $newBook, $revision, $db );
		}

		$this->moveBook( $oldBookInfo, $newBook, $db );

		return true;
	}

	/**
	 * @param BookInfo $oldBookInfo
	 * @param Title $newBook
	 * @param IDatabase $db
	 */
	private function moveBook( BookInfo $oldBookInfo, Title $newBook, IDatabase $db ) {
		$db->update(
			'bs_books',
			[
				'book_namespace' => $newBook->getNamespace(),
				'book_title' => $newBook->getDBkey(),
			],
			[
				'book_id' => $oldBookInfo->getId()
			]
		);
	}

	/**
	 * @param Title $newBook
	 * @param RevisionRecord $revisionRecord
	 * @param IDatabase $db
	 */
	private function createBook( Title $newBook, RevisionRecord $revisionRecord, $db ) {
		$db->insert(
			'bs_books',
			[
				'book_namespace' => $newBook->getNamespace(),
				'book_title' => $newBook->getDBkey(),
				'book_name' => $newBook->getText(),
				'book_namespace' => 'public'
			]
		);

		$content = $revisionRecord->getContent( SlotRecord::MAIN );
		if ( $content instanceof BookContent ) {
			$this->doSaveBookSource( $newBook, $revisionRecord );
		}
	}

	/**
	 * @param Title $book
	 * @param RevisionRecord $revisionRecord
	 */
	private function doSaveBookSource( Title $book, RevisionRecord $revisionRecord ) {
		$bookSourceParser = new BookSourceParser(
			$revisionRecord,
			$this->parserFactory->getNodeProcessors(),
			$this->titleFactory
		);

		$chapterData = $bookSourceParser->getChapterDataModelArray();
		if ( empty( $chapterData ) ) {
			return;
		}

		$updater = new ChapterUpdater( $this->loadBalancer, $this->bookLookup, $this->logger );
		$status = $updater->update( $book, $chapterData );
		if ( !$status ) {
			$this->logger->error( 'onMultiContentSave: Database update error' );
		}
	}
}
