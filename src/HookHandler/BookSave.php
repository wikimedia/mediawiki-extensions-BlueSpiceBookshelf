<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterUpdater;
use BlueSpice\Bookshelf\Content\BookContent;
use CommentStoreComment;
use JsonContent;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Wikitext\ParserFactory;
use Psr\Log\LoggerInterface;
use Status;
use Title;
use TitleFactory;
use Wikimedia\Rdbms\LoadBalancer;

class BookSave {

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
	 * @param RenderedRevision $renderedRevision
	 * @param UserIdentity $user
	 * @param CommentStoreComment $summary
	 * @param int $flags
	 * @param Status $hookStatus
	 * @return bool
	 */
	public function onMultiContentSave(
		RenderedRevision $renderedRevision, UserIdentity $user, CommentStoreComment $summary, $flags, Status $hookStatus
	) {
		$revisionRecord = $renderedRevision->getRevision();
		$page = $revisionRecord->getPage();
		$title = $this->titleFactory->newFromID( $page->getId() );

		if ( !$title ) {
			return true;
		}

		if ( $revisionRecord->hasSlot( 'book_meta' ) ) {
			$this->doSaveBookMeta( $title, $revisionRecord );
			return true;
		}

		$content = $revisionRecord->getContent( SlotRecord::MAIN );
		if ( $content instanceof BookContent ) {
			$this->doSaveBookSource( $title, $revisionRecord );
		}

		return true;
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

	/**
	 * @param Title $book
	 * @param RevisionRecord $revisionRecord
	 */
	private function doSaveBookMeta( Title $book, RevisionRecord $revisionRecord ) {
		$bookInfo = $this->bookLookup->getBookInfo( $book );
		if ( $bookInfo === null ) {
			$this->createBookEntry( $book );
			$bookInfo = $this->bookLookup->getBookInfo( $book );
		}

		if ( $bookInfo === null ) {
			return;
		}

		/** @var JsonContent */
		$content = $revisionRecord->getContent( 'book_meta' );
		if ( $content instanceof JsonContent === false ) {
			return;
		}

		$json = $content->getText();
		$meta = json_decode( $json, true );

		$db = $this->loadBalancer->getConnection( DB_PRIMARY );

		// update name in bs_books table if a new title is set in metadata
		if ( isset( $meta['title'] ) && $meta['title'] !== $bookInfo->getName() ) {
			$db->update(
				'bs_books',
				[
					'book_name' => $meta['title']
				], [
					'book_id' => $bookInfo->getId()
				]
			);
		}

		// write all metadata as key => value in bs_book_meta table
		$db->delete(
			'bs_book_meta',
			[
				'm_book_id' => $bookInfo->getId()
			]
		);

		foreach ( $meta as $key => $value ) {
			$db->insert(
				'bs_book_meta',
				[
					'm_book_id' => $bookInfo->getId(),
					'm_key' => trim( $key ),
					'm_value' => trim( $value )
				]
			);
		}
	}

	/**
	 * @param Title $book
	 */
	private function createBookEntry( Title $book ) {
		$db = $this->loadBalancer->getConnection( DB_PRIMARY );

		$type = 'public';
		if ( $book->getNamespace() === NS_BOOK ) {
			$type = 'private';
		}

		$db->insert(
			'bs_books',
			[
				'book_namespace' => $book->getNamespace(),
				'book_title' => $book->getDBKey(),
				'book_name' => $book->getText(),
				'book_type' => $type,
			]
		);
	}
}
