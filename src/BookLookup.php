<?php

namespace BlueSpice\Bookshelf;

use Title;
use TitleFactory;
use Wikimedia\Rdbms\LoadBalancer;

class BookLookup {

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var LoadBalancer */
	private $loadBalancer = null;

	/** @var ChapterLookup */
	private $chapterLookup = null;

	/**
	 * @param TitleFactory $titleFactory
	 * @param LoadBalancer $loadBalancer
	 * @param ChapterLookup $chapterLookup
	 */
	public function __construct(
		TitleFactory $titleFactory, LoadBalancer $loadBalancer,	ChapterLookup $chapterLookup
	) {
		$this->titleFactory = $titleFactory;
		$this->loadBalancer = $loadBalancer;
		$this->chapterLookup = $chapterLookup;
	}

	/**
	 * @return BookDataModel[]
	 */
	public function getBooks(): array {
		$books = [];

		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$results = $dbr->select(
			'bs_books',
			'*',
			[],
			__METHOD__
		);

		if ( $results < 1 ) {
			return [];
		}

		foreach ( $results as $res ) {
			$book = $this->titleFactory->makeTitle( $res->book_namespace, $res->book_title );

			$key = $book->getPrefixedDBkey();
			if ( !isset( $books[$key] ) ) {
				$books[$key] = new BookDataModel(
					$res->book_namespace,
					$res->book_title,
					$res->book_name,
					$res->book_type
				);
			}
		}

		return $books;
	}

	/**
	 * @param Title $title
	 * @return BookDataModel[]
	 */
	public function getBooksForPage( Title $title ): array {
		$books = [];

		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$res = $dbr->select(
			'bs_book_chapters',
			'chapter_book_id',
			[
				'chapter_namespace' => $title->getNamespace(),
				'chapter_title' => $title->getDBKey()
			],
			__METHOD__,
			[
				 'ORDER BY' => 'chapter_number'
			]
		);

		$bookIDs = [];
		foreach ( $res as $row ) {
			$bookIDs[] = $row->chapter_book_id;
		}

		if ( empty( $bookIDs ) ) {
			return [];
		}

		$res = $dbr->select(
			'bs_books',
			'*',
			[
				'book_id' => $bookIDs
			],
			__METHOD__
		);

		if ( empty( $res ) ) {
			return [];
		}

		$books = [];
		foreach ( $res as $row ) {
			$book = $this->titleFactory->makeTitle( $row->book_namespace, $row->book_title );

			$key = $book->getPrefixedDBkey();
			if ( !isset( $books[$key] ) ) {
				$books[$key] = new BookDataModel(
					$row->book_namespace,
					$row->book_title,
					$row->book_name,
					$row->book_type
				);
			}
		}

		return $books;
	}

	/**
	 * @param Title $title
	 * @return int|null
	 */
	public function getBookId( Title $title ): ?int {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$result = $dbr->select(
			'bs_books',
			'book_id',
			[
				'book_namespace' => $title->getNamespace(),
				'book_title' => $title->getDBKey()
			],
			__METHOD__,
			[]
		);

		if ( !$result ) {
			return null;
		}

		$id = null;
		foreach ( $result as $res ) {
			$id = $res->book_id;
		}

		return $id;
	}

	/**
	 * @param Title $title
	 * @return BookInfo|null
	 */
	public function getBookInfo( Title $title ): ?BookInfo {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$result = $dbr->select(
			'bs_books',
			'*',
			[
				'book_namespace' => $title->getNamespace(),
				'book_title' => $title->getDBKey()
			],
			__METHOD__,
			[]
		);

		if ( !$result ) {
			return null;
		}

		$info = [];
		foreach ( $result as $res ) {
			$info = [
				'book_id' => $res->book_id,
				'book_namespace' => $res->book_namespace,
				'book_title' => $res->book_title,
				'book_name' => $res->book_name,
				'book_type' => $res->book_type,
			];
		}

		if ( empty( $info ) ) {
			return null;
		}

		return new BookInfo(
			$info['book_id'],
			$info['book_namespace'],
			$info['book_title'],
			$info['book_name'],
			$info['book_type'],
		);
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	public function getBookHierarchy( Title $title ): array {
		$chapters = $this->chapterLookup->getChaptersOfBook( $title );
		$bookHierarchyBuilder = new BookHierarchyBuilder();
		$hierarchy = $bookHierarchyBuilder->build( $chapters );

		return $hierarchy;
	}
}
