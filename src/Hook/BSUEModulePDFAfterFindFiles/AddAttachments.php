<?php

namespace BlueSpice\Bookshelf\Hook\BSUEModulePDFAfterFindFiles;

use BsPDFServlet;
use DOMElement;
use DOMXPath;
use FatalError;
use MediaWiki\MediaWikiServices;
use MWException;
use PDFFileResolver;
use Title;

class AddAttachments {
	/**
	 * @param BsPDFServlet $sender
	 * @param null $html Unused
	 * @param array &$files
	 * @param array $params
	 * @param DOMXPath $DOMXPath
	 * @return bool
	 * @throws FatalError
	 * @throws MWException
	 */
	public static function callback(
		$sender, $html, &$files, $params, $DOMXPath
	) {
		global $wgUploadPath;
		// Find all files for attaching and merging...
		if ( $params['attachments'] != '1' ) {
			return true;
		}

		// Backwards compatibility
		if ( !empty( $files['ATTACHMENT'] ) ) {
			foreach ( $files['ATTACHMENT'] as $sFileName => $sFilePath ) {
				$files['attachments'][$sFileName] = $sFilePath;
			}
			unset( $files['ATTACHMENT'] );
		}

		// TODO RBV (08.02.11 15:15): Necessary to exclude images?
		$oFileAnchorElements = $DOMXPath->query(
			"//a[contains(@class,'internal') and not(contains(@class, 'image'))]"
		);

		$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();

		foreach ( $oFileAnchorElements as $oFileAnchorElement ) {
			if ( $oFileAnchorElement instanceof DOMElement === false ) {
				continue;
			}
			$sHref = urldecode( $oFileAnchorElement->getAttribute( 'href' ) );

			$vUploadPathIndex = strpos( $sHref, $wgUploadPath );
			if ( $vUploadPathIndex == false ) {
				continue;
			}

			$sFileTitle = $oFileAnchorElement->getAttribute( 'data-bs-title' );
			$oTitle = Title::newFromText( $sFileTitle );
			if ( $oTitle && $oTitle->getNamespace() === NS_MEDIA ) {
				$oTitle = Title::makeTitle( NS_FILE, $oTitle->getDBkey() );
			}
			if ( $oTitle === null ) {
				// Fallback to less secure standard attribute
				$sFileTitle = $oFileAnchorElement->getAttribute( 'title' );
				$oTitle = Title::makeTitle( NS_FILE, $sFileTitle );
			}
			if ( $oTitle->exists() ) {
				$oFile = $repoGroup->findFile( $oTitle );
				$oBackend = $oFile->getRepo()->getBackend();
				$oLocalFile = $oBackend->getLocalReference(
					[ 'src' => $oFile->getPath() ]
				);
				if ( $oLocalFile === null ) {
					continue;
				}

				$fileResolver = PDFFileResolver::factory(
					$oFileAnchorElement,
					$params['webroot-filesystempath'],
					'href'
				);

				// TODO: This should be part of BSF
				$sHrefFilename = str_replace(
					[ '/', '\\', ':', '*', '?', '"', '|', '>', '<', ],
					'-',
					str_replace( ' ', '_', $oTitle->getText() )
				/*
				 * Replacing spaces with underscores is because of the
				 * new implementation of "BShtml2PDF.war"
				 * org.xhtmlrenderer.pdf.ITextOutputDevice.processLink:271ff
				 * uses the uri to build the filename _and_ to fetch the
				 * resource. But xhtmlrenderes user agent needs URI
				 * encoded spaces (%20) to get the data. But thos would
				 * also show up in the file name. A better solution would
				 * be to have a separate data-fs-embed-file-name attribute
				 * for unencoded filename
				 */
				);
				$sAbsoluteFileSystemPath = $fileResolver->getAbsoluteFilesystemPath();
			} else {
				continue;
			}

			MediaWikiServices::getInstance()->getHookContainer()->run(
				'BSUEModulePDFFindFiles',
				[
					$sender,
					$oFileAnchorElement,
					&$sAbsoluteFileSystemPath,
					&$sHrefFilename,
					'attachments'
				]
			);
			$oFileAnchorElement->setAttribute( 'data-fs-embed-file', 'true' );
			// https://otrs.hallowelt.biz/otrs/index.pl?Action=AgentTicketZoom;TicketID=5036#30864
			$oFileAnchorElement->setAttribute( 'href', 'attachments/' . $sHrefFilename );
			// Necessary for org.xhtmlrenderer.pdf.ITextReplacedElementFactory.
			// Otherwise not replaceable
			$sStyle = $oFileAnchorElement->getAttribute( 'style' );
			$oFileAnchorElement->setAttribute( 'style', 'display:inline-block;' . $sStyle );
			$files['attachments'][$sHrefFilename] = $sAbsoluteFileSystemPath;
		}

		return true;
	}
}
