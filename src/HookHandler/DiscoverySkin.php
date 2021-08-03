<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\GlobalActionsTool;
use BlueSpice\Bookshelf\MainLinkPanel;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class DiscoverySkin implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsTools',
			[
				'bs-special-bookshelf' => [
					'factory' => static function () {
						return new GlobalActionsTool();
					}
				]
			]
		);
		$registry->register(
			'MainLinksPanel',
			[
				'bs-special-bookshelf' => [
					'factory' => static function () {
						return new MainLinkPanel();
					}
				]
			]
		);
	}
}
