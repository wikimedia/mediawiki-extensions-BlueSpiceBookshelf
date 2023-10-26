<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\GlobalActionsEditing;
use BlueSpice\Bookshelf\Panel\ChapterPagerPanel;
use BlueSpice\Bookshelf\Panel\MainLinkPanel;
use BlueSpice\Bookshelf\Panel\SidebarBookPanel;
use ConfigFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;
use RequestContext;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @var ConfigFactory
	 */
	private $configFactory = null;

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( ConfigFactory $configFactory ) {
		$this->configFactory = $configFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsEditing',
			[
				'bs-special-bookshelf' => [
					'factory' => static function () {
						return new GlobalActionsEditing();
					}
				]
			]
		);
		$config = $this->configFactory->makeConfig( 'bsg' );
		if ( $config->get( 'BookshelfMainLinksBookshelf' ) ) {
			$registry->register(
				'MainLinksPanel',
				[
					'bs-special-bookshelf' => [
						'factory' => static function () {
							return new MainLinkPanel();
						},
						'position' => 90
					]
				]
			);
		}

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
		if ( $config->get( 'BookShelfShowChapterNavigationPagerBeforeContent' ) ) {
			$registry->register(
				'DataBeforeContent', [
					'chapter-pager' => [
						'factory' => static function () use ( $title ) {
							return new ChapterPagerPanel( $title, 'bs-bookshelfui-chapter-pager-cnt-top' );
						}
					]
				]
			);
		}
		if ( $config->get( 'BookShelfShowChapterNavigationPagerAfterContent' ) ) {
			$registry->register(
				'DataAfterContent', [
					'chapter-pager' => [
						'factory' => static function () use ( $title ) {
							return new ChapterPagerPanel( $title, 'bs-bookshelfui-chapter-pager-bottom-top' );
						},
						'position' => 1
					]
				]
			);
		}
	}
}
