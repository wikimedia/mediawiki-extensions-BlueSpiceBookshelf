<?php

namespace BlueSpice\Bookshelf\Panel;

use MediaWiki\Context\RequestContext;
use MediaWiki\Language\RawMessage;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\ActionLink;

class ActionEntrypoint extends ActionLink {

	/**
	 * @param PermissionManager $permissionManager
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( private readonly PermissionManager $permissionManager,
		private readonly TitleFactory $titleFactory ) {
		parent::__construct( [] );
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'n-bookshelf';
	}

	/**
	 * @inheritDoc
	 */
	public function getHref(): string {
		/** @var Title */
		$specialpage = SpecialPage::getTitleFor( 'Books' );
		return $specialpage->getLocalURL();
	}

	/**
	 * @inheritDoc
	 */
	public function getPermissions(): array {
		return [ 'bookshelf-viewspecialpage' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-bookshelf-mainlinks-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-bookshelf-mainlinks-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-bookshelf-mainlinks-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function showAction(): bool {
		$user = RequestContext::getMain()->getUser();
		$title = $this->titleFactory->newFromText( 'Dummy', NS_BOOK );
		if ( $this->permissionManager->userCan( 'edit', $user, $title ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getActionClass(): string {
		return 'new-book-action';
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'bi-bs-create-page';
	}

	/**
	 * @inheritDoc
	 */
	public function getActionAriaLabel(): Message {
		return Message::newFromKey( 'bs-bookshelf-entrypoint-action-book-aria-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function getActionTitle(): Message {
		return Message::newFromKey( 'bs-bookshelf-entrypoint-action-book-aria-title' );
	}

	/**
	 * @inheritDoc
	 */
	public function getActionLabel(): Message {
		return new RawMessage( '' );
	}

	/**
	 * @inheritDoc
	 */
	public function showActionLabel(): bool {
		return false;
	}

}
