<?php

namespace BlueSpice\Bookshelf\Panel;

use BlueSpice\Bookshelf\TreeDataProvider;
use IContextSource;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleTreeContainer;
use Title;

class BookNavigationTreeContainer extends SimpleTreeContainer {

	/** @var Title */
	private $title = null;

	/** @var MediaWikiServices */
	private $services = null;

	/** @var array */
	private $treeData = [];

	/**
	 * @param Title $title
	 */
	public function __construct( Title $title ) {
		parent::__construct( [] );

		$this->title = $title;
		$this->services = MediaWikiServices::getInstance();
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
		$treeDataGenerator = $this->services->get( 'MWStakeCommonUITreeDataGenerator' );

		$nodes = $treeDataGenerator->generate(
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

		$treeDataProvider = new TreeDataProvider();
		$treeData = $treeDataProvider->get( $this->title );

		$this->treeData = $treeData;

		return $treeData;
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
		$name = $this->title->getPrefixedDBKey();
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
			$name = $item['name'];
			$path = $item['path'];

			$paths[$name] = $path;

			if ( isset( $item['items'] ) && !empty( $item['items'] ) ) {
				$paths = array_merge(
					$paths,
					$this->makeTreePathsList( $item['items'] )
				);
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

	public function getRequiredRLModules(): array
	{
		return [ 'mwstake.component.commonui.tree-component' ];
	}
}
