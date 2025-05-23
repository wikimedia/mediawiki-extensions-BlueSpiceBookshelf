<?php

namespace BlueSpice\Bookshelf\Tests;

use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use PageHierarchyProvider;

/**
 * @group BlueSpice
 * @group BlueSpiceBookmaker
 * @group BlueSpiceBookshelf
 * @group Database
 *
 * @covers PageHierarchyProvider
 */
class PageHierarchyProviderTest extends MediaWikiIntegrationTestCase {

	/**
	 *
	 * @return Title
	 */
	private function getTestBookTitle() {
		return Title::makeTitle( NS_BOOK, 'UNIT TEST BOOK' );
	}

	/**
	 *
	 * @var PageHierarchyProvider
	 */
	protected $oPHP = null;

	public function addDBDataOnce() {
		$this->insertPage(
			$this->getTestBookTitle()->getPrefixedText(),
			file_get_contents( __DIR__ . '/data/Book_1.wiki' )
		);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->oPHP = PageHierarchyProvider::getInstanceFor(
			$this->getTestBookTitle()->getPrefixedText()
		);
	}

	public function testGetExtendedTOCJSON() {
		$oTree = $this->oPHP->getExtendedTOCJSON();

		$this->assertSame(
			$this->getTestBookTitle()->getPrefixedText(),
			$oTree->articleTitle
		);

		$this->assertObjectHasProperty( 'children', $oTree );
		$this->assertCount( 3, $oTree->children );

		$oChapterTwo = $oTree->children[1];
		$this->assertObjectHasProperty( 'children', $oChapterTwo );
		$this->assertCount( 4, $oChapterTwo->children );

		$this->recursiveCheckTreeNode( $oTree );
	}

	public function recursiveCheckTreeNode( $oTreeNode ) {
		$this->assertObjectHasProperty( 'text', $oTreeNode );
		# $this->assertObjectHasProperty( 'bookshelf', $oTreeNode );

		$this->assertObjectHasProperty( 'articleId', $oTreeNode );
		$this->assertObjectHasProperty( 'articleTitle', $oTreeNode );
		$this->assertObjectHasProperty( 'articleDisplayTitle', $oTreeNode );

		$this->assertObjectHasProperty( 'children', $oTreeNode );
		$this->assertIsArray( $oTreeNode->children );

		foreach ( $oTreeNode->children as $oChildNode ) {
			$this->recursiveCheckTreeNode( $oChildNode );
		}
	}

	public function testGetNumberFor() {
		$this->assertEquals(
			'2.1.1',
			$this->oPHP->getNumberFor( 'የከፈተውን' )
		);

		$this->assertEquals(
			'2.1.2',
			$this->oPHP->getNumberFor( 'Media:Test テスト.xls' )
		);
	}

	public function testGetEntryFor() {
		$aEntry = (array)$this->oPHP->getEntryFor( 'Dolor sit' );

		$this->assertEquals( 'Dolor sit', $aEntry['articleTitle'] );
		$this->assertEquals( 'amet', $aEntry['articleDisplayTitle'] );
		$this->assertFalse( $aEntry['articleIsRedirect'] );
		$this->assertSame( '2', $aEntry['articleNumber'] );
		$this->assertEquals( 'wikilink-with-alias', $aEntry['articleType'] );
	}
}
