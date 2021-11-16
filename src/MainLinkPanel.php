<?php

namespace BlueSpice\Bookshelf;

use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;
use SpecialPage;

class MainLinkPanel extends RestrictedTextLink {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct( [] );
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'n-bookshelf';
	}

	/**
	 *
	 * @return string[]
	 */
	public function getPermissions(): array {
		$permissions = [
			'bookshelf-viewspecialpage'
		];
		return $permissions;
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		$specialPage = SpecialPage::getTitleFor( 'Bookshelf' );
		return $specialPage->getLocalURL();
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bookshelf' );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bookshelf' );
	}

	/**
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bookshelf' );
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRequiredRLStyles(): array {
		return [ 'ext.bookshelf.special-bookshelf.styles' ];
	}

}
