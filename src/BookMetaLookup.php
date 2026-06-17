<?php

namespace BlueSpice\Bookshelf;

use MediaWiki\Title\Title;
use Wikimedia\Rdbms\IConnectionProvider;

class BookMetaLookup {

	/** @var IConnectionProvider */
	private $connectionProvider;

	/** @var BookLookup */
	private $bookLookup = null;

	/**
	 * @param IConnectionProvider $connectionProvider
	 * @param BookLookup $bookLookup
	 */
	public function __construct( IConnectionProvider $connectionProvider, BookLookup $bookLookup ) {
		$this->connectionProvider = $connectionProvider;
		$this->bookLookup = $bookLookup;
	}

	/**
	 * @param Title $book
	 * @return array
	 */
	public function getMetaForBook( Title $book ): array {
		$meta = [];

		$bookID = $this->bookLookup->getBookId( $book );

		$results = $this->connectionProvider->getReplicaDatabase()->newSelectQueryBuilder()
			->table( 'bs_book_meta' )
			->fields( '*' )
			->where( [
				'm_book_id' => $bookID
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $results as $result ) {
			$key = $result->m_key;
			$meta[$key] = $result->m_value;
		}

		return $meta;
	}

	/**
	 * @param Title $book
	 * @param string $key
	 * @return string
	 */
	public function getMetaValueForBook( Title $book, string $key ): string {
		$value = '';

		$bookID = $this->bookLookup->getBookId( $book );

		$results = $this->connectionProvider->getReplicaDatabase()->newSelectQueryBuilder()
			->table( 'bs_book_meta' )
			->field( 'm_value' )
			->where( [
				'm_book_id' => $bookID,
				'm_key' => $key
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $results as $result ) {
			$value = $result->m_value;
		}

		return $value;
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function getAllMetaValuesForKey( string $key ): array {
		$values = [];

		$results = $this->connectionProvider->getReplicaDatabase()->newSelectQueryBuilder()
			->table( 'bs_book_meta' )
			->field( 'm_value' )
			->where( [
				'm_key' => $key
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $results as $result ) {
			$values[] = $result->m_value;
		}

		array_unique( $values );

		return $values;
	}
}
