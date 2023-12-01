<?php

namespace BlueSpice\Bookshelf;

use IContextSource;
use Title;
use TitleFactory;
use Wikimedia\Rdbms\LoadBalancer;

class Utilities {

	/**
	 * @var IContextSource
	 */
	private $context = null;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory = null;

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer = null;

	/**
	 * @var BookContextProviderFactory
	 */
	private $bookContextProviderFactory = null;

	/**
	 * @param IContextSource $context
	 * @param TitleFactory $titleFactory
	 * @param LoadBalancer $loadBalancer
	 * @param BookContextProviderFactory $bookContextProviderFactory
	 */
	public function __construct(
		IContextSource $context, TitleFactory $titleFactory, LoadBalancer $loadBalancer,
		BookContextProviderFactory $bookContextProviderFactory
	) {
		$this->context = $context;
		$this->titleFactory = $titleFactory;
		$this->loadBalancer = $loadBalancer;
		$this->bookContextProviderFactory = $bookContextProviderFactory;
	}


	/**
	 * @param Title $title
	 * @return array
	 */
	public function getBooksForPage( Title $title ): array {
		$books = [];

		$db = $this->loadBalancer->getConnection( DB_REPLICA );

		$results = $db->select(
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

		foreach ( $results as $result ) {
			$res = $db->select(
				'bs_books',
				'*',
				[
					'book_id' => $result->chapter_book_id
				],
				__METHOD__
			);

			if ( !$res ) {
				continue;
			}

			foreach ( $res as $row ) {
				$book = $this->titleFactory->makeTitle( $row->book_namespace, $row->book_title );

				$key = $book->getPrefixedDBkey();
				if ( !isset( $books[$key] ) ) {
					$books[$key] = [
						'book_id' => $row->book_id,
						'book_namespace' => $row->book_namespace,
						'book_title' => $row->book_title,
						'book_name' => $row->book_name,
						'book_type' => $row->book_type
					];
				}
			}
		}

		return $books;
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	public function getChaptersOfBook( Title $title ): array {
		$pages = [];

		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $db->select(
			'bs_books',
			'book_id',
			[
				'book_namespace' => $title->getNamespace(),
				'book_title' => $title->getDBKey()
			],
			__METHOD__
		);

		if ( $res < 1 ) {
			return [];
		}

		$bookID = null;
		foreach ( $res as $row ) {
			$bookID = $row->book_id;
		}

		$results = $db->select(
			'bs_book_chapters',
			'*',
			[
				'chapter_book_id' => $bookID
			],
			__METHOD__,
			[
				'ORDER BY' => 'chapter_number'
			]
		);

		foreach ( $results as $result ) {
			$pages[] = [
				'chapter_namespace' => $result->chapter_namespace,
				'chapter_title' => $result->chapter_title,
				'chapter_name' => $result->chapter_name,
				'chapter_number' => (string)$result->chapter_number,
				'chapter_type' => $result->chapter_type,
			];
		}

		return $pages;
	}

	/**
	 * Returns prefixed db key of book source page
	 *
	 * @param Title $title
	 * @return title|null
	 */
	public function getActiveBook( Title $title ): ?title {
		$activeBookContextProvider = $this->bookContextProviderFactory->getActiveContextProvider();
		$bookContext = $activeBookContextProvider->getContext( $title );

		$books = $this->getBooksForPage( $title );

		if ( $bookContext ) {
			$dbKey = $bookContext->getPrefixedDBKey();

			if ( isset( $books[$dbKey] ) ) {
				$page = $this->titleFactory->newFromDBkey( $dbKey );

				return $page;
			}
		}

		// If no valid book context is set just take the first book in the list
		if ( !empty( $books ) ) {
			$book = array_shift( $books );
			$namespace = (int)$book['book_namespace'];
			$text = $book['book_title'];

			$page = $this->titleFactory->makeTitle(
				$namespace,
				$text
			);

			return $page;
		}

		return null;
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	public function getBookHierarchy( Title $title ): array {
		$chapters = $this->getChaptersOfBook( $title );
		$bookHierarchyBuilder = new BookHierarchyBuilder();
		$hierarchy = $bookHierarchyBuilder->build( $chapters );

		return $hierarchy;
	}

	/**
	 * @param Title $page
	 * @param Title $book
	 * @return array
	 */
	private function getChapterInfoFor( Title $page, Title $book ): array {
		$chapterInfo = [];

		$bookData = $this->queryBookSingle( [
			'book_namespace' => $page->getNamespace(),
			'book_title' => $page->getDBKey(),
		], [
			'ORDER BY' => 'chapter_number'
		] );

		$bookID = $bookData ? $bookData['book_id'] : null;
		if ( $bookID === null ) {
			return [];
		}

		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$results = $db->select(
			'bs_book_chapters',
			'chapter_number',
			[
				'chapter_book_id' => $bookID,
				'chapter_namespace' => $page->getNamespace(),
				'chapter_title' => $page->getDBKey(),
			],
			__METHOD__,
			[
				 'ORDER BY' => 'chapter_number'
			]
		);

		foreach ( $results as $result ) {
			$chapterInfo = [
				'chapter_name' => $result->chapter_number,
				'chapter_number' => $result->chapter_number
			];
		}

		return $chapterInfo;
	}

	/**
	 * @param array $conds
	 * @param array|null $options
	 *
	 * @return array|null
	 */
	public function queryBooks( array $conds, ?array $options = [] ): array {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );

		$res = $db->select(
			'bs_books',
			'*',
			$conds,
			__METHOD__,
			$options
		);

		if ( !$res ) {
			return [];
		}

		$books = [];
		foreach ( $res as $row ) {
			$bookTitle = $this->titleFactory->makeTitle( $row->book_namespace, $row->book_title );
			$books[] = [
				'book_title_object' => $bookTitle,
				'book_id' => $row->book_id,
				'book_namespace' => $row->book_namespace,
				'book_title' => $row->book_title,
				'book_name' => $row->book_name,
				'book_type' => $row->book_type
			];
		}

		return $books;
	}

	/**
	 * @param array $conds
	 * @param array|null $options
	 *
	 * @return array|null
	 */
	public function queryBookSingle( array $conds, ?array $options = [] ): ?array {
		$books = $this->queryBooks( $conds, $options );
		return !empty( $books ) ? $books[0] : null;
	}
}
