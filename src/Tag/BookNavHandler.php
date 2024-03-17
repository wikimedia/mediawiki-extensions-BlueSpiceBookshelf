<?php

namespace BlueSpice\Bookshelf\Tag;

use BlueSpice\Bookshelf\Panel\BookNavigationTreeContainer;
use BlueSpice\Bookshelf\Renderer\ComponentRenderer;
use BlueSpice\Tag\Handler;
use Html;
use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleTreeLinkNode;
use PageHierarchyProvider;
use Parser;
use PPFrame;
use Title;
use TitleFactory;

class BookNavHandler extends Handler {

	public const ATTR_BOOK = 'book';
	public const ATTR_CHAPTER = 'chapter';

	/** @var string */
	private $bookInput = '';

	/** @var string */
	private $chapterInput = '';

	/** @var string */
	private $pageDisplayText = '';

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var ComponentRenderer */
	private $componentRenderer = null;

	/**
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param TitleFactory $titleFactory
	 * @param ComponentRenderer $componentRenderer
	 */
	public function __construct( $processedInput, array $processedArgs, Parser $parser,
		PPFrame $frame, TitleFactory $titleFactory, ComponentRenderer $componentRenderer ) {
		parent::__construct( $processedInput, $processedArgs, $parser, $frame );
		$this->bookInput = $this->processedArgs[self::ATTR_BOOK];
		$this->chapterInput = $this->processedArgs[self::ATTR_CHAPTER];
		$this->titleFactory = $titleFactory;
		$this->componentRenderer = $componentRenderer;
	}

	/**
	 * @return string $bookNav
	 */
	public function handle(): string {
		$bookTitle = $this->titleFactory->newFromText( $this->bookInput, NS_BOOK );
		if ( !$bookTitle || !$bookTitle->exists() ) {
			return Message::newFromKey( 'bs-bookshelf-booknav-book-not-exist', $this->bookInput )->plain();
		}

		$pageTitle = $this->getPageTitle( $bookTitle->getFullText() );
		if ( !$pageTitle || !$pageTitle->exists() ) {
			return Message::newFromKey( 'bs-bookshelf-booknav-chapter-not-exist', $this->chapterInput )->plain();
		}

		$parserOutput = $this->parser->getOutput();
		$parserOutput->addModules( [
			'ext.bluespice.bookshelf.bookNavFilter',
			'mwstake.component.commonui.tree-component'
		] );
		$parserOutput->addModuleStyles( [ 'ext.bluespice.bookshelf.booknav.styles' ] );

		$bookNavTreeContainer = new BookNavigationTreeContainer( $pageTitle );
		$subComponents = $bookNavTreeContainer->getSubComponents();

		$bookNav = Html::openElement( 'div', [
			'style' => 'display: flex;'
		] );
		$bookNav .= Html::element( 'h2', [
			'style' => 'width: 80%; margin: 0; padding: 0;'
		], $bookTitle->getText()
		);
		$bookNav .= $this->buildSearchBox();
		$bookNav .= Html::closeElement( 'div' );

		if ( $this->chapterInput === '' ) {
			foreach ( $subComponents as $subComponent ) {
				$bookNav .= $this->componentRenderer->getComponentHtml( $subComponent );
			}
		} else {
			$bookNav .= $this->buildTreeFromSubComponents( $subComponents );
		}

		return $bookNav;
	}

	/**
	 * @param string $bookTitle
	 * @return Title|null
	 */
	private function getPageTitle( string $bookTitle ): ?Title {
		$provider = PageHierarchyProvider::getInstanceFor( $bookTitle );
		$toc = $provider->getSimpleTOCArray();
		foreach ( $toc as $page ) {
			$chapterParts = [];
			foreach ( $page['number-array'] as $chapterPart ) {
				$chapterParts[] = $chapterPart;
			}
			$chapter = implode( '.', $chapterParts );

			if ( $chapter === $this->chapterInput || $this->chapterInput === '' ) {
				$pageText = $page['text'];
				$pageText = str_replace( '[', '', $pageText );
				$pageText = str_replace( ']', '', $pageText );
				$titleAndDisplay = explode( '|', $pageText );
				$pageTitleText = $titleAndDisplay[0];
				$this->pageDisplayText = $titleAndDisplay[1];

				return $this->titleFactory->newFromText( $pageTitleText );
			}
		}
		return null;
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
	 * @param SimpleTreeLinkNode[] $subComponents
	 * @param string &$tree
	 * @return string $tree
	 */
	private function buildTreeFromSubComponents( array $subComponents, string &$tree = '' ) {
		foreach ( $subComponents as $subComponent ) {
			$chapterBranch = $subComponent->getText()->plain();
			$spacePos = strpos( $chapterBranch, ' ' );
			$chapterBranch = substr( $chapterBranch, $spacePos + 1 );
			if ( $chapterBranch === $this->pageDisplayText ) {
				$tree .= $this->componentRenderer->getComponentHtml( $subComponent );
			}

			$subComponents = $subComponent->getSubComponents();
			if ( !empty( $subComponents ) ) {
				$this->buildTreeFromSubComponents( $subComponents, $tree );
			}
		}

		return $tree;
	}
}
