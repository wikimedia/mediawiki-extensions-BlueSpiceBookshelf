<?php

use BlueSpice\ExtensionAttributeBasedRegistry;

class PageHierarchyProvider {

	// Static fields
	private static $prInstances = [];

	// Instance fields
	private $sSourceArticleTitle  = '';
	private $sIndentChar          = '*';

	private $aSimpleTOC   = null;
	private $aExtendedTOC = null;

	private $oSourceArticleTitle = null;
	private $sSourceContent      = '';

	/**
	 *
	 * @var \BagOStuff
	 */
	private $cache = null;

	private function __construct( $sSourceArticleTitle, $aParams ) {
		$this->sSourceArticleTitle  = $sSourceArticleTitle;
		$this->sIndentChar          = $aParams['indent-char'];

		$this->oSourceArticleTitle = Title::newFromText( $sSourceArticleTitle );

		if ( $this->oSourceArticleTitle == null ||
			$this->oSourceArticleTitle->exists() == false ) {
			throw new InvalidArgumentException(
				'Provided SourceArticleTitle ('.$sSourceArticleTitle.') is not valid or Article '
				. 'does not exist!'
			);
		}

		$this->cache = wfGetMainCache();
		$cacheKey = $this->getCacheKey(
			$this->oSourceArticleTitle,
			__METHOD__,
			$aParams
		);

		$this->sSourceContent = $this->cache->getWithSetCallback(
			$cacheKey,
			3600,
			function () use( $aParams ) {
				$oPCP = new BsPageContentProvider();
				$data = $oPCP->getWikiTextContentFor( $this->oSourceArticleTitle, $aParams );
				return $data;
			}
		);

		// Initialize SimpleTOCArray
		$this->createSimpleTOCArrayFromContent();
	}

	/**
	 * N-glton for reusing an instance for a given source article
	 * @param string $sSourceArticleTitle The title of the article containing the hierarchical list
	 * as string. I.e. 'MyNS:Some Hierarcy Article'.
	 * @param array $aParams The parameters used for processing.
	 * @return PageHierarchyProvider Instance of the PageHierarchyProvider for the requested
	 * SourceArticle
	 */
	public static function getInstanceFor( $sSourceArticleTitle, $aParams = [] ) {
		$aParams = [ 'indent-char' => '*' ] + $aParams;
		$sParamHash = md5( $aParams['indent-char'] );
		$sInstanceKey = md5( $sSourceArticleTitle.$sParamHash );
		if ( !isset( self::$prInstances[ $sInstanceKey ] )
			|| self::$prInstances[ $sInstanceKey ] == null ) {

			 self::$prInstances[ $sInstanceKey ] =
				new PageHierarchyProvider( $sSourceArticleTitle, $aParams );
		}
		return self::$prInstances[ $sInstanceKey ];
	}

	/**
	 * N-glton for reusing an instance for a given article title.
	 * Looks if article belongs to a source article.
	 * @param string $sArticleTitle The title of the article. I.e. 'MyNS:Some Article'.
	 * @param array $aParams The parameters used for processing.
	 * @return PageHierarchyProvider Instance of the PageHierarchyProvider if source article exists,
	 * null otherwise.
	 */
	public static function getInstanceForArticle( $sArticleTitle, $aParams = [] ) {
		$aParams = [ 'indent-char' => '*' ] + $aParams;

		// Checks, if article belongs to an own toc.
		// If not, we have got to analyze the Articles content and look for the sourcearticle
		$oPageContentProvider = new BsPageContentProvider();
		$oTitle = Title::newFromText( $sArticleTitle );
		$sContent = $oPageContentProvider->getWikiTextContentFor( $oTitle, $aParams );

		// TODO: Make tag recognition more flexible.
		// I. e. BsTagFinder for finding several tags <book />, etc.
		$aBookshelfTags = BsTagFinder::find(
			$sContent,
			[ 'hierarchicaltoc', 'bookshelf', 'htoc', 'bs:bookshelf' ]
		);
		$sHTOCTitle = self::findSuitableSourceArticleReference( $aBookshelfTags );
		if ( empty( $sHTOCTitle ) ) {
			// nothing to do if no tag is found
			throw new InvalidArgumentException(
				'Provided Article ('.$sArticleTitle.') does not contain reference to sourcearticle!'
			);
		} else {
			$oHTOC = self::getInstanceFor( $sHTOCTitle, $aParams );
		}
		return $oHTOC;
	}

/**
 *
 * @param array $aTags
 * @return string The prefixed text of the source title or an empty string if nothing suitable was
 * found
 */
	private static function findSuitableSourceArticleReference( $aTags ) {
		foreach ( $aTags as $aTag ) {
			if ( empty( $aTag ) ) { continue;
			}
			$aAttributes = $aTag['attributes'];
			$sSourceArticleTitle = '';
			if ( isset( $aAttributes['sourcepagetitle'] ) ) {
				$sSourceArticleTitle = $aAttributes['sourcepagetitle'];
			}
			if ( isset( $aAttributes['book'] ) ) {
				$sSourceArticleTitle = $aAttributes['book'];
			}
			if ( isset( $aAttributes['src'] ) ) {
				$sSourceArticleTitle = $aAttributes['src'];
			}
			if ( !empty( $sSourceArticleTitle ) ) {
				return $sSourceArticleTitle;
			}
		}
		return '';
	}

	/**
	 * TODO RBV (09.09.11 15:10): locate elsewhere
	 * @return array
	 */
	public function getBookMeta() {
		$oPCP = new BsPageContentProvider();
		$sContent = $oPCP->getWikiTextContentFor( $this->oSourceArticleTitle );

		$aBookMeta = BsTagFinder::find( $sContent, [ 'bookmeta', 'bs:bookmeta' ] );

		// We only use the first (!) occurence. There shouldn't be more.
		if ( isset( $aBookMeta[0] ) && isset( $aBookMeta[0]['attributes'] ) ) {
			$aBookMeta = $aBookMeta[0]['attributes'];
		} else {
			$aBookMeta = [];
		}

		return $aBookMeta;
	}

	/**
	 * Parses a Wiki List Syntax into a numerated array.
	 * Thanks to Sebastian Ulbricht for parsing logic.
	 * @return array Two dimesional array. [0] => array(1, 3, 2, ...),
	 * [1] => [[Some Wiki Link]]. Or null if an invalid $sourceString was committed.
	 */
	public function getSimpleTOCArray() {
		return $this->aSimpleTOC;
	}

	/**
	 *
	 * @return array
	 */
	public function getExtendedTOCArray() {
		$cacheKey = $this->getCacheKey(
			$this->oSourceArticleTitle,
			__METHOD__
		);
		$extendedToc = $this->cache->get( $cacheKey );

		if ( $extendedToc === false ) {
			$this->ensureExtendedTOCArray();
			$this->cache->set( $cacheKey, $this->aExtendedTOC );
		} else {
			$this->aExtendedTOC = $extendedToc;
		}

		return $this->aExtendedTOC;
	}

	/**
	 * Creates a JSON string representation of the hierarchy
	 * @param array $aParams
	 * @return stdClass The JSON object.
	 */
	public function getExtendedTOCJSON( $aParams = [] ) {
		$cacheKey = $this->getCacheKey(
			$this->oSourceArticleTitle,
			__METHOD__,
			$aParams
		);

		$result = $this->cache->get( $cacheKey );

		if ( $result === false ) {
			$aParams = array_merge(
				[
					'suppress-number-in-text' => false,
				],
				$aParams
			);

			$this->ensureExtendedTOCArray();
			$aTOC = $this->aExtendedTOC;
			$config = \BlueSpice\Services::getInstance()->getConfigFactory()
				->makeConfig( 'bsg' );
			// TODO: inject from outside!
			$sBookDisplayText = $config->get( 'BookshelfSupressBookNS' )
				? $this->oSourceArticleTitle->getText()
				: $this->oSourceArticleTitle->getPrefixedText();

			$sJson = '{';
			$sJson .= '"text": '.FormatJson::encode( $sBookDisplayText ).','
				.'"articleTitle": "'  .$this->oSourceArticleTitle->getPrefixedText().'",'
				.'"articleDisplayTitle": '.FormatJson::encode( $sBookDisplayText ).','
				.'"articleId": "'  .$this->oSourceArticleTitle->getArticleID().'",'
				// .'"id": "'  .md5( $this->oSourceArticleTitle->getFullText() ).'",'
				.'"bookshelf": '. FormatJson::encode( [
					'type' => 'wikipage',
					'page_id' => $this->oSourceArticleTitle->getArticleID(),
					'page_namespace' => $this->oSourceArticleTitle->getNamespace(),
					'page_title' => $this->oSourceArticleTitle->getText(),
				] ).','
				.'"children": [';

			$iPreviousLevel = 1;
			$numLines = count( $aTOC ) - 1;
			for ( $l = 0; $l <= $numLines; $l++ ) {
				$aRow = $aTOC[$l];
				$iCurrentLevel = count( explode( '.', $aRow['number'] ) );
				// avoid undefined index error
				$iNextLevel = ( $l < count( $aTOC ) && isset( $aTOC[$l + 1] ) )
					? count( explode( '.', $aTOC[$l + 1]['number'] ) )
					: 0;
				$iNextLevel = ( $iNextLevel == 0 ) ? 1 : $iNextLevel;

				$sText = $aRow['display-title'];
				if ( !$aParams['suppress-number-in-text'] ) {
					$sText = $aRow['number'].'. '.$sText;
				}

				$sJson .= '{';
				$sJson .=
					// ExtJS NodeInterface properties
					// TODO: Implement reasonabe node ids:
					// <page_id>/<page_id>/Page_title_with_out_escaped_slashes/<page_id>
					'"text": '.                 FormatJson::encode( $sText ).','
					.'"id": "'.                  $aRow['number'].'",'

					.'"articleNumber": "'.      $aRow['number'].'",'
					.'"articleType": "'.         $aRow['type'].'",'
					.'"articleTitle": '.         FormatJson::encode( $aRow['title'] ).','
					.'"articleDisplayTitle": '.  FormatJson::encode( $aRow['display-title'] ).','
					.'"articleId": '.            $aRow['article-id'].','
					.'"articleIsRedirect": '.    FormatJson::encode( $aRow['is-redirect'] ).','

					// New data container
					.'"bookshelf": '. FormatJson::encode( $aRow['bookshelf'] ).',';

				$sJson .= '"children": [';
				// Has no children
				if ( $iCurrentLevel > $iNextLevel ) {
					$sJson .= ']}';
					$iLevelDifference = $iCurrentLevel - $iNextLevel;
					for ( $n = 0; $n < $iLevelDifference; $n++ ) {
						$sJson .= ']}';
					}
					$sJson .= ',';
				} elseif ( $iCurrentLevel == $iNextLevel ) {
					$sJson .= ']},';
				}
				$iPreviousLevel = $iCurrentLevel;
			}
			// Cut off tailing comma
			$sJson = substr( $sJson, 0, -1 );
			$sJson .= ']}';

			$result = FormatJson::decode( $sJson );

			$this->cache->set( $cacheKey, $result );
		}

		return $result;
	}

	/**
	 *
	 * @param string $sArticleTitle
	 * @param array $aParams
	 * @return array
	 */
	public function getEntryFor( $sArticleTitle, $aParams = [] ) {
		$oJSON = $this->getExtendedTOCJSON( $aParams );
		return $this->findEntry( $sArticleTitle, $oJSON->children );
	}

	/**
	 *
	 * @param string $sArticleTitle
	 * @param stdClass[] $aEntries
	 * @return stdClass
	 */
	protected function findEntry( $sArticleTitle, $aEntries ) {
		$sNormSearchTitle = str_replace( '_', ' ', $sArticleTitle );

		foreach ( $aEntries as $oEntry ) {
			$sNormTitle = str_replace( '_', ' ', $oEntry->articleTitle );
			if ( $sNormTitle === $sNormSearchTitle ) {
				return $oEntry;
			}
			if ( isset( $oEntry->children ) && !empty( $oEntry->children ) ) {
				$oEntry = $this->findEntry( $sArticleTitle, $oEntry->children );
				if ( $oEntry !== null ) {
					return $oEntry;
				}
			}
		}

		return null;
	}

	/**
	 * Looks for the number in TOC to which the article belongs to. If the article does not belong
	 * to the current book, it analyzes the articels content, looks for the sourcearticle and tries
	 * to find out the number there.
	 *
	 * @param string $sArticleTitle The title of the article.
	 * @param bool $recurseFlag Use $recurseFlag == true if you call it recursively from inside the
	 * function.
	 * @return string The content number in TOC or an empty string if nothing was found.
	 */
	public function getNumberFor( $sArticleTitle, $recurseFlag = false ) {
		$cacheKey = $this->getCacheKey(
			\Title::newFromText( $sArticleTitle ),
			__METHOD__
		);
		$number = $this->cache->get( $cacheKey );

		if ( $number === false ) {

			$oSearchTitle = Title::newFromText( $sArticleTitle );
			// Is this Article in the current array?
			$this->ensureExtendedTOCArray();
			foreach ( $this->aExtendedTOC as $entry ) {
				$oCurrentTitle = Title::newFromText( $entry['title'] );
				if ( $oCurrentTitle instanceof Title
					&& $oCurrentTitle->equals( $oSearchTitle ) ) {
					return $entry['number'];
				}
				if ( $sArticleTitle === $entry['title'] ) {
					return $entry['number'];
				}
			}

			// this prevents from infinite loop (this happens, when nothing is found)
			if ( $recurseFlag == true ) {
				return '';
			}

			// If not, we have got to analyze the Articles content and look for the sourcearticle
			$oPageContentProvider = new BsPageContentProvider();
			$oTitle = Title::newFromText( $sArticleTitle );
			$sContent = $oPageContentProvider->getWikiTextContentFor( $oTitle );

			$aBookshelfTags = BsTagFinder::find(
				$sContent,
				[ 'hierarchicaltoc', 'bookshelf', 'htoc', 'bs:bookshelf' ]
			);
			$sHTOCTitle = self::findSuitableSourceArticleReference( $aBookshelfTags );
			if ( empty( $sHTOCTitle ) ) { return '';
			}
			$oHTOC = self::getInstanceFor( $sHTOCTitle );

			$number = $oHTOC->getNumberFor( $sArticleTitle, 1 );
			$this->cache->set( $cacheKey, $number );
		}

		return $number;
	}

	/**
	 * Find out the extendedTOCArray with all the parents to article title.
	 * @param string $sArticleTitle The title of the page we are looking for
	 * ?do we need? param boolean $recurseFlag Use $recurseFlag == true if you call it recursively
	 * from inside the function.
	 * @param array $aParams
	 * @return array Returns an extendedTOCArray with sourcepagetitle as first array element.
	 *  array (
	 *    'sourcetitle' => 'Inhaltsverzeichnis',
	 *     0 => array (
	 *       'number' => '1',
	 *       'type' => 'wikilink-with-title',
	 *       'article-id' => '1'
	 *       'namespace-text' => '',
	 *       'namespace-id' => 0,
	 *       'revision-id' => 2957,
	 *       'title' => 'Hauptseite',
	 */
	public function getAncestorsFor( $sArticleTitle, $aParams = [] ) {
		$sNumber = $this->getNumberFor( $sArticleTitle );

		// Checks, if article belongs to an own toc.
		// If not, we have got to analyze the Articles content and look for the sourcearticle
		$oPCP = new BsPageContentProvider();
		$oTitle = Title::newFromText( $sArticleTitle );
		$sContent = $oPCP->getWikiTextContentFor( $oTitle, $aParams );

		$aBookshelfTags = BsTagFinder::find(
			$sContent,
			[ 'hierarchicaltoc', 'bookshelf', 'htoc' ]
		);
		$sHTOCTitle = self::findSuitableSourceArticleReference( $aBookshelfTags );
		if ( $sContent != $sHTOCTitle && $sHTOCTitle != $this->sSourceArticleTitle ) {
			// getting a new toc array instance
			$sTitle = $sHTOCTitle;
			$oHTOC = self::getInstanceFor( $sHTOCTitle );
			$arTOC = $oHTOC->getExtendedTOCArray();
		} else {
			$sTitle = $this->sSourceArticleTitle;
			$arTOC = $this->getExtendedTOCArray();
		}

		$aParents = null;
		$aParents['sourcearticletitle'] = $sTitle;
		$aParents['ancestors'] = [];
		if ( $sNumber == null ) {
			return $aParents;
		}
		// loop through the array and search for given numbers
		// (i.e. 1.1.1, 1.1, 1)
		foreach ( $arTOC as $iIndex => $aEntry ) {
			$sPattern = $sNumber;
			while ( strpos( $sPattern, '.' ) > 0 ) {
				$sPattern = substr( $sPattern, 0, strrpos( $sPattern, '.' ) );
				// find the array with number
				if ( $aEntry['number'] == $sPattern ) {
					$aParents['ancestors'][$iIndex] = $aEntry;
				}
			}
		}

		return $aParents;
	}

	/**
	 * Makes a string of Wikitext links as list and saves it for the set $mSSourceArticleTitle.
	 *
	 * @param array $arHierarchyArray The complete array.
	 * array (
	 *   'sourcetitle' => 'Inhaltsverzeichnis',
	 *   0 => array (
	 *     'number' => '1',
	 *     'type' => 'wikilink-with-title',
	 *     'article-id' => '1',
	 *     'namespace-text' => '',
	 *     'namespace-id' => 0,
	 *     'revision-id' => 2957,
	 *     'title' => 'Hauptseite',
	 *     ...
	 * @param string $sSummary i.e.'Edited by test'.
	 * @param array $arBookMeta
	 *
	 * @return string Returns the string list.
	 */
	public function saveHierarchy( $arHierarchyArray, $sSummary, $arBookMeta = [] ) {
		// the bookmeta info
		$sBookMeta = "<bs:bookmeta \n";
		foreach ( $arBookMeta as $key => $value ) {
			$sBookMeta .= "\t$key="."\"".$value."\" \n";
		}
		$sBookMeta .= "/>\n";

		// $sLinkList = null;
		$sLinkList = $sBookMeta;
		$util = \BlueSpice\Services::getInstance()->getBSUtilityFactory();

		foreach ( $arHierarchyArray as $key => $value ) {
			// making the number to stars 3.2.1 -> ***; 1.4 -> **
			$sStars = str_replace( '.', '',
				preg_replace( '#[0-9]{1,65535}#s', '*', $value['number'] )
			);

			$sArticleTitle         = $value['title'];
			$sArticleDisplayTitle  = $value['display-title'];

			$oArticleTitle = Title::newFromText( $sArticleTitle );
			if ( $oArticleTitle == null ) {
				$sLink = $sArticleTitle;
			} else {
				$linkHelper = $util->getWikiTextLinksHelper( '' )
					->getInternalLinksHelper()->addTargets( [
					$sArticleDisplayTitle => $oArticleTitle
				] );
				$sLink = $linkHelper->getWikitext();

				// Make sure that changes to the 'display-title' are shown on
				// next page load. This should already be done by
				// 'BsCore::addTemplateLinkDependencyByText'
				// in 'Bookshelf:onBookshelfTag' but there it doesn't work :(
				WikiPage::factory( $oArticleTitle )->doPurge();
			}

			$sLinkList .= $sStars.' '.$sLink."\n";
		}

		$oArticle = new Article( $this->oSourceArticleTitle );
		$oStatus = $oArticle->doEdit( $sLinkList, $sSummary, EDIT_DEFER_UPDATES );

		return $oStatus;
	}

	private function createSimpleTOCArrayFromContent() {
		$this->aSimpleTOC = [];

		/* Thanks to Sebastian Ulbricht! */
		$iLevel = 0;
		$aNumber = [];
		$aLines = explode( "\n", $this->sSourceContent );

		foreach ( $aLines as $sText ) {
			// Is line empty or does not start with a valid indent character?
			if ( empty( $sText ) || $sText[0] != $this->sIndentChar ) { continue;
			}

			$iDepth = 0;
			$bIsIndentCharacter = true;
			// Count indent characters '*', ':', or '#' into $intDepth and cut them off
			do {
				if ( isset( $sText[ $iDepth ] ) && $sText[ $iDepth ] == $this->sIndentChar ) {
					$iDepth++;
				} else { $bIsIndentCharacter = false;
				}
			}
			while ( $bIsIndentCharacter );
			$sText = substr( $sText, $iDepth );

			// Skip line processing if empty
			$sText = trim( $sText );
			if ( empty( $sText ) ) { continue;
			}

			if ( $iDepth > $iLevel ) {
				while ( $iDepth > $iLevel ) {
					array_push( $aNumber, 1 );
					$iLevel++;
				}
			} elseif ( $iDepth < $iLevel ) {
				while ( $iDepth < $iLevel ) {
					array_pop( $aNumber );
					$iLevel--;
					$aNumber[ $iLevel - 1 ]++;
				}
			} else {
				$aNumber[ $iLevel - 1 ]++;
			}
			$this->aSimpleTOC[] = [
				'number-array' => $aNumber,
				'text'         => trim( $sText )
			];
		}
	}

	private function createExtendedTOCArrayFromSimpleTOCArray() {
		$this->aExtendedTOC = [];
		$this->initLineProcessors();

		foreach ( $this->aSimpleTOC as $aListEntry ) {
			$aProcessedEntry = $this->processLine( $aListEntry[ 'text' ] );
			$aProcessedEntry['number'] = implode( '.', $aListEntry['number-array'] );
			$this->aExtendedTOC[] = $aProcessedEntry;
		}
	}

	private function ensureExtendedTOCArray() {
		if ( is_null( $this->aExtendedTOC ) ) {
			$this->createExtendedTOCArrayFromSimpleTOCArray();
		}
	}

	/**
	 *
	 * @param \Title $title
	 * @param string $method
	 * @param array $aParams
	 * @return string
	 */
	protected function getCacheKey( $title, $method, $aParams = [] ) {
		return $this->cache->makeKey(
			$title->getPrefixedDBkey(),
			$method,
			md5( serialize( $aParams ) ),
			$this->oSourceArticleTitle->getLatestRevID()
		);
	}

	/**
	 * @var ILineProcessor[]
	 */
	protected $lineProcessors = [];

	private function initLineProcessors() {
		$lineParserRegistry = new ExtensionAttributeBasedRegistry(
			'BlueSpiceBookshelfLineProcessors'
		);
		foreach ( $lineParserRegistry->getAllValues() as $lineParserFactory ) {
			if ( !is_callable( $lineParserFactory ) ) {
				continue;
			}
			$this->lineProcessors[] = call_user_func_array( $lineParserFactory, [] );
		}
	}

	/**
	 *
	 * @param string $line
	 * @return array
	 */
	private function processLine( $line ) {
		foreach ( $this->lineProcessors as $lineProcessor ) {
			if ( $lineProcessor->applies( $line ) ) {
				$result = $lineProcessor->process( $line );
				if ( $lineProcessor->isFinal() ) {
					break;
				}
			}
		}
		return $result->toArray();
	}
}
