<?php

namespace BlueSpice\Bookshelf\Hook\ParserFirstCallInit;

use BlueSpice\Bookshelf\TagProcessor;
use BlueSpice\Hook\ParserFirstCallInit;

class SetParserHooks extends ParserFirstCallInit {

	protected function doProcess() {
		$tagProcessor = new TagProcessor( $this->getConfig() );

		$this->parser->setHook( 'bs:booklist',  [ $tagProcessor, 'onBookshelfListTag' ] );
		$this->parser->setHook( 'booklist',     [ $tagProcessor, 'onBookshelfListTag' ] );

		$this->parser->setHook( 'bs:booknode',     [ $tagProcessor, 'onBookshelfNodeTag' ] );
		$this->parser->setHook( 'booknode',     [ $tagProcessor, 'onBookshelfNodeTag' ] );
	}
}
