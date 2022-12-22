<?php

use BlueSpice\Bookshelf\TreeParser;
use BlueSpice\ExtensionAttributeBasedRegistry;
use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'BSBookshelfTreeParser' => function ( MediaWikiServices $services ) {
		$lineParsers = new ExtensionAttributeBasedRegistry( 'BlueSpiceBookshelfLineProcessors' );
		return new TreeParser(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$lineParsers
		);
	},
	'BSBookshelfPageHierarchyProviderFactory' => function ( MediaWikiServices $services ) {
		return new \BlueSpice\Bookshelf\PageHierarchyProviderFactory();
	},
];

// @codeCoverageIgnoreEnd
