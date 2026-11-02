<?php

namespace BlueSpice\Bookshelf\Tag;

use BlueSpice\Bookshelf\BookLookup;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Tag\TagSearchHandler;
use Exception;
use MediaWiki\Config\Config;
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
		if ( !$this->isValidBook( $this->book ) ) {
			throw new Exception(
				Message::newFromKey( 'bs-bookshelf-tag-searchinbook-error', $this->book )->text()
			);
		}
		// Filter by book name, ParseBookFilter converts it to the indexed book page key on query
		$lookup->addTermsFilter( 'books', [ $this->book ] );
	}

	/**
	 * @param mixed $book
	 * @return bool
	 */
	private function isValidBook( mixed $book ): bool {
		if ( !$book || !is_string( $book ) ) {
			return false;
		}

		return $this->bookLookup->getBookTitleFromName( $book ) !== null;
	}
}
