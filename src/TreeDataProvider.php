<?php

namespace BlueSpice\Bookshelf;

use InvalidArgumentException;
use PageHierarchyProvider;
use stdClass;
use Title;
use TitleFactory;

class TreeDataProvider {

	/** @var TitleFactory */
	private $titleFactory;

	/** @var Title */
	private $title;

	/** @var PageHierarchyProvider */
	private $phProvider = null;

	/**
	 * @param Title $title
	 * @return array
	 */
	public function get( Title $title ): array {
		$this->title = $title;

		$phProvider = $this->getPageHierarchyProvider();
		if ( $phProvider instanceof PageHierarchyProvider === false ) {
			return [];
		}

		$extendedTOC = $phProvider->getExtendedTOCJSON();

		$data = [];
		foreach ( $extendedTOC->children as $item ) {
			$this->processTocItem( $item, $data );
		}

		return $data;
	}

	/**
	 * @param stdClass $item
	 * @param array &$data
	 * @param string $path
	 * @return void
	 */
	private function processTocItem( stdClass $item, array &$data, $path = '' ): void {
		$fullId = md5( $item->text );
		$id = substr( $fullId, 0, 6 );

		$path .= "/$id";

		$itemData = [];
		$this->titleFactory = new TitleFactory();
		$title = $this->titleFactory->newFromText( $item->articleTitle );

		if ( $item->articleType === 'plain-text' ) {
			$itemData = $this->makeTextNode( $item, $title, $path );
		}
		if ( $item->articleType === 'wikilink-with-alias' ) {
			$itemData = $this->makeLinkNode( $item, $title, $path );
		}
		if ( $item->articleType === 'wikilink' ) {
			$itemData = $this->makeLinkNode( $item, $title, $path );
		}

		if ( isset( $item->children ) && !empty( $item->children ) ) {
			foreach ( $item->children as $children ) {
				$this->processTocItem( $children, $itemData['items'], $path );
			}
		}

		if ( !empty( $itemData ) ) {
			$data[] = $itemData;
		}
	}

	/**
	 * @param stdClass $item
	 * @param Title $title
	 * @param string $path
	 * @return array
	 */
	private function makeTextNode( stdClass $item, Title $title, $path ): array {
		$fullId = md5( $item->text );
		$id = substr( $fullId, 0, 6 );

		$data = [
			'id' => $id,
			'name' => $title->getPrefixedDBkey(),
			'text' => $item->text,
			'href' => $title->getLocalURL(),
			'path' => trim( $path, '/' ),
			'items' => []
		];

		$classes = $this->getClasses( $title, [ $item->articleType ] );

		if ( !empty( $classes ) ) {
			$data['classes'] = $classes;
		}

		return $data;
	}

	/**
	 * @param stdClass $item
	 * @param Title $title
	 * @param string $path
	 * @return array
	 */
	private function makeLinkNode( stdClass $item, Title $title, $path ): array {
		$data = $this->makeTextNode( $item, $title, $path );
		$data['href'] = $title->getLocalURL();

		return $data;
	}

	/**
	 * @param Title $title
	 * @return bool
	 */
	private function isActiveTitle( Title $title ): bool {
		if ( $title->equals( $this->title ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param Title $title
	 * @param array $classes
	 * @return array
	 */
	private function getClasses( Title $title, array $classes = [] ): array {
		if ( $this->isActiveTitle( $title ) ) {
			$classes[] = 'acitve';
		}

		return $classes;
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
			return $this->phProvider;
		} catch ( InvalidArgumentException $ex ) {
			return null;
		}

		return null;
	}
}
