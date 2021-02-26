<?php

namespace BlueSpice\Bookshelf\Hook\SkinTemplateOutputPageBeforeExec;

use BlueSpice\Hook\SkinTemplateOutputPageBeforeExec;
use BlueSpice\SkinData;

class AddToBookTool extends SkinTemplateOutputPageBeforeExec {

	protected function skipProcessing() {
		if ( $this->skin->getTitle()->exists() === false ) {
			return true;
		}
		if ( $this->skin->getTitle()->userCan( 'edit' ) === false ) {
			return true;
		}
	}

	protected function doProcess() {
		$this->mergeSkinDataArray(
			SkinData::EDIT_MENU,
			[
				'bookshelf-add-to-book' => [
					'text' => wfMessage( 'bs-bookshelf-add-to-book-label' )->text(),
					'href' => '#',
					'class' => false,
					'id' => 'ca-bookshelf-add-to-book'
				]
			]
		);

		return true;
	}
}
