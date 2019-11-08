Ext.define('BS.Bookshelf.form.field.BookCombo', {
	extend: 'MWExt.form.field.GridPicker',
	requires: [ 'BS.store.BSApi' ],

	triggerAction: 'all',
	typeAhead: true,
	displayField: 'book_displaytext',
	valueField: 'page_id',

	gridConfig: {
		border:true,
		hideHeaders: true,
		features: [{
			ftype: 'grouping',
			groupHeaderTpl: [
				'{name:this.formatName}',
				{
					formatName: function(name) {
						var label = mw.message( 'bs-bookshelf-group-namespace' ).plain();
						if( name === 'user_book' ) {
							label = mw.message( 'bs-bookshelf-group-user' ).plain();
						}
						return label;
					}
				}
			],
			collapsible: false
		}],
		columns: [{
			dataIndex: 'book_displaytext',
			flex: 1
		}]
	},

	initComponent: function(){
		this.store = new BS.store.BSApi({
			apiAction: 'bs-bookshelf-store',
			proxy: {
				extraParams: {
					limit: 9999 //Bad hack to avoid paging
				}
			},
			fields: [
				{ name: 'page_id', type: 'number' },
				'page_title', 'page_namespace',
				'book_first_chapter_prefixedtext', 'book_prefixedtext',
				'book_displaytext', 'book_meta', 'book_type' ],
			groupField: 'book_type'
		});

		this.callParent(arguments);
	}
});