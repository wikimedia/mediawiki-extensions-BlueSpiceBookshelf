<?php
/**
 * Bookshelf extension for BlueSpice
 *
 * Enables BlueSpice to manage and export hierarchical collections of articles
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://bluespice.com
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @author     Sebastian Ulbricht
 * @package    BlueSpiceBookshelf
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

class Bookshelf extends BsExtensionMW {

	/**
	 * extension.json callback
	 */
	public static function onRegistration() {
		//phpcs:disable
		global $wgExtraNamespaces, $bsgSystemNamespaces;
		//phpcs:enable
		if ( !defined( 'NS_BOOK' ) ) {
			define( 'NS_BOOK', 1504 );
			$wgExtraNamespaces[NS_BOOK] = 'Book';
			$bsgSystemNamespaces[1504] = 'NS_BOOK';
		}

		if ( !defined( 'NS_BOOK_TALK' ) ) {
			define( 'NS_BOOK_TALK', 1505 );
			$wgExtraNamespaces[NS_BOOK_TALK] = 'Book_talk';
			$bsgSystemNamespaces[1505] = 'NS_BOOK_TALK';
		}
	}

	/**
	 *
	 */
	protected function initExt() {
		// Hooks and Events
		$this->setHook( 'ParserFirstCallInit' );

		$this->setHook( 'BSUEModulePDFgetPage' );
		$this->setHook( 'BSUEModulePDFbeforeGetPage' );

		$this->setHook( 'BSUEModulePDFcollectMetaData' );
		$this->setHook( 'BSUEModulePDFAfterFindFiles' );

		$this->setHook( 'SkinTemplateNavigation' );
	}

	/**
	 * Adds the "Add to book" menu entry in view mode
	 *
	 * @param SkinTemplate $oSkinTemplate
	 * @param array &$links
	 *
	 * @return bool Always true to keep hook running
	 */
	public function onSkinTemplateNavigation( $oSkinTemplate, &$links ) {
		if ( $this->getTitle()->exists() === false ) {
			return true;
		}
		if ( $this->getTitle()->userCan( 'edit' ) === false ) {
			return true;
		}

		$links['actions']['bookshelf-add-to-book'] = [
			'text' => wfMessage( 'bs-bookshelf-add-to-book-label' )->text(),
			'href' => '#',
			'class' => false,
			'id' => 'ca-bookshelf-add-to-book'
		];

		return true;
	}

	/**
	 *
	 * @param Title $oTitle
	 * @param DOMDocument $oPageDOM
	 * @param array &$aParams
	 * @param DOMXPath $oDOMXPath
	 * @param array &$aMeta
	 * @return bool Always true to keep hook running
	 */
	public function onBSUEModulePDFcollectMetaData( $oTitle, $oPageDOM, &$aParams, $oDOMXPath,
			&$aMeta ) {
		if ( $oTitle->getNamespace() != NS_BOOK ) {
			return true;
		}

		if ( $this->getConfig()->get( 'BookshelfSupressBookNS' ) ) {
			// Otherwise it has intentionally been overwritten and we don't want to overwrite it
			// again
			if ( $aMeta['title'] === $oTitle->getPrefixedText() ) {
			 $aMeta['title'] = $oTitle->getText();
			}
		}
		// TODO RBV (01.02.12 14:14): Currently the bs:bookmeta tag renders a
		// div.bs-universalexport-meta. Therefore things like "subtitle" are
		// read in by BsPDFPageProvider. Not sure if this is good...
		return true;
	}

	/**
	 * Modify document for UEModulePDF: Add numbers to headlines, etc.
	 * @param Title $oTitle The title that gets exported
	 * @param array &$aPage The page array from UEModulePDFs PDFPageProvider
	 * @param array &$aParams All given params
	 * @param DOMXPath $oDOMXPath
	 * @return bool
	 */
	public function onBSUEModulePDFgetPage( $oTitle, &$aPage, &$aParams, $oDOMXPath ) {
		$bUserNumberHeadings = (bool)$this->getUser()->getOption( 'numberheadings' );
		// TODO RBV (10.02.12 16:35): Use Hook "BSUEModulePDFcleanUpDOM"
		// Remove <bs:bookshelf ... /> generated Markup
		$oBookshelfTagContainerElements =
			$oDOMXPath->query( "//*[contains(@class, 'bs-bookshelf-toc')]" );
		foreach ( $oBookshelfTagContainerElements as $oBookshelfTagContainerElement ) {
			$oBookshelfTagContainerElement->parentNode
				->removeChild( $oBookshelfTagContainerElement );
		}

		$sRequestedTitle = $oTitle->getPrefixedText();
		$sDisplayTitle = $sRequestedTitle;
		try {
			$oPHP = PageHierarchyProvider::getInstanceForArticle( $sRequestedTitle );
			$oEntry = $oPHP->getEntryFor( $sRequestedTitle );
			$sNumber = $oEntry->articleNumber;

			if ( isset( $oEntry->articleDisplayTitle ) ) {
				$sDisplayTitle = $oEntry->articleDisplayTitle;
			}
			// Fallback in case of no display title but subpage
			if ( str_replace( '_', ' ', $sDisplayTitle ) === $sRequestedTitle
					&& $oTitle->isSubpage() ) {
				$sDisplayTitle = basename( $oTitle->getText() );
			}
			$bHasChildren = isset( $oEntry->children ) && !empty( $oEntry->children );

			$aPage['number'] = $sNumber;

			// Add number to headlines
			$sClassesToPrefix = [
				'bs-ue-document-title',
				'firstHeading',
				// ATTENTION: In old MW this was 'mw-headline', but this now matches
				// with 'mw-headline-number'
				'mw-headline-number',
				'tocnumber'
			];

			$sClassXpath = implode( "') or contains(@class, '", $sClassesToPrefix );
			$sClassXpath = "contains(@class, '" . $sClassXpath . "')";

			$oHeadlineElements = $oDOMXPath->query( "//*[$sClassXpath]" );
			foreach ( $oHeadlineElements as $oHeadlineElement ) {
				if ( $oHeadlineElement->firstChild === null ) { continue;
				}

				$sSeperator = '.';
				$sCssClass = $oHeadlineElement->getAttribute( 'class' );
				if ( in_array( $sCssClass, [ 'bs-ue-document-title', 'firstHeading' ] ) ) {
					$sSeperator = ' ';
				}
				if ( $sSeperator === '.' && $bUserNumberHeadings === false ) {
					// No numberation for internal headings
					continue;
				}

				if ( $sSeperator === '.' && $bHasChildren === true ) {
					// Avoid number collision with child node articles
					continue;
				}

				$numNode = $aPage['dom']->createElement( 'span', $sNumber . $sSeperator );
				$numNode->setAttribute( 'class', 'bs-chapter-number' );

				$oHeadlineElement->insertBefore(
					$numNode,
					$oHeadlineElement->firstChild
				);
			}

			// Modify heading nodes
			if ( $bUserNumberHeadings === true && $bHasChildren === false ) {
				$BookmarkElements = $aPage['bookmark-element']->getElementsByTagName( 'bookmark' );
				foreach ( $BookmarkElements as $oBookmarkElement ) {
					$sName = $oBookmarkElement->getAttribute( 'name' );
					$oBookmarkElement->setAttribute( 'name', $sNumber . '.' . $sName );
				}
			}

			// Modify page title node
			$aPage['bookmark-element']->setAttribute(
				'name', $sNumber . ' ' . $aPage['bookmark-element']->getAttribute( 'name' )
			);

			$aAncestors = $oPHP->getAncestorsFor( $sRequestedTitle );
			$this->createRunningHeader( $aPage, $aAncestors, $oPHP->getBookMeta() );
		}
		catch ( Exception $e ) {
			// No bookshelf tag? Well in this case we do not need to take any action...
			wfDebugLog(
				'BS::Bookshelf',
				'onBSUEModulePDFgetPage: Error: ' . $e->getMessage()
			);
		}
		return true;
	}

	/**
	 * Adds File attachment feature to UEModulePDF
	 * @param object $oSender
	 * @param DOMDocument $oHtml
	 * @param array &$aFiles
	 * @param array $aParams
	 * @param DOMXPath $oDOMXPath
	 * @return bool always true to keep hook running
	 */
	public function onBSUEModulePDFAfterFindFiles( $oSender, $oHtml, &$aFiles, $aParams,
			$oDOMXPath ) {
		global $wgUploadPath;
		// Find all files for attaching and merging...
		if ( $aParams['attachments'] != '1' ) {
			return true;
		}

		// Backwards compatibility
		if ( !empty( $aFiles['ATTACHMENT'] ) ) {
			foreach ( $aFiles['ATTACHMENT'] as $sFileName => $sFilePath ) {
				$aFiles['attachments'][$sFileName] = $sFilePath;
			}
			unset( $aFiles['ATTACHMENT'] );
		}

		// TODO RBV (08.02.11 15:15): Necessary to exclude images?
		$oFileAnchorElements = $oDOMXPath->query(
			"//a[contains(@class,'internal') and not(contains(@class, 'image'))]"
		);
		foreach ( $oFileAnchorElements as $oFileAnchorElement ) {
			if ( $oFileAnchorElement instanceof DOMElement === false ) {
				continue;
			}
			$sHref = urldecode( $oFileAnchorElement->getAttribute( 'href' ) );

			$vUploadPathIndex = strpos( $sHref, $wgUploadPath );
			if ( $vUploadPathIndex == false ) {
				continue;
			}

			// Available with MW1.24+ and BS2.23+
			$sFileTitle = $oFileAnchorElement->getAttribute( 'data-bs-title' );
			$oTitle = Title::newFromText( $sFileTitle );
			if ( $oTitle === null ) {
				// Fallback to less secure standard attribute
				$sFileTitle = $oFileAnchorElement->getAttribute( 'title' );
				$oTitle = Title::makeTitle( NS_FILE, $sFileTitle );
			}
			if ( $oTitle->exists() ) {
				$oFile = RepoGroup::singleton()->findFile( $oTitle );
				$oBackend = $oFile->getRepo()->getBackend();
				$oLocalFile = $oBackend->getLocalReference(
					[ 'src' => $oFile->getPath() ]
				);

				// TODO: This should be part of BSF
				$sHrefFilename = str_replace(
					[ '/', '\\', ':', '*', '?', '"', '|', '>', '<', ],
					'-',
					str_replace( ' ', '_', $oTitle->getText() )
					/*
					 * Replacing spaces with underscores is because of the
					 * new implementation of "BShtml2PDF.war"
					 * org.xhtmlrenderer.pdf.ITextOutputDevice.processLink:271ff
					 * uses the uri to build the filename _and_ to fetch the
					 * resource. But xhtmlrenderes user agent needs URI
					 * encoded spaces (%20) to get the data. But thos would
					 * also show up in the file name. A better solution would
					 * be to have a seperate data-fs-embed-file-name attribute
					 * for unencoded filename
					 */
				);
				$sAbsoluteFileSystemPath = $oLocalFile->getPath();
			} else {
				$sRelativeHref = substr( $sHref, $vUploadPathIndex );
				$sHrefFilename = basename( $sRelativeHref );
				$sAbsoluteFileSystemPath = $oSender->getFileSystemPath( $sRelativeHref );
			}

			\Hooks::run(
				'BSUEModulePDFFindFiles',
				[
					$oSender,
					$oFileAnchorElement,
					&$sAbsoluteFileSystemPath,
					&$sHrefFilename,
					'attachments'
				]
			);
			$oFileAnchorElement->setAttribute( 'data-fs-embed-file', 'true' );
			// https://otrs.hallowelt.biz/otrs/index.pl?Action=AgentTicketZoom;TicketID=5036#30864
			$oFileAnchorElement->setAttribute( 'href', 'attachments/' . $sHrefFilename );
			// Neccessary for org.xhtmlrenderer.pdf.ITextReplacedElementFactory.
			// Otherwise not replaceable
			$sStyle = $oFileAnchorElement->getAttribute( 'style' );
			$oFileAnchorElement->setAttribute( 'style', 'display:inline-block;' . $sStyle );
			$aFiles['attachments'][$sHrefFilename] = $sAbsoluteFileSystemPath;
		}

		return true;
	}

	/**
	 * Sets new Parser-Hooks for the <bs:bookshelf /> tag
	 * @param Parser &$oParser The current parser object from MediaWiki Framework
	 * @return true
	 */
	public function onParserFirstCallInit( &$oParser ) {
		$oParser->setHook( 'bs:bookshelf',   [ $this, 'onBookshelfTag' ] );
		$oParser->setHook( 'bookshelf',      [ $this, 'onBookshelfTag' ] );
		$oParser->setHook( 'htoc',           [ $this, 'onBookshelfTag' ] );
		$oParser->setHook( 'hierachicaltoc', [ $this, 'onBookshelfTag' ] );

		$oParser->setHook( 'bs:bookmeta',  [ $this, 'onBookshelfMetaTag' ] );
		$oParser->setHook( 'bookmeta',     [ $this, 'onBookshelfMetaTag' ] );

		$oParser->setHook( 'bs:booklist',  [ $this, 'onBookshelfListTag' ] );
		$oParser->setHook( 'booklist',     [ $this, 'onBookshelfListTag' ] );

		$oParser->setHook( 'bs:booknode',     [ $this, 'onBookshelfNodeTag' ] );
		$oParser->setHook( 'booknode',     [ $this, 'onBookshelfNodeTag' ] );
		return true;
	}

	/**
	 * @param string $sInput Content of $lt;bs:collectiontoc /&gt; from MediaWiki Framework
	 * @param array $aAttributes Attributes of &lt;bs:collectiontoc /&gt; from MediaWiki Framework
	 * @param Parser $oParser Parser object from MediaWiki Framework
	 * @return string
	 */
	public function onBookshelfTag( $sInput, $aAttributes, $oParser ) {
		$sSourceArticleName =
			BsCore::sanitizeArrayEntry( $aAttributes, 'src', '', BsPARAMTYPE::STRING );
		$sSourceArticleName =
			BsCore::sanitizeArrayEntry( $aAttributes, 'book', $sSourceArticleName,
				BsPARAMTYPE::STRING );
		$iTreePanelWidth =
			BsCore::sanitizeArrayEntry( $aAttributes, 'width', 300, BsPARAMTYPE::INT );
		$iTreePanelHeight =
			BsCore::sanitizeArrayEntry( $aAttributes, 'height', 400, BsPARAMTYPE::INT );
		$sTreePanelFloat =
			BsCore::sanitizeArrayEntry( $aAttributes, 'float', '', BsPARAMTYPE::STRING );
		$sTreePanelStyle =
			BsCore::sanitizeArrayEntry( $aAttributes, 'style', '', BsPARAMTYPE::STRING );

		$oErrorListView = new ViewTagErrorList( $this );
		if ( !empty( $sTreePanelStyle ) ) {
			$sTreePanelStyle = ' style="' . $sTreePanelStyle . '"';
		} else {
				if ( $sTreePanelFloat == 'right' || $sTreePanelFloat == 'left' ) {
			$sMarginSide = ( $sTreePanelFloat == 'right' ) ? 'left' : 'right';
					$sTreePanelStyle = ' style="margin-' . $sMarginSide . ': 10px; float: '
							. $sTreePanelFloat . '"';
				} elseif ( !empty( $sTreePanelFloat ) ) {
					$oErrorListView->addItem(
						new ViewTagError( 'float: '
							. wfMessage( 'bs-bookshelf-tagerror-attribute-float-not-valid' )->text()
						)
					);
				}
		}

		$oTreePanelWidthValidatorResponse =
			BsValidator::isValid( 'PositiveInteger', $iTreePanelWidth, [ 'fullResponse' => true ] );
		if ( $oTreePanelWidthValidatorResponse->getErrorCode() ) {
			$oErrorListView->addItem(
				new ViewTagError( 'width: ' .
					wfMessage( 'bs-bookshelf-positive-integer-validation-not-approved' )
						->text()
				)
			);
		}

		$oTreePanelHeightValidatorResponse =
			BsValidator::isValid( 'PositiveInteger', $iTreePanelHeight,
				[ 'fullResponse' => true ] );
		if ( $oTreePanelHeightValidatorResponse->getErrorCode() ) {
			$oErrorListView->addItem(
				new ViewTagError( 'height: '
					. wfMessage( 'bs-bookshelf-positive-integer-validation-not-approved' )
						->text()
				)
			);
		}

		if ( empty( $sSourceArticleName ) ) {
			$oErrorListView->addItem(
				new ViewTagError( wfMessage( 'bs-bookshelf-tagerror-no-attribute-given' )->text() )
			);
		}

		if ( $oErrorListView->hasItems() ) {
			return $oErrorListView->execute();
		}

		$oCurrentTitle = $oParser->getTitle();
		$sTitle = $oCurrentTitle->getPrefixedText();
		$sNumber = '';
		$bHasChildren = false;
		$sDisplayTitle = $sTitle;

		try {
			$oPHP = PageHierarchyProvider::getInstanceFor(
				$sSourceArticleName,
				[ 'follow-redirects' => true ]
			);
			$oJSTreeJSON = $oPHP->getExtendedTOCJSON();
			$oEntry = $oPHP->getEntryFor( $sTitle );
			if ( $oEntry !== null ) {
				$sNumber = $oEntry->articleNumber;

				if ( isset( $oEntry->articleDisplayTitle ) ) {
					$sDisplayTitle = $oEntry->articleDisplayTitle;
				}
				// Fallback in case of no display title but subpage
				if ( str_replace( '_', ' ', $sDisplayTitle ) === $sTitle && $oCurrentTitle->isSubpage() ) {
					$sDisplayTitle = basename( $oCurrentTitle->getText() );
				}
				$bHasChildren = isset( $oEntry->children ) && !empty( $oEntry->children );
			}
		} catch ( Exception $e ) {
			$oErrorListView->addItem(
				new ViewTagError(
					wfMessage(
						'bs-bookshelf-tagerror-article-title-not-valid',
						$sSourceArticleName
					)->plain()
				)
			);
			return $oErrorListView->execute();
		}

		$aAdditionalAttribs = [];
		\Hooks::run(
			'BSBookshelfTagBeforeRender',
			[
				&$sSourceArticleName,
				$oJSTreeJSON,
				&$sNumber,
				&$aAdditionalAttribs
			]
		);

		$oParserOut = $oParser->getOutput();

		$oParserOut->setProperty( 'bs-bookshelf-sourcearticle', $sSourceArticleName );
		$oParserOut->setProperty( 'bs-bookshelf-number', $sNumber );
		$oParserOut->setProperty( 'bs-bookshelf-display-title', $sDisplayTitle );

		if ( $this->getConfig()->get( 'BookshelfTitleDisplayText' ) ) {
			$sTitleText = $sDisplayTitle;
			if ( $sNumber ) {
				$sTitleText = '<span class="bs-chapter-number">' . $sNumber . '. </span>' . $sTitleText;
			}
			if ( $sTitleText !== $sTitle ) {
				$oParserOut->setTitleText( $sTitleText );
			}
		}

		// This seems to better place than "BeforePageDisplay" hook
		$oParserOut->addModules( 'ext.bluespice.bookshelf' );
		$oParserOut->addModuleStyles( 'ext.bluespice.bookshelf.styles' );

		$aAttribs = [
			'class' => 'bs-bookshelf-toc',
			'data-bs-src' => $sSourceArticleName,
			'data-bs-has-children' => $bHasChildren,
			'data-bs-tree' => FormatJson::encode( $oJSTreeJSON )
		];

		if ( !empty( $sNumber ) ) {
			$aAttribs['data-bs-number'] = $sNumber;
		}

		$aAttribs = array_merge( $aAttribs, $aAdditionalAttribs );

		return Html::element( 'div', $aAttribs );
	}

	private $iMetaTagCount = 0;

	/**
	 * @param string $sContent Content of $lt;bookmeta /&gt; from MediaWiki Framework
	 * @param array $aAttributes Attributes of &lt;bookmeta /&gt; from MediaWiki Framework
	 * @param Parser $oParser Parser object from MediaWiki Framework
	 * @return string
	 */
	public function onBookshelfMetaTag( $sContent, $aAttributes, $oParser ) {
		// TODO: Potential security risk: are sAttributes properly preprocessed here?
		$oParser->getOutput()->setProperty(
			'bs-bookshelf-meta',
			FormatJson::encode( $aAttributes )
		);

		$oParser->getOutput()->setProperty( 'bs-tag-universalexport-meta', 1 );
		$oParser->getOutput()->setProperty(
			'bs-universalexport-meta',
			json_encode( $aAttributes )
		);

		$aOut = [];
		$aOut[] = '<div class="bs-universalexport-meta"';
		foreach ( $aAttributes as $sKey => $sValue ) {
			$aOut[] = ' ' . $sKey . '="' . $sValue . '"';
		}
		$aOut[] = '></div>';

		return implode( '', $aOut );
	}

	/**
	 * @param string $sContent Content of $lt;booklist /&gt; from MediaWiki Framework
	 * @param array $aAttributes Attributes of &lt;booklist /&gt; from MediaWiki Framework
	 * @param Parser $oParser Parser object from MediaWiki Framework
	 * @return string
	 */
	public function onBookshelfListTag( $sContent, $aAttributes, $oParser ) {
		$oParser->disableCache();
		if ( empty( $aAttributes['filter'] ) ) {
			return 'No filter set.';
		}

		$aFilters = explode( '|', trim( $aAttributes['filter'] ) );
		$aParsedFilters = [];
		foreach ( $aFilters as $sKeyValuePair ) {
			$aParts = explode( ':', trim( $sKeyValuePair ), 2 );
			if ( count( $aParts ) < 2 ) { continue;
			}
			$sKey   = trim( $aParts[0] );
			$sValue = trim( $aParts[1] );
			$aParsedFilters[$sKey] = $sValue;
		}

		// TODO RBV (19.12.11 16:32): error message if invalid filter
		$aBooks = [];
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
				'page',
				[ 'page_id', 'page_title' ],
				[ 'page_namespace' => NS_BOOK ],
				__METHOD__,
				[ 'ORDER BY' => 'page_id' ]
		);

		foreach ( $res as $row ) {
			$oSourceTitle = Title::newFromID( $row->page_id );
			if ( $oSourceTitle === null ) { continue;
			}

			$oPHProvider  = PageHierarchyProvider::getInstanceFor(
				$oSourceTitle->getPrefixedText()
			);
			$aBookMeta    = $oPHProvider->getBookMeta();
			if ( empty( $aBookMeta ) ) {
				// No tag found?
				continue;
			}

			$aMeta = $aBookMeta;

			$bMatch = false;
			foreach ( $aParsedFilters as $sKey => $sValue ) {
				if ( empty( $aMeta[$sKey] ) ) { continue;
				}
				if ( strpos( $aMeta[$sKey], $sValue ) !== false ) {
					$bMatch = true;
				} else {
					$bMatch = false;
				}
			}
			if ( !$bMatch ) {
				// Not what we are looking for
				continue;
			}

			$aBooks[] = [
				'link' => Linker::link( $oSourceTitle ),
				'meta' => $aMeta
			];
		}

		// TODO RBV (20.12.11 10:30): Display meta in tooltip...
		// TODO: allow PDF links to be injected
		$sOut = '<ul>';
		foreach ( $aBooks as $aBook ) {
			$sOut .= '<li>' . $aBook['link'] . '</li>';
		}
		$sOut .= '</ul>';

		return $sOut;
	}

	/**
	 * Adapts page titles in PDF Export
	 * @param array &$aParams
	 * @return bool
	 */
	public function onBSUEModulePDFbeforeGetPage( &$aParams ) {
		if ( !isset( $aParams['title'] ) ) {
			return true;
		}

		if ( !$this->getConfig()->get( 'BookshelfTitleDisplayText' ) ) {
			return true;
		}

		$oTitle = Title::newFromText( $aParams['title'] );
		if ( $oTitle == null ) {
			return true;
		}

		$sTitle = $oTitle->getPrefixedText();
		try {
			$oPHP = PageHierarchyProvider::getInstanceForArticle( $sTitle );
			$oEntry = $oPHP->getEntryFor( $sTitle );
			$sDisplayTitle = $sTitle;
			if ( $oEntry !== null ) {
				if ( isset( $oEntry->articleDisplayTitle ) ) {
					$sDisplayTitle = $oEntry->articleDisplayTitle;
				}
				// Fallback in case of no display title but subpage
				if ( str_replace( '_', ' ', $sDisplayTitle ) === $sTitle && $oTitle->isSubpage() ) {
					$sDisplayTitle = basename( $oTitle->getText() );
				}
			}

			$aParams['display-title'] = $sDisplayTitle;
		}
		catch ( Exception $e ) {
			return true;
		}

		return true;
	}

	/**
	 *
	 * @param array $aPage
	 * @param array $aAncestors
	 * @param array $aBookMeta
	 * @return null
	 */
	protected function createRunningHeader( $aPage, $aAncestors, $aBookMeta ) {
		$oSourceTitle = Title::newFromText( $aAncestors['sourcearticletitle'] );

		if ( isset( $aBookMeta['title'] ) && !empty( $aBookMeta['title'] ) ) {
			$sSourceTitle = $aBookMeta['title'];
		} elseif ( $this->getConfig()->get( 'BookshelfSupressBookNS' ) ) {
			$sSourceTitle = $oSourceTitle->getText();
		} else {
			$sSourceTitle = $oSourceTitle->getPrefixedText();
		}

		$oRunningHeader = $aPage['dom']->createElement( 'div' );
		$oRunningHeader->setAttribute( 'class', 'bs-runningheader' );
		$aPage['bodycontent-element']->parentNode->insertBefore(
			$oRunningHeader, $aPage['bodycontent-element']
		);

		$oBookTitle = $aPage['dom']->createElement( 'div' );
		$oSourceTextNode = $aPage['dom']->createTextNode( $sSourceTitle );
		$oBookTitle->appendChild( $oSourceTextNode );
		$oBookTitle->setAttribute( 'class', 'bs-booktitle' );

		$oAncestorTable = $aPage['dom']->createElement( 'table' );
		$oAncestorTR = $oAncestorTable->appendChild( $aPage['dom']->createElement( 'tr' ) );
		$oRunningHeader->appendChild( $oAncestorTable );

		$oAncestorTD = $oAncestorTR->appendChild( $aPage['dom']->createElement( 'td' ) );
		$oAncestorTD->setAttribute( 'class', 'bs-ancestors-left' );
		$oAncestorTD->appendChild( $oBookTitle );

		if ( empty( $aAncestors['ancestors'] ) ) {
			// If there are no ancestors we don't need to create a second TD
			return;
		}

		$oChapterAncestors = $aPage['dom']->createElement( 'div' );
		$oChapterAncestors->setAttribute( 'class', 'bs-ancestors' );

		foreach ( $aAncestors['ancestors'] as $aAncestor ) {
			$oChapterAncestor = $aPage['dom']->createElement( 'div' );
			$oChapterAncestor->setAttribute( 'class', 'bs-ancestor' );

			$sNumberedAncestorTitle = $aAncestor['number'] . '. ' . $aAncestor['display-title'];
			$oAncestorElement = $aPage['dom']->createTextNode( $sNumberedAncestorTitle );

			$oChapterAncestor->appendChild( $oAncestorElement );
			$oChapterAncestors->appendChild( $oChapterAncestor );
		}

		$oAncestorTD = $oAncestorTR->appendChild( $aPage['dom']->createElement( 'td' ) );
		$oAncestorTD->setAttribute( 'class', 'bs-ancestors-right' );
		$oAncestorTD->appendChild( $oChapterAncestors );
	}

	/**
	 * Renders a generic node tag that gets evaluated by ... future code or clientside extensions
	 * @param String $sInput
	 * @param array $aAttributes
	 * @param Parser $oParser
	 * @return string The HTML to be send to the client
	 */
	public function onBookshelfNodeTag( $sInput, $aAttributes, $oParser ) {
		// Taken directly from Html::element implementation. This allows us to
		// use Html::rawElement below and yet give a hook handler the chance to
		// add childs to the content
		$sNodeText = strtr( $aAttributes['text'], [
			'&' => '&amp;',
			'<' => '&lt;'
		] );

		$aAttribs = [
			'class' => 'bs-bookshelf-node'
		];

		foreach ( $aAttributes as $sAttributeName => $sAttributeValue ) {
			$aAttribs['data-bs-node-' . $sAttributeName] = $sAttributeValue;
		}

		$sElement = 'span';
		\Hooks::run(
			'BSBookshelfNodeTag',
			[ strtolower( $aAttributes['type'] ), &$sNodeText, &$aAttribs, &$sElement, $oParser ]
		);

		$sOutput = Html::rawElement( $sElement, $aAttribs, $sNodeText );

		return $sOutput;
	}
}
