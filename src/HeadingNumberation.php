<?php

namespace BlueSpice\Bookshelf;

class HeadingNumberation {

	/** @var array */
	private $headingCounter = [ 0, 0, 0, 0, 0, 0, 0 ];

	/** @var int */
	private $curHighestLevel = 6;

	/**
	 * @param string $articleNumber
	 * @param string $html
	 * @return string
	 */
	public function execute( string $articleNumber, string $html ): string {
		$headings = $this->buildHeadingMap( $html );
		$newHtml = $this->processHeadings( $articleNumber, $headings, $html );

		return $newHtml;
	}

	/**
	 * @param string $text
	 * @return array
	 */
	private function buildHeadingMap( string $text ): array {
		$regex = '#<h(\d)>.*?<span class="mw-headline" id="(.*?)">(.*?)</span>(.*?)</h(\d)>#';

		$headings = [];
		$status = preg_match_all( $regex, $text, $matches );
		if ( !$status ) {
			return $headings;
		}

		for ( $index = 0; $index < count( $matches[0] ); $index++ ) {
			$headings[] = [
				'search' => $matches[0][$index],
				'level' => (int)$matches[1][$index],
				'text' => $matches[3][$index]
			];
		}

		return $headings;
	}

	/**
	 * @param int $articleNumber
	 * @param array $headings
	 * @param string $text
	 * @return string
	 */
	private function processHeadings( int $articleNumber, array $headings, string $text ): string {
		for ( $index = 0; $index < count( $headings ); $index++ ) {
			$level = $headings[$index]['level'];

			if ( $this->curHighestLevel > $level ) {
				$this->curHighestLevel = $level;
			}

			$level = $level - $this->curHighestLevel;

			$this->increaseHeadingCounter( $level );
			$this->resetHeadingCounter( $level + 1 );
			$numberation = $this->getHeadingNumberation( $articleNumber );

			$repalcementText = $numberation . $headings[$index]['text'];

			$replacement = str_replace( $headings[$index]['text'], $repalcementText, $headings[$index]['search'] );

			$text = str_replace( $headings[$index]['search'], $replacement, $text );
		}
		return $text;
	}

	/**
	 * @param int $level
	 * @return void
	 */
	private function increaseHeadingCounter( int $level ): void {
		$this->headingCounter[$level]++;
	}

	/**
	 * @param int $level
	 * @return void
	 */
	private function resetHeadingCounter( int $level ): void {
		for ( $index = $level; $index <= 6; $index++ ) {
			$this->headingCounter[$index] = 0;
		}
	}

	/**
	 * @param int $articleNumber
	 * @return string
	 */
	private function getHeadingNumberation( int $articleNumber ): string {
		$counter = $this->clearHeadingCounter();
		$numberation = implode( '.', $counter );

		$html = '<span class="bs-chapter-number">' . $articleNumber . '.</span>';
		$html .= '<span class="mw-headline-number">' . $numberation . '. </span>';

		return $html;
	}

	/**
	 * @return array
	 */
	private function clearHeadingCounter(): array {
		$counter = $this->headingCounter;
		for ( $index = 6; $index >= 0; $index-- ) {
			if ( $counter[$index] === 0 ) {
				unset( $counter[$index] );
			}
		}
		return $counter;
	}

}
