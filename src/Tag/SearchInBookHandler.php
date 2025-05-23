<?php

namespace BlueSpice\Bookshelf\Tag;

use BlueSpice\Bookshelf\BookLookup;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Tag\TagSearchHandler;
use Config;
use Exception;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

class SearchInBookHandler extends TagSearchHandler {

	/** @var string|null */
	private ?string $book = null;

	/**
	 * @param Config $config
	 * @param BookLookup $bookLookup
	 * @param int $tagId
	 */
	public function __construct(
		Config $config,
		private readonly BookLookup $bookLookup,
		int $tagId,
	) {
		parent::__construct( $config, $tagId );
	}

	/**
	 * @inheritDoc
	 */
	public function getRenderedContent( string $input, array $params, Parser $parser, PPFrame $frame ): string {
		$this->book = $params['book'] ?? null;
		return parent::getRenderedContent( $input, $params, $parser, $frame );
	}

	/**
	 * @param Lookup $lookup
	 * @return void
	 * @throws Exception
	 */
	protected function modifyLookup( Lookup $lookup ) {
		$bookPrefixed = $this->getBookFilterValue( $this->book );
		if ( !$bookPrefixed ) {
			throw new Exception(
				Message::newFromKey( 'bs-bookshelf-tag-searchinbook-error', $this->book )->text()
			);
		}
		$lookup->addTermsFilter( 'books', [ $bookPrefixed ] );
	}

	/**
	 * @param mixed $book
	 * @return string|null
	 */
	private function getBookFilterValue( mixed $book ): ?string {
		if ( !$book || !is_string( $book ) ) {
			return null;
		}

		$title = $this->bookLookup->getBookTitleFromName( $book );
		if ( !$title ) {
			return null;
		}
		return $title->getPrefixedDBkey();
	}
}
