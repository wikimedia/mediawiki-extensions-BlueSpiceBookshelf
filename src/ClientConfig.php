<?php

namespace BlueSpice\Bookshelf;

use Config;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use MWStake\MediaWiki\Component\ManifestRegistry\ManifestAttributeBasedRegistry;

class ClientConfig {

	/**
	 *
	 * @param Context $context
	 * @param Config $config
	 * @return array
	 */
	public static function makeConfigJson(
		Context $context,
		Config $config
	) {
		$services = MediaWikiServices::getInstance();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		$defaultTemplate = $config->get( 'UEModuleBookPDFDefaultTemplate' );
		$defaultTemplatePath = $config->get( 'UEModuleBookPDFTemplatePath' );

		$availableTemplates = [];
		$dir = opendir( $defaultTemplatePath );
		if ( $dir ) {
			$subDir = readdir( $dir );
			while ( $subDir !== false ) {
				if ( in_array( $subDir, [ '.', '..', 'common' ] ) ) {
					$subDir = readdir( $dir );
					continue;
				}

				if ( !is_dir( "{$defaultTemplatePath}/{$subDir}" ) ) {
					$subDir = readdir( $dir );
					continue;
				}

				if ( file_exists( "{$defaultTemplatePath}/{$subDir}/template.php" ) ) {
					$availableTemplates[] = $subDir;
				}

				$subDir = readdir( $dir );
			}
		}

		if ( empty( $availableTemplates ) ) {
			$defaultTemplate = '';
		} else {
			if ( !in_array( $defaultTemplate, $availableTemplates ) ) {
				$defaultTemplate = $availableTemplates[0];
			}
		}
		return [
			'defaultTemplate' => $defaultTemplate,
			'availableTemplates' => $availableTemplates
		];
	}

	/**
	 *
	 * @return array
	 */
	public static function getRegisteredMetadata() {
		$registry = new ManifestAttributeBasedRegistry(
			'BlueSpiceBookshelfMetaData'
		);
		$services = MediaWikiServices::getInstance();
		$objectFactory = $services->getObjectFactory();

		$pages = [];
		$data = $registry->getAllValues();
		$modules = [];
		foreach ( $data as $key => $spec ) {
			$object = $objectFactory->createObject( $registry->getObjectSpec( $key ) );
			if ( !( $object instanceof IMetaDataDescription ) ) {
				continue;
			}

			$pages[ $key ] = [
				'classname' => $object->getJSClassname(),
				'key' => $object->getKey()
			];
			$modules = array_merge( $modules, $object->getRLModules() );
		}
		array_unique( $modules );
		return [
			'modules' => $modules,
			'pages' => $pages
		];
	}

	/**
	 *
	 * @return array
	 */
	public static function getBookshelfData() {
		$services = MediaWikiServices::getInstance();
		$metaLookup = $services->get( 'BSBookshelfBookMetaLookup' );

		$values = $metaLookup->getAllMetaValuesForKey( 'bookshelf' );
		$values = array_unique( $values );
		return $values;
	}

	/**
	 *
	 * @return array
	 */
	public static function getCreateNewBookPlugins() {
		$modules = ExtensionRegistry::getInstance()->getAttribute(
			'BlueSpiceBookshelfCreateNewBookModules'
		);

		return $modules;
	}
}
