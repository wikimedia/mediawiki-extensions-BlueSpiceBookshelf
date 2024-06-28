<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IDatabase;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class ConvertContentModel extends LoggedUpdateMaintenance {

	/**
	 * @param IDatabase $db
	 * @return array
	 */
	private function getPages( $db ) {
		$res = $db->select(
			'page',
			[ 'page_id', 'page_namespace', 'page_title', 'page_content_model' ],
			[ 'page_namespace' => [ NS_BOOK ] ],
			__METHOD__
		);

		$pages = [];
		foreach ( $res as $row ) {
			if ( $row->page_content_model === 'book' ) {
				// Nothing to do
				continue;
			}
			$title = Title::newFromRow( $row );
			if ( !$title ) {
				continue;
			}
			$pages[(int)$row->page_id] = $title;
		}

		return $pages;
	}

	/**
	 * @param Title[] $pages
	 * @param IDatabase $db
	 * @return bool
	 */
	private function convert( $pages, IDatabase $db ) {
		if ( empty( $pages ) ) {
			return true;
		}

		$res = $db->update(
			'page',
			[
				'page_content_model' => 'book'
			],
			[
				'page_id' => array_keys( $pages )
			],
			__METHOD__
		);
		if ( $res ) {
			foreach ( $pages as $title ) {
				$title->invalidateCache();
			}
		}
		return $res;
	}

	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$this->output( "...Update '" . $this->getUpdateKey() . "': " );
		$dbLoadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$pages = $this->getPages(
			$dbLoadBalancer->getConnection( DB_REPLICA )
		);
		$res = $this->convert(
			$pages,
			$dbLoadBalancer->getConnection( DB_PRIMARY )
		);
		$this->output( "OK\n" );
		return $res;
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bs_bookmaker_convert_content_model3';
	}
}

$maintClass = ConvertContentModel::class;
require_once RUN_MAINTENANCE_IF_MAIN;
