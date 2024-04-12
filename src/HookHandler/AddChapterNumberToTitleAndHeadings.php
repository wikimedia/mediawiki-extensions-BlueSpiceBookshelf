<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\ChapterInfo;
use BlueSpice\Bookshelf\ChapterLookup;
use BlueSpice\Bookshelf\HeadingNumberation;
use BlueSpice\Bookshelf\TOCNumberation;
use ConfigFactory;
use OutputPage;
use Skin;
use Title;

class AddChapterNumberToTitleAndHeadings {

	/** @var Config */
	private $config;

	/** @var BookContextProviderFactory */
	private $bookContextProviderFactory = null;

	/** @var ChapterLookup */
	private $bookChapterLookup = null;

	/** @var ChapterInfo */
	private $chapterInfo = null;

	/** @var Title */
	private $activeBook = null;

	/**
	 * @param ConfigFactory $configFactory
	 * @param BookContextProviderFactory $bookContextProviderFactory
	 * @param ChapterLookup $bookChapterLookup
	 */
	public function __construct(
		ConfigFactory $configFactory, BookContextProviderFactory $bookContextProviderFactory,
		ChapterLookup $bookChapterLookup
	) {
		$this->config = $configFactory->makeConfig( 'bsg' );
		$this->bookContextProviderFactory = $bookContextProviderFactory;
		$this->bookChapterLookup = $bookChapterLookup;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return bool
	 */
	public function onBeforePageDisplay( $out, $skin ) {
		$title = $out->getTitle();
		if ( !$title ) {
			return true;
		}

		$chapterInfo = $this->getChapterInfo( $title );
		if ( $chapterInfo instanceof ChapterInfo === false ) {
			return true;
		}

		$displayTitle = $out->getPageTitle();
		// If a title text is set in the book source it should be used instead of title
		// and even instead of DISPLAYTITLE
		if ( $this->config->get( 'BookshelfTitleDisplayText' ) ) {
			$displayTitle = $chapterInfo->getName();
		}

		$number = $chapterInfo->getNumber();

		$out->setPageTitle( "$number $displayTitle" );

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

		$activeBook = $this->getActiveBook( $out->getTitle() );
		$chapterInfo = $this->getChapterInfo( $out->getTitle() );
		if ( $chapterInfo instanceof ChapterInfo === false ) {
			return true;
		}

		$children = $this->bookChapterLookup->getChildren( $this->activeBook, $chapterInfo );
		if ( !empty( $children ) ) {
			// Otherwise the internal headlines would have same numbers as child node articles
			return true;
		}

		$headingNumberation = new HeadingNumberation();
		$text = $headingNumberation->execute(
			$chapterInfo->getNumber(),
			$text
		);

		$tocNumberation = new TOCNumberation();
		$text = $tocNumberation->execute(
			$chapterInfo->getNumber(),
			$text
		);

		return true;
	}

	/**
	 * @param Title $title
	 * @return $title|null
	 */
	private function getActiveBook( Title $title ): ?Title {
		if ( $this->activeBook === null ) {
			$bookContextProvider = $this->bookContextProviderFactory->getProvider( $title );
			$this->activeBook = $bookContextProvider->getActiveBook();
		}
		return $this->activeBook;
	}

	/**
	 * @param Title $title
	 * @return ChapterInfo|null
	 */
	private function getChapterInfo( Title $title ): ?ChapterInfo {
		if ( $this->activeBook === null ) {
			return $this->chapterInfo;
		}
		if ( $this->chapterInfo === null ) {
			$this->chapterInfo = $this->bookChapterLookup->getChapterInfoFor( $this->activeBook, $title );
		}
		return $this->chapterInfo;
	}
}
