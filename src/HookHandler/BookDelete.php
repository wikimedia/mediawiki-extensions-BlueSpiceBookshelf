<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\BookLookup;
use ManualLogEntry;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use TitleFactory;
use Wikimedia\Rdbms\LoadBalancer;

class BookDelete {

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var LoadBalancer */
	private $loadBalancer = null;

	/** @var BookLookup */
	private $bookLookup = null;

	/** @var LoggerInterface */
	private $logger = null;

	/**
	 * @param TitleFactory $titleFactory
	 * @param LoadBalancer $loadBalancer
	 * @param BookLookup $bookLookup
	 */
	public function __construct(
		TitleFactory $titleFactory, LoadBalancer $loadBalancer, BookLookup $bookLookup
	) {
		$this->titleFactory = $titleFactory;
		$this->loadBalancer = $loadBalancer;
		$this->bookLookup = $bookLookup;
		$this->logger = LoggerFactory::getInstance( 'BSBookshelf' );
	}

	/**
	 * @param ProperPageIdentity $page
	 * @param Authority $deleter
	 * @param string $reason
	 * @param int $pageID
	 * @param RevisionRecord $deletedRev
	 * @param ManualLogEntry $logEntry
	 * @param int $archivedRevisionCount
	 * @return bool
	 */
	public function onPageDeleteComplete( ProperPageIdentity $page, Authority $deleter, string $reason,
		int $pageID, RevisionRecord $deletedRev, ManualLogEntry $logEntry, int $archivedRevisionCount
	) {
		$title = $this->titleFactory->castFromPageIdentity( $page );

		if ( !$title ) {
			return true;
		}

		if ( $title->getNamespace() !== NS_BOOK ) {
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
}
