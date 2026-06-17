<?php
namespace BlueSpice\Bookshelf\Tests\Structure;

use BlueSpice\Bookshelf\BookMetaLookup;
use MediaWiki\Tests\Structure\BundleSizeTestBase;

class BundleSizeTest extends BundleSizeTestBase {

	protected function setUp(): void {
		parent::setUp();
		$metaLookup = $this->createMock( BookMetaLookup::class );
		$metaLookup->method( 'getAllMetaValuesForKey' )->willReturn( [] );
		$this->setService( 'BSBookshelfBookMetaLookup', $metaLookup );
	}

	/** @inheritDoc */
	public function getBundleSizeConfig(): string {
		return dirname( __DIR__, 3 ) . '/bundlesize.config.json';
	}
}
