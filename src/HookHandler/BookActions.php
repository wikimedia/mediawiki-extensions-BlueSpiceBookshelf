<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\BookInfo;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterUpdater;
use BlueSpice\Bookshelf\Content\BookContent;
use Exception;
use JsonContent;
use ManualLogEntry;
use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\Hook\MultiContentSaveHook;
use MediaWiki\User\UserFactory;
use Message;
use MWStake\MediaWiki\Component\Wikitext\ParserFactory;
use Psr\Log\LoggerInterface;
use Title;
use TitleFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;

class BookActions implements MultiContentSaveHook, PageDeleteCompleteHook, PageMoveCompleteHook {

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var ParserFactory */
	private $parserFactory = null;

	/** @var LoadBalancer */
	private $loadBalancer = null;

	/** @var BookLookup */
	private $bookLookup = null;

	/** @var UserFactory */
	private $userFactory = null;

	/** @var LoggerInterface */
	private $logger = null;

	/**
	 * @param TitleFactory $titleFactory
	 * @param ParserFactory $parserFactory
	 * @param LoadBalancer $loadBalancer
	 * @param BookLookup $bookLookup
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		TitleFactory $titleFactory, ParserFactory $parserFactory,
		LoadBalancer $loadBalancer, BookLookup $bookLookup,
		UserFactory $userFactory
	) {
		$this->titleFactory = $titleFactory;
		$this->parserFactory = $parserFactory;
		$this->loadBalancer = $loadBalancer;
		$this->bookLookup = $bookLookup;
		$this->userFactory = $userFactory;
		$this->logger = LoggerFactory::getInstance( 'BSBookshelf' );
	}

	/**
	 * @inheritDoc
	 */
	public function onMultiContentSave(
		$renderedRevision, $user, $summary, $flags, $hookStatus
	) {
		$revisionRecord = $renderedRevision->getRevision();
		$page = $revisionRecord->getPage();
		$title = $this->titleFactory->castFromPageIdentity( $page );

		if ( !$title ) {
			return true;
		}

		if ( $title->getNamespace() !== NS_BOOK && !$this->isUserBook( $title ) ) {
			return true;
		}

		if ( $revisionRecord->hasSlot( 'book_meta' ) ) {
			$this->doSaveBookMeta( $title, $revisionRecord );
		}

		$content = $revisionRecord->getContent( SlotRecord::MAIN );
		if ( $content instanceof BookContent || $content->isEmpty() ) {
			$this->doSaveBookSource( $title, $revisionRecord );
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageDeleteComplete( ProperPageIdentity $page, Authority $deleter, string $reason,
		int $pageID, RevisionRecord $deletedRev, ManualLogEntry $logEntry, int $archivedRevisionCount
	) {
		$title = $this->titleFactory->castFromPageIdentity( $page );

		if ( !$title ) {
			return true;
		}

		if ( $title->getNamespace() !== NS_BOOK && !$this->isUserBook( $title ) ) {
			return true;
		}

		$bookID = $this->bookLookup->getBookId( $title );

		$db = $this->loadBalancer->getConnection( DB_PRIMARY );
		$db->delete(
			'bs_book_chapters',
			[
				'chapter_book_id' => $bookID
			]
		);

		$db->delete(
			'bs_book_meta',
			[
				'm_book_id' => $bookID
			]
		);

		$db->delete(
			'bs_books',
			[
				'book_id' => $bookID
			]
		);

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageMoveComplete(
		$old, $new, $userIdentity, $pageid, $redirid, $reason, $revision
	) {
		$oldBook = $this->titleFactory->newFromLinkTarget( $old );
		$newBook = $this->titleFactory->newFromLinkTarget( $new );

		if ( $newBook->getNamespace() !== NS_BOOK && !$this->isUserBook( $newBook ) ) {
			return true;
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
	 *
	 * @param Title $title
	 * @return bool
	 */
	private function isUserBook( $title ) {
		if ( $title->getNamespace() !== NS_USER ) {
			return false;
		}
		if ( !$title->isSubpage() ) {
			return false;
		}
		$user = $this->userFactory->newFromName( $title->getRootText() );
		if ( !$user || $user->isAnon() ) {
			return false;
		}
		$prefix = Message::newFromKey(
			'bs-bookshelf-personal-books-page-prefix',
			$user->getName()
		);
		$bookTitle = $this->titleFactory->makeTitle(
			NS_USER,
			$prefix->inContentLanguage()->parse() . $title->getSubpageText()
		);
		return $bookTitle && $title->equals( $bookTitle );
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

		$this->assertBook( $book );

		$updater = new ChapterUpdater( $this->loadBalancer, $this->bookLookup, $this->logger );
		$chapterData = $bookSourceParser->getChapterDataModelArray();
		if ( empty( $chapterData ) ) {
			$updater->delete( $book );
			return;
		}
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
		$this->assertBook( $book );
		$bookInfo = $this->bookLookup->getBookInfo( $book );

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
	private function assertBook( Title $book ) {
		$bookId = $this->bookLookup->getBookId( $book );
		if ( $bookId === null ) {
			$this->createBookEntry( $book );
		}
	}

	/**
	 * @param Title $book
	 */
	private function createBookEntry( Title $book ) {
		$db = $this->loadBalancer->getConnection( DB_PRIMARY );

		$type = 'public';
		if ( $book->getNamespace() !== NS_BOOK ) {
			$type = 'private';
		}

		$status = $db->insert(
			'bs_books',
			[
				'book_namespace' => $book->getNamespace(),
				'book_title' => $book->getDBKey(),
				'book_name' => $book->getText(),
				'book_type' => $type,
			]
		);

		if ( !$status ) {
			$this->logger->error( 'onMultiContentSave: Could not create book' );
			throw new Exception( 'onMultiContentSave: Could not create book' );
		}
	}
}
