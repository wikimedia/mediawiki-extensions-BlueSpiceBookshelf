<?php

namespace BlueSpice\Bookshelf\Panel;

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\ChapterLookup;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\ComponentBase;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleButton;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleCard;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleCardFooter;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleCardHeader;
use MWStake\MediaWiki\Component\CommonUserInterface\ITabPanel;
use RawMessage;

class SidebarBookPanel extends ComponentBase implements ITabPanel {

	/** @var Title */
	protected $title;

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var BookContextProviderFactory */
	private $bookContextProviderFactory = null;

	/** @var BookLookup */
	private $bookLookup = null;

	/** @var ChapterLookup */
	private $chapterLookup = null;

	/**
	 * @param Title $title
	 * @param TitleFactory $titleFactory
	 * @param BookContextProviderFactory $bookContextProviderFactory
	 * @param BookLookup $bookLookup
	 * @param ChapterLookup $chapterLookup
	 */
	public function __construct(
		Title $title, TitleFactory $titleFactory, BookContextProviderFactory $bookContextProviderFactory,
		BookLookup $bookLookup, ChapterLookup $chapterLookup
	) {
		$this->title = $title;
		$this->titleFactory = $titleFactory;
		$this->bookContextProviderFactory = $bookContextProviderFactory;
		$this->bookLookup = $bookLookup;
		$this->chapterLookup = $chapterLookup;
	}

	/**
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
	 * @inheritDoc
	 */
	public function getRequiredRLStyles(): array {
		return [
			'ext.bookshelf.navigation-panel.styles'
		];
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-bookshelf-panel-book-navigation-text' );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-bookshelf-panel-book-navigation-title' );
	}

	/**
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-bookshelf-panel-book-navigation-aria-label' );
	}

	/**
	 * @return Message
	 */
	public function getAriaDesc(): Message {
		return Message::newFromKey( 'bs-bookshelf-panel-book-navigation-aria-desc' );
	}

	/**
	 * @return IComponent[]
	 */
	public function getSubComponents(): array {
		$bookContextProvider = $this->bookContextProviderFactory->getProvider( $this->title );
		$activeBook = $bookContextProvider->getActiveBook();

		if ( $activeBook instanceof Title === false ) {
			return [];
		}

		$items = [];

		$headerItems = [];
		if ( $this->userCanEditBook( $activeBook ) ) {
			$headerItems[] = new SimpleButton( [
				'id' => 'ca-chapter-create',
				'classes' => [ 'book-chapter-create', 'bi-bs-create-page', 'ca-new-chapter' ],
				'data-title' => $activeBook->getText(),
				'aria-label' => new Message( 'bs-bookshelf-create-chapter-btn-aria-label' ),
				'title' => new Message( 'bs-bookshelf-create-chapter-btn-title' ),
				'text' => new RawMessage( '' )
			] );
		}

		$allBooks = $this->bookLookup->getBooksForPage( $this->title );
		if ( count( $allBooks ) > 1 ) {
			$headerItems[] = new BookSelectWidget( [
					'id' => 'book-nav-pri-book-selector',
					'container-classes' => [],
					'button-classes' => [ 'btn' ],
					'menu-classes' => []
				],
				$this->title,
				$this->bookLookup,
				$this->titleFactory
			);
		}

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
							$this->getBookHeading( $activeBook )
						),
						new SimpleCard( [
							'id' => 'n-book-panel-header-actions',
							'classes' => [ 'bg-transp' ],
							'items' => $headerItems
						] )
					]
				] ),
				new BookNavigationChapterPagerContainer(
					$this->title, $this->titleFactory, $this->bookContextProviderFactory, $this->chapterLookup
				),
				new AsyncBookNavigationTreeContainer( $activeBook ),
				new SimpleCardFooter( [
					'id' => 'n-book-panel-footer',
					'classes' => [ 'bg-transp' ],
						'items' => [
							new Literal(
								'n-book-panel-footer-text',
								$this->getBookEditLink( $activeBook )
							)
						]
				] )
			]
		] );

		return $items;
	}

	/**
	 * @param IContextSource $context
	 * @return bool
	 */
	public function shouldRender( IContextSource $context ): bool {
		if ( $this->title->isRedirect() ) {
			$webRequestValues = $context->getRequest()->getValues();
			if ( !isset( $webRequestValues['redirect'] ) || $webRequestValues['redirect'] !== 'no' ) {
				$redirTarget = MediaWikiServices::getInstance()->getRedirectLookup()
					->getRedirectTarget( $context->getWikiPage() );
				if ( $redirTarget instanceof LinkTarget ) {
					$this->title = Title::newFromLinkTarget( $redirTarget );
				}
			}
		}

		$provider = $this->bookContextProviderFactory->getProvider( $this->title );

		if ( $provider->getActiveBook() === null ) {
			return false;
		}

		return true;
	}

	/**
	 * @param IContextSource $context
	 * @return bool
	 */
	public function isActive( $context ): bool {
		return $this->shouldRender( $context );
	}

	/**
	 * @param Title|null $activeBook
	 * @return string
	 */
	private function getBookHeading( $activeBook ): string {
		$heading = Html::element( 'span', [
			'class' => 'book-title'
		], $this->getBookTitle( $activeBook ) );

		return $heading;
	}

	/**
	 * @param Title|null $activeBook
	 * @return string
	 */
	private function getBookTitle( $activeBook ): string {
		if ( !$activeBook instanceof Title ) {
			return '';
		}
		$bookInfo = $this->bookLookup->getBookInfo( $activeBook );
		if ( !$bookInfo ) {
			return '';
		}
		return $bookInfo->getName();
	}

	/**
	 * @param Title|null $activeBook
	 * @return string
	 */
	protected function getBookEditLink( $activeBook ): string {
		if ( !$activeBook ) {
			return '';
		}

		if ( !$this->userCanEditBook( $activeBook ) ) {
			return '';
		}

		$bookEditorLink = Html::openElement(
			'a',
			[
				'id' => 'book-panel-edit-book',
				'href' => $activeBook->getFullURL( [ 'action' => 'edit' ] ),
				'title' => wfMessage( 'bs-bookshelfui-book-title-link-edit' )->text()
			]
		);
		$bookEditorLink .=
			wfMessage( 'bs-bookshelfui-book-title-link-edit-text' )->text();

		$bookEditorLink .= Html::closeElement( 'a' );

		return $bookEditorLink;
	}

	/**
	 * @param Title $activeBook
	 * @return void
	 */
	protected function userCanEditBook( $activeBook ) {
		$user = RequestContext::getMain()->getUser();
		$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		if ( $permissionManager->userCan( 'edit', $user, $activeBook ) ) {
			return true;
		}
		return false;
	}
}
