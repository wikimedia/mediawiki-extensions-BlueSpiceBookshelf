<?php

namespace BlueSpice\Bookshelf\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$this->out->addModules( [
			'ext.bluespice.bookshelf.navigationTab',
			'mwstake.component.commonui.tree-component'
		] );

		$this->out->addModuleStyles( [
			'ext.bluespice.bookshelf.chapter-pager.styles',
			'ext.bluespice.bookshelf.booknav.styles'
		] );

		if ( !$this->skin->getUser()->isAnon() ) {
			$location = $this->skin->msg(
				'bs-bookshelf-personal-books-page-prefix',
				$this->skin->getUser()->getName()
			);
			$this->out->addJsConfigVars(
				'bsgBookshelfUserBookLocation',
				$this->skin->getUser()->getUserPage()->getNsText()
					. ':' . $location->inContentLanguage()->parse()
			);
		}
	}

}
