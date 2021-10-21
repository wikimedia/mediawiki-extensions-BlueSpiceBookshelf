<?php

namespace BlueSpice\Bookshelf\Hook\SkinTemplateNavigationUniversal;

use BlueSpice\Hook\SkinTemplateNavigationUniversal;

class AddEditBook extends SkinTemplateNavigationUniversal {
	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		$title = $this->sktemplate->getTitle();
		if ( !$title->exists() ) {
			return true;
		}
		$user = $this->sktemplate->getUser();
		$permissionManager = $this->getServices()->getPermissionManager();
		$userCan = $permissionManager->userCan( 'edit', $user, $title );
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
			'text' => $this->msg( 'edit' )->text(),
			'href' => $this->sktemplate->getTitle()->getLocalURL( [
				'action' => 'editbook',
			] ),
			'class' => false,
			'id' => 'edit-book'
		];

		return true;
	}

}
