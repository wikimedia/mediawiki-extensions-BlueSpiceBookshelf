<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\HeadingNumberation;
use BlueSpice\Bookshelf\TOCNumberation;
use ConfigFactory;
use Parser;

class HeadingNumberationHandler {

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
	 * @param Parser $parser
	 * @param string &$text
	 * @return bool
	 */
	public function onParserAfterTidy( Parser $parser, &$text ) {
		if ( $this->config->get( 'BookshelfPrependPageTOCNumbers' ) === false ) {
			return true;
		}

		$bookshelfTag = [];
		$status = preg_match(
			'#<div\s(class=".*?bs-tag-bs_bookshelf.*?".*?)\s*><div\s(.*?)\s*></div>#',
			$text,
			$bookshelfTag
		);

		if ( !$status ) {
			return true;
		}

		$bookData = $this->extractBookData( $bookshelfTag[2] );

		if ( !isset( $bookData['number'] ) ) {
			return true;
		}

		if ( isset( $bookData['has-children'] ) ) {
			// Otherwise the internal headlines would have same numbers as child node articles
			return true;
		}

		$headingNumberation = new HeadingNumberation();
		$text = $headingNumberation->execute(
			$bookData['number'],
			$text
		);

		$tocNumberation = new TOCNumberation();
		$tocHTML = $tocNumberation->execute(
			$bookData['number'],
			$parser->getOutput()->getTOCHTML()
		);

		$parser->getOutput()->setTOCHTML( $tocHTML );

		return true;
	}

	/**
	 * @param string $bookshelfTocData
	 * @return array
	 */
	private function extractBookData( string $bookshelfTocData ): array {
		if ( $bookshelfTocData === '' ) {
			return [];
		}

		$bookData = [];

		$data = [];
		$status = preg_match( '#data-bs-number="(.*?)"#', $bookshelfTocData, $data );
		if ( $status ) {
			$bookData['number'] = (int)$data[1];
		}

		$data = [];
		$status = preg_match( '#data-bs-has-children="1"#', $bookshelfTocData, $data );
		if ( $status ) {
			$bookData['has-children'] = true;
		}

		return $bookData;
	}
}
