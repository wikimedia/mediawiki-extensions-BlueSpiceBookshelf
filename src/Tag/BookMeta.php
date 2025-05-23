<?php

namespace BlueSpice\Bookshelf\Tag;

use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\GenericTag;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;
use MWStake\MediaWiki\Component\GenericTagHandler\MarkerType;
use MWStake\MediaWiki\Component\GenericTagHandler\MarkerType\NoWiki;

class BookMeta extends GenericTag {

	/**
	 * @inheritDoc
	 */
	public function getTagNames(): array {
		return [ 'bookmeta', 'bs:bookmeta' ];
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
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getClientTagSpecification(): ClientTagSpecification|null {
		return null;
	}
}
