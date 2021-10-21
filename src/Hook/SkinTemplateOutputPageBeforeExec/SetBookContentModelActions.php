<?php

namespace BlueSpice\Bookshelf\Hook\SkinTemplateOutputPageBeforeExec;

use BlueSpice\Hook\SkinTemplateOutputPageBeforeExec;
use BlueSpice\SkinData;
use Message;

class SetBookContentModelActions extends SkinTemplateOutputPageBeforeExec {

	protected function skipProcessing() {
		return $this->skin->getTitle()->getContentModel() !== 'book';
	}

	protected function doProcess() {
		$this->template->data[SkinData::FEATURED_ACTIONS]['edit']['editbooksource'] = [
			'position' => '10',
			'id' => 'edit-book-source',
			'text' => $this->getText(),
			'title' => $this->getText(),
			'href' => $this->skin->getTitle()->getLocalURL( [
				'action' => 'editbooksource',
			] )
		];

		unset( $this->template->data[SkinData::FEATURED_ACTIONS]['edit']['new-section'] );
	}

	private function getText() {
		$exists = $this->skin->getTitle()->exists();
		if ( $exists ) {
			return Message::newFromKey( 'bs-bookshelf-action-editbook' )->plain();
		}
		return Message::newFromKey( 'bs-bookshelf-action-editbook-create' )->plain();
	}
}
