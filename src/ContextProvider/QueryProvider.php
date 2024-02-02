<?php

namespace BlueSpice\Bookshelf\ContextProvider;

use BlueSpice\Bookshelf\IBookContextProvider;
use Title;
use TitleFactory;
use WebRequest;

class QueryProvider implements IBookContextProvider {

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var WebRequest */
	private $webRequest = null;

	/** @var string|false */
	private $param = false;

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
		$this->webRequest = new WebRequest();
		$this->param = $this->webRequest->getText( 'book', '' );
	}

	/**
	 * @return bool
	 */
	public function isResponsible(): bool {
		if ( $this->param !== '' ) {
			$this->webRequest->setSessionData( 'book', $this->param );
			return true;
		}
		return false;
	}

	/**
	 * Returns the book title that should be used
	 * for the current page
	 *
	 * @return Title|null
	 */
	public function getActiveBook(): ?Title {
		$book = $this->titleFactory->newFromText( $this->param );
		if ( $book->exists() ) {
			return $book;
		}
		return null;
	}

	/**
	 * @return int
	 */
	public function getPriority(): int {
		return 100;
	}

}
