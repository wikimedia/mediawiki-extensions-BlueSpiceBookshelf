<?php

namespace BlueSpice\Bookshelf\Tag;

use BlueSpice\Bookshelf\Panel\BookNavigationTreeContainer;
use BlueSpice\Discovery\Renderer\ComponentRenderer;
use BlueSpice\Tag\Handler;
use MediaWiki\MediaWikiServices;
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
	 */
	public function __construct( $processedInput, array $processedArgs, Parser $parser,
		PPFrame $frame, TitleFactory $titleFactory ) {
		parent::__construct( $processedInput, $processedArgs, $parser, $frame );
		$this->bookInput = $this->processedArgs[self::ATTR_BOOK];
		$this->chapterInput = $this->processedArgs[self::ATTR_CHAPTER];
		$this->titleFactory = $titleFactory;
		$this->componentRenderer = MediaWikiServices::getInstance()
			->getService( 'BlueSpiceDiscoveryComponentRenderer' );
	}

	/**
	 * @return string $tree
	 */
	public function handle(): string {
		$bookTitle = $this->titleFactory->newFromText( $this->bookInput, NS_BOOK );
		if ( !$bookTitle || !$bookTitle->exists() ) {
			return Message::newFromKey( 'bs-bookshelf-booknav-book-not-exist', $this->bookInput )->plain();
		}

		$pageTitle = $this->getPageTitle();
		if ( !$pageTitle || !$pageTitle->exists() ) {
			return Message::newFromKey( 'bs-bookshelf-booknav-chapter-not-exist', $this->chapterInput )->plain();
		}

		$parserOutput = $this->parser->getOutput();
		$parserOutput->addModules( [ 'mwstake.component.commonui.tree-component' ] );
		$parserOutput->addModuleStyles( [ 'ext.bluespice.booknav.styles' ] );

		$bookNavTreeContainer = new BookNavigationTreeContainer( $pageTitle );
		$subComponents = $bookNavTreeContainer->getSubComponents();

		if ( $this->chapterInput === '' ) {
			$string = '';
			foreach ( $subComponents as $subComponent ) {
				$string .= $this->componentRenderer->getComponentHtml( $subComponent );
			}
			return $string;
		}

		$tree = $this->extractTreeFromSubComponents( $subComponents );

		return $tree;
	}

	/**
	 * @return Title|null
	 */
	private function getPageTitle(): ?Title {
		$provider = PageHierarchyProvider::getInstanceFor( "Book:$this->bookInput" );
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
	 * @param SimpleTreeLinkNode[] $subComponents
	 * @param string &$tree
	 * @return string $tree
	 */
	private function extractTreeFromSubComponents( array $subComponents, string &$tree = '' ) {
		foreach ( $subComponents as $subComponent ) {
			$chapterBranch = $subComponent->getText()->plain();
			$spacePos = strpos( $chapterBranch, ' ' );
			$chapterBranch = substr( $chapterBranch, $spacePos + 1 );
			if ( $chapterBranch === $this->pageDisplayText ) {
				$tree .= $this->componentRenderer->getComponentHtml( $subComponent );
			}

			$subComponents = $subComponent->getSubComponents();
			if ( !empty( $subComponents ) ) {
				$this->extractTreeFromSubComponents( $subComponents, $tree );
			}
		}

		return $tree;
	}
}
