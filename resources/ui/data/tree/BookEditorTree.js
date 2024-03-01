bs.util.registerNamespace( 'ext.bookshelf.ui.data.tree' );

ext.bookshelf.ui.data.tree.BookEditorTree = function ( cfg ) {
	ext.bookshelf.ui.data.tree.BookEditorTree.parent.call( this, cfg );
};

// eslint-disable-next-line max-len
OO.inheritClass( ext.bookshelf.ui.data.tree.BookEditorTree, ext.menueditor.ui.data.tree.Tree );

// eslint-disable-next-line max-len
ext.bookshelf.ui.data.tree.BookEditorTree.prototype.getPossibleNodesForLevel = function ( lvl ) {
	return [ 'bs-bookshelf-chapter-wikilink-with-alias', 'bs-bookshelf-chapter-plain-text' ];
};

ext.bookshelf.ui.data.tree.BookEditorTree.prototype.getMaxLevels = function () {
	return 100;
};
