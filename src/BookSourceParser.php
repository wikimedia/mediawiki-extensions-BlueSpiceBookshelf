<?php

namespace BlueSpice\Bookshelf;

use BlueSpice\Bookshelf\Content\BookContent;
use BlueSpice\Bookshelf\MenuEditor\Node\ChapterPlainText;
use BlueSpice\Bookshelf\MenuEditor\Node\ChapterWikiLinkWithAlias;
use Content;
use MediaWiki\Extension\MenuEditor\IMenuNodeProcessor;
use MediaWiki\Extension\MenuEditor\Node\MenuNode;
use MediaWiki\Extension\MenuEditor\Parser\WikitextMenuParser;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MWStake\MediaWiki\Lib\Nodes\INodeProcessor;
use TitleFactory;

class BookSourceParser extends WikitextMenuParser {

	/** @var TitleFactory */
	private $titleFactory = null;

	/**
	 * @param RevisionRecord $revision
	 * @param array $nodeProcessors
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( RevisionRecord $revision, array $nodeProcessors, TitleFactory $titleFactory ) {
		$filteredNodesProcessors = $this->filterNodeProcessors( $nodeProcessors );
		$this->titleFactory = $titleFactory;
		parent::__construct( $revision, $filteredNodesProcessors );
	}

	/**
	 * @param array $nodeProcessors
	 * @return array
	 */
	private function filterNodeProcessors( array $nodeProcessors ): array {
		$filteredNodeProcessors = $this->getSupportedNodeProcessors( $nodeProcessors );

		return $filteredNodeProcessors;
	}

	/**
	 * @param array $nodeProcessors
	 * @return array
	 */
	private function getSupportedNodeProcessors( array $nodeProcessors ): array {
		$supportedNames = [ 'bs-bookshelf-chapter-plain-text', 'bs-bookshelf-chapter-wikilink-with-alias' ];
		$supportedNodeProcessors = [];

		foreach ( $supportedNames as $name ) {
			if ( isset( $nodeProcessors[$name] ) ) {
				$supportedNodeProcessors[$name] = $nodeProcessors[$name];
			}
		}

		$supportedNodeProcessors = array_filter(
			$supportedNodeProcessors,
			static function ( INodeProcessor $processor ) {
				return $processor instanceof IMenuNodeProcessor;
			}
		);

		return $supportedNodeProcessors;
	}

	/**
	 * @return DataChapterModel[]
	 */
	public function getChapterDataModelArray(): array {
		$nodes = $this->parse();

		$lastLevel = 1;
		$number = [];
		$chapters = [];
		foreach ( $nodes as $node ) {
			if ( $node instanceof MenuNode === false ) {
				continue;
			}
			if ( $node instanceof ChapterPlainText ) {
				$chapters[] = new ChapterDataModel(
					null,
					null,
					$node->getNodeText(),
					$this->buildChapterNumber( $node->getLevel(), $lastLevel, $number ),
					ChapterDataModel::PLAIN_TEXT
				);
			} elseif ( $node instanceof ChapterWikiLinkWithAlias ) {
				$target = $this->titleFactory->newFromText( $node->getTarget() );

				$name = $node->getLabel();
				if ( $name === '' ) {
					$name = $target->getText();
				}

				$chapters[] = new ChapterDataModel(
					$target->getNamespace(),
					$target->getDBkey(),
					$name,
					$this->buildChapterNumber( $node->getLevel(), $lastLevel, $number ),
					ChapterDataModel::WIKILINK_WITH_ALIAS
				);
			} else {
				continue;
			}

			$lastLevel = $node->getLevel();
		}

		return $chapters;
	}

	/**
	 * @param int $level
	 * @param int $lastLevel
	 * @param array &$number
	 * @return string
	 */
	private function buildChapterNumber( int $level, int $lastLevel, array &$number ): string {
		$index = $level - 1;
		$lastIndex = $lastLevel - 1;

		if ( $level < $lastLevel ) {
			for ( $idx = $lastIndex; $idx >= $level; $idx-- ) {
				unset( $number[$idx] );
			}
		}

		if ( !isset( $number[$index] ) ) {
			$number[$index] = 0;
		}

		$number[$index]++;

		return implode( '.', $number );
	}

	/**
	 * @return Content
	 */
	public function getContent(): Content {
		return new BookContent( $this->revision->getContent( SlotRecord::MAIN )->getText() );
	}
}
