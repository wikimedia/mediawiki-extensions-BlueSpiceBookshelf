<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\GlobalActionsTool;
use BlueSpice\Bookshelf\MainLinkPanel;
use BlueSpice\Bookshelf\SidebarBookPanel;
use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;
use BlueSpice\Discovery\ITemplateDataProvider;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;
use RequestContext;

class DiscoverySkin implements
	BlueSpiceDiscoveryTemplateDataProviderAfterInit,
	MWStakeCommonUIRegisterSkinSlotComponents
{

	/**
	 *
	 * @param ITemplateDataProvider $registry
	 * @return void
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->unregister( 'toolbox', 'ca-bookshelf-add-to-book' );
		$registry->register( 'actions_secondary', 'ca-bookshelf-add-to-book' );
	}

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
					},
					'position' => 20
				]
			]
		);

		$context = RequestContext::getMain();
		$title = $context->getTitle();
		$registry->register(
			"SidebarPrimaryTabPanels",
			[
				'book' => [
					'factory' => static function () use ( $title ) {
						return new SidebarBookPanel( $title );
					}
				]
			]
		);
	}
}
