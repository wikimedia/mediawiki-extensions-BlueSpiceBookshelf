Ext.define( 'BS.Bookshelf.dialog.AddToBook', {
	extend: 'BS.Window',
	requires: [ 'BS.Bookshelf.form.field.BookCombo', 'BS.action.APICopyPage' ],
	closeAction: 'destroy',
	title: mw.message( 'bs-bookshelf-add-to-book-label' ).plain(),

	afterInitComponent: function() {
		this.cbBooks = new BS.Bookshelf.form.field.BookCombo({
			fieldLabel: mw.message( 'bs-bookshelf-add-to-book-label-book' ).plain(),
			forceSelection: true
		});

		this.tfAlias = new Ext.form.field.Text({
			fieldLabel: mw.message( 'bs-bookshelf-add-to-book-label-alias' ).plain()
		});

		this.chbModifyBookshelfTag = new Ext.form.field.Checkbox({
			//fieldLabel: '&nbsp;',
			boxLabel: mw.message( 'bs-bookshelf-add-to-book-label-mod-bstag' ).plain()
		});

		this.items = [
			this.cbBooks,
			this.tfAlias,
			this.chbModifyBookshelfTag
		];

		this.callParent( arguments );
	},

	setData: function( currentPageName ) {
		var alias = currentPageName;
		alias = alias.split('/').reverse()[0]; //basename()
		this.tfAlias.setValue( alias.replace( /_/g, ' ' ) );

		this.callParent( arguments );
	},

	onBtnOKClick: function() {
		var record = this.cbBooks.getValue();
		if( !record || record === '' ) {
			bs.util.alert(
				'bs-bui-addtobookdialog-alert-empty',
				{
					textMsg: 'bs-bookshelfui-empty-selection'
				}
			);
			return;
		}
		this.doAddToBook();
	},

	doAddToBook: function() {
		this.setLoading( true );
		var me = this;
		var selectedBookPageId =  this.cbBooks.getValue();
		var selectedBookDisplayText =  this.cbBooks.getRawValue();
		var alias = this.tfAlias.getValue();
		var modifyBookshelfTag = this.chbModifyBookshelfTag.getValue();
		var currentPageName = this.currentData;
		var messageTitle = mw.message( 'bs-bookshelf-add-to-book-label' ).plain();

		var wikiText = "\n* [[{0}|{1}]]".format(
			this.currentData,
			alias
		);

		var api = new mw.Api();
		api.postWithToken( 'csrf', {
			action: 'edit',
			pageid: selectedBookPageId,
			appendtext: wikiText
		})
		.done(function( response, xhr ){
			mw.notify(
				mw.message( 'bs-bookshelf-add-to-book-added', selectedBookDisplayText ).parse(),
				{ title: messageTitle }
			);
			if( modifyBookshelfTag ) {
				var copyAction = new BS.action.APICopyPage({
					sourceTitle: currentPageName,
					targetTitle: currentPageName //We just want to modify the content
				});
				copyAction.on( 'beforesaveedit', function( action, edit ) {
					edit.content = edit.content.replace(/<(bs:)?bookshelf.*?(src|book)=\"(.*?)\".*?\/>/gi, function( fullmatch, group ) {
						return '';
					});
					edit.content = '<bs:bookshelf src="' + response.edit.title + '" />' + "\n" + edit.content.trim();
				});
				copyAction.execute().done(function() {
					mw.notify(
						mw.message( 'bs-bookshelf-add-to-book-mod-bstag' ).plain(),
						{ title: messageTitle }
					);
					me.afterApiCallSuccess();
					window.location.reload();
				});
			}
			else {
				me.afterApiCallSuccess();
			}
		});
	},

	afterApiCallSuccess: function() {
		if ( this.fireEvent( 'ok', this, this.getData() ) ) {
			this.close();
		}
	}
});