$(document).on( 'click', '#ca-bookshelf-add-to-book', function( e ) {
	e.preventDefault();
	require( './ui/dialog/AddToBook.js' );

	var dialog = new bs.bookshelf.ui.dialog.AddToBook( {
		pagename: mw.config.get( 'wgPageName' )
	} );
	dialog.show().closed.then( function( data ) {
		if ( data.needsReload ) {
			window.location.reload();
		}
	}.bind( this ) );

	return false;
} );
