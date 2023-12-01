<?php

namespace BlueSpice\Bookshelf\ExtendedSearch\LookupModifier;

use BS\ExtendedSearch\Source\LookupModifier\LookupModifier;

class AddBookAggregation extends LookupModifier {

	/**
	 * @inerhitDoc
	 */
	public function apply() {
		$this->lookup->setBucketTermsAggregation( 'books' );
	}

	/**
	 * @inerhitDoc
	 */
	public function undo() {
		$this->lookup->removeBucketTermsAggregation( 'books' );
	}
}
