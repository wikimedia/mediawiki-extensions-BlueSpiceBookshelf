<?php

use BlueSpice\Bookshelf\BookSourceParser;
use BlueSpice\Bookshelf\ChapterDataModel;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;

require_once dirname( __DIR__, 3 ) . '/maintenance/Maintenance.php';

class RebuildBooks extends LoggedUpdateMaintenance {

	/** @var IDatabase */
	private $db = null;

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var RevisionLookup */
	private $revisionLookup = null;

	/** @var ParserFactory */
	private $parserFactory = null;

	/** @var Title[] */
	private $books = [];

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bookshelf-rebuild-books';
	}

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'BlueSpiceBookshelf' );
	}

	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$this->output( "BlueSpiceBookshelf - update tables bs_books and bs_book_chapters\n" );

		$services = MediaWikiServices::getInstance();

		/** @var LoadBalancer */
		$loadBalancer = $services->getDBLoadBalancer();

		$this->db = $loadBalancer->getConnection( DB_PRIMARY );
		$this->titleFactory = $services->getTitleFactory();
		$this->revisionLookup = $services->getRevisionLookup();
		$this->parserFactory = $services->get( 'MWStakeWikitextParserFactory' );

		$this->truncateTables();
		$this->fetchBooks();
		$this->updateBooksTable();
		$this->updateBookChaptersTable();

		return true;
	}

	private function truncateTables() {
		// Truncate 'bs_book_chapters'
		$res = $this->db->select(
			'bs_book_chapters',
			'chapter_id',
			[]
		);

		$chapterIDs = [];
		foreach ( $res as $row ) {
			$chapterIDs[] = $row->chapter_id;
		}

		if ( !empty( $chapterIDs ) ) {
			$this->output( "Truncate table 'bs_book_chapters' ..." );
			$this->db->delete(
				'bs_book_chapters',
				[
					'chapter_id' => $chapterIDs
				]
			);
			$this->output( "\033[32m  done\n\033[39m" );
		}

		// Truncate 'bs_books'
		$res = $this->db->select(
			'bs_books',
			'book_id',
			[]
		);

		$bookIDs = [];
		foreach ( $res as $row ) {
			$bookIDs[] = $row->book_id;
		}

		if ( !empty( $bookIDs ) ) {
			$this->output( "Truncate table 'bs_books' ..." );
			$this->db->delete(
				'bs_books',
				[
					'book_id' => $bookIDs
				]
			);
			$this->output( "\033[32m  done\n\033[39m" );
		}
	}

	private function fetchBooks() {
		$dbr = $this->getDB( DB_REPLICA );
		$res = $dbr->select(
			'page',
			'*',
			[
				'page_content_model' => 'book'
			],
			__METHOD__
		);

		if ( $res->numRows() < 1 ) {
			return [];
		}

		foreach ( $res as $row ) {
			$title = $this->titleFactory->makeTitle( $row->page_namespace, $row->page_title );
			$key = $title->getPrefixedDBkey();
			$this->books[$key] = $title;
		}
	}

	private function updateBooksTable() {
		foreach ( $this->books as $book ) {
			$type = 'public';
			if ( $book->getNamespace() === NS_USER ) {
				$type = 'private';
			}

			$this->output( "Insert into 'bs_books' " . $book->getPrefixedDBKey() );
			$this->db->insert(
				'bs_books',
				[
					'book_namespace' => $book->getNamespace(),
					'book_title' => $book->getDBkey(),
					'book_name' => $book->getDBkey(),
					'book_type' => $type
				]
			);
			$this->output( "\033[32m  done\n\033[39m" );
		}
	}

	private function updateBookChaptersTable() {
		foreach ( $this->books as $book ) {
			$revisionRecord = $this->revisionLookup->getRevisionByTitle( $book );
			$bookSourceParser = new BookSourceParser(
				$revisionRecord,
				$this->parserFactory->getNodeProcessors(),
				$this->titleFactory
			);

			/** @var ChapterDataModel */
			$chapters = $bookSourceParser->getChapterDataModelArray();

			$res = $this->db->select(
				'bs_books',
				'book_id',
				[
					'book_namespace' => $book->getNamespace(),
					'book_title' => $book->getDBkey()
				]
			);

			$bookId = null;
			foreach ( $res as $row ) {
				$bookId = $row->book_id;
			}

			if ( $bookId !== null ) {
				foreach ( $chapters as $chapter ) {
					$this->output( "Insert " . $chapter->getName() . " into 'bs_book_chapters' ..." );
					$this->db->insert(
						'bs_book_chapters',
						[
							'chapter_book_id' => $bookId,
							'chapter_namespace' => $chapter->getNamespace(),
							'chapter_title' => $chapter->getTitle(),
							'chapter_name' => $chapter->getName(),
							'chapter_number' => $chapter->getNumber(),
							'chapter_type' => $chapter->getType()
						]
					);
					$this->output( "\033[32m  done\n\033[39m" );
				}
			} else {
				$this->output( "\033[31mNo valid book_id for " . $book->getPrefixedDBKey() . "\n\033[39m" );
			}
		}
	}
}

$maintClass = RebuildBooks::class;
require_once RUN_MAINTENANCE_IF_MAIN;
