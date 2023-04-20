<?php

namespace BlueSpice\Bookshelf;

use Html;
use InvalidArgumentException;
use PageHierarchyProvider;
use Title;

class ChapterPager {
	/** @var string */
	protected $bookTitle;

	/** @var array */
	protected $previousTitle = [];

	/** @var array */
	protected $currentTitle = [];

	/** @var array */
	protected $nextTitle = [];

	/** @var PageHierarchyProvider|null */
	private $phProvider = null;

	/**
	 * @param Title $title
	 * @return PageHierarchyProvider|null
	 */
	private function getPageHierarchyProvider( Title $title ): ?PageHierarchyProvider {
		if ( $this->phProvider instanceof PageHierarchyProvider ) {
			return $this->phProvider;
		}

		try {
			$this->phProvider = PageHierarchyProvider::getInstanceForArticle(
				$title->getPrefixedText()
			);
			return $this->phProvider;
		} catch ( InvalidArgumentException $ex ) {
			return null;
		}

		return null;
	}

	/**
	 * @param Title $title
	 * @return void
	 */
	public function makePagerData( $title ) {
		$phProvider = $this->getPageHierarchyProvider( $title );
		if ( $phProvider instanceof PageHierarchyProvider ) {
			$extendedToc = $this->phProvider->getExtendedTOCJSON();
			$bookMeta = $this->phProvider->getBookMeta();

			$this->bookTitle = $extendedToc->bookshelf->page_title;
			if ( isset( $bookMeta['title'] ) ) {
				$this->bookTitle = $bookMeta['title'];
			}

			$flatArray = $this->flatArray( (array)$extendedToc->children );
			for ( $i = 0; $i < count( $flatArray ); $i++ ) {
				if ( $title->getFullText() === $flatArray[$i]['articleTitle'] ) {
					$this->currentTitle = $flatArray[$i];
					if ( $i > 0 ) {
						$this->previousTitle = $flatArray[$i - 1];
					}
					if ( ( $i + 1 ) < count( $flatArray ) ) {
						$this->nextTitle = $flatArray[$i + 1];
					}
				}
			}
		}
	}

	/**
	 *
	 * @param array $data
	 * @return array
	 */
	private function flatArray( $data ) {
		$items = [];
		for ( $i = 0; $i < count( $data ); $i++ ) {
			$item = (array)$data[$i];
			if ( array_key_exists( 'children', $item ) ) {
				$children = $this->flatArray( (array)$item['children'] );
				unset( $item['children'] );
				$items[] = array_merge( $items, $item );
				$items = array_merge( $items, $children );
			} else {
				$items[] = $item;
			}
		}
		return $items;
	}

	/**
	 *
	 * @return string
	 */
	public function getBookTitle() {
		return $this->bookTitle;
	}

	/**
	 *
	 * @return array
	 */
	public function getNextPageData() {
		return $this->nextTitle;
	}

	/**
	 *
	 * @return string
	 */
	private function getNextPageButton() {
		return $this->makePagerButton( $this->getNextPageData(), 'next' );
	}

	/**
	 *
	 * @return array
	 */
	public function getCurrentPageData() {
		return $this->currentTitle;
	}

	/**
	 *
	 * @return array
	 */
	public function getPreviousPageData() {
		return $this->previousTitle;
	}

	/**
	 *
	 * @return string
	 */
	private function getPreviousPageButton() {
		return $this->makePagerButton( $this->getPreviousPageData(), 'previous' );
	}

	/**
	 *
	 * @return string
	 */
	public function getPagerToolbar(): string {
		$html = Html::openElement( 'div', [ 'class' => 'bs-chapter-pager-toolbar' ] );
		$html .= $this->getPreviousPageButton();
		$html .= $this->getNextPageButton();
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * @param array $pageData
	 * @param string $type
	 * @return string
	 */
	private function makePagerButton( array $pageData, string $type ): string {
		$btnTitle = null;
	   if ( !empty( $pageData ) && isset( $pageData['articleTitle'] ) ) {
		   $btnTitle = Title::newFromText( $pageData['articleTitle'] );
	   }

	   $btnData = [];
	   if ( $btnTitle ) {
		   $btnData = [
			   'class' => "$type-chapter",
			   'href' => $btnTitle->getFullURL(),
			   'title' => $pageData['text']
		   ];
	   } else {
		   $btnData = [
			   'class' => "disabled $type-chapter",
			   'disabled' => 'true'
		   ];
	   }

	   $html = Html::openElement( 'a', $btnData );
	   /**
		* Message keys:
		* bs-bookshelfui-chapterpager-next
		* bs-bookshelfui-chapterpager-previous
		*/
	   $html .= Html::element(
			   'span',
			   [],
			   wfMessage( "bs-bookshelfui-chapterpager-$type" )->plain()
		   );
	   $html .= Html::closeElement( 'a' );

	   return $html;
	}

	/**
	 * @return string
	 */
	public function getDefaultPagerHtml() {
		$html = Html::openElement( 'div', [ 'class' => 'bs-chapter-pager-heading' ] );
		$html .= Html::element(
			'h4',
			[
				'class' => 'book-title'
			],
			$this->getBookTitle()
		);
		$html .= Html::closeElement( 'div' );
		$html .= $this->getPagerToolbar();

		return $html;
	}

}
