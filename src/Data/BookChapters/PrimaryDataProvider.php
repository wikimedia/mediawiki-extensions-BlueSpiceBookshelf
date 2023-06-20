<?php

namespace BlueSpice\Bookshelf\Data\BookChapters;

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

		$filterConds = $this->makePreFilterConds( $params->getFilter() );

		$res = $this->db->select(
			'bs_book_chapters',
			'*',
			$filterConds,
			__METHOD__,
			[
				 'ORDER BY' => 'chapter_number'
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

		$bookNamespace = $filterFinder->findByField( 'chapter_book_namespace' );
		$bookTitle = $filterFinder->findByField( 'chapter_book_title' );
		$PageNamespace = $filterFinder->findByField( 'chapter_page_namespace' );
		$PageTitle = $filterFinder->findByField( 'chapter_page_title' );
		$chapterTitle = $filterFinder->findByField( 'chapter_title' );
		$chapterType = $filterFinder->findByField( 'chapter_type' );

		if ( $bookNamespace instanceof Filter ) {
			$conds['chapter_book_namespace'] = $bookNamespace->getValue();
		}
		if ( $bookTitle instanceof Filter ) {
			$conds['chapter_book_title'] = $bookTitle->getValue();
		}
		if ( $PageNamespace instanceof Filter ) {
			$conds['chapter_page_namespace'] = $PageNamespace->getValue();
		}
		if ( $PageTitle instanceof Filter ) {
			$conds['chapter_page_title'] = $PageTitle->getValue();
		}
		if ( $chapterTitle instanceof Filter ) {
			$conds['chapter_title'] = $chapterTitle->getValue();
		}
		if ( $chapterType instanceof Filter ) {
			$conds['chapter_type'] = $chapterType->getValue();
		}

		return $conds;
	}

	/**
	 *
	 * @param \stdClass $row
	 */
	protected function appendRowToData( \stdClass $row ) {
		$this->data[] = new Record( (object)[
			Record::CHAPTER_ID => $row->{Record::CHAPTER_ID},
			Record::CHAPTER_BOOK_NAMESPACE => $row->{Record::CHAPTER_BOOK_NAMESPACE},
			Record::CHAPTER_BOOK_TITLE => $row->{Record::CHAPTER_BOOK_TITLE},
			Record::CHAPTER_PAGE_NAMESPACE => $row->{Record::CHAPTER_PAGE_NAMESPACE},
			Record::CHAPTER_PAGE_TITLE => $row->{Record::CHAPTER_PAGE_TITLE},
			Record::CHAPTER_TITLE => $row->{Record::CHAPTER_TITLE},
			Record::CHAPTER_NUMBER => $row->{Record::CHAPTER_NUMBER},
			Record::CHAPTER_TYPE => $row->{Record::CHAPTER_TYPE},
		] );
	}
}
