<?php

namespace BlueSpice\Bookshelf\Hook;

use Title;

interface BSBookshelfPageAddedToBookHook {

	/**
	 * @param Title $book
	 * @param Title $page
	 * @return void
	 */
	public function onBSBookshelfPageAddedToBook( Title $book, Title $page ): void;
}
