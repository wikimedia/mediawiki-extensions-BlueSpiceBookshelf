<?php

namespace BlueSpice\Bookshelf\BooksOverviewActions;

use BlueSpice\Bookshelf\IBooksOverviewAction;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;

class Watch implements IBooksOverviewAction {

	/**
	 * @var Title
	 */
	private $book = null;

	/**
	 * @var string
	 */
	private $displayTitle = '';

	/**
	 * @var bool
	 */
	private $watched = false;

	/**
	 * @param Title $book
	 * @param string $displayTitle
	 * @param bool $watched
	 */
	public function __construct( Title $book, string $displayTitle, bool $watched ) {
		$this->book = $book;
		$this->displayTitle = $displayTitle;
		$this->watched = $watched;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'book_watch';
	}

	/**
	 * @return int
	 */
	public function getPosition(): int {
		return 70;
	}

	/**
	 * @return array
	 */
	public function getClasses(): array {
		return [ 'bs-books-overview-action-watch' ];
	}

	/**
	 * @return array
	 */
	public function getIconClasses(): array {
		return [ $this->watched ? 'bi-eye-fill' : 'bi-eye' ];
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		$key = $this->watched ?
			'bs-bookshelf-books-overview-page-book-action-unwatch-text' :
			'bs-bookshelf-books-overview-page-book-action-watch-text';
		return new Message( $key );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		$titleText = $this->book->getPrefixedText();
		if ( $this->displayTitle !== '' ) {
			$titleText = $this->displayTitle;
		}
		$key = $this->watched ?
			'bs-bookshelf-books-overview-page-book-action-unwatch-title' :
			'bs-bookshelf-books-overview-page-book-action-watch-title';
		return new Message( $key, [ $titleText ] );
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		return '';
	}

	/**
	 * @return string
	 */
	public function getRequiredPermission(): string {
		return 'viewmywatchlist';
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [ 'bs.bookshelf.action.watch' ];
	}
}
