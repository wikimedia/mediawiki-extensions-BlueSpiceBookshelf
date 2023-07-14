<?php

namespace BlueSpice\Bookshelf\Panel;

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\ChapterLookup;
use Html;
use IContextSource;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\ComponentBase;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleCard;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleCardFooter;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleCardHeader;
use MWStake\MediaWiki\Component\CommonUserInterface\ITabPanel;
use MWStake\MediaWiki\Component\CommonUserInterface\TreeDataGenerator;
use PageHierarchyProvider;
use Title;
use TitleFactory;

class SidebarBookPanel extends ComponentBase implements ITabPanel {

	/** @var Title */
	protected $title;

	/** @var PageHierarchyProvider */
	private $phProvider = null;

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var BookContextProviderFactory */
	private $bookContextProviderFactory = null;

	/** @var BookLookup */
	private $bookLookup = null;

	/** @var ChapterLookup */
	private $chapterLookup = null;

	/** @var TreeDataGenerator */
	private $treeDataGenerator = null;

	/**
	 * @param Title $title
	 * @param TitleFactory $titleFactory
	 * @param BookContextProviderFactory $bookContextProviderFactory
	 * @param BookLookup $bookLookup
	 * @param ChapterLookup $chapterLookup
	 * @param TreeDataGenerator $treeDataGenerator
	 */
	public function __construct(
		Title $title, TitleFactory $titleFactory, BookContextProviderFactory $bookContextProviderFactory,
		BookLookup $bookLookup, ChapterLookup $chapterLookup, TreeDataGenerator $treeDataGenerator
	) {
		$this->title = $title;
		$this->titleFactory = $titleFactory;
		$this->bookContextProviderFactory = $bookContextProviderFactory;
		$this->bookLookup = $bookLookup;
		$this->chapterLookup = $chapterLookup;
		$this->treeDataGenerator = $treeDataGenerator;
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'book-navigation-panel';
	}

	/**
	 * @inheritDoc
	 */
	public function getContainerClasses(): array {
		return [ 'book-nav-panel' ];
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRequiredRLStyles(): array {
		return [
			'ext.bookshelf.navigation-panel.styles',
			'ext.bluespice.bookshelf.chapter-pager.styles'
		];
	}

	/**
	 *
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-bookshelfui-panel-navigation-text' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-bookshelfui-panel-navigation-title' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-bookshelfui-panel-navigation-aria-label' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getAriaDesc(): Message {
		return Message::newFromKey( 'bs-bookshelfui-panel-navigation-aria-desc' );
	}

	/**
	 *
	 * @return IComponent[]
	 */
	public function getSubComponents(): array {
		$items = [];
		$items[] = new SimpleCard( [
			'id' => 'n-book-panel',
			'classes' => [ 'w-100', 'bg-transp' ],
			'items' => [
				new SimpleCardHeader( [
					'id' => 'n-book-panel-header',
					'classes' => [ 'bg-transp' ],
					'items' => [
						new Literal(
							'n-book-panel-header-text',
							'<h4>' . $this->getBookTitle() . '</h4>'
						)
					]
				] ),
				new BookNavigationChapterPagerContainer(
					$this->title, $this->titleFactory, $this->bookContextProviderFactory, $this->chapterLookup
				),
				new BookNavigationTreeContainer(
					$this->title, $this->titleFactory, $this->bookContextProviderFactory,
					$this->bookLookup, $this->treeDataGenerator
				),
				new SimpleCardFooter( [
					'id' => 'n-book-panel-footer',
					'classes' => [ 'bg-transp' ],
						'items' => [
							new Literal(
								'n-book-panel-footer-text',
								$this->getBookEditLink()
							)
						]
				] )
			]
		] );

		return $items;
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function shouldRender( IContextSource $context ): bool {
		if ( $this->title->isRedirect() ) {
			$webRequestValues = $context->getRequest()->getValues();
			if ( !isset( $webRequestValues['redirect'] ) || $webRequestValues['redirect'] !== 'no' ) {
				$redirTarget = MediaWikiServices::getInstance()->getRedirectLookup()
					->getRedirectTarget( $context->getWikiPage() );
					$this->title  = Title::newFromLinkTarget( $redirTarget );
			}
		}

		$provider = $this->bookContextProviderFactory->getProvider( $this->title );

		if ( $provider->getActiveBook() === null ) {
			return false;
		}

		return true;
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function isActive( $context ): bool {
		return $this->shouldRender( $context );
	}

	/**
	 * @return PageHierarchyProvider|null
	 */
	private function getPageHierarchyProvider(): ?PageHierarchyProvider {
		if ( $this->phProvider instanceof PageHierarchyProvider ) {
			return $this->phProvider;
		}

		try {
			$this->phProvider = PageHierarchyProvider::getInstanceForArticle(
				$this->title->getPrefixedText()
			);
			// Check if the page is actually in the book before showing the book nav
			if ( $this->phProvider->getEntryFor( $this->title->getPrefixedText() ) === null ) {
				return false;
			}
			return $this->phProvider;
		} catch ( InvalidArgumentException $ex ) {
			return null;
		}

		return null;
	}

	/**
	 * @return string
	 */
	private function getBookTitle(): string {
		if ( $this->phProvider === null ) {
			return '';
		}

		$extendedToc = $this->phProvider->getExtendedTOCJSON();
		$bookMeta = $this->phProvider->getBookMeta();

		$bookTitle = $extendedToc->bookshelf->page_title;
		if ( isset( $bookMeta['title'] ) ) {
			$bookTitle = $bookMeta['title'];
		}

		return $bookTitle;
	}

	/**
	 * @return string
	 */
	protected function getBookEditLink(): string {
		$phProvider = $this->getPageHierarchyProvider();

		if ( $phProvider instanceof PageHierarchyProvider === false ) {
			return '';
		}

		// Check if the page is actually in the book before showing the book nav
		if ( $phProvider->getEntryFor( $this->title->getPrefixedText() ) === null ) {
			return '';
		}

		$extendedToc = $phProvider->getExtendedTOCJSON();

		$bookEditorTitle = \Title::makeTitleSafe(
			$extendedToc->bookshelf->page_namespace,
			$extendedToc->bookshelf->page_title
		);

		$bookEditorLink = Html::openElement(
			'a',
			[
				'id' => 'book-panel-edit-book',
				'href' => $bookEditorTitle->getFullURL( [ 'action' => 'edit' ] ),
				'title' => wfMessage( 'bs-bookshelfui-book-title-link-edit' )->plain()
			]
		);
		$bookEditorLink .=
			wfMessage( 'bs-bookshelfui-book-title-link-edit-text' )->plain();

		$bookEditorLink .= Html::closeElement( 'a' );

		return $bookEditorLink;
	}
}
