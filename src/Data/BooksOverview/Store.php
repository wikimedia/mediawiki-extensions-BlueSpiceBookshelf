<?php

namespace BlueSpice\Bookshelf\Data\BooksOverview;

use BlueSpice\Bookshelf\BookMetaLookup;
use Config;
use IContextSource;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Permissions\PermissionManager;
use MWStake\MediaWiki\Component\DataStore\IStore;
use MWStake\MediaWiki\Component\DataStore\NoWriterException;
use RepoGroup;
use TitleFactory;
use Wikimedia\Rdbms\LoadBalancer;

class Store implements IStore {

	/**
	 * @var \IContextSource
	 */
	protected $context = null;

	/**
	 * @var Config
	 */
	private $config = null;

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer = null;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory = null;

	/**
	 * @var HookContainer
	 */
	private $hookRunner = null;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager = null;

	/**
	 * @var RepoGroup
	 */
	private $repoGroup = null;

	/** @var BookMetaLookup */
	private $bookMetaLookup = null;

	/**
	 * @param ContextSource $context
	 * @param Config $config
	 * @param LoadBalancer $loadBalancer
	 * @param BookMetaLookup $bookMetaLookup
	 * @param TitleFactory $titleFactory
	 * @param PermissionManager $permissionManager
	 * @param HookContainer $hookContainer
	 * @param RepoGroup $repoGroup
	 */
	public function __construct(
		IContextSource $context, Config $config, LoadBalancer $loadBalancer,
		BookMetaLookup $bookMetaLookup, TitleFactory $titleFactory, PermissionManager $permissionManager,
		HookContainer $hookContainer, RepoGroup $repoGroup
	) {
		$this->context = $context;
		$this->config = $config;
		$this->loadBalancer = $loadBalancer;
		$this->bookMetaLookup = $bookMetaLookup;
		$this->titleFactory = $titleFactory;
		$this->permissionManager = $permissionManager;
		$this->hookRunner = $hookContainer;
		$this->repoGroup = $repoGroup;
	}

	/**
	 *
	 * @return Reader
	 */
	public function getReader() {
		return new Reader(
			$this->context,
			$this->config,
			$this->loadBalancer,
			$this->titleFactory,
			$this->permissionManager,
			$this->hookRunner,
			$this->repoGroup,
			$this->bookMetaLookup
		);
	}

	/**
	 *
	 * @throws NoWriterException
	 */
	public function getWriter() {
		throw new NoWriterException();
	}
}
