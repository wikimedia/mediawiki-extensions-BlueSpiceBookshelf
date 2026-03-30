<?php

namespace BlueSpice\Bookshelf\Component;

use MediaWiki\Context\IContextSource;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleLink;

class CreateBookButton extends SimpleLink {

	/**
	 * @param TitleFactory $titleFactory
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( private readonly TitleFactory $titleFactory,
		private readonly PermissionManager $permissionManager ) {
		return parent::__construct( [] );
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'create-book-btn';
	}

	/**
	 * @inheritDoc
	 */
	public function getSubComponents(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getClasses(): array {
		return [ 'new-book-action', 'ico-btn', 'bi-bs-create-page' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getRole(): string {
		return 'button';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-bookshelf-actionmenuentry-create-new-book' );
	}

	/**
	 * @inheritDoc
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-bookshelf-actionmenuentry-create-new-book' );
	}

	/**
	 * @inheritDoc
	 */
	public function getHref(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function shouldRender( IContextSource $context ): bool {
		$user = $context->getUser();
		$bookDummyTitle = $this->titleFactory->newFromText( 'Dummy', NS_BOOK );
		$userCan = $this->permissionManager->userCan( 'edit', $user, $bookDummyTitle );
		if ( $userCan ) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getRequiredRLModules(): array {
		return [ 'ext.bluespice.bookshelf.createNewBook' ];
	}
}
