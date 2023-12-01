<?php

namespace BlueSpice\Bookshelf\ExtendedSearch\LookupModifier;

use BlueSpice\Bookshelf\Utilities;
use BS\ExtendedSearch\Source\LookupModifier\LookupModifier;

class ParseBookFilter extends LookupModifier {

	/** @var Utilities */
	private $utility;
	/** @var array */
	private $originalFilters = [ 'books' => [] ];
	/** @var array */
	private $parsedFilters = [ 'books' => [] ];

	/**
	 * @param $lookup
	 * @param $context
	 * @param Utilities $utilities
	 */
	public function __construct( $lookup, $context, Utilities $utilities ) {
		parent::__construct( $lookup, $context );
		$this->utility = $utilities;
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
		$newValues = array_map( function( $bookName ) {
			$bookData = $this->utility->queryBookSingle( [ 'book_name' => $bookName ] );
			if ( !$bookData ) {
				return null;
			}
			return $bookData['book_title_object']->getPrefixedDbKey();
		}, $books );
		$this->parsedFilters['books'] = $newValues;
		$this->lookup->addTermsFilter( 'books', $newValues );
	}

	/**
	 * @return void
	 */
	public function undo() {
		if ( !empty( $this->originalFilters['books'] ) ) {
			$this->lookup->addTermsFilter( 'books', $this->originalFilters['books'] );
		}
		if ( !empty ( $this->parsedFilters['books'] ) ) {
			$this->lookup->removeTermsFilter( 'books', $this->parsedFilters['books'] );
		}
	}
}
