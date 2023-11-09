<?php

use BlueSpice\Bookshelf\Renderer\ComponentRenderer;
use BlueSpice\Bookshelf\TreeParser;
use BlueSpice\Bookshelf\Utilities;
use BlueSpice\ExtensionAttributeBasedRegistry;
use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'BSBookshelfTreeParser' => static function ( MediaWikiServices $services ) {
		$lineParsers = new ExtensionAttributeBasedRegistry( 'BlueSpiceBookshelfLineProcessors' );
		return new TreeParser(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$lineParsers
		);
	},
	'BSBookshelfPageHierarchyProviderFactory' => static function ( MediaWikiServices $services ) {
		return new \BlueSpice\Bookshelf\PageHierarchyProviderFactory();
	},
	'BSBookshelfUtilities' => static function ( MediaWikiServices $services ) {
		return new Utilities(
			RequestContext::getMain(),
			$services->getMainConfig(),
			$services
		);
	},
	'BSBookshelfComponentRenderer' => static function ( MediaWikiServices $services ) {
		$renderer = new ComponentRenderer(
			$services->getService( 'MWStakeCommonUIComponentManager' ),
			$services->getService( 'MWStakeCommonUIRendererDataTreeBuilder' ),
			$services->getService( 'MWStakeCommonUIRendererDataTreeRenderer' )
		);
		return $renderer;
	},
];

// @codeCoverageIgnoreEnd
