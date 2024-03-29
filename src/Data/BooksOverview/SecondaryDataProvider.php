<?php

namespace BlueSpice\Bookshelf\Data\BooksOverview;

use BlueSpice\Bookshelf\BookMetaLookup;
use BlueSpice\Bookshelf\BooksOverviewActions\Delete;
use BlueSpice\Bookshelf\BooksOverviewActions\Edit;
use BlueSpice\Bookshelf\IBooksOverviewAction;
use Config;
use InvalidArgumentException;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Permissions\PermissionManager;
use PageHierarchyProvider;
use RepoGroup;
use Title;
use TitleFactory;
use User;

class SecondaryDataProvider extends \MWStake\MediaWiki\Component\DataStore\SecondaryDataProvider {

	/**
	 * @var TitleFactory
	 */
	private $titleFactory = null;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager = null;

	/**
	 * @var HookContainer
	 */
	private $hookRunner = null;

	/**
	 * @var User
	 */
	private $user = null;

	/**
	 * @var RepoGroup
	 */
	private $repoGroup = null;

	/**
	 * @var Config
	 */
	private $config = null;

	/**
	 * @var BookMetaLookup
	 */
	private $bookMetaLookup = null;

	/**
	 * Array with stock images for bookshelf image if not set in book meta data
	 * @var array
	 */
	private $stockImages = [
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage001.jpg",
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage002.jpg",
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage003.jpg",
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage004.jpg",
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage005.jpg",
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage006.jpg",
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage007.jpg",
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage008.jpg",
		"/extensions/BlueSpiceBookshelf/resources/images/BookshelfImages/bookshelfImage009.jpg"
	];

	/**
	 *
	 * @param TitleFactory $titleFactory
	 * @param PermissionManager $permissionManager
	 * @param HookContainer $hookRunner
	 * @param User $user
	 * @param RepoGroup $repoGroup
	 * @param Config $config
	 * @param BookMetaLookup $bookMetaLookup
	 */
	public function __construct(
		TitleFactory $titleFactory, PermissionManager $permissionManager,
		HookContainer $hookRunner, User $user, RepoGroup $repoGroup, Config $config, BookMetaLookup $bookMetaLookup
	) {
		$this->titleFactory = $titleFactory;
		$this->permissionManager = $permissionManager;
		$this->user = $user;
		$this->hookRunner = $hookRunner;
		$this->repoGroup = $repoGroup;
		$this->config = $config;
		$this->bookMetaLookup = $bookMetaLookup;
	}

	/**
	 *
	 * @param Record &$dataSet
	 */
	protected function doExtend( &$dataSet ) {
		$book = $this->getBook(
			$dataSet->get( Record::BOOK_NAMESPACE ),
			$dataSet->get( Record::BOOK_TITLE )
		);

		$meta = $this->makeBookMeta( $book );

		$displayTitle = $this->makeDisplayTitle( $dataSet, $book, $meta );
		$dataSet->set( Record::DISPLAYTITLE, $displayTitle );

		$subtitle = $this->makeSubtitle( $meta );
		$dataSet->set( Record::SUBTITLE, $subtitle );

		$bookshelf = $this->makeBookshelf( $meta );
		$dataSet->set( Record::BOOKSHELF, $bookshelf );

		$localUrl = $this->makeFirstChapterURL( $book );
		$dataSet->set( Record::FIRST_CHAPTER_URL, $localUrl );

		$imageURL = $this->makeImageURL( $meta );
		$dataSet->set( Record::IMAGE_URL, $imageURL );

		$this->setActions( $dataSet, $displayTitle );
	}

	/**
	 * @param Title $book
	 * @return string
	 */
	private function makeFirstChapterURL( Title $book ): string {
		$localUrl = '';

		if ( $book->isKnown() ) {
			$localUrl = $book->getLocalURL();
		}

		return $localUrl;
	}

	/**
	 * @param Title $book
	 * @return array
	 */
	private function makeBookMeta( Title $book ): array {
		$requiredMeta = [
			'title' => $book->getText(),
			'subtitle' => '',
			'bookshelfimage' => '',
			'bookshelf' => ''
		];

		$meta = $this->bookMetaLookup->getMeta( $book );

		return array_merge( $requiredMeta, $meta );
	}

	/**
	 * @param Record &$dataSet
	 * @param Title $book
	 * @param array $meta
	 * @return string
	 */
	private function makeDisplayTitle( Record &$dataSet, Title $book, array $meta ): string {
		$displayTitle = $dataSet->get( Record::DISPLAYTITLE );
		if ( $displayTitle === $dataSet->get( Record::BOOK_TITLE ) ) {
			$displayTitle = $book->getText();
		}

		if ( isset( $meta['title'] ) && $meta['title'] !== '' ) {
			$displayTitle = $meta['title'];
		}
		return $displayTitle;
	}

	/**
	 * @param array $meta
	 * @return string
	 */
	private function makeSubtitle( array $meta ): string {
		$subtitle = '';

		if ( isset( $meta['subtitle'] ) && $meta['subtitle'] !== '' ) {
			$subtitle = $meta['subtitle'];
		}

		return $subtitle;
	}

	/**
	 * @param array $meta
	 * @return string
	 */
	private function makeBookshelf( array $meta ): string {
		$bookshelf = '';

		if ( isset( $meta['bookshelf'] ) && $meta['bookshelf'] !== '' ) {
			$bookshelf = $meta['bookshelf'];
		}

		return $bookshelf;
	}

	/**
	 * @param array $meta
	 * @return string
	 */
	private function makeImageURL( array $meta ): string {
		$path = $meta['bookshelfimage'];

		if ( isset( $meta['bookshelfimage'] ) && $meta['bookshelfimage'] !== '' ) {
			$basename = basename( $meta['bookshelfimage'] );

			$fileTitle = $this->titleFactory->newFromText( $basename );

			$localFile = $this->repoGroup->findFile( $fileTitle );
			if ( $localFile !== false ) {
				$thumb = $localFile->transform( [
					'width' => 300
				] );
				$path = $thumb->getURL();
			}
		} else {
			$numOfCharInTitle = strlen( $meta['title'] );
			$numOfStockImages = count( $this->stockImages );
			$index = $numOfCharInTitle % $numOfStockImages;
			$server = $this->config->get( 'Server' );
			$scriptPath = $this->config->get( 'ScriptPath' );
			$path = $server . $scriptPath . $this->stockImages[$index];
		}

		return $path;
	}

	/**
	 * @param Record &$dataSet
	 * @param string $displayTitle
	 */
	private function setActions( Record &$dataSet, string $displayTitle ) {
		$actions = [];

		$book = $this->getBook(
			$dataSet->get( Record::BOOK_NAMESPACE ),
			$dataSet->get( Record::BOOK_TITLE )
		);

		if ( $this->user instanceof User ) {
			$actionsItems = [];

			$this->setDefaultActionItems( $actionsItems, $book, $displayTitle );
			$this->setExternalActionItems( $actionsItems, $book, $displayTitle );

			$this->sortActionItems( $actionsItems );
			foreach ( $actionsItems as $actionItem ) {
				if ( $actionItem instanceof IBooksOverviewAction === false ) {
					continue;
				}

				if ( $this->permissionManager->quickUserCan(
					$actionItem->getRequiredPermission(), $this->user, $book )
				) {
					$name = $actionItem->getName();

					$actionData = [
						'iconClass' => implode( ' ', $actionItem->getIconClasses() ),
						'class' => implode( ' ', $actionItem->getClasses() ),
						'text' => $actionItem->getText()->plain(),
						'title' => $actionItem->getTitle()->plain(),
						'href' => $actionItem->getHref(),
						'book' => $book->getPrefixedDBkey()
					];

					$actions[$name] = $actionData;
				}
			}
		}

		$dataSet->set(
			Record::ACTIONS,
			$actions
		);
	}

	/**
	 * Allow other extensions to hook in and add their own actions
	 *
	 * @param array &$actions
	 * @param Title $book
	 * @param string $displayTitle
	 */
	private function setExternalActionItems( array &$actions, Title $book, string $displayTitle ) {
		$this->hookRunner->run( 'BSBookshelfBooksOverviewBeforeSetBookActions', [ &$actions, $book, $displayTitle ] );
	}

	/**
	 * Set the default actions
	 * edit, move and delete
	 *
	 * @param array &$actions
	 * @param Title $book
	 * @param string $displayTitle
	 */
	private function setDefaultActionItems( array &$actions, Title $book, string $displayTitle ) {
		$actions = [];

		// Delete action
		$actions['delete'] = new Delete( $book, $displayTitle );

		// Edit action
		$actions['edit'] = new Edit( $book, $displayTitle );
	}

	/**
	 * @param array &$actions
	 */
	private function sortActionItems( array &$actions ) {
		usort( $actions, static function ( $actionA, $actionB ) {
			$positionA = $actionA->getPosition();
			$positionB = $actionB->getPosition();

			return $positionA > $positionB ? 1 : 0;
		} );
	}

	/**
	 * @param Title $title
	 * @return PageHierarchyProvider|null
	 */
	private function getPageHierarchyProvider( Title $title ): ?PageHierarchyProvider {
		try {
			$phProvider = PageHierarchyProvider::getInstanceFor(
				$title->getPrefixedDBkey()
			);
			return $phProvider;
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return null;
	}

	/**
	 * @param int $namespace
	 * @param string $title
	 * @return Title
	 */
	private function getBook( int $namespace, string $title ): Title {
		return $this->titleFactory->makeTitleSafe( $namespace, $title );
	}
}
