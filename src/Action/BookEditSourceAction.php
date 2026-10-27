<?php

namespace BlueSpice\Bookshelf\Action;

use MediaWiki\Actions\EditAction;

class BookEditSourceAction extends EditAction {
	/**
	 * @inheritDoc
	 */
	public function getName() {
		return 'editbooksource';
	}
}
