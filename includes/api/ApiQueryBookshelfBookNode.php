<?php

use MediaWiki\Api\ApiQueryBase;
use Wikimedia\ParamValidator\ParamValidator;

class ApiQueryBookshelfBookNode extends ApiQueryBase {

	/**
	 *
	 * @param string $query
	 * @param string $moduleName
	 */
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'bsbsbn' );
	}

	/**
	 *
	 * @throws Exception
	 */
	public function execute() {
		$aParams = $this->extractRequestParams( false );

		// TODO: Also allow query by wiki page title instead of 'book+path'?
		// $pageSet = $this->getPageSet();
		// $pageSet->getGoodTitles();
		throw new Exception( 'Not yet implemented' );
	}

	/**
	 *
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'prop' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_DEFAULT => 'prev|current|next',
				ParamValidator::PARAM_TYPE => [
					'prev',
					'current',
					'next',
					// 'nextsibling',
					// 'prevsibling',
				]
			],
			'path' => [
				ParamValidator::PARAM_TYPE => 'string'
			],
			'book' => [
				ParamValidator::PARAM_TYPE => 'string'
			],
		];
	}
}
