<?php

namespace BlueSpice\Bookshelf\ContentHandler;

use BlueSpice\Bookshelf\Action\BookEditAction;
use BlueSpice\Bookshelf\Action\BookEditSourceAction;
use BlueSpice\Bookshelf\BookInfo;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\BookViewTreeDataBuilder;
use BlueSpice\Bookshelf\ChapterLookup;
use BlueSpice\Bookshelf\Content\BookContent;
use Content;
use Html;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\MediaWikiServices;
use MWException;
use ParserOutput;
use TextContentHandler;
use Title;
use TitleFactory;

class BookContentHandler extends TextContentHandler {

	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = 'book' ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_TEXT ] );
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return BookContent::class;
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return [
			'edit' => BookEditAction::class,
			'menueditsource' => BookEditSourceAction::class,
		];
	}

	/**
	 * @param Content $content
	 * @param ContentParseParams $cpoParams
	 * @param ParserOutput &$output The output object to fill (reference).
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	) {
		// parse just to get links etc into the database, HTML is replaced below.
		$services = MediaWikiServices::getInstance();
		$output = $services->getParser()
			->parse(
				$content->getText(),
				$cpoParams->getPage(),
				$cpoParams->getParserOptions(),
				true,
				true,
				$cpoParams->getRevId()
			);

		try {
			$pageRef = $cpoParams->getPage();

			$titleFactory = $services->getTitleFactory();
			$book = $titleFactory->castFromPageReference( $pageRef );

			$bookLookup = $services->get( 'BSBookshelfBookLookup' );
			$bookChapterLookup = $services->get( 'BSBookshelfBookChapterLookup' );
			if ( $bookChapterLookup instanceof ChapterLookup ) {
				$this->setChaptersTree( $output, $book, $bookLookup, $bookChapterLookup, $titleFactory );
				$this->setHtmlFrame( $output );
				$output->addModules( [ 'ext.bluespice.bookshelf.view' ] );
			}
		}
		catch ( MWException $e ) {
			$output->addWarningMsg( "bs-bookshelf-warning", $e->getText() );
		}
	}

	/**
	 * @param ParserOutput $output
	 * @param Title $book
	 * @param BookLookup $bookLookup
	 * @param ChapterLookup $chapterLookup
	 * @param TitleFactory $titleFactory
	 */
	private function setChaptersTree(
		ParserOutput $output,
		Title $book,
		BookLookup $bookLookup,
		ChapterLookup $chapterLookup,
		TitleFactory $titleFactory
	) {
		$chapters = $chapterLookup->getChaptersOfBook( $book );
		$bookInfo = $bookLookup->getBookInfo( $book );

		$name = '';
		if ( $bookInfo instanceof BookInfo ) {
			$name = $bookInfo->getName();
		}

		$dataBuilder = new BookViewTreeDataBuilder( $titleFactory );
		$data = $dataBuilder->build( $book, $chapters, $name );

		$output->setJsConfigVar( 'bsBookshelfTreeData', $data );
	}

	/**
	 * @param ParserOutput $output
	 */
	private function setHtmlFrame( ParserOutput $output ) {
		$html = Html::element( 'div', [ 'id' => 'bs-bookshelf-view' ] );

		$output->setText( $html );
	}
}
