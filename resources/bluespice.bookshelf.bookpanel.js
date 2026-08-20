$( () => {
	window.ext = window.ext || {};
	const ext = window.ext;

	require( './ui/panel/BookNavigationTreePanel.js' );
	require( './ui/dialog/CreateChapter.js' );

	function openCreateChapterDialog( chapter ) {
		new mw.Api().get( {
			action: 'bs-book-chapters-store',
			limit: 9999,
			filter: JSON.stringify( [ {
				value: mw.config.get( 'bsActiveBookId' ),
				property: 'chapter_book_id',
				operator: 'eq',
				type: 'string'
			} ] )
		} ).done( ( result ) => {
			const dialog = new bs.bookshelf.ui.dialog.CreateChapter( {
				bookId: mw.config.get( 'bsActiveBookId' ),
				chapters: result.results,
				chapterNumber: chapter
			} );
			if ( !this.windowManager ) {
				this.windowManager = new OO.ui.WindowManager( { modal: true } );
				$( document.body ).append( this.windowManager.$element );
			}

			this.windowManager.addWindows( [ dialog ] );
			this.windowManager.openWindow( dialog ).closed.then( ( data ) => {
				if ( data && data.action === 'create' && data.hasOwnProperty( 'page' ) ) {
					const newChapter = mw.Title.newFromText( data.page );
					window.location.href = newChapter.getUrl();
				}
			} );
		} );
	}

	/* eslint-disable-next-line no-jquery/no-global-selector */
	const $bookTreeCnt = $( '#book-panel-tree' );

	const bookTreePanel = new ext.bookshelf.ui.panel.BookNavigationTreePanel( {
		skeletonID: 'bs-bookshelf-tree-skeleton'
	} );
	$bookTreeCnt.append( bookTreePanel.$element );

	$( document ).on( 'click', '.ca-new-chapter', ( e ) => {
		openCreateChapterDialog( '' );
		e.defaultPrevented = true;
		return false;
	} );
} );
