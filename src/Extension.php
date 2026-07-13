<?php

namespace BlueSpice\Bookshelf;

use BlueSpice\Bookshelf\MenuEditor\NodeProcessor\ChapterPlainTextProcessor;
use BlueSpice\Bookshelf\MenuEditor\NodeProcessor\ChapterWikiLinkWithAliasProcessor;
use BlueSpice\Bookshelf\Tag\SearchInBook as SearchInBookTag;
use MediaWiki\Debug\MWDebug;
use MediaWiki\Registration\ExtensionRegistry;

class Extension extends \BlueSpice\Extension {

	/**
	 * extension.json callback
	 */
	public static function onRegistration() {
		$GLOBALS['bsgSystemNamespaces'][1504] = 'NS_BOOK';
		$GLOBALS['bsgSystemNamespaces'][1505] = 'NS_BOOK_TALK';
		// force enable user namespace subpages, cause we need to save books in these
		$GLOBALS['wgNamespacesWithSubpages'][NS_USER] = true;
		static::checkLegacy();

		if ( ExtensionRegistry::getInstance()->isLoaded( 'BlueSpiceExtendedSearch' ) ) {
			// Add tag conditionally
			$GLOBALS['bsgExtensionAttributeRegistryOverrides']['BlueSpiceFoundationTagRegistry'] = [
				'merge' => [
					'searchinbook' => SearchInBookTag::class
				]
			];
		}

		mwsInitComponents();
		$GLOBALS['mwsgWikitextNodeProcessorRegistry'] += [
			'bs-bookshelf-chapter-plain-text' => [
				'class' => ChapterPlainTextProcessor::class
			],
			'bs-bookshelf-chapter-wikilink-with-alias' => [
				'class' => ChapterWikiLinkWithAliasProcessor::class,
				'services' => [ 'TitleFactory' ]
			]
		];
	}

	/**
	 * Check legacy configs and set if necessary
	 */
	public static function checkLegacy() {
		// Apply deprecated globals => will override new ones if set!
		if ( isset( $GLOBALS['bsgBookShelfUIDefaultCoverImage'] ) ) {
			static::logGlobalDeprecation(
				'bsgBookShelfUIDefaultCoverImage',
				'bsgBookShelfDefaultCoverImage'
			);
			$GLOBALS['bsgBookShelfDefaultCoverImage'] =
				$GLOBALS['bsgBookShelfUIDefaultCoverImage'];
		}
		if ( isset( $GLOBALS['BookShelfUIShowChapterNavigationPagerBeforeContent'] ) ) {
			static::logGlobalDeprecation(
				'BookShelfUIShowChapterNavigationPagerBeforeContent',
				'BookShelfShowChapterNavigationPagerBeforeContent'
			);
			$GLOBALS['BookShelfShowChapterNavigationPagerBeforeContent'] =
				$GLOBALS['BookShelfUIShowChapterNavigationPagerBeforeContent'];
		}
		if ( isset( $GLOBALS['BookShelfUIShowChapterNavigationPagerAfterContent'] ) ) {
			static::logGlobalDeprecation(
				'BookShelfUIShowChapterNavigationPagerAfterContent',
				'BookShelfShowChapterNavigationPagerAfterContent'
			);
			$GLOBALS['BookShelfShowChapterNavigationPagerAfterContent'] =
				$GLOBALS['BookShelfUIShowChapterNavigationPagerAfterContent'];
		}

		$registy = ExtensionRegistry::getInstance()->getAttribute(
			'BlueSpiceBookshelfUIMassAddHandlerRegistry'
		);
		if ( !empty( $registy ) ) {
			// Add legacy extension attribute values
			$GLOBALS['bsgExtensionAttributeRegistryOverrides']['BlueSpiceBookshelfMassAddHandlerRegistry'] = [
				'merge' => $registy
			];
		}
	}

	/**
	 * @param string $old
	 * @param string $new
	 */
	protected static function logGlobalDeprecation( $old, $new ) {
		MWDebug::warning( "Using $old is deprecated, use $new instead" );
	}
}
