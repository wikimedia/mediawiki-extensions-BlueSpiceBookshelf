<?php

namespace BlueSpice\Bookshelf\Panel;

use BlueSpice\Bookshelf\ChapterPager;
use Html;
use InvalidArgumentException;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;
use PageHierarchyProvider;

class ChapterPagerPanel extends Literal {

	/**
	 *
	 * @var Title
	 */
	protected $title = null;

	/**
	 * @var string
	 */
	private $id = '';

	/**
	 * @inheritDoc
	 */
	public function __construct( $title, $id ) {
		$this->title = $title;
		$this->id = $id;
		parent::__construct( 'bs-book-chapterpager', '' );
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 *
	 * @return string
	 */
	public function getHtml(): string {
		$chapterPager = new ChapterPager();
		$chapterPager->makePagerData( $this->title );

		$html = Html::openElement( 'div', [ 'class' => 'bs-bookshelfui-chapter-pager-default-pnl' ] );
		$html .= $chapterPager->getDefaultPagerHtml( $this->title );
		$html .= Html::closeElement( 'div' );

		return $html;
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function shouldRender( $context ): bool {
		$title = $context->getTitle();
		if ( $title->isRedirect() ) {
			$webRequestValues = $context->getRequest()->getValues();
			if ( !isset( $webRequestValues['redirect'] ) || $webRequestValues['redirect'] !== 'no' ) {
				$title = $context->getWikiPage()->getRedirectTarget();
			}
		}
		try {
			$provider = PageHierarchyProvider::getInstanceForArticle(
				$title->getPrefixedText()
			);
		} catch ( InvalidArgumentException $ex ) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRequiredRLStyles(): array {
		return [
			'ext.bluespice.bookshelf.chapter-pager.styles'
		];
	}
}
