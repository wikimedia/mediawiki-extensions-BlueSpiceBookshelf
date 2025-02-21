<?php

namespace BlueSpice\Bookshelf\Integration\PDFCreator\PageParams;

use BlueSpice\Bookshelf\BookMetaLookup;
use BlueSpice\Bookshelf\IMetaDataDescription;
use MediaWiki\Extension\PDFCreator\IPageParamsProvider;
use MediaWiki\Extension\PDFCreator\Utility\ParamDesc;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\ManifestRegistry\ManifestAttributeBasedRegistry;
use Wikimedia\ObjectFactory\ObjectFactory;

class BookParams implements IPageParamsProvider {

	/** @var TitleFactory */
	private $titleFactory;

	/** @var BookMetaLookup */
	private $bookMetaLookup;

	/** @var ObjectFactory */
	private $objectFactory;

	/** @var array */
	private $skipParams = [
		'bookshelfimage',
		'pdftemplate'
	];

	/**
	 *
	 * @param TitleFactory $titleFactory
	 * @param BookMetaLookup $bookMetaLookup
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct( TitleFactory $titleFactory,
		BookMetaLookup $bookMetaLookup, ObjectFactory $objectFactory ) {
		$this->titleFactory = $titleFactory;
		$this->bookMetaLookup = $bookMetaLookup;
		$this->objectFactory = $objectFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( ?PageIdentity $pageIdentity, ?UserIdentity $userIdentity ): array {
		if ( $pageIdentity === null ) {
			return [];
		}
		$title = $this->titleFactory->newFromPageIdentity( $pageIdentity );
		$bookMeta = $this->bookMetaLookup->getMetaForBook( $title );

		$params = [];
		foreach ( $bookMeta as $key => $item ) {
			$params['book-' . $key ] = $item;
		}
		return $params;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamsDescription(): array {
		$registry = new ManifestAttributeBasedRegistry(
			'BlueSpiceBookshelfMetaData'
		);

		$desc = [];
		$data = $registry->getAllValues();
		foreach ( $data as $key => $spec ) {
			$object = $this->objectFactory->createObject( $registry->getObjectSpec( $key ) );
			if ( !( $object instanceof IMetaDataDescription ) ) {
				continue;
			}
			if ( in_array( $object->getKey(), $this->skipParams, true ) ) {
				continue;
			}

			$desc[] = new ParamDesc(
				'book-' . $object->getKey(),
				Message::newFromKey( 'bs-bookshelf-export-pageparam-desc-' . $object->getKey() )
			);
		}
		return $desc;
	}
}
