<?php

namespace BlueSpice\Bookshelf\Data\BooksOverview;

use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\FilterFinder;
use MWStake\MediaWiki\Component\DataStore\PrimaryDatabaseDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use stdClass;

class PrimaryDataProvider extends PrimaryDatabaseDataProvider {

	/**
	 * @return string[]
	 */
	protected function getTableNames() {
		return [ 'b' => 'bs_books' ];
	}

	/**
	 * @return string[]
	 */
	protected function getFields() {
		return [ 'b.book_name', 'b.book_namespace', 'b.book_title' ];
	}

	/**
	 * @return array
	 */
	protected function getDefaultConds() {
		return [ 'book_type' => 'public' ];
	}

	/**
	 * @param ReaderParams $params
	 * @return array
	 */
	protected function makePreFilterConds( ReaderParams $params ) {
		$conds = $this->getDefaultConds();

		$filterFinder = new FilterFinder( $params->getFilter() );

		$bookNamespace = $filterFinder->findByField( 'page_namespace' );
		$bookTitle = $filterFinder->findByField( 'page_title' );

		if ( $bookNamespace instanceof Filter ) {
			$conds['page_namespace'] = $bookNamespace->getValue();
			$bookNamespace->setApplied();
		}
		if ( $bookTitle instanceof Filter ) {
			$conds['page_title'] = $bookTitle->getValue();
			$bookTitle->setApplied();
		}

		return $conds;
	}

	/**
	 * @param stdClass $row
	 */
	protected function appendRowToData( stdClass $row ) {
		$this->data[] = new Record( (object)[
			Record::DISPLAYTITLE => $row->book_name,
			Record::BOOK_NAMESPACE => $row->book_namespace,
			Record::BOOK_TITLE => $row->book_title
		] );
	}
}
