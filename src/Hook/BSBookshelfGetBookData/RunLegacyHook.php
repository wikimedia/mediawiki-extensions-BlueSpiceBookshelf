<?php

namespace BlueSpice\Bookshelf\Hook\BSBookshelfGetBookData;

use BlueSpice\Bookshelf\Hook\BSBookshelfGetBookData;

class RunLegacyHook extends BSBookshelfGetBookData {

	protected function doProcess() {
		\Hooks::run( 'BSBookshelfBookUI', [
			null, $this->getContext()->getOutput(), $this->bookData
		] );
	}
}
