<?php

namespace BlueSpice\Bookshelf\MetaData;

use BlueSpice\Bookshelf\IMetaDataDescription;
use Message;

class Department implements IMetaDataDescription {

	/**
	 * @inheritDoc
	 */
	public function getKey(): string {
		return 'department';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return Message::newFromKey( 'bs-bookshelfui-bookmetatag-department' );
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModules(): array {
		return [ 'bluespice.bookshelf.metadata.pages' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getJSClassname(): string {
		return 'bs.bookshelf.ui.pages.DepartmentMeta';
	}

}
