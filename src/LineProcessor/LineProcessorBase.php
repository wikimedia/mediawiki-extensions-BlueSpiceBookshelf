<?php

namespace BlueSpice\Bookshelf\LineProcessor;

use BlueSpice\Bookshelf\ILineProcessor;

abstract class LineProcessorBase implements ILineProcessor {

	/**
	 * @return static
	 */
	public static function factory() {
		return new static();
	}

	public function isFinal(): bool {
		return false;
	}
}
