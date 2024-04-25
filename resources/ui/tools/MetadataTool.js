bs.util.registerNamespace( 'ext.bookshelf.ui.tools' );

ext.bookshelf.ui.tools.MetadataTool = function () {
	ext.bookshelf.ui.tools.MetadataTool.super.apply( this, arguments );
};

OO.inheritClass( ext.bookshelf.ui.tools.MetadataTool, OO.ui.Tool );
ext.bookshelf.ui.tools.MetadataTool.static.name = 'metadata';
ext.bookshelf.ui.tools.MetadataTool.static.icon = 'add';
ext.bookshelf.ui.tools.MetadataTool.static.title = mw.message( 'bs-bookshelf-toolbar-tool-metadata-title' ).text();
ext.bookshelf.ui.tools.MetadataTool.static.label = mw.message( 'bs-bookshelf-toolbar-tool-metadata-label' ).text();
ext.bookshelf.ui.tools.MetadataTool.static.flags = [ ];
ext.bookshelf.ui.tools.MetadataTool.static.displayBothIconAndLabel = true;

ext.bookshelf.ui.tools.MetadataTool.prototype.onSelect = function () {
	this.setActive( false );
	mw.loader.using( 'ext.bookshelf.metadata.dialog' ).done( function () {
		var windowManager = new OO.ui.WindowManager();
		$( document.body ).append( windowManager.$element );

		var dialog = new ext.bookshelf.ui.dialog.MetaDataDialog();
		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog );
		dialog.on( 'metadataset', function ( metadata ) {
			this.toolbar.emit( 'metadataset', metadata );
		}.bind( this ) );
		this.toolbar.emit( 'updateState' );
	}.bind( this ) );
};

ext.bookshelf.ui.tools.MetadataTool.prototype.onUpdateState = function () {};
