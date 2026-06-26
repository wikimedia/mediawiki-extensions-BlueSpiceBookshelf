( function ( mw, $ ) {
	$( document ).on( 'click', '.bs-books-overview-action-watch', ( e ) => {
		e.preventDefault();
		let target = e.target;
		if ( target.nodeName !== 'A' ) {
			target = $( target ).parent();
		}

		const $target = $( target );
		const $icon = $target.find( 'i' );
		const $text = $target.find( 'span' );
		const bookTitle = $target.data( 'prefixed_db_key' );
		const displayTitle = $target.closest( '.bs-card' ).find( '.bs-card-title' ).text();
		const iconClasses = $icon.attr( 'class' ) || '';
		const watched = iconClasses.indexOf( 'bi-eye-fill' ) !== -1;

		const api = new mw.Api();
		const apiCall = watched ? api.unwatch( bookTitle ) : api.watch( bookTitle );
		apiCall.done( () => {
			const nowWatched = !watched;
			$icon.toggleClass( 'bi-eye-fill', nowWatched ).toggleClass( 'bi-eye', !nowWatched );

			// The following messages are used here:
			// * bs-bookshelf-books-overview-page-book-action-watch-title
			// * bs-bookshelf-books-overview-page-book-action-unwatch-title
			$target.attr( 'title', mw.message(
				nowWatched ?
					'bs-bookshelf-books-overview-page-book-action-unwatch-title' :
					'bs-bookshelf-books-overview-page-book-action-watch-title',
				displayTitle
			).text() );

			// The following messages are used here:
			// * bs-bookshelf-books-overview-page-book-action-watch-text
			// * bs-bookshelf-books-overview-page-book-action-unwatch-text
			$text.text( mw.message(
				nowWatched ?
					'bs-bookshelf-books-overview-page-book-action-unwatch-text' :
					'bs-bookshelf-books-overview-page-book-action-watch-text'
			).text() );

			// The following messages are used here:
			// * bs-bookshelf-books-overview-page-book-watched
			// * bs-bookshelf-books-overview-page-book-unwatched
			mw.notify( mw.message(
				nowWatched ?
					'bs-bookshelf-books-overview-page-book-watched' :
					'bs-bookshelf-books-overview-page-book-unwatched',
				displayTitle
			).text() );
		} );
	} );
}( mediaWiki, jQuery ) );
