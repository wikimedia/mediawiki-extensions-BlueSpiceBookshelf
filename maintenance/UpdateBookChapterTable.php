<?php

use Wikimedia\Rdbms\IMaintainableDatabase;

require_once dirname( __DIR__, 3 ) . '/maintenance/Maintenance.php';

class UpdateBookChapterTable extends LoggedUpdateMaintenance {

	/**
	 * @var TitleFactory
	 */
	private $titleFactory = null;

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'BlueSpiceBookshelf' );
	}

	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$this->output( "BlueSpiceBookshelf - update bs_book_chapters table\n" );

		$this->titleFactory = new TitleFactory();

		$tableContent = $this->buildTableContentFromContentModelBook();
		$this->insertTableContent( $tableContent );
		$this->cleanupTable( $tableContent );

		return true;
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bookshelf-populate-book-chapter-table';
	}

	/**
	 * @param array $data
	 */
	private function insertTableContent( array $data ): void {
		$dbm = $this->getDB( DB_PRIMARY );
		$dbr = $this->getDB( DB_REPLICA );

		foreach ( $data as $row ) {
			$conds = [
				'chapter_book_namespace' => $row['chapter_book_namespace'],
				'chapter_book_title' => $row['chapter_book_title'],
				'chapter_page_namespace' => $row['chapter_page_namespace'],
				'chapter_page_title' => $row['chapter_page_title'],
				'chapter_title' => $row['chapter_title'],
				'chapter_type' => $row['chapter_type']
			];

			$res = $dbr->select(
				'bs_book_chapters',
				'*',
				$conds,
				__METHOD__
			);

			$book_title = $this->titleFactory->makeTitle(
				$row['chapter_book_namespace'],
				$row['chapter_book_title']
			);

			if ( $row['chapter_type'] === 'plain-text' ) {
				if ( $res->numRows() < 1 ) {
					$this->insertRow(
						$dbm,
						$row,
						$row['chapter_title'],
						$book_title->getPrefixedDBkey()
					);
				} else {
					$this->updateRow(
						$dbm,
						$row,
						$conds,
						$row['chapter_title'],
						$book_title->getPrefixedDBkey()
					);
				}
			} else {
				$page_title = $this->titleFactory->makeTitle(
					$row['chapter_page_namespace'],
					$row['chapter_page_title']
				);

				if ( $res->numRows() < 1 ) {
					$this->insertRow(
						$dbm,
						$row,
						$page_title->getPrefixedDBkey(),
						$book_title->getPrefixedDBkey()
					);
				} else {
					$this->updateRow(
						$dbm,
						$row,
						$conds,
						$page_title->getPrefixedDBkey(),
						$book_title->getPrefixedDBkey()
					);
				}
			}
		}
	}

	/**
	 * @param array $data
	 * @return void
	 */
	private function cleanupTable( array $data ): void {
		$dbm = $this->getDB( DB_PRIMARY );
		$dbr = $this->getDB( DB_REPLICA );

		$validData = [];
		foreach ( $data as $chapter ) {
			$book_title = $this->titleFactory->makeTitle(
				$chapter['chapter_book_namespace'],
				$chapter['chapter_book_title']
			);

			$chapterTitle = '';
			if ( $chapter['chapter_type'] === 'plain-text' ) {
				$chapterTitle = $chapter['chapter_title'];
			} else {
				$page_title = $this->titleFactory->makeTitle(
					$chapter['chapter_page_namespace'],
					$chapter['chapter_page_title']
				);
				$chapterTitle = $page_title->getPrefixedDBkey();
			}

			$book_title_key = $book_title->getPrefixedDBkey();
			if ( !isset( $validData[$book_title_key] ) ) {
				$validData[$book_title_key] = [];
			}
			$validData[$book_title_key][$chapterTitle] = $chapter['chapter_type'];
		}

		foreach ( $data as $book_title_key => $specs ) {
			$book = $this->titleFactory->makeTitle(
				$specs['chapter_book_namespace'],
				$specs['chapter_book_title']
			 );

			$res = $dbr->select(
				'bs_book_chapters',
				'*',
				[
					'chapter_book_namespace' => $specs['chapter_book_namespace'],
					'chapter_book_title' => $specs['chapter_book_title']
				],
				__METHOD__
			);

			if ( $res->numRows() < 1 ) {
				continue;
			}

			$book_key = $book->getPrefixedDBkey();

			foreach ( $res as $row ) {
				if ( $row->chapter_type === 'plain-text' ) {
					$chapter_key = $row->chapter_title;

					if ( isset( $validData[$book_key][$chapter_key] )
						&& $validData[$book_key][$chapter_key] === 'plain-text'
					) {
						continue;
					}

					$conds = [
						'chapter_book_namespace' => $book->getNamespace(),
						'chapter_book_title' => $book->getDBkey(),
						'chapter_page_namespace' => null,
						'chapter_page_title' => null,
						'chapter_title' => $row->chapter_title,
						'chapter_type' => $row->chapter_type
					];

				} else {
					$page = $this->titleFactory->makeTitle(
						$row->chapter_page_namespace,
						$row->chapter_page_title
					);

					$chapter_key = $page->getPrefixedDBkey();

					if ( isset( $validData[$book_key][$chapter_key] )
						&& $validData[$book_key][$chapter_key] !== 'plain-text'
					) {
						continue;
					}

					$conds = [
						'chapter_book_namespace' => $book->getNamespace(),
						'chapter_book_title' => $book->getDBkey(),
						'chapter_page_namespace' => $page->getNamespace(),
						'chapter_page_title' => $page->getDBkey(),
						'chapter_type' => $row->chapter_type
					];
				}

				$this->deleteRow(
					$dbm,
					$conds,
					$page->getPrefixedDBkey(),
					$book->getPrefixedDBkey()
				);
			}
		}
	}

	/**
	 * @param IMaintainableDatabase $dbm
	 * @param stdClass $row
	 * @param string $chapterName
	 * @param string $bookName
	 */
	private function insertRow( $dbm, $row, string $chapterName, string $bookName ) {
		$this->output(
			"\033[32m insert \033[0m \"" . $chapterName
			 . "\" into book \"" . $bookName . "\"\n"
		);
		$dbm->insert(
			'bs_book_chapters',
			$row,
			__METHOD__
		);
	}

	/**
	 * @param IMaintainableDatabase $dbm
	 * @param stdClass $row
	 * @param array $conds
	 * @param string $chapterName
	 * @param string $bookName
	 */
	private function updateRow( $dbm, $row, $conds, string $chapterName, string $bookName ) {
		$this->output(
			"\033[33m update \033[0m \"" . $chapterName
			. "\" of book \"" . $bookName . "\"\n"
		);
		$dbm->update(
			'bs_book_chapters',
			$row,
			$conds,
			__METHOD__
		);
	}

	/**
	 * @param IMaintainableDatabase $dbm
	 * @param array $conds
	 * @param string $chapterName
	 * @param string $bookName
	 */
	private function deleteRow( $dbm, $conds, string $chapterName, string $bookName ) {
		$this->output(
			"\033[33m delete \033[0m \"" . $chapterName
			. "\" of book \"" . $bookName . "\"\n"
		);
		$dbm->delete(
			'bs_book_chapters',
			$conds,
			__METHOD__
		);
	}

	/**
	 * @return array
	 */
	private function buildTableContentFromContentModelBook(): array {
		$books = $this->readData();
		$data = $this->buildTableData( $books );
		return $data;
	}

	/**
	 * @param array $books
	 * @return array
	 */
	private function buildTableData( array $books ): array {
		$data = [];
		foreach ( $books as $book => $toc ) {
			foreach ( $toc as $chapterData ) {
				$bookTitle = $this->titleFactory->newFromDBkey( $book );

				if ( $chapterData['type'] === 'plain-text' ) {
					$chapterPageNamespace = null;
					$chapterPageTitle = null;
				} else {
					$chapterTitle = $this->titleFactory->newFromDBkey( $chapterData['title'] );
					$chapterPageNamespace = $chapterTitle->getNamespace();
					$chapterPageTitle = $chapterTitle->getDBkey();
				}

				$data[] = [
					'chapter_book_namespace' => $bookTitle->getNamespace(),
					'chapter_book_title' => $bookTitle->getDBkey(),
					'chapter_page_namespace' => $chapterPageNamespace,
					'chapter_page_title' => $chapterPageTitle,
					'chapter_title' => $chapterData['display-title'],
					'chapter_number' => $chapterData['number'],
					'chapter_type' => $chapterData['type']
				];
			}
		}
		return $data;
	}

	/**
	 * @return array
	 */
	protected function readData(): array {
		$dbr = $this->getDB( DB_REPLICA );
		$res = $dbr->select(
			'page',
			'*',
			[
				'page_content_model' => 'book'
			],
			__METHOD__
		);

		if ( $res->numRows() < 1 ) {
			return [];
		}

		$books = [];
		foreach ( $res as $row ) {
			$title = $this->titleFactory->makeTitle( $row->page_namespace, $row->page_title );

			$phProvider = $this->getPageHierarchyProvider( $title );
			if ( !$phProvider ) {
				continue;
			}

			$name = $title->getPrefixedDBkey();
			$toc = $phProvider->getExtendedTOCArray();
			$books[$name] = $toc;
		}
		return $books;
	}

	/**
	 * @param Title $title
	 * @return PageHierarchyProvider|null
	 */
	private function getPageHierarchyProvider( Title $title ): ?PageHierarchyProvider {
		try {
			$phProvider = PageHierarchyProvider::getInstanceFor(
				$title->getPrefixedDBkey()
			);
			return $phProvider;
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return null;
	}
}

$maintClass = UpdateBookChapterTable::class;
require_once RUN_MAINTENANCE_IF_MAIN;
