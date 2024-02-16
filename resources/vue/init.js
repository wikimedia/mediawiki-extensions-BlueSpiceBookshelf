( function ( mw, $, bs, d, undefined ) {
	const Vue = require( 'vue' );
	const BooksApp = require( './components/BooksApp.vue' );

	function render() {
		var deferred = $.Deferred();
		var dfdList = getStoreData();

		dfdList.done( function( response ) {
			var h = Vue.h;

			var vm = Vue.createMwApp( {
				mounted: function () {
					deferred.resolve( this.$el );
				},
				render: function() {
					var books = [];

					if ( response.length > 0 ) {
						books = response;
						searchableData = setSearchableData( books );
					}

					return h( BooksApp, {
						items: books,
						searchableData: searchableData
					} );
				}
			} );

			vm.mount( '#bs-books-wrapper' );
			$( '#bs-books-wrapper' ).removeClass( 'loading' );
		} );

		return deferred;
	};

	function getStoreData() {
		var dfd = $.Deferred();

		mw.loader.using( 'mediawiki.api' ).done( function() {
			var api = new mw.Api();
			api.abort();
			api.get( {
					"action": "bs-books-overview-store",
			} )
			.done( function( response ) {
				dfd.resolve( response.results );
			} ).fail( function() {
				dfd.reject()
			} );
		} );

		return dfd.promise();
	}

	/**
	 * Set up an array with keywords used by the codex search
	 * @param {*} items
	 * @returns
	 */
	function setSearchableData ( items ) {
		var data = [];
		items.forEach( function ( item )  {
			data.push(
				item.book_title.toLowerCase() + " "
				+ item.displaytitle.toLowerCase() + " "
				+ item.subtitle.toLowerCase()
			);
		} );
		return data;
	}

	render();


} )( mediaWiki, jQuery, blueSpice, document );