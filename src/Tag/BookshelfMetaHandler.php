<?php

namespace BlueSpice\Bookshelf\Tag;

use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;

class BookshelfMetaHandler implements ITagHandler {

	/**
	 * @inheritDoc
	 */
	public function getRenderedContent( string $input, array $params, Parser $parser, PPFrame $frame ): string {
		// TODO: Potential security risk: are sAttributes properly preprocessed here?
		$parser->getOutput()->setPageProperty(
			'bs-bookshelf-meta',
			FormatJson::encode( $params )
		);
		$parser->getOutput()->setPageProperty(
			'bs-universalexport-meta',
			FormatJson::encode( $params )
		);
		$parser->getOutput()->setPageProperty( 'bs-tag-universalexport-meta', 1 );

		$attribs = [
			'class' => 'bs-universalexport-meta'
		];

		return Html::element( 'div', array_merge( $params, $attribs ) );
	}
}
