<?php

namespace BlueSpice\Bookshelf\Hook\SkinTemplateNavigation;

use BlueSpice\Hook\SkinTemplateNavigation;

class AddAddToBookEntry extends SkinTemplateNavigation {
	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		if ( !$this->sktemplate->getTitle()->exists() ) {
			return true;
		}
		if ( !$this->sktemplate->getTitle()->userCan( 'edit' ) ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$this->links['actions']['bookshelf-add-to-book'] = [
			'text' => $this->msg( 'bs-bookshelf-add-to-book-label' )->text(),
			'href' => '#',
			'class' => false,
			'id' => 'ca-bookshelf-add-to-book'
		];
		return true;
	}

}
