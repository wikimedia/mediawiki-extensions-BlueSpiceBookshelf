<?php

namespace BlueSpice\Bookshelf\Tag;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\FormEngine\StandaloneFormSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\GenericTag;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;
use MWStake\MediaWiki\Component\GenericTagHandler\MarkerType;
use MWStake\MediaWiki\Component\GenericTagHandler\MarkerType\NoWiki;
use MWStake\MediaWiki\Component\InputProcessor\Processor\StringValue;

class BookList extends GenericTag {

	/**
	 * @inheritDoc
	 */
	public function getTagNames(): array {
		return [ 'booklist', 'bs:booklist' ];
	}

	/**
	 * @inheritDoc
	 */
	public function hasContent(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getHandler( MediaWikiServices $services ): ITagHandler {
		return new BookListHandler(
			$services->getTitleFactory(),
			$services->getLinkRenderer(),
			$services->getService( 'BSBookshelfBookLookup' ),
			$services->getService( 'BSBookshelfBookMetaLookup' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getMarkerType(): MarkerType {
		return new NoWiki();
	}

	/**
	 * @inheritDoc
	 */
	public function getParamDefinition(): ?array {
		$filter = new StringValue();
		$filter->setRequired( false );

		return [ 'filter' => $filter ];
	}

	/**
	 * @inheritDoc
	 */
	public function getClientTagSpecification(): ClientTagSpecification|null {
		$formSpec = new StandaloneFormSpecification();
		$formSpec->setItems( [
			[
				'type' => 'text',
				'name' => 'filter',
				'label' => Message::newFromKey( 'bs-bookshelf-ve-booklist-attr-filter-label' )->text(),
				'help' => Message::newFromKey( 'bs-bookshelf-ve-booklist-attr-filter-help' )->text(),
			],
		] );

		return new ClientTagSpecification(
			'Booklist',
			Message::newFromKey( 'bs-bookshelf-tag-booklist-description' ),
			$formSpec,
			Message::newFromKey( 'bs-bookshelf-ve-booklistinspector-title' )
		);
	}
}
