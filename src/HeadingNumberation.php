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
	 * @param string $html
	 * @return array
	 */
	private function buildHeadingMap( string $html ): array {
		$regEx = '#<h(\d)><span class="mw-headline" id="(.*?)">(.*?)</span>(.*?)</h(\d)>#';

		$headings = [];
		$matches = [];
		$status = preg_match_all( $regEx, $html, $matches );
		if ( !$status ) {
			return $headings;
		}

		for ( $index = 0; $index < count( $matches[0] ); $index++ ) {
			$headings[] = [
				'search' => $matches[0][$index],
				'level' => (int)$matches[1][$index],
				'id' => $matches[2][$index],
				'text' => $matches[3][$index],
				'additional' => $matches[4][$index],
			];
		}

		return $headings;
	}

	/**
	 * @param int $articleNumber
	 * @param array $headings
	 * @param string $html
	 * @return string
	 */
	private function processHeadings( int $articleNumber, array $headings, string $html ): string {
		for ( $index = 0; $index < count( $headings ); $index++ ) {
			$level = $headings[$index]['level'];

			if ( $this->curHighestLevel > $level ) {
				$this->curHighestLevel = $level;
			}

			$level = $level - $this->curHighestLevel;

			$this->increaseHeadingCounter( $level );
			$this->resetHeadingCounter( $level + 1 );
			$numberation = $this->getHeadingNumberation( $articleNumber );

			$html = preg_replace(
				$this->getReplacementRegEx( $headings[$index] ),
				$this->getReplacementHtml( $numberation, $headings[$index] ),
				$html
			);
		}

		return $html;
	}

	/**
	 * @param array $item
	 * @return string
	 */
	private function getReplacementRegEx( array $item ): string {
		$level = $item['level'];
		$id = $item['id'];

		$regEx = '#<h' . $level . '><span class="mw-headline" id="' . preg_quote( $id, '/' ) . '">';
		$regEx .= '(.*?)</span>.*?</h' . $level . '>#';

		return $regEx;
	}

	/**
	 * @param string $numberation
	 * @param array $item
	 * @return string
	 */
	private function getReplacementHtml( string $numberation, array $item ): string {
		$level = $item['level'];
		$id = $item['id'];
		$text = $item['text'];
		$additional = $item['additional'];

		$html = '<h' . $level . '>';
		$html .= '<span class="mw-headline" id="' . $id . '">' . $numberation . $text . '</span>';
		$html .= $additional;
		$html .= '</h' . $level . '>';

		return $html;
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
