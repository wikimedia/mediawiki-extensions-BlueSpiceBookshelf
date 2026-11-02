<?php

namespace BlueSpice\Bookshelf\ExtendedSearch\LookupModifier;

use BlueSpice\Bookshelf\BookLookup;
use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Source\LookupModifier\LookupModifier;
use MediaWiki\Context\IContextSource;
use MediaWiki\Title\Title;

class ParseBookFilter extends LookupModifier {

	/** @var BookLookup */
	private $bookLookup;

	/** @var array */
	private $originalFilters = [ 'books' => [] ];
	/** @var array */
	private $parsedFilters = [ 'books' => [] ];

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @param BookLookup $bookLookup
	 */
	public function __construct( $lookup, $context, BookLookup $bookLookup ) {
		parent::__construct( $lookup, $context );
		$this->bookLookup = $bookLookup;
	}

	/**
	 * @return void
	 */
	public function apply() {
		$filters = $this->lookup->getFilters();
		$terms = $filters['terms'] ?? [];
		if ( !isset( $terms['books'] ) ) {
			return;
		}
		$this->originalFilters['books'] = $terms['books'];
		$books = $terms['books'];
		// Remove the original filter
		$this->lookup->removeTermsFilter( 'books', $books );
		$newValues = array_map( function ( $book ) {
			$title = $this->bookLookup->getBookTitleFromName( $book );
			if ( $title ) {
				return $title->getPrefixedDBkey();
			}
			$title = Title::newFromText( $book );
			if ( $title && $this->bookLookup->getBookInfo( $title ) ) {
				return $title->getPrefixedDBkey();
			}
			return null;
		}, $books );
		$newValues = array_filter( $newValues );
		$this->parsedFilters['books'] = $newValues;
		$this->lookup->addTermsFilter( 'books', $newValues );
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ Backend::QUERY_TYPE_SEARCH, Backend::QUERY_TYPE_AUTOCOMPLETE ];
	}

	/**
	 * @return void
	 */
	public function undo() {
		if ( !empty( $this->parsedFilters['books'] ) ) {
			$this->lookup->removeTermsFilter( 'books', $this->parsedFilters['books'] );
		}
		if ( !empty( $this->originalFilters['books'] ) ) {
			$this->lookup->addTermsFilter( 'books', $this->originalFilters['books'] );
		}
	}
}
