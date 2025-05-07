bs.util.registerNamespace( 'ext.bookshelf.ui.panel' );

require( './../../data/BookNavigationTreeStore.js' );
require( './../data/tree/BookNavigationTree.js' );

ext.bookshelf.ui.panel.BookNavigationTreePanel = function ( cfg ) {
	cfg.store = new ext.bookshelf.data.BookNavigationTreeStore( {
		path: 'bookshelf/navigation'
	} );
	ext.bookshelf.ui.panel.BookNavigationTreePanel.parent.call( this, cfg );
};

OO.inheritClass( ext.bookshelf.ui.panel.BookNavigationTreePanel, OOJSPlus.ui.panel.NavigationTreePanel );

ext.bookshelf.ui.panel.BookNavigationTreePanel.prototype.updatePages = function () {
	this.$treeCnt.children().remove();

	const pageTree = new ext.bookshelf.ui.data.tree.BookNavigationTree( {
		style: {
			IconExpand: 'next',
			IconCollapse: 'expand'
		},
		data: this.pages,
		allowDeletions: false,
		allowAdditions: false,
		store: this.store,
		includeRedirect: false
	} );
	this.$treeCnt.append( pageTree.$element );
};
