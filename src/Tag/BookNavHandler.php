<?php

namespace BlueSpice\Bookshelf\Tag;

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\Panel\BookNavigationTreeContainer;
use BlueSpice\Bookshelf\Renderer\ComponentRenderer;
use MediaWiki\Html\Html;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\TreeDataGenerator;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;

class BookNavHandler implements ITagHandler {

	/**
	 * @param TitleFactory $titleFactory
	 * @param ComponentRenderer $componentRenderer
	 * @param BookContextProviderFactory $bookContextProviderFactory
	 * @param BookLookup $bookLookup
	 * @param TreeDataGenerator $treeDataGenerator
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory,
		private readonly ComponentRenderer $componentRenderer,
		private readonly BookContextProviderFactory $bookContextProviderFactory,
		private readonly BookLookup $bookLookup,
		private readonly TreeDataGenerator $treeDataGenerator
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getRenderedContent( string $input, array $params, Parser $parser, PPFrame $frame ): string {
		$bookTitle = $params['book'] ?? '';
		if ( $bookTitle->getNamespace() === 0 ) {
			// if only book name, without prefix is specified
			$bookTitle = $this->titleFactory->makeTitle( NS_BOOK, $bookTitle->getPrefixedDbKey() );
		}
		$bookNavTreeContainer = new BookNavigationTreeContainer(
			$bookTitle,
			$this->titleFactory,
			$this->bookContextProviderFactory,
			$this->bookLookup,
			$this->treeDataGenerator,
			$bookTitle,
			'ct-booknav-'
		);
		$subComponents = $bookNavTreeContainer->getSubComponents();
		$html = '';
		if ( ( $params['chapter'] ?? '' ) === '' ) {
			$this->buildSubComponentsHtml( $subComponents, $html );
		} else {
			$this->buildSubComponentsSegmentHtml( $subComponents, $html, $params['chapter'] );
		}

		$bookNav = Html::openElement( 'div', [
			'style' => 'display: flex;'
		] );
		$bookNav .= Html::element( 'h2', [
			'style' => 'width: 80%; margin: 0; padding: 0;'
		], $bookTitle->getText()
		);
		$bookNav .= $this->buildSearchBox();
		$bookNav .= Html::closeElement( 'div' );

		$bookNav .= Html::openElement( 'ul', [
			'class' => 'mws-tree root',
			'role' => 'tree',
			'tabindex' => '0'
		] );

		$bookNav .= $html;
		$bookNav .= Html::closeElement( 'ul' );

		return $bookNav;
	}

	/**
	 * @return string $searchBox
	 */
	private function buildSearchBox(): string {
		$searchBox = Html::openElement( 'div', [
			'data-selector' => '.bs-tag-booknav .mws-tree-item',
			'style' => 'width: 20%;'
		] );
		$searchBox .= Html::openElement( 'div', [
			'id' => 'ooui-php-1',
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'class' => 'container-filter-search oo-ui-widget oo-ui-widget-enabled oo-ui-inputWidget oo-ui-iconElement oo-ui-textInputWidget oo-ui-textInputWidget-type-search oo-ui-textInputWidget-php',
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'data-ooui' => '{"_":"OO.ui.SearchInputWidget","type":"search","icon":"search","required":false,"classes":["container-filter-search"]}'
		] );
		$searchBox .= Html::element( 'input', [
			'type' => 'search',
			'tabindex' => '0',
			'value' => '',
			'placeholder' => Message::newFromKey( 'bs-bookshelf-booknav-searchbox-placeholder' )->text(),
			'class' => 'oo-ui-inputWidget-input'
		] );
		$searchBox .= Html::element( 'span', [
			'class' => 'oo-ui-iconElement-icon oo-ui-icon-search'
		] );
		$searchBox .= Html::element( 'span', [
			'class' => 'oo-ui-indicatorElement-indicator oo-ui-indicatorElement-noIndicator'
		] );
		$searchBox .= Html::closeElement( 'div' );
		$searchBox .= Html::closeElement( 'div' );

		return $searchBox;
	}

	/**
	 * @param array $subComponents
	 * @param string &$html
	 * @return void
	 */
	private function buildSubComponentsHtml( array $subComponents, string &$html ): void {
		foreach ( $subComponents as $subComponent ) {
			$html .= $this->componentRenderer->getComponentHtml( $subComponent );
		}
	}

	/**
	 * @param array $subComponents
	 * @param string &$html
	 * @param string $chapter
	 * @return void
	 */
	private function buildSubComponentsSegmentHtml( array $subComponents, string &$html, string $chapter ): void {
		foreach ( $subComponents as $subComponent ) {
			$label = $subComponent->getText()->plain();

			if ( str_starts_with( $label, $chapter ) ) {
				$html .= $this->componentRenderer->getComponentHtml( $subComponent );
			}
		}
	}
}
