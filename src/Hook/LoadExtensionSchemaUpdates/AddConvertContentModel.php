<?php

namespace BlueSpice\Bookshelf\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddConvertContentModel extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		// UserBookContentLanguage must be executed before teh conversion to book content model!
		$this->updater->addPostDatabaseUpdateMaintenance( 'UserBookContentLanguage' );
		$this->updater->addPostDatabaseUpdateMaintenance(
			'ConvertContentModel'
		);
		$this->updater->addPostDatabaseUpdateMaintenance(
			'FixUserSubpageContentModel'
		);
		return true;
	}

}
