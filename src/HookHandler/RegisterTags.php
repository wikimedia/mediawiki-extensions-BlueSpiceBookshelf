<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\Tag\BookList;
use BlueSpice\Bookshelf\Tag\BookMeta;
use BlueSpice\Bookshelf\Tag\BookNav;
use BlueSpice\Bookshelf\Tag\Bookshelf;
use BlueSpice\Bookshelf\Tag\SearchInBook;
use MediaWiki\Registration\ExtensionRegistry;
use MWStake\MediaWiki\Component\GenericTagHandler\Hook\MWStakeGenericTagHandlerInitTagsHook;

class RegisterTags implements MWStakeGenericTagHandlerInitTagsHook {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeGenericTagHandlerInitTags( array &$tags ) {
		$tags[] = new BookList();
		$tags[] = new BookNav();
		$tags[] = new BookMeta();
		$tags[] = new Bookshelf();
		if ( ExtensionRegistry::getInstance()->isLoaded( 'BlueSpiceExtendedSearch' ) ) {
			$tags[] = new SearchInBook();
		}
	}
}
