<?php

namespace BlueSpice\Bookshelf;

use BlueSpice\Bookshelf\Data\BookChapters\Reader;
use BlueSpice\Bookshelf\Data\BookChapters\Store;
use BlueSpice\Context;
use Config;
use IContextSource;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\DataStore\FieldType;
use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\Filter\StringValue;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\RecordSet;
use MWStake\MediaWiki\Component\DataStore\ResultSet;
use Title;
use TitleFactory;

class Utilities {

	/**
	 * @var IContextSource
	 */
	private $context = null;

	/**
	 * @var Config
	 */
	private $config = null;

	/**
	 * @var MediaWikiServices
	 */
	private $services = null;

	/**
	 * @var Store
	 */
	private $bookChaptersStore = null;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory = null;

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param MediaWikiServices $services
	 */
	public function __construct( IContextSource $context, Config $config, MediaWikiServices $services ) {
		$this->context = $context;
		$this->config = $config;
		$this->services = $services;
		$this->titleFactory = $services->getTitleFactory();

		$this->bookChaptersStore = $this->getBookChaptersStore();
	}

	/**
	 * @return Store
	 */
	private function getBookChaptersStore() {
		return new Store(
			new Context( $this->context, $this->config ),
			$this->config,
			$this->services->getDBLoadBalancer()
		);
	}

	/**
	 * @param @param ReaderParams $params
	 * @return array
	 */
	public function readBookChaptersStore( ReaderParams $params ): array {
		/** @var Reader */
		$reader = $this->bookChaptersStore->getReader();
		/** @var ResultSet */
		$resultSet = $reader->read( $params );
		/** @var RecordSet */
		$recordSet = $resultSet->getRecords();

		$data = [];
		foreach ( $recordSet as $record ) {
			$data[] = $record->jsonSerialize();
		}

		return $data;
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	public function getBooksForPage( Title $title ): array {
		$books = [];

		$readerParams = new ReaderParams( [
			ReaderParams::PARAM_FILTER => [
				[
					Filter::KEY_COMPARISON => StringValue::COMPARISON_EQUALS,
					Filter::KEY_PROPERTY => 'chapter_page_namespace',
					Filter::KEY_VALUE => $title->getNamespace(),
					Filter::KEY_TYPE => FieldType::STRING
				],
				[
					Filter::KEY_COMPARISON => StringValue::COMPARISON_EQUALS,
					Filter::KEY_PROPERTY => 'chapter_page_title',
					Filter::KEY_VALUE => $title->getDBkey(),
					Filter::KEY_TYPE => FieldType::STRING
				]
			]
		] );

		$results = $this->readBookChaptersStore( $readerParams );

		foreach ( $results as $result ) {
			$book = $this->titleFactory->makeTitle( $result['chapter_book_namespace'], $result['chapter_book_title'] );

			$key = $book->getPrefixedDBkey();
			if ( !isset( $books[$key] ) ) {
				$books[$key] = [
					'chapter_book_namespace' => $result['chapter_book_namespace'],
					'chapter_book_title' => $result['chapter_book_title']
				];
			}
		}

		return $books;
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	public function getChaptersOfBook( Title $title ): array {
		$pages = [];

		$readerParams = new ReaderParams( [
			ReaderParams::PARAM_FILTER => [
				[
					Filter::KEY_COMPARISON => StringValue::COMPARISON_EQUALS,
					Filter::KEY_PROPERTY => 'chapter_book_namespace',
					Filter::KEY_VALUE => $title->getNamespace(),
					Filter::KEY_TYPE => FieldType::STRING
				],
				[
					Filter::KEY_COMPARISON => StringValue::COMPARISON_EQUALS,
					Filter::KEY_PROPERTY => 'chapter_book_title',
					Filter::KEY_VALUE => $title->getDBkey(),
					Filter::KEY_TYPE => FieldType::STRING
				]
			]
		] );

		$results = $this->readBookChaptersStore( $readerParams );

		foreach ( $results as $result ) {
			$page = $this->titleFactory->makeTitle( $result['chapter_page_namespace'], $result['chapter_page_title'] );

			$key = $page->getPrefixedDBkey();
			if ( !isset( $pages[$key] ) ) {
				$pages[$key] = [
					'chapter_page_namespace' => $result['chapter_book_namespace'],
					'chapter_page_title' => $result['chapter_book_title'],
					'chapter_title' => $result['chapter_title'],
					'chapter_number' => $result['chapter_number'],
					'chapter_type' => $result['chapter_type'],
				];
			}
		}

		return $pages;
	}

	/**
	 * Returns prefixed db key of book source page
	 * @return string
	 */
	public function getCurrentBookSource(): string {
		return '';
	}
}
