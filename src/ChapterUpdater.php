<?php

namespace BlueSpice\Bookshelf;

use Title;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;

class ChapterUpdater {

	/** @var LoadBalancer */
	private $loadBalancer = null;

	/** @var BookLookup */
	private $bookLookup = null;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param BookLookup $bookLookup
	 */
	public function __construct( LoadBalancer $loadBalancer, BookLookup $bookLookup ) {
		$this->loadBalancer = $loadBalancer;
		$this->bookLookup = $bookLookup;
	}

	/**
	 * @param Title $book
	 * @return bool
	 */
	public function delete( Title $book ): bool {
		$bookId = $this->bookLookup->getBookId( $book );
		if ( $bookId === null ) {
			return false;
		}

		/** @var IDatabase|false */
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );

		// Remove old entries
		$status = $dbw->delete(
			'bs_book_chapters',
			[
				'chapter_book_id' => $bookId
			],
			__METHOD__
		);

		return $status;
	}

	/**
	 * @param Title $book
	 * @param ChapterDataModel[] $chapters
	 * @return bool
	 */
	private function insert( Title $book, array $chapters ): bool {
		$bookId = $this->bookLookup->getBookId( $book );

		if ( $bookId === null ) {
			return false;
		}
		/** @var IDatabase|false */
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );

		foreach ( $chapters as $chapter ) {
			$res = $dbw->insert(
				'bs_book_chapters',
				[
					'chapter_namespace' => $chapter->getNamespace(),
					'chapter_title' => $chapter->getTitle(),
					'chapter_name' => $chapter->getName(),
					'chapter_number' => $chapter->getNumber(),
					'chapter_type' => $chapter->getType(),
					'chapter_book_id' => $bookId
				]
			);

			if ( !$res ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Title $book
	 * @param ChapterDataModel[] $chapters
	 * @return bool
	 */
	public function update( Title $book, array $chapters ): bool {
		$status = $this->delete( $book );
		if ( !$status ) {
			return false;
		}

		$status = $this->insert( $book, $chapters );
		return $status;
	}
}
