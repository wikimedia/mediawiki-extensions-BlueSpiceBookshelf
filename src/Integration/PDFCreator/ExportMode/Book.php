<?php

namespace BlueSpice\Bookshelf\Integration\PDFCreator\ExportMode;

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\ChapterLookup;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\PDFCreator\IContextSourceAware;
use MediaWiki\Extension\PDFCreator\IExportMode;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;

class Book implements IExportMode, IContextSourceAware {

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

	/** @var PermissionManager */
	private $permissionManager;

	/** @var IContextSource */
	private $context;

	/**
	 * @param BookLookup $bookLookup
	 * @param TitleFactory $titleFactory
	 * @param ChapterLookup $chapterLookup
	 * @param BookContextProviderFactory $bookContextProviderFactory
	 * @param ConfigFactory $configFactory
	 * @param PermissionManager|null $permissionManager
	 */
	public function __construct( BookLookup $bookLookup, TitleFactory $titleFactory,
		ChapterLookup $chapterLookup, BookContextProviderFactory $bookContextProviderFactory,
		ConfigFactory $configFactory, ?PermissionManager $permissionManager ) {
		$this->bookLookup = $bookLookup;
		$this->titleFactory = $titleFactory;
		$this->chapterLookup = $chapterLookup;
		$this->bookContextProviderFactory = $bookContextProviderFactory;
		$this->configFactory = $configFactory;
		if ( !$permissionManager ) {
			$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		}
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @param IContextSource $context
	 * @return void
	 */
	public function setContext( IContextSource $context ): void {
		$this->context = $context;
	}

	/**
	 * @inheritDoc
	 */
	public function getKey(): string {
		return 'book';
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel(): string {
		return 'bs-bookshelf-export-mode-book-label';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModules(): array {
		return [ 'ext.bluespice.bookshelf.book.plugin' ];
	}

	/**
	 * @inheritDoc
	 */
	public function applies( $format ): bool {
		return ( $this->getKey() === $format ) ? true : false;
	}

	/**
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
	 * @inheritDoc
	 */
	public function getExportPages( $title, $data ): array {
		if ( isset( $data[ 'book' ] ) ) {
			$bookTitle = $this->titleFactory->newFromText( $data['book'] );
		} else {
			if ( $title->getNamespace() !== NS_BOOK ) {
				$bookOptions = $this->bookLookup->getBooksForPage( $title );
				if ( empty( $bookOptions ) ) {
					return [];
				}
				$bookContextProvider = $this->bookContextProviderFactory->getProvider( $title );
				$bookTitle = $bookContextProvider->getActiveBook();
			} else {
				$bookTitle = $title;
			}
		}
		if ( !$bookTitle ) {
			return [];
		}

		$chapters = $this->chapterLookup->getChaptersOfBook( $bookTitle );
		if ( empty( $chapters ) ) {
			return [];
		}
		// Reorder chapter models to match the requested order from $data['chapters']
		if ( isset( $data['chapters'] ) && !empty( $data['chapters'] ) ) {
			$chapterNumbers = $data['chapters'];
			$chapterModels = $chapters;
			$chapters = [];
			foreach ( $chapterNumbers as $number ) {
				foreach ( $chapterModels as $model ) {
					if ( $model->getNumber() !== $number ) {
						continue;
					}
					$chapters[] = $model;
					break;
				}
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
			if ( !$this->userCanReadPage( $chapterTitle ) ) {
				continue;
			}
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

	/**
	 * @param Title $title
	 * @return bool
	 */
	protected function userCanReadPage( $title ) {
		$user = $this->context->getUser();
		if ( $this->permissionManager->userCan( 'read', $user, $title ) ) {
			return true;
		}
		return false;
	}
}
