<?php

namespace BlueSpice\Bookshelf\Content;

use MediaWiki\Content\TextContent;

class BookContent extends TextContent {
	/**
	 * @param string $text
	 * @param string $model_id
	 */
	public function __construct( $text, $model_id = 'book' ) {
		parent::__construct( $text, $model_id );
	}
}
