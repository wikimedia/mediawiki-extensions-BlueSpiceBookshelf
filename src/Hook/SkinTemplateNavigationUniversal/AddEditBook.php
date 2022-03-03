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

		if ( $title->getContentModel() !== 'book' ) {
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
		$this->links['views']['edit'] = [
			'text' => $this->msg( 'edit' )->text(),
			'title' => $this->msg( 'edit' )->text(),
			'href' => $this->sktemplate->getTitle()->getLocalURL( [
				'action' => 'edit',
			] ),
			'id' => 'edit-book'
		];

		$this->links['views']['editbooksource'] = $this->links['views']['edit'];
		$this->links['views']['editbooksource']['id']
		= 'editbooksource';
		$this->links['views']['editbooksource']['text']
			= $this->msg( 'bs-bookshelf-action-editbook' )->plain();
		$this->links['views']['editbooksource']['href'] = $this->sktemplate->getTitle()->getLinkURL( [
			'action' => 'editbooksource'
		] );

		return true;
	}

}
