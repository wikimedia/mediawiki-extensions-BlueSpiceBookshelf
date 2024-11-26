( function ( mw, $ ) {
	const Vue = require( 'vue' );
	const BooksApp = require( './components/BooksApp.vue' );

	function render() {
		const deferred = $.Deferred();
		const dfdList = getStoreData();

		let filter = '';
		const query = window.location.search;
		const queryParams = new URLSearchParams( query ); // eslint-disable-line compat/compat
		if ( queryParams.has( 'filter' ) ) {
			filter = queryParams.get( 'filter' );
		}
		dfdList.done( function ( response ) {
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
						books.sort( function ( bookA, bookB ) {
							return bookA.displaytitle.toLowerCase().localeCompare( bookB.displaytitle.toLowerCase() );
						} );
						searchableData = setSearchableData( books );
					}

					return h( BooksApp, {
						items: books,
						searchableData: searchableData,
						searchPlaceholderLabel: mw.message( 'bs-books-overview-page-book-search-placeholder' ).plain(),
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

		mw.loader.using( 'mediawiki.api' ).done( function () {
			const api = new mw.Api();
			api.abort();
			api.get( {
				action: 'bs-books-overview-store',
				limit: -1
			} )
				.done( function ( response ) {
					const modules = getModules( response.results );
					mw.loader.using( modules ).done( function () {
						dfd.resolve( response.results );
					} );
				} ).fail( function () {
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
		const data = [];
		items.forEach( function ( item ) {
			const title = mw.Title.makeTitle( item.book_namespace, item.book_title );
			let prefText = '';
			if ( title !== null ) {
				prefText = title.getPrefixedDb();
			}

			data.push(
				prefText.toLowerCase() + ' ' +
				item.bookshelf.toLowerCase() + ' ' +
				item.book_title.toLowerCase() + ' ' +
				item.displaytitle.toLowerCase() + ' ' +
				item.subtitle.toLowerCase()
			);
		} );

		return data;
	}

	function getModules( results ) {
		let allModules = [];
		results.forEach( function ( result ) {
			if ( result.modules.length <= 0 ) {
				return;
			}
			allModules = allModules.concat( result.modules );
		} );

		const modules = allModules.filter( function ( v, i, self ) {
			return i == self.indexOf( v ); // eslint-disable-line eqeqeq
		} );
		return modules;
	}

	render();

}( mediaWiki, jQuery ) );
