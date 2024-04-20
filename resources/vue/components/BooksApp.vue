<template>
	<div class="bs-books-search">
		<cdx-search-input
			:clearable="true"
			:placeholder="searchPlaceholderLabel"
			:aria-label="searchPlaceholderLabel"
			@update:model-value="getSearchResults"
		></cdx-search-input>
	</div>
	<div class="bs-books-bookshelfs">
		<bookshelf  v-for="bookshelf in bookshelfs"
			v-show="bookshelf.isVisible"
			v-bind:bookshelf="bookshelf.name"
			v-bind:books="bookshelf.books"
		></bookshelf>
	</div>
</template>

<script>
var Bookshelf = require( './Bookshelf.vue' );
var { CdxSearchInput } = require( '@wikimedia/codex' );

// @vue/component
module.exports = exports = {
	name: 'BooksApp',
	props: {
		items: {
			type: Array
		},
		searchableData: {
			type: Array
		},
		searchPlaceholderLabel: {
			type: String
		}
	},
	components: {
		'bookshelf': Bookshelf,
		CdxSearchInput: CdxSearchInput
	},
	data: function () {
		this.items.forEach( function( item ) {
			item.isVisible = true;
		} );

		// Initial bookselfs is in local scope. The bookshelfs value in return statement is global.
		// If we use bookshelfs here for createBookshelfs the search input would not change the visible items.
		var initialBookshelfs = createBookshelfs( this.items );

		return {
			searchInput: [],
			bookshelfs: initialBookshelfs
		};
	},
	methods: {
		getSearchResults: function( search ) {
			search = search.toLowerCase();
			found = 1;

			if ( !this.items ) {
				return;
			}

			if ( search !== '' && search !== false ) {
				if ( search.search( ' ' ) !== -1 ) {
					this.searchInput = search.split( ' ' );
				} else  {
					this.searchInput[0] = search;
				}

				for ( let x = 0; x < this.searchableData.length; x++ ) {
					for (let y = 0; y < this.searchInput.length; y++ ) {
						if ( this.searchableData[x].search( this.searchInput[y] )  === -1 ) {
							found = 0;
						}
					}

					if ( found === 0 ) {
						this.items[x].isVisible = false;
						this.bookshelfs = createBookshelfs( this.items );
					} else {
						this.items[x].isVisible = true;
						this.bookshelfs = createBookshelfs( this.items );
					}

					found = 1;
				}
			} else {
				// All chars in search input had been deleted
				this.items.forEach( function( item ) {
					item.isVisible = true;
				} );
				this.bookshelfs = createBookshelfs( this.items );
			}
		}
	}
};

function createBookshelfs( items ) {
	// Find all bookshelf names
	var bookshelfNames = [];
	items.forEach( function( item ) {
		if ( item.hasOwnProperty( 'bookshelf' ) ) {
			bookshelfNames.push( item.bookshelf );
		}
	} );

	// Make bookshelf names unique in array bookshelfNames
	bookshelfNames = bookshelfNames.filter( ( value, index, array ) => array.indexOf( value ) === index );

	// Fill bookshelfs with books
	var bookshelfsInData = [];
	bookshelfNames.forEach(
		function( bookshelfName ) {
			var bookshelfBooks = items.filter(
				obj => {
					return obj.bookshelf === bookshelfName
				}
			)

			bookshelfsInData.push( {
				name: bookshelfName,
				books: bookshelfBooks
			} );
		},
		{
			items: items,
		}
	);

	// Sort booksheslf alphabetically
	bookshelfsInData.sort( function( bookshelfA, bookshelfB ) {
		return bookshelfA.name.toLowerCase().localeCompare( bookshelfB.name.toLowerCase() );
	} )

	// Move various books (books without bookshelf value) to the end of the list
	const variousBooksIndex = bookshelfsInData.findIndex( bookshelf => bookshelf.name == '' );
	const variousBooks = bookshelfsInData[variousBooksIndex];
	variousBooks.name = mw.message( 'bs-books-overview-page-bookshelf-various-books' ).plain();
	bookshelfsInData.shift( ...bookshelfsInData.splice( 0, variousBooksIndex ) );
	bookshelfsInData.push( variousBooks );

	// Show only bookshelfs with visible books
	bookshelfsInData.forEach( function( item ) {
		var isVisible = false;
		item.books.forEach( function( book ) {
			if ( book.isVisible === true ) {
				isVisible = true;
			}
		} );
		item.isVisible = isVisible;
	} );

	return bookshelfsInData;
}
</script>

<style lang="css">
:root {
	--bs-books-overview-page-focus-visible-color: #3E5389;
	--bs-books-overview-page-book-new: #BD1D1D;
}
.bs-books-search {
	width: 50%;
	margin-left: 20px;
}
.bs-books-bookshelfs {
	margin-top: 20px;
}
@media ( max-width: 768px ) {
	.bs-books-search {
		width: 100%;
		margin-left: 0px;
	}
}
</style>