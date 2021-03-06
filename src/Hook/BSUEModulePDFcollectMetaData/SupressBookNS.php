<?php

namespace BlueSpice\Bookshelf\Hook\BSUEModulePDFcollectMetaData;

use BlueSpice\UEModulePDF\Hook\BSUEModulePDFcollectMetaData;

class SupressBookNS extends BSUEModulePDFcollectMetaData {

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		if ( !$this->getConfig()->get( 'BookshelfSupressBookNS' ) ) {
			return true;
		}
		return $this->title->getNamespace() !== NS_BOOK;
	}

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		// Otherwise it has intentionally been overwritten and we don't want to overwrite it
		// again
		if ( $this->meta['title'] === $this->title->getPrefixedText() ) {
			$this->meta['title'] = $this->title->getText();
		}
		// TODO RBV (01.02.12 14:14): Currently the bs:bookmeta tag renders a
		// div.bs-universalexport-meta. Therefore things like "subtitle" are
		// read in by BsPDFPageProvider. Not sure if this is good...
		return true;
	}

}
