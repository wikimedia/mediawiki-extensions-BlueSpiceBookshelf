<?php

namespace BlueSpice\Bookshelf;

use Title;
use Wikimedia\Rdbms\LoadBalancer;

class BookMetaLookup {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer = null;

	/** @var BookLookup */
	private $bookLookup = null;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param BookLookup $bookLookup
	 */
	public function __construct( LoadBalancer $loadBalancer, BookLookup $bookLookup	) {
		$this->loadBalancer = $loadBalancer;
		$this->bookLookup = $bookLookup;
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	public function getMeta( Title $title ): array {
		$meta = [];

		$bookID = $this->bookLookup->getBookId( $title );

		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$results = $db->select(
			'bs_book_meta',
			'*',
			[
				'm_book_id' => $bookID
			]
		);

		foreach ( $results as $result ) {
			$key = $result->m_key;
			$meta[$key] = $result->m_value;
		}

		return $meta;
	}
}
