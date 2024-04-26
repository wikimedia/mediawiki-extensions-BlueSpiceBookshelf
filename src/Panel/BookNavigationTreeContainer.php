<?php

namespace BlueSpice\Bookshelf\Panel;

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\TreeDataProvider;
use IContextSource;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleTreeContainer;
use MWStake\MediaWiki\Component\CommonUserInterface\TreeDataGenerator;
use Title;
use TitleFactory;

class BookNavigationTreeContainer extends SimpleTreeContainer {

	/** @var Title */
	private $title = null;

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var array */
	private $treeData = [];

	/** @var BookContextProviderFactory */
	private $bookContxtProviderFactory = null;

	/** @var BookLookup */
	private $bookLookup = null;

	/** @var TreeDataGenerator */
	private $treeDataGenerator = null;

	/** @var Title|null */
	private $forceActiveBook = null;

	/** @var string */
	private $idPrefix = null;

	/**
	 * @param Title $title
	 * @param TitleFactory $titleFactory
	 * @param BookContextProviderFactory $bookContxtProviderFactory
	 * @param BookLookup $bookLookup
	 * @param TreeDataGenerator $treeDataGenerator
	 * @param Title|null $forceActiveBook
	 * @param string $idPrefix
	 */
	public function __construct(
		Title $title, TitleFactory $titleFactory, BookContextProviderFactory $bookContxtProviderFactory,
		BookLookup $bookLookup, TreeDataGenerator $treeDataGenerator, $forceActiveBook = null, string $idPrefix = ''
	) {
		parent::__construct( [] );

		$this->title = $title;
		$this->titleFactory = $titleFactory;
		$this->bookContxtProviderFactory = $bookContxtProviderFactory;
		$this->bookLookup = $bookLookup;
		$this->treeDataGenerator = $treeDataGenerator;
		$this->forceActiveBook = $forceActiveBook;
		$this->idPrefix = $idPrefix;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return 'bs-book-tree-nav';
	}

	/**
	 * @inheritDoc
	 */
	public function getSubComponents(): array {
		$nodes = $this->treeDataGenerator->generate(
			$this->getTreeData(),
			$this->getTreeExpandPaths()
		);

		return $nodes;
	}

	/**
	 * @return array
	 */
	private function getTreeData(): array {
		if ( !empty( $this->treeData ) ) {
			return $this->treeData;
		}

		$treeDataProvider = new TreeDataProvider(
			$this->bookLookup, $this->bookContxtProviderFactory, $this->titleFactory, $this->idPrefix
		);

		$this->treeData = $treeDataProvider->get( $this->title, $this->forceActiveBook );

		return $this->treeData;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function shouldRender( IContextSource $context ): bool {
		return true;
	}

	/**
	 * @return array
	 */
	private function getTreeExpandPaths(): array {
		$name = $this->title->getPrefixedDBkey();
		$treeData = $this->getTreeData();

		$paths = $this->makeTreePathsList( $treeData );
		if ( isset( $paths[$name] ) ) {
			return [
				$paths[$name]
			];
		}

		return [];
	}

	/**
	 * @param array $items
	 * @return array
	 */
	private function makeTreePathsList( array $items ): array {
		$paths = [];
		foreach ( $items as $item ) {
			$title = $this->titleFactory->newFromText( $item['name'] );
			$name = $title->getPrefixedDBkey();
			$path = $item['path'];

			$paths[$name] = trim( $path, '/' );

			if ( isset( $item['items'] ) && !empty( $item['items'] ) ) {
				$paths = $paths + $this->makeTreePathsList( $item['items'] );
			}
		}

		return $paths;
	}

	/**
	 * @return string[]
	 */
	public function getRequiredRLStyles(): array {
		return [];
	}

	/**
	 * @return array
	 */
	public function getRequiredRLModules(): array {
		return [ 'mwstake.component.commonui.tree-component' ];
	}
}
