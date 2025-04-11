$( document ).on( 'click', '#ca-bookshelf-add-to-book', ( e ) => {
	e.preventDefault();
	require( './ui/dialog/AddToBook.js' );

	const dialog = new bs.bookshelf.ui.dialog.AddToBook( {
		pagename: mw.config.get( 'wgPageName' )
	} );
	dialog.show().closed.then( () => {
		window.location.reload();
	} );

	return false;
} );
