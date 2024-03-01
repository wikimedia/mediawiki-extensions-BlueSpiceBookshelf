<?php

namespace BlueSpice\Bookshelf\Content;

use JsonContent;
use MWException;

class BookMetaContent extends JsonContent {
	/**
	 * @param string $text
	 * @param string $model_id
	 * @throws MWException
	 */
	public function __construct( $text, $model_id = 'book_meta' ) {
		parent::__construct( $text, $model_id );
	}
}
