<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\HeadingNumberation;
use BlueSpice\Bookshelf\TOCNumberation;
use ConfigFactory;
use OutputPage;
use Skin;

class AddChapterNumberToTitleAndHeadings {

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( ConfigFactory $configFactory ) {
		$this->config = $configFactory->makeConfig( 'bsg' );
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return bool
	 */
	public function onBeforePageDisplay( $out, $skin ) {
		$titleText = $skin->getTitle()->getFullText();
		$bookData = $this->getBookData( $out->getHTML(), $titleText );

		if ( empty( $bookData ) ) {
			return true;
		}

		$displayTitle = $out->getPageTitle();

		// Page title text might be wrapped in HTML elements and it may contain a namespace
		// <span class="mw-page-title-namespace">...</span>
		// <span class="mw-page-title-separator">:</span>
		// <span class="mw-page-title-main">...</span>
		// In any case we want to show only the subpage title or DISPLAYTITLE but
		// DISPLAYTITLE is not wrapped in html
		$regEx = '.*?(<span\s*?class="mw-page-title-main"\s*?>)(.*)(</span>).*$';
		$matches = [];
		$status = preg_match( '#' . $regEx . '#', $displayTitle, $matches );
		if ( $status ) {
			// We only want to show the subpage title.
			$subpageText = $skin->getTitle()->getSubpageText();
			$displayTitle = $matches[1] . $subpageText . $matches[3];
		}

		// If a title text is set in the book source it should be used instead of title
		// and even instead of DISPLAYTITLE
		if ( $this->config->get( 'BookshelfTitleDisplayText' ) ) {
			if ( isset( $bookData['articleDisplayTitle'] ) ) {
				$displayTitle = $bookData['articleDisplayTitle'];
			}
		}

		if ( isset( $bookData['number'] ) ) {
			$out->setPageTitle( $bookData['number'] . ' ' . $displayTitle );
		}

		return true;
	}

	/**
	 * @param OutputPage $out
	 * @param string &$text
	 * @return bool
	 */
	public function onOutputPageBeforeHTML( OutputPage $out, &$text ) {
		if ( $this->config->get( 'BookshelfPrependPageTOCNumbers' ) === false ) {
			return true;
		}

		$bookData = $this->getBookData( $text );

		if ( isset( $bookData['has-children'] ) ) {
			// Otherwise the internal headlines would have same numbers as child node articles
			return true;
		}

		if ( isset( $bookData['number'] ) ) {
			$headingNumberation = new HeadingNumberation();
			$text = $headingNumberation->execute(
				$bookData['number'],
				$text
			);

			$tocNumberation = new TOCNumberation();
			$text = $tocNumberation->execute(
				$bookData['number'],
				$text
			);
		}
		return true;
	}

	/**
	 * @param string $html
	 * @param string $titleText
	 * @return array
	 */
	private function getBookData( string $html, string $titleText = '' ): array {
		$bookshelfTag = [];
		$status = preg_match(
			'#<div\s(class=".*?bs-tag-bs_bookshelf.*?".*?)\s*><div\s(.*?)\s*></div>#',
			$html,
			$bookshelfTag
		);

		if ( !$status ) {
			return [];
		}

		return $this->extractBookData( $bookshelfTag[2], $titleText );
	}

	/**
	 * @param string $fullBookData
	 * @param string $titleText
	 * @return array
	 */
	private function extractBookData( string $fullBookData, string $titleText = '' ): array {
		if ( $fullBookData === '' ) {
			return [];
		}

		$bookData = [];

		$data = [];
		$status = preg_match( '#data-bs-number="(.*?)"#', $fullBookData, $data );
		if ( $status ) {
			$bookData['number'] = $data[1];
		}

		$data = [];
		$status = preg_match( '#data-bs-has-children="1"#', $fullBookData, $data );
		if ( $status ) {
			$bookData['has-children'] = true;
		}

		if ( $titleText === '' ) {
			return $bookData;
		}

		$data = [];
		$regEx = '&quot;articleTitle&quot;\:&quot;';
		$regEx .= preg_quote( $titleText );
		$regEx .= '&quot;,&quot;articleDisplayTitle&quot;\:&quot;(.*?)&quot;';

		$fullBookData = preg_replace_callback( '/\\\\u([0-9a-fA-F]{4})/u', static function ( $matches ) {
			return mb_convert_encoding( pack( 'H*', $matches[1] ), 'UTF-8', 'UTF-16BE' );
		}, $fullBookData );

		$status = preg_match( "#$regEx#u", $fullBookData, $data );
		if ( $status ) {
			$bookData['articleDisplayTitle'] = $data[1];
		}

		return $bookData;
	}
}
