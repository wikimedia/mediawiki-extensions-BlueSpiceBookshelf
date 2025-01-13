<?php

namespace BlueSpice\Bookshelf\Integration\PDFCreator\ExportMode;

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\ChapterLookup;
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

	/**
	 *
	 * @param BookLookup $bookLookup
	 * @param TitleFactory $titleFactory
	 * @param ChapterLookup $chapterLookup
	 * @param BookContextProviderFactory $bookContextProviderFactory
	 */
	public function __construct( BookLookup $bookLookup, TitleFactory $titleFactory,
		ChapterLookup $chapterLookup, BookContextProviderFactory $bookContextProviderFactory ) {
		$this->bookLookup = $bookLookup;
		$this->titleFactory = $titleFactory;
		$this->chapterLookup = $chapterLookup;
		$this->bookContextProviderFactory = $bookContextProviderFactory;
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

		$chapters = $this->chapterLookup->getChaptersOfBook( $bookTitle );
		if ( empty( $chapters ) ) {
			return [];
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
