<?php

namespace BlueSpice\Bookshelf\Integration\PDFCreator\Module;

use BlueSpice\Bookshelf\Integration\PDFCreator\Utility\BookmarksXMLBuilder;
use BlueSpice\Bookshelf\Integration\PDFCreator\Utility\TocBuilder;
use MediaWiki\Extension\PDFCreator\Module\Batch;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;

class Book extends Batch {

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'book';
	}

	/**
	 * @param array &$pages
	 * @param ExportContext $context
	 * @param bool $embedPageToc
	 * @return void
	 */
	protected function addTocPage( array &$pages, ExportContext $context, bool $embedPageToc = false ): void {
		$tocPageBuilder = new TocBuilder();
		$pages = $tocPageBuilder->execute( $pages, $embedPageToc );
	}

	/**
	 * @param array $pages
	 * @param ExportContext $context
	 * @return string
	 */
	protected function getBookmarksXML( array $pages, ExportContext $context ): string {
		$xmlBuilder = new BookmarksXMLBuilder();
		return $xmlBuilder->execute( $pages );
	}
}
