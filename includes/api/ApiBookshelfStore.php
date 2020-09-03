<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;

class ApiBookshelfStore extends BSApiExtJSStoreBase {

	/**
	 *
	 * @var LinkRenderer
	 */
	protected $linkRenderer = null;

	/**
	 *
	 * @param \ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$this->linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
	}

	/**
	 *
	 * @param string $sQuery
	 * @return stdClass[]
	 */
	protected function makeData( $sQuery = '' ) {
		$aPersonalBooks = $this->fetchPersonalBooks();
		$aBookNSBooks = $this->fetchBookNamespaceBooks();

		$aAllBooks = array_merge( $aBookNSBooks, $aPersonalBooks );
		$aFilteredBooks = [];
		$sLcQuery = strtolower( $sQuery );
		foreach ( $aAllBooks as $oDataSet ) {
			$sLcDisplayText = strtolower( $oDataSet->book_displaytext );
			if ( !empty( $sLcQuery ) && strpos( $sLcDisplayText, $sLcQuery ) === false ) {
				continue;
			}
			$aFilteredBooks[] = $oDataSet;
		}

		// This hook is DEPRECATED! Use hooks from base class instead!
		\Hooks::run(
			'BSBookshelfManagerGetBookDataComplete',
			[
				$aAllBooks,
				$this->getParameter( 'limit' ),
				$this->getParameter( 'start' ),
				'ASC'
			],
			'2.27'
		);
		return $aFilteredBooks;
	}

	/**
	 *
	 * @return string
	 */
	protected function getDescription() {
		return 'ExtJS store backend for Bookshelf';
	}

	/**
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		$aParams = parent::getAllowedParams();
		// TODO: Add 'user' field to allow fechting for different users
		return $aParams;
	}

	/**
	 *
	 * @return array
	 */
	public function getParamDescription() {
		$aDesc = parent::getParamDescription();
		// TODO: Add 'user' field to allow fechting for different users
		return $aDesc;
	}

	/**
	 *
	 * @return array
	 */
	public function fetchBookNamespaceBooks() {
		$aData = [];

		if ( MediaWikiServices::getInstance()
			->getPermissionManager()
			->userCan(
				'read',
				$this->getUser(),
				Title::makeTitle( NS_BOOK, 'X' )
			) === false
		) {
			return $aData;
		}

		$dbr = $this->getDB();
		$res = $dbr->select(
			'page',
			[ 'page_id', 'page_title', 'page_namespace' ],
			[ 'page_namespace' => NS_BOOK ],
			__METHOD__,
			[ 'ORDER BY' => 'page_title' ]
		);

		foreach ( $res as $row ) {
			$oDataSet = $this->makeDataSet( $row );
			if ( $oDataSet === null ) {
				continue;
			}
			$oDataSet->book_type = 'ns_book';
			$aData[] = $oDataSet;
		}

		return $aData;
	}

	/**
	 * We fetch all pages from the NS_USER namespace that are subpages of
	 * the current users name and contain a <bs:bookmeta /> tag
	 * @return array
	 */
	public function fetchPersonalBooks() {
		$aData = [];
		if ( $this->getUser()->isAnon() ) {
			return $aData;
		}

		$sUserBooksPrefix = wfMessage( 'bs-bookshelf-personal-books-page-prefix' )
			->inContentLanguage()
			->params( $this->getUser()->getName() )
			->plain();

		$dbr = $this->getDB();
		$res = $dbr->select(
			[ 'page', 'page_props' ],
			'*',
			[
				'pp_page = page_id',
				'page_namespace' => NS_USER,
				'pp_propname' => 'bs-bookshelf-meta',
				'page_title ' . $dbr->buildLike(
					$sUserBooksPrefix,
					$dbr->anyString()
				)
			],
			__METHOD__,
			[ 'ORDER BY' => 'page_title' ]
		);

		foreach ( $res as $row ) {
			$oDataSet = $this->makeDataSet( $row );
			if ( $oDataSet === null ) {
				continue;
			}
			// We need to remove the prefix from the display text
			$oDataSet->book_displaytext =
				str_replace( $sUserBooksPrefix, '', $oDataSet->book_displaytext );
			$oDataSet->book_type = 'user_book';
			$aData[] = $oDataSet;
		}

		return $aData;
	}

	/**
	 *
	 * @param object $row
	 * @return stdClass|null
	 */
	public function makeDataSet( $row ) {
		$oTitle = Title::newFromID( $row->page_id );
		$oPHP = PageHierarchyProvider::getInstanceFor( $oTitle->getPrefixedText() );
		$aTOC = $oPHP->getExtendedTOCArray();

		$sFirstChapterPrefixedText = null;
		$oFirstTitle = $oTitle;

		if ( isset( $aTOC[0] ) ) {
			$aFirstTitle = $aTOC[0];
			$oFirstTitle = Title::newFromText( $aFirstTitle['title'] );
			$sFirstChapterPrefixedText = $oFirstTitle instanceof Title ?
				$oFirstTitle->getPrefixedText() : $aFirstTitle['title'];
		}

		$oBook = new stdClass();
		$oBook->page_id = (int)$row->page_id;
		$oBook->page_title = $row->page_title;
		$oBook->page_namespace = (int)$row->page_namespace;
		$oBook->book_first_chapter_prefixedtext = $sFirstChapterPrefixedText;
		$oBook->book_first_chapter_link
			= $this->linkRenderer->makeLink( $oFirstTitle, $oTitle->getText() );
		$oBook->book_prefixedtext = $oTitle->getPrefixedText();
		$oBook->book_displaytext = $oTitle->getText();
		$oBook->book_meta = $oPHP->getBookMeta();

		// maybe for future or optional? Include full book tree in response. Very expensive!
		// $oBook->book_tree = $oPHP->getExtendedTOCJSON();

		// This hook is DEPRECATED! Use hooks from base class instead!
		\Hooks::run( 'BSBookshelfManagerGetBookDataRow', [ $oTitle, $oBook ] );

		return $oBook;
	}
}
