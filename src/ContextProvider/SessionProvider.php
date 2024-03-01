<?php

namespace BlueSpice\Bookshelf\ContextProvider;

use MediaWiki\Session\SessionManager;
use TitleFactory;

class SessionProvider extends QueryProvider {

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
		$this->session = SessionManager::getGlobalSession();
		$this->param = $this->session->get( 'book', '' );
	}

	/**
	 * @return int
	 */
	public function getPriority(): int {
		return 50;
	}

}
