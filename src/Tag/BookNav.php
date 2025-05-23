<?php

namespace BlueSpice\Bookshelf\Tag;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\FormEngine\StandaloneFormSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\GenericTag;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;
use MWStake\MediaWiki\Component\InputProcessor\Processor\StringValue;

class BookNav extends GenericTag {

	/**
	 * @inheritDoc
	 */
	public function getTagNames(): array {
		return [ 'booknav' ];
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
		return new BookNavHandler(
			$services->getTitleFactory(),
			$services->getService( 'BSBookshelfComponentRenderer' ),
			$services->getService( 'BSBookshelfBookContextProviderFactory' ),
			$services->getService( 'BSBookshelfBookLookup' ),
			$services->getService( 'MWStakeCommonUITreeDataGenerator' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getParamDefinition(): ?array {
		return [
			'book' => [
				'type' => 'title',
				// We have to allow both NS_MAIN and NS_BOOK, in case specified name has no NS prefix
				'allowedNamespaces' => [ NS_MAIN, NS_BOOK ],
				'required' => true,
			],
			'chapter' => new StringValue(),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getClientTagSpecification(): ClientTagSpecification|null {
		$formSpec = new StandaloneFormSpecification();
		$formSpec->setItems( [
			[
				'type' => 'title',
				'name' => 'book',
				'required' => true,
				'label' => Message::newFromKey( 'bs-bookshelf-booknav-book-label' )->text(),
				'help' => Message::newFromKey( 'bs-bookshelf-booknav-book-help' )->text(),
				'widget_namespace' => NS_BOOK,
				'widget_$overlay' => true
			],
			[
				'type' => 'text',
				'name' => 'chapter',
				'label' => Message::newFromKey( 'bs-bookshelf-booknav-chapter-label' )->text(),
				'help' => Message::newFromKey( 'bs-bookshelf-booknav-chapter-help' )->text(),
			],
		] );

		return new ClientTagSpecification(
			'Booknav',
			Message::newFromKey( 'bs-bookshelf-booknav-desc' ),
			$formSpec,
			Message::newFromKey( 'bs-bookshelf-booknav-title' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getResourceLoaderModules(): ?array {
		return [
			'ext.bluespice.bookshelf.bookNavFilter',
			'mwstake.component.commonui.tree-component',
			'ext.bluespice.bookshelf.booknav.styles'
		];
	}
}
