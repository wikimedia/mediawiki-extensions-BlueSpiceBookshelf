<?php

namespace BlueSpice\Bookshelf\HookHandler\SkinTemplateNavigation;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;
use SkinTemplate;
use TitleFactory;

class AddNewBook implements SkinTemplateNavigation__UniversalHook {

	/** @var PermissionManager */
	private $permissionManager;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 *
	 * @param PermissionManager $permissionManager
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( PermissionManager $permissionManager, TitleFactory $titleFactory ) {
		$this->permissionManager = $permissionManager;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @return bool
	 */
	protected function skipProcessing( SkinTemplate $sktemplate ) {
		$title = $sktemplate->getTitle();
		if ( !$title->isSpecial( 'Books' ) ) {
			return true;
		}
		$user = $sktemplate->getUser();
		// Check if user can create pages in NS Book
		$title = $this->titleFactory->newFromText( 'Dummy', NS_BOOK );

		$userCan = $this->permissionManager->userCan( 'edit', $user, $title );
		if ( !$userCan ) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( $this->skipProcessing( $sktemplate ) ) {
			return;
		}

		$links['actions']['bookshelf-create-new-book'] = [
			'text' => $sktemplate->msg( 'bs-bookshelf-actionmenuentry-create-new-book' )->text(),
			'href' => '#',
			'class' => false,
			'id' => 'ca-bookshelf-create-new-book',
			'position' => 1,
		];
		$sktemplate->getOutput()->addModules( 'ext.bluespice.bookshelf.createNewBook' );
	}
}