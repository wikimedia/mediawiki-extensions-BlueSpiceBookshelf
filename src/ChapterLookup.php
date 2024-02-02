<?php

namespace BlueSpice\Bookshelf;

use Title;
use Wikimedia\Rdbms\LoadBalancer;

class ChapterLookup {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer = null;

	/**
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct( LoadBalancer $loadBalancer	) {
		$this->loadBalancer = $loadBalancer;
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
			$pages[] = new ChapterDataModel(
				$result->chapter_namespace,
				$result->chapter_title,
				$result->chapter_name,
				(string)$result->chapter_number,
				$result->chapter_type,
			);
		}

		return $pages;
	}

	/**
	 * @param Title $page
	 * @param Title $book
	 * @return array
	 */
	private function getChapterInfoFor( Title $page, Title $book ): array {
		$chapterInfo = [];

		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $db->select(
			'bs_books',
			'book_id',
			[
				'book_namespace' => $page->getNamespace(),
				'book_title' => $page->getDBKey(),
			],
			__METHOD__,
			[
				 'ORDER BY' => 'chapter_number'
			]
		);

		$bookID = null;
		foreach ( $res as $row ) {
			$bookID = $row->book_id;
		}

		if ( $bookID === null ) {
			return '';
		}

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
}
