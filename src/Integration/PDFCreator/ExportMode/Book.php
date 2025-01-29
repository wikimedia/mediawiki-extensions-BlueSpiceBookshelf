<?php

namespace BlueSpice\Bookshelf\Integration\PDFCreator\ExportMode;

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\ChapterDataModel;
use BlueSpice\Bookshelf\ChapterLookup;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Extension\PDFCreator\Interface\IExportMode;
use MediaWiki\Title\TitleFactory;

class Book implements IExportMode {

	/** @var BookLookup */
	private $bookLookup;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var ChapterLookup */
	private $chapterLookup;

	/** @var BookContextProviderFactory */
	private $bookContextProviderFactory;

	/** @var ConfigFactory */
	private $configFactory;

	/**
	 *
	 * @param BookLookup $bookLookup
	 * @param TitleFactory $titleFactory
	 * @param ChapterLookup $chapterLookup
	 * @param BookContextProviderFactory $bookContextProviderFactory
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( BookLookup $bookLookup, TitleFactory $titleFactory,
		ChapterLookup $chapterLookup, BookContextProviderFactory $bookContextProviderFactory,
		ConfigFactory $configFactory ) {
		$this->bookLookup = $bookLookup;
		$this->titleFactory = $titleFactory;
		$this->chapterLookup = $chapterLookup;
		$this->bookContextProviderFactory = $bookContextProviderFactory;
		$this->configFactory = $configFactory;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getKey(): string {
		return 'book';
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getLabel(): string {
		return 'bs-bookshelf-export-mode-book-label';
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRLModules(): array {
		return [ 'ext.bluespice.bookshelf.book.plugin' ];
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function applies( $format ): bool {
		return ( $this->getKey() === $format ) ? true : false;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getDefaultTemplate(): string {
		$bsgConfig = $this->configFactory->makeConfig( 'bsg' );
		$template = $bsgConfig->get( 'BookshelfDefaultBookTemplate' );
		$templateTitle = $this->titleFactory->newFromText( 'MediaWiki:PDFCreator/' . $template );
		if ( !$templateTitle->exists() ) {
			return '';
		}
		return $template;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getExportPages( $title, $data ): array {
		if ( isset( $data[ 'book' ] ) ) {
			$bookTitle = $this->titleFactory->newFromText( $data['book'] );
		} else {
			$bookOptions = $this->bookLookup->getBooksForPage( $title );
			if ( empty( $bookOptions ) ) {
				return [];
			}
			$bookContextProvider = $this->bookContextProviderFactory->getProvider( $title );
			$bookTitle = $bookContextProvider->getActiveBook();
		}
		if ( !$bookTitle ) {
			return [];
		}

		if ( !isset( $data['chapters'] ) || empty( $data['chapters'] ) ) {
			$chapters = $this->chapterLookup->getChaptersOfBook( $bookTitle );
			if ( empty( $chapters ) ) {
				return [];
			}
		} else {
			$chapterModels = $data['chapters'];
			$chapters = [];
			foreach ( $chapterModels as $model ) {
				$chapters[] = new ChapterDataModel(
					$model['namespace'],
					$model['title'],
					$model['name'],
					$model['number'],
					$model['type']
				);
			}
		}

		$chapterPages = [];
		foreach ( $chapters as $chapter ) {
			if ( $chapter->getType() === 'plain-text' ) {
				$chapterPages[] = [
					'type' => 'raw',
					'label' => $chapter->getNumber() . ' ' . $chapter->getName(),
					'params' => [
						'tocnumber' => $chapter->getNumber(),
						'toctext' => $chapter->getName()
					]
				];
				continue;
			}
			$chapterTitle = $this->titleFactory->makeTitle( $chapter->getNamespace(), $chapter->getTitle() );
			$chapterPages[] = [
				'type' => 'page',
				'target' => $chapterTitle->getPrefixedDBkey(),
				'label' => $chapter->getNumber() . ' ' . $chapter->getName(),
				'params' => [
					'tocnumber' => $chapter->getNumber(),
					'toctext' => $chapter->getName()
				]
			];
		}

		return $chapterPages;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function isRelevantExportMode( $title ): bool {
		if ( !$title->exists() ) {
			return false;
		}
		$books = $this->bookLookup->getBooksForPage( $title );
		if ( empty( $books ) ) {
			return false;
		}
		return true;
	}
}
