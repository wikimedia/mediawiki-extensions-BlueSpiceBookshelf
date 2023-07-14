<?php

namespace BlueSpice\Bookshelf;

use MediaWiki\Extension\MenuEditor\IMenuNodeProcessor;
use MediaWiki\Extension\MenuEditor\Node\RawText;
use MediaWiki\Extension\MenuEditor\Node\WikiLink;
use MediaWiki\Extension\MenuEditor\Parser\WikitextMenuParser;
use MediaWiki\Revision\RevisionRecord;
use MWStake\MediaWiki\Lib\Nodes\INodeProcessor;
use MWStake\MediaWiki\Lib\Nodes\MutableNode;
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
		$supportedNames = [ 'menu-raw-text', 'menu-wiki-link' ];
		$supportedNodeProcessors = [];

		foreach ( $supportedNames as $key => $name ) {
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
			if ( $node instanceof MutableNode === false ) {
				continue;
			}

			if ( $node instanceof RawText ) {
				$chapters[] = new ChapterDataModel(
					null,
					null,
					$node->getNodeText(),
					$this->buildChapterNumber( $node->getLevel(), $lastLevel, $number ),
					ChapterDataModel::PLAIN_TEXT
				);
			} elseif ( $node instanceof WikiLink ) {
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

}
