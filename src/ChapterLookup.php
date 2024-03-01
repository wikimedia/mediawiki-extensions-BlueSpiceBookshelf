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
	 * @param Title $book
	 * @param Title $title
	 * @return ChapterInfo@null
	 */
	public function getChapterInfoFor( Title $book, Title $title ): ?ChapterInfo {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $db->select(
			'bs_books',
			'book_id',
			[
				'book_namespace' => $book->getNamespace(),
				'book_title' => $book->getDBKey(),
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
			[ 'chapter_name', 'chapter_number', 'chapter_type' ],
			[
				'chapter_book_id' => $bookID,
				'chapter_namespace' => $title->getNamespace(),
				'chapter_title' => $title->getDBKey(),
			],
			__METHOD__,
			[
				'ORDER BY' => 'chapter_number'
			]
		);

		$chapterInfo = null;
		foreach ( $results as $result ) {
			$chapterInfo = new ChapterInfo(
				$result->chapter_name,
				$result->chapter_number,
				$result->chapter_type
			);
		}

		return $chapterInfo;
	}

	/**
	 * @param Title $book
	 * @param ChapterInfo $chapterInfo
	 * @return ChapterInfo[]
	 */
	public function getChildren( Title $book, ChapterInfo $chapterInfo ): array {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $db->select(
			'bs_books',
			'book_id',
			[
				'book_namespace' => $book->getNamespace(),
				'book_title' => $book->getDBKey(),
			]
		);

		$bookID = null;
		foreach ( $res as $row ) {
			$bookID = $row->book_id;
		}

		if ( $bookID === null ) {
			return [];
		}

		$results = $db->select(
			'bs_book_chapters',
			'*',
			[
				'chapter_book_id=' . $bookID,
				'chapter_number like "' . $chapterInfo->getNumber() . '%"',
				'NOT chapter_number="' . $chapterInfo->getNumber() . '"',
			],
			__METHOD__,
			[
				'ORDER BY' => 'chapter_number'
			]
		);

		$children = [];
		foreach ( $results as $result ) {
			$children[] = new ChapterDataModel(
				$result->chapter_namespace,
				$result->chapter_title,
				$result->chapter_name,
				$result->chapter_number,
				$result->chapter_type
			);
		}

		return $children;
	}
}
