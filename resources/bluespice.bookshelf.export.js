(function( mw, $, d, undefined ){
	$( document ).on( 'click', '.bs-books-overview-action-export', ( e ) => {
		e.preventDefault();
		var target = e.target;
		if ( target.nodeName != 'A' ) {
			target = $( target).parent();
		}

		let bookTitle = $( target ).data( 'prefixed_db_key' );
		exportBook( bookTitle, [] );
	} );

	window.onBookshelfViewToolExportBook = function( data ) {
		let currentBook = mw.config.get( 'wgPageName' );
		let selectedItems = findSelectedItems( data );

		let chapters = [];
		for ( var index = 0; index < selectedItems.length; index++ ) {
			chapters.push( selectedItems[index] );
		}
		exportBook( currentBook, chapters );
	}

	function exportBook ( bookTitle, chapters ) {
		mw.loader.using( [ 'bluespice.bookshelf.api', 'ext.pdfcreator.export.api' ] ).done( () => {
			const bookApi = new ext.bookshelf.api.Api();
			bookApi.getBookTemplateForBook( bookTitle ).done( ( templateData ) => {
				let template = templateData['template'];
				let pageId = templateData[ 'pageid' ];

				const data = {
					mode: 'book',
					book: bookTitle,
					template: template,
					chapters: chapters,
					relevantTitle: bookTitle
				}
				mw.hook( 'pdfcreator.export.data' ).fire( this, data );

				const pdfApi = new ext.pdfcreator.api.Api();
				pdfApi.export( pageId, data ).done( () => {
					mw.notify( 'PDF erstellt' );
				} )
				.fail( ( error ) => {
					console.log( error );
				} )
			} ).fail( () => {
				console.error( 'export api module could not be loaded' );
			} );
		} )
	}

	function findSelectedItems ( items ) {
		let selectedItems = []
		for ( var index = 0; index < items.length; index++ ) {
			let item = items[index];

			if ( item.hasOwnProperty( 'selected' ) && item.selected === true ) {
				selectedItems.push( item.chapter );
			}

			if ( item.hasOwnProperty( 'children' ) && item.children.length > 0 ) {
				let selectedChildren = findSelectedItems( item.children );
				selectedItems = selectedItems.concat( selectedChildren );
			}
		}
		return selectedItems;
	}
} )( mediaWiki, jQuery, document );