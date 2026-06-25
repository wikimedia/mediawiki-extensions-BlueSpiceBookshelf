<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\BookLookup;
use MediaWiki\Extension\NotifyMe\Hook\NotifyMeWatchlistProviderGetWatchersHook;
use MediaWiki\Extension\NotifyMe\Hook\NotifyMeWatchlistProviderGetWatchSourceHook;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\ITitleEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use MWStake\MediaWiki\Component\Events\TitleEvent;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

class NotifyMeResolveBookWatchers implements
	NotifyMeWatchlistProviderGetWatchersHook,
	NotifyMeWatchlistProviderGetWatchSourceHook
{

	/** @var array */
	private array $bookWatchers = [];

	public function __construct(
		private readonly BookLookup $bookLookup,
		private readonly ILoadBalancer $lb,
		private readonly UserFactory $userFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onNotifyMeWatchlistProviderGetWatchSource( Notification $notification, Message &$description ) {
		if ( !( $notification->getEvent() instanceof ITitleEvent ) ) {
			return;
		}

		$title = $notification->getEvent()->getTitle();
		if ( !isset( $this->bookWatchers[$title->getArticleID()] ) ) {
			$this->bookWatchers[$title->getArticleID()] = $this->getBookWatchers( $title );
		}

		foreach ( $this->bookWatchers[$title->getArticleID()] as $watcher ) {
			if ( $watcher->getId() === $notification->getTargetUser()->getId() ) {
				$description = Message::newFromKey( 'bs-bookshelf-notifyme-subscription-reason' );
				return;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onNotifyMeWatchlistProviderGetWatchers(
		INotificationEvent $event, IChannel $channel, array &$watchers
	): void {
		if ( !( $event instanceof TitleEvent ) ) {
			return;
		}
		$title = $event->getTitle();
		$this->bookWatchers[$title->getArticleID()] = $this->getBookWatchers( $title );
		$watchers = array_merge( $watchers, $this->bookWatchers[$title->getArticleID()] );
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	private function getBookWatchers( Title $title ): array {
		$pageBooks = $this->bookLookup->getBooksForPage( $title );
		if ( empty( $pageBooks ) ) {
			return [];
		}
		$db = $this->lb->getConnection( DB_REPLICA );
		$conditions = [];
		foreach ( $pageBooks as $book ) {
			if ( !$book->getTitle() ) {
				continue;
			}
			$conditions[] = $db->makeList( [
				'wl_namespace' => $book->getNamespace(),
				'wl_title' => $book->getTitle()
			],
				ISQLPlatform::LIST_OR
			);
		}
		$res = $db->newSelectQueryBuilder()
			->from( 'watchlist', 'wl' )
			->select( 'wl_user' )
			->where( $db->makeList( $conditions, ISQLPlatform::LIST_OR ) )
			->caller( __METHOD__ )
			->groupBy( 'wl_user' )
			->fetchResultSet();

		$users = [];
		foreach ( $res as $row ) {
			$users[] = $this->userFactory->newFromId( $row->wl_user );
		}
		return $users;
	}
}
