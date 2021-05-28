Ext.define( 'BS.Bookshelf.dialog.AddToBook', {
	extend: 'MWExt.Dialog',
	requires: [ 'BS.Bookshelf.form.field.BookCombo', 'BS.action.APICopyPage' ],
	closeAction: 'destroy',
	title: mw.message( 'bs-bookshelf-add-to-book-label' ).plain(),
	height: 200,

	afterInitComponent: function() {
		this.cbBooks = new BS.Bookshelf.form.field.BookCombo({
			fieldLabel: mw.message( 'bs-bookshelf-add-to-book-label-book' ).plain(),
			forceSelection: true,
			maxHeight: 24,
		});

		this.tfAlias = new Ext.form.field.Text({
			fieldLabel: mw.message( 'bs-bookshelf-add-to-book-label-alias' ).plain(),
			maxHeight: 24,
			margin: '10 0'
		});

		this.chbModifyBookshelfTag = new Ext.form.field.Checkbox({
			//fieldLabel: '&nbsp;',
			boxLabel: mw.message( 'bs-bookshelf-add-to-book-label-mod-bstag' ).plain(),
			hidden: true,
			margin: '15 0'
		});

		this.cbBooks.on( 'change', function( sender, value ) {
			var record = sender.getStore().findRecord( 'book_prefixedtext', value );
			if ( record ) {
				var type = record.get( 'book_type' );
				var location = bs.bookshelf.storageLocationRegistry.lookup( type );
				if ( location && location.allowChangingPageTags() ) {
					return this.chbModifyBookshelfTag.setVisible( true );
				}
			}
			this.chbModifyBookshelfTag.setVisible( false );
			this.chbModifyBookshelfTag.setValue( false );
		}.bind( this ) );

		this.items = [
			this.cbBooks,
			this.tfAlias,
			this.chbModifyBookshelfTag
		];

		this.callParent( arguments );
	},

	getData: function () {
		return {
			'prefixedText': this.cbBooks.getValue(),
			'displayText': this.cbBooks.getRawValue()
		};
	},

	setData: function( obj ) {
		var alias = obj.pagename;
		alias = alias.split('/').reverse()[0]; //basename()
		this.tfAlias.setValue( alias.replace( /_/g, ' ' ) );
	},

	onBtnOKClick: function() {
		var record = this.cbBooks.getValue();
		if( !record || record === '' ) {
			bs.util.alert(
				'bs-bui-addtobookdialog-alert-empty',
				{
					textMsg: 'bs-bookshelf-empty-selection'
				}
			);
			return;
		}
		this.doAddToBook();
	},

	doAddToBook: function() {
		this.setLoading( true );
		var me = this,
			selectedBook =  this.getData(),
			alias = this.tfAlias.getValue(),
			modifyBookshelfTag = this.chbModifyBookshelfTag.getValue(),
			messageTitle = mw.message( 'bs-bookshelf-add-to-book-label' ).plain(),
			record = this.cbBooks.getStore().findRecord( 'book_prefixedtext', selectedBook.prefixedText ),
			storage = bs.bookshelf.storageLocationRegistry.lookup( record.get( 'book_type' ) );

		var wikiText = "\n* [[{0}|{1}]]".format(
			mw.config.get( 'wgPageName' ),
			alias
		);

		storage.appendText( record, wikiText, modifyBookshelfTag ).done( function() {
			mw.notify(
				mw.message( 'bs-bookshelf-add-to-book-added', selectedBook.displayText ).parse(),
				{ title: messageTitle }
			);
			me.afterApiCallSuccess();
			if ( modifyBookshelfTag ) {
				mw.notify(
					mw.message( 'bs-bookshelf-add-to-book-mod-bstag' ).plain(),
					{ title: messageTitle }
				);
				window.location.reload();
			}
		} );
	},

	afterApiCallSuccess: function() {
		if ( this.fireEvent( 'ok', this, this.getData() ) ) {
			this.close();
		}
	}
});
