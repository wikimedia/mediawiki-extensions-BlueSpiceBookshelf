<?php

namespace BlueSpice\Bookshelf\Tag;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\FormEngine\StandaloneFormSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\GenericTag;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;
use MWStake\MediaWiki\Component\InputProcessor\Processor\StringValue;

class SearchInBook extends GenericTag {

	/** @var int */
	private int $idCounter = 0;

	/**
	 * @inheritDoc
	 */
	public function getTagNames(): array {
		return [ 'searchinbook', 'bs:searchinbook' ];
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
		$id = $this->idCounter++;

		return new SearchInBookHandler(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$services->getService( 'BSBookshelfBookLookup' ),
			$id
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getParamDefinition(): ?array {
		return [
			'placeholder' => ( new StringValue() )->setDefaultValue(
				Message::newFromKey( 'bs-extendedsearch-tagsearch-ve-tagsearch-tb-placeholder' )->text()
			),
			'book' => ( new StringValue() )->setRequired( true ),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getClientTagSpecification(): ClientTagSpecification|null {
		$formSpec = new StandaloneFormSpecification();
		$formSpec->setItems( [
			[
				'type' => 'book',
				'name' => 'book',
				'required' => true,
				'label' => Message::newFromKey( 'bs-bookshelf-droplet-search-book-label' )->text(),
				'help' => Message::newFromKey( 'bs-bookshelf-droplet-search-book-help' )->text(),
			],
			[
				'type' => 'text',
				'name' => 'placeholder',
				'label' => Message::newFromKey( 'bs-bookshelf-searchbook-ve-placeholder-label' )->text(),
				'help' => Message::newFromKey( 'bs-bookshelf-searchbook-ve-placeholder-help' )->text(),
				'widget_placeholder' => Message::newFromKey(
					'bs-bookshelf-searchbook-ve-placeholder-text'
				)->text(),
			]
		] );

		return new ClientTagSpecification(
			'SearchInBook',
			Message::newFromKey( 'bs-bookshelf-droplet-search-description' ),
			$formSpec,
			Message::newFromKey( 'bs-bookshelf-droplet-search-name' )
		);
	}
}
