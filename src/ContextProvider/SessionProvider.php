<?php

namespace BlueSpice\Bookshelf\ContextProvider;

use BlueSpice\Bookshelf\IBookContextProvider;
use Title;
use TitleFactory;
use WebRequest;

class SessionProvider implements IBookContextProvider {
	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var string|false */
	private $sessionData = false;

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
		$webRequest = new WebRequest();
		$this->sessionData = $webRequest->getSessionData( 'book' );
	}

	/**
	 * @return bool
	 */
	public function isResponsible(): bool {
		if ( $this->sessionData !== '' ) {
			return true;
		}
		return false;
	}

	/**
	 * @return Title|null
	 */
	public function getActiveBook(): ?Title {
		$book = $this->titleFactory->newFromText( $this->sessionData );
		if ( $book->exists() ) {
			return $book;
		}
		return null;
	}

	/**
	 * @return int
	 */
	public function getPriority(): int {
		return 50;
	}

}
