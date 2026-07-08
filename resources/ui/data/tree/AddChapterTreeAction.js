bs.util.registerNamespace( 'ext.bookshelf.ui.data.tree' );

require( './../../dialog/CreateChapter.js' );

ext.bookshelf.ui.data.tree.AddChapterAction = function ( cfg ) {
	cfg = Object.assign( {
		actionName: 'add-chapter',
		icon: 'add',
		label: 'Create subchapter',
		invisibleLabel: true,
		classes: [ 'create-subpage-item' ]
	}, cfg || {} );

	ext.bookshelf.ui.data.tree.AddChapterAction.parent.call( this, cfg );
};

OO.inheritClass( ext.bookshelf.ui.data.tree.AddChapterAction, OOJSPlus.ui.data.NavigationTreeItemAction );

ext.bookshelf.ui.data.tree.AddChapterAction.prototype.getTitle = function () {
	return 'Create subchapter';
};

ext.bookshelf.ui.data.tree.AddChapterAction.prototype.onAction = function ( context ) {
	new mw.Api().get( {
		action: 'bs-book-chapters-store',
		limit: 9999,
		filter: JSON.stringify( [ {
			value: mw.config.get( 'bsActiveBookId' ),
			property: 'chapter_book_id',
			operator: 'eq',
			type: 'string'
		} ] )
	} ).done( ( result ) => {
		const dialog = new bs.bookshelf.ui.dialog.CreateChapter( {
			bookId: mw.config.get( 'bsActiveBookId' ),
			chapters: result.results,
			chapterNumber: context.item.dataset.number
		} );
		if ( !this.windowManager ) {
			this.windowManager = new OO.ui.WindowManager( { modal: true } );
			$( document.body ).append( this.windowManager.$element );
		}

		this.windowManager.addWindows( [ dialog ] );
		this.windowManager.openWindow( dialog ).closed.then( ( data ) => {
			/* eslint-disable-next-line */
			if ( data && data.action === 'create' && data.hasOwnProperty( 'page' ) ) {
				const newChapter = mw.Title.newFromText( data.page );
				window.location.href = newChapter.getUrl();
			}
		} );
	} );
};
