<?php

namespace BlueSpice\Bookshelf\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$this->out->addModules( 'ext.bluespice.bookshelf.addToBook' );
	}

}
