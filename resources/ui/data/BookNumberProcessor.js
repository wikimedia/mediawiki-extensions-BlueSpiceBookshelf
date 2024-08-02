window.ext = window.ext || {};
window.ext.bookshelf = window.ext.bookshelf || {};
window.ext.bookshelf.ui = window.ext.bookshelf.ui || {};
window.ext.bookshelf.ui.data = window.ext.bookshelf.ui.data || {};

ext.bookshelf.ui.data.BookNumberProcessor = function () {};

OO.initClass( ext.bookshelf.ui.data.BookNumberProcessor );

ext.bookshelf.ui.data.BookNumberProcessor.prototype.calculateNumbersFromList = function ( items ) {
	let numbering = [];
	let counters = [ 0 ];

	items.forEach( function ( item ) {
		const level = item.level;
		while ( counters.length < level ) {
			counters.push( 0 );
		}
		counters[ level - 1 ]++;
		for ( let i = level; i < counters.length; i++ ) {
			counters[ i ] = 0;
		}
		numbering.push( counters.slice( 0, level ).join( '.' ) + '.' );
	} );

	return numbering;
};

ext.bookshelf.ui.data.BookNumberProcessor.prototype.calculateNumberForElement = function ( number, items, data ) {
	number = number || 1;

	for ( var i in items ) {
		if ( items[ i ] === data ) {
			break;
		}

		if ( data.level > items[ i ].level ) {
			if ( items[ i ].items.length > 0 ) {
				return number + '.' + this.calculateNumberForElement( number, items[i].items, data );
			}
		}
		number++;
	}
	return number;
};
