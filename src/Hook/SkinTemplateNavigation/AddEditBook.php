<?php

namespace BlueSpice\Bookshelf\Hook\SkinTemplateNavigation;

use BlueSpice\Hook\SkinTemplateNavigation;

class AddEditBook extends SkinTemplateNavigation {
	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		$title = $this->sktemplate->getTitle();
		if ( !$title->exists() ) {
			return true;
		}
		if ( $title->getContentModel() !== 'book' ) {
			return true;
		}
		$userCan = $title->userCan( 'edit' );
		if ( !$userCan ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$this->links['edit']['edit'] = [
			'text' =>  $this->msg( 'edit' )->text(),
			'href' => $this->sktemplate->getTitle()->getLocalURL( [
				'action' => 'editbook',
			]),
			'class' => false,
			'id' => 'edit-book'
		];

		return true;
	}

}
