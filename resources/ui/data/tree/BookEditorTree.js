bs.util.registerNamespace( 'ext.bookshelf.ui.data.tree' );

ext.bookshelf.ui.data.tree.BookEditorTree = function ( cfg ) {
	cfg.classes = [ 'bs-book-tree' ];
	ext.bookshelf.ui.data.tree.BookEditorTree.parent.call( this, cfg );
	this.numberProcessor = new ext.bookshelf.ui.data.BookNumberProcessor();
	this.bookTitle = mw.config.get( 'wgRelevantPageName' );
	this.metadataManager = new ext.bookshelf.data.BookMetaDataManager( this.bookTitle );

	this.connect( this, {
		nodeRemoved: 'updateNodeNumbers'
	} );

	mw.hook( 'menueditor.toolbar' ).add( function ( menuToolbar ) {
		this.metadataManager.load().done( function ( data ) {
			menuToolbar.toolbar.data = data;
		} );
		menuToolbar.toolbar.connect( this, {
			metadataset: function ( metadata ) {
				this.metadataManager.setData( metadata );
			},
			save: function() {
				this.metadataManager.save( this.metadataManager.getData() ).done( function () {
					this.emit( 'saveSuccess' );
				} ).fail( function ( error ) {
					console.log( error );
				} );
			}
		} );
	}.bind( this ) );
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

ext.bookshelf.ui.data.tree.BookEditorTree.prototype.createItemWidget = function ( item, lvl, isLeaf ) {
	var classname = ext.menueditor.registry.node.registry[ item.type ];
	if ( !classname ) {
		// eslint-disable-next-line no-console
		console.error( 'Node of type ' + item.type + ' is not registered' );
		throw new Error();
	}

	classname = ext.menueditor.util.callbackFromString( classname );
	var maxLevels = this.getMaxLevels();
	// eslint-disable-next-line new-cap
	return new classname( {
		name: this.randomName( item.type ),
		level: lvl,
		tree: this,
		nodeData: item,
		leaf: isLeaf,
		// eslint-disable-next-line max-len
		allowAdditions: this.allowAdditions && ( maxLevels ? lvl + 1 < maxLevels : true ) && classname.static.canHaveChildren,
		allowEdits: this.editable,
		allowDeletions: this.allowDeletions
	} );
};

ext.bookshelf.ui.data.tree.BookEditorTree.prototype.onDragStop = function( item, $target, e, ui ) {
	ext.bookshelf.ui.data.tree.BookEditorTree.parent.prototype.onDragStop.call( this, item, $target, e, ui );
	this.updateNodeNumbers();
};

ext.bookshelf.ui.data.tree.BookEditorTree.prototype.getNodes = function () {
	var nodes = ext.menueditor.ui.data.tree.Tree.parent.prototype.getNodes.call( this );
	return nodes.map( function ( e ) {
		return $.extend( e.getNodeData(), { level: e.getLevel() + 1, name: e.getName() } );
	} );
};

ext.bookshelf.ui.data.tree.BookEditorTree.prototype.updateNodeNumbers = function () {
	var data = this.getNodes();
	var numberings = this.numberProcessor.calculateNumbersFromList( data );

	if ( numberings.length === 0 ) {
		return;
	}

	for ( var i in data ) {
		var name = data[ i ].name;
		var item = this.flat[ name ];
		item.updateNumber( numberings[ i ] );
	};
};
