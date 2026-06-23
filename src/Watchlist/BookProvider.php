<?php

namespace BlueSpice\Bookshelf\Watchlist;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\EnhancedStandardUIs\Watchlist\GenericWatchlistItemProvider;

/**
 * Watched books (pages in the Book namespace), shown as the "Books" tab on the enhanced
 * Special:EditWatchlist page provided by EnhancedStandardUIs.
 *
 * Removal currently uses the generic unwatch from the base provider. A Book-specific
 * removeCallback (clearing notifications for pages within the book) can be added later by
 * registering a JS `module`/`providerClass` for this provider.
 */
class BookProvider extends GenericWatchlistItemProvider {

	/**
	 * @inheritDoc
	 */
	public function getKey(): string {
		return 'books';
	}

	/**
	 * @inheritDoc
	 */
	public function getTabTitle(): string {
		return RequestContext::getMain()->msg( 'enhanced-standard-uis-watchlist-tab-books' )->text();
	}

	/**
	 * @inheritDoc
	 */
	public function getTabIcon(): string {
		return 'book';
	}

	/**
	 * @inheritDoc
	 */
	protected function isInScope( int $namespace ): bool {
		return defined( 'NS_BOOK' ) && $namespace === NS_BOOK;
	}
}
