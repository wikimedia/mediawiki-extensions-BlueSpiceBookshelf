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
	mw.loader.using( 'ext.bookshelf.metadata.dialog' ).done( () => {
		if ( !this.windowManager ) {
			this.windowManager = new OO.ui.WindowManager( {
				modal: true
			} );
			$( document.body ).append( this.windowManager.$element );
		}
		const data = this.toolbar.data;
		const dialog = new ext.bookshelf.ui.dialog.MetaDataDialog( {
			data: data
		} );
		this.windowManager.addWindows( [ dialog ] );
		this.windowManager.openWindow( dialog );
		dialog.on( 'metadataset', ( metadata ) => {
			this.toolbar.emit( 'metadataset', metadata );
		} );
		this.toolbar.emit( 'updateState' );
	} );
};

ext.bookshelf.ui.tools.MetadataTool.prototype.onUpdateState = function () {};
