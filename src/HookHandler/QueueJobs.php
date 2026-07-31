<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\Hook\BSBookshelfPageAddedToBookHook;
use BlueSpice\Bookshelf\Hook\BSBookshelfPageRemovedFromBookHook;
use BS\ExtendedSearch\Source\Job\UpdateWikiPage;
use JobQueueGroup;
use JobSpecification;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Title\Title;

class QueueJobs implements BSBookshelfPageAddedToBookHook, BSBookshelfPageRemovedFromBookHook {

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	/**
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function __construct( JobQueueGroup $jobQueueGroup ) {
		$this->jobQueueGroup = $jobQueueGroup;
	}

	/**
	 * @inheritDoc
	 */
	public function onBSBookshelfPageAddedToBook( Title $book, Title $page ): void {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'BlueSpiceExtendedSearch' ) ) {
			$this->jobQueueGroup->push( new UpdateWikiPage( $page ) );
		}
		if ( ExtensionRegistry::getInstance()->isLoaded( 'BlueSpiceSMWConnector' ) ) {
			$this->jobQueueGroup->push( $this->makeSMWUpdateJobSpec( $page ) );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onBSBookshelfPageRemovedFromBook( Title $book, Title $page ): void {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'BlueSpiceExtendedSearch' ) ) {
			$this->jobQueueGroup->push( new UpdateWikiPage( $page ) );
		}
		if ( ExtensionRegistry::getInstance()->isLoaded( 'BlueSpiceSMWConnector' ) ) {
			$this->jobQueueGroup->push( $this->makeSMWUpdateJobSpec( $page ) );
		}
	}

	/**
	 * `SMW\MediaWiki\Jobs\UpdateJob` requires a number of injected services and therefore must not
	 * be instantiated directly. Pushing a specification lets the job queue build it from the
	 * `smw.update` registration once the job is run.
	 *
	 * @param Title $page
	 * @return JobSpecification
	 */
	private function makeSMWUpdateJobSpec( Title $page ): JobSpecification {
		return new JobSpecification(
			'smw.update',
			[
				'namespace' => $page->getNamespace(),
				'title' => $page->getDBkey(),
			],
			[ 'removeDuplicates' => true ],
			$page
		);
	}
}
