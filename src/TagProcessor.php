<?php

namespace BlueSpice\Bookshelf;

use Config;
use Html;
use MediaWiki\MediaWikiServices;
use PageHierarchyProvider;
use Parser;
use Title;

/**
 * Temp class, until all tags are implemented using TagRegistry
 */
class TagProcessor {
	/** @var Config */
	private $config;

	/**
	 * TagProcessor constructor.
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @return Config
	 */
	protected function getConfig() {
		return $this->config;
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

			$link = MediaWikiServices::getInstance()->getLinkRenderer()->makeLink(
				$oSourceTitle
			);
			$aBooks[] = [
				'link' => $link,
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
	 * Renders a generic node tag that gets evaluated by ... future code or clientside extensions
	 * @param string $sInput
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
		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BSBookshelfNodeTag',
			[ strtolower( $aAttributes['type'] ), &$sNodeText, &$aAttribs, &$sElement, $oParser ]
		);

		$sOutput = Html::rawElement( $sElement, $aAttribs, $sNodeText );

		return $sOutput;
	}
}
