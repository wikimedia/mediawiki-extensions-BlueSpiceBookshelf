<?php

namespace BlueSpice\Bookshelf\Panel;

use BlueSpice\Bookshelf\ChapterPager;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;
use Title;

class BookNavigationChapterPagerContainer extends Literal {

	/** @var Title */
	private $title = null;

	/**
	 * @param Title $title
	 */
	public function __construct( Title $title ) {
		parent::__construct( '', '' );

		$this->title = $title;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'bs-book-navigation-chapter-pager';
	}

	/**
	 * Raw HTML string
	 *
	 * @return string
	 */
	public function getHtml(): string {
		$chapterPager = new ChapterPager();
		$chapterPager->makePagerData( $this->title );

		return $chapterPager->getPagerToolbar();
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRequiredRLStyles(): array {
		return [ 'ext.bluespice.bookshelf.chapter-pager.styles' ];
	}

}
