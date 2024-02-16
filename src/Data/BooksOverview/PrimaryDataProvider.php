<?php

namespace BlueSpice\Bookshelf\Data\BooksOverview;

use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\FilterFinder;

class PrimaryDataProvider extends \BlueSpice\Data\Settings\PrimaryDataProvider {

	/**
	 *
	 * @param ReaderParams $params
	 * @return Record[]
	 */
	public function makeData( $params ) {
		$this->data = [];

		$joinCond = 'c.chapter_id = ( select c1.chapter_id from bs_book_chapters as c1 ';
		$joinCond .= 'where c1.chapter_book_id=b.book_id and not c1.chapter_title="NULL" limit 1)';

		$filterConds = $this->makePreFilterConds( $params->getFilter() );

		$res = $this->db->select(
			[ 'b' => 'bs_books', 'c' => 'bs_book_chapters' ],
			[ 'b.book_name', 'b.book_namespace', 'b.book_title', 'c.chapter_namespace', 'c.chapter_title', ],
			[],
			__METHOD__,
			[],
			[
				'c' => [
					'JOIN', [ $joinCond ]
				]
			]
		);

		foreach ( $res as $row ) {
			$this->appendRowToData( $row );
		}

		return $this->data;
	}

	/**
	 *
	 * @param Filter[] $preFilters
	 * @return array
	 */
	protected function makePreFilterConds( $preFilters ) {
		$conds = [];

		$filterFinder = new FilterFinder( $preFilters );

		$bookNamespace = $filterFinder->findByField( 'page_namespace' );
		$bookTitle = $filterFinder->findByField( 'page_title' );

		if ( $bookNamespace instanceof Filter ) {
			$conds['page_namespace'] = $bookNamespace->getValue();
		}
		if ( $bookTitle instanceof Filter ) {
			$conds['page_title'] = $bookTitle->getValue();
		}

		return $conds;
	}

	/**
	 *
	 * @param \stdClass $row
	 */
	protected function appendRowToData( \stdClass $row ) {
		$this->data[] = new Record( (object)[
			Record::DISPLAYTITLE => $row->book_name,
			Record::BOOK_NAMESPACE => $row->book_namespace,
			Record::BOOK_TITLE => $row->book_title,
			Record::CHAPTER_NAMESPACE => $row->chapter_namespace,
			Record::CHAPTER_TITLE => $row->chapter_title,
		] );
	}
}
