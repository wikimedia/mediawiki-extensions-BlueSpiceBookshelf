( function ( mw, $ ) {
	const Vue = require( 'vue' );
	const BooksApp = require( './components/BooksApp.vue' );

	function render() {
		const deferred = $.Deferred();
		const dfdList = getStoreData();

		let filter = '';
		const query = window.location.search;
		const queryParams = new URLSearchParams( query );
		if ( queryParams.has( 'filter' ) ) {
			filter = queryParams.get( 'filter' );
		}
		dfdList.done( ( response ) => {
			const h = Vue.h;

			const vm = Vue.createMwApp( {
				mounted: function () {
					deferred.resolve( this.$el );
				},
				render: function () {
					let books = [];
					let searchableData = [];

					if ( response.length > 0 ) {
						books = response;
						// Sort books alphabetically
						books.sort( ( bookA, bookB ) => bookA.displaytitle.toLowerCase().localeCompare( bookB.displaytitle.toLowerCase() ) );
						searchableData = setSearchableData( books );
					}

					return h( BooksApp, {
						items: books,
						searchableData: searchableData,
						searchPlaceholderLabel: mw.message( 'bs-books-overview-page-book-search-placeholder' ).text(),
						filter: filter
					} );
				}
			} );

			vm.mount( '#bs-books-wrapper' );
			$( '#bs-books-wrapper' ).removeClass( 'loading' ); // eslint-disable-line no-jquery/no-global-selector
		} );

		return deferred;
	}

	function getStoreData() {
		const dfd = $.Deferred();

		mw.loader.using( 'mediawiki.api' ).done( () => {
			const api = new mw.Api();
			api.abort();
			api.get( {
				action: 'bs-books-overview-store',
				limit: -1
			} )
				.done( ( response ) => {
					const modules = getModules( response.results );
					mw.loader.using( modules ).done( () => {
						dfd.resolve( response.results );
					} );
				} ).fail( () => {
					console.log( 'fail' ); // eslint-disable-line no-console
					dfd.reject();
				} );
		} );

		return dfd.promise();
	}

	/**
	 * Set up an array with keywords used by the codex search
	 *
	 * @param {*} items
	 * @return {Array}
	 */
	function setSearchableData( items ) {
		const canonicalBookNamespaceName = 'Book';

		const data = [];
		items.forEach( ( item ) => {
			const searchableData = [
				item.bookshelf,
				item.book_title,
				item.displaytitle,
				item.subtitle
			];

			const title = mw.Title.makeTitle( item.book_namespace, item.book_title );
			if ( title !== null ) {
				searchableData.push( title.getPrefixedDb() );
				searchableData.push(
					canonicalBookNamespaceName + ':' + title.getMain()
				);
			}

			data.push( searchableData
				.filter( Boolean )
				.map( ( str ) => str.toLowerCase() )
				.join( ' ' ) );
		} );

		return data;
	}

	function getModules( results ) {
		let allModules = [];
		results.forEach( ( result ) => {
			if ( result.modules.length <= 0 ) {
				return;
			}
			allModules = allModules.concat( result.modules );
		} );

		const modules = allModules.filter( ( v, i, self ) => i == self.indexOf( v ) ); // eslint-disable-line eqeqeq
		return modules;
	}

	render();

}( mediaWiki, jQuery ) );
