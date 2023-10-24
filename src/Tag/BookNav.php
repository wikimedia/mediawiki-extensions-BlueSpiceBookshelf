<?php

namespace BlueSpice\Bookshelf\Tag;

use BlueSpice\ParamProcessor\ParamDefinition;
use BlueSpice\ParamProcessor\ParamType;
use BlueSpice\Tag\Tag;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;

class BookNav extends Tag {

	public const ATTR_BOOK = 'book';
	public const ATTR_CHAPTER = 'chapter';

	/** @var MediaWikiServices */
	private $services;

	public function __construct() {
		$this->services = MediaWikiServices::getInstance();
	}

	/**
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return PageBreakHandler
	 */
	public function getHandler( $processedInput, array $processedArgs, Parser $parser, PPFrame $frame ) {
		$titleFactory = $this->services->getTitleFactory();

		return new BookNavHandler(
			$processedInput,
			$processedArgs,
			$parser,
			$frame,
			$titleFactory,
		);
	}

	/**
	 * @return string[]
	 */
	public function getTagNames() {
		return [
			'booknav'
		];
	}

	/**
	 * @return IParamDefinition[]
	 */
	public function getArgsDefinitions() {
		return [
			new ParamDefinition(
			ParamType::STRING,
			static::ATTR_BOOK,
			''
			),
		new ParamDefinition(
			ParamType::STRING,
			static::ATTR_CHAPTER,
			''
			)
		];
	}
}
