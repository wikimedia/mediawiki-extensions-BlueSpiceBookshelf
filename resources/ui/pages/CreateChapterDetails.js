bs.util.registerNamespace( 'bs.bookshelf.ui.pagess' );

bs.bookshelf.ui.pages.CreateChapterDetails = function ( cfg ) {
	cfg = cfg || {};
	this.pageName = '';
	bs.bookshelf.ui.pages.CreateChapterDetails.parent.call( this, 'chapter', cfg );
};

OO.inheritClass( bs.bookshelf.ui.pages.CreateChapterDetails, OOJSPlus.ui.booklet.DialogBookletPage );

bs.bookshelf.ui.pages.CreateChapterDetails.prototype.getTitle = function () {
	return mw.message( 'bs-bookshelf-create-chapter-book-title' ).text();
};

bs.bookshelf.ui.pages.CreateChapterDetails.prototype.getItems = function () {
	this.chapterNumberLabel = new OO.ui.LabelWidget( {
		label: '',
		classes: [ 'bs-bookshelf-create-chapter-number-label' ]
	} );
	this.chapterNameInput = new OO.ui.TextInputWidget( {
		required: true
	} );

	this.chapterNameField = new OO.ui.FieldLayout( this.chapterNameInput, {
		label: mw.message( 'bs-bookshelf-create-chapter-input-chapter-name' ).text(),
		align: 'top'
	} );

	this.chapterNameInput.connect( this, {
		change: function () {
			this.chapterNameField.setErrors( [] );
			this.chapterNameInput.setValidityFlag( true );
		}
	} );

	const horizontalLayout = new OO.ui.HorizontalLayout( {
		classes: [ 'bs-bookshelf-create-chapter-layout' ],
		items: [
			this.chapterNumberLabel,
			this.chapterNameField
		]
	} );
	return [ horizontalLayout ];
};

bs.bookshelf.ui.pages.CreateChapterDetails.prototype.setData = function ( data ) {
	bs.bookshelf.ui.pages.CreateChapterDetails.parent.prototype.setData.call( this, data );
	if ( data && data.pageName ) {
		this.pageName = data.pageName;
		this.chapterNameInput.setValue( data.pageName );
	}

	const dialog = this.getDialog();
	this.chapterNumberLabel.setLabel( dialog.chapterNumberToCreate );
};

bs.bookshelf.ui.pages.CreateChapterDetails.prototype.getActionKeys = function () {
	return [ 'back', 'create' ];
};

bs.bookshelf.ui.pages.CreateChapterDetails.prototype.onAction = function ( action ) {
	if ( action === 'back' ) {
		return $.Deferred().resolve( {
			action: 'switchPanel',
			page: 'page',
			data: {
				pageName: this.pageName
			}
		} ).promise();
	}

	if ( action !== 'create' ) {
		return bs.bookshelf.ui.pages.CreateChapterDetails.parent.prototype.onAction.call( this, action );
	}

	const dfd = $.Deferred();
	const dialog = this.getDialog();
	const chapterName = this.chapterNameInput.getValue().trim();
	this.chapterNameField.setErrors( [] );
	this.chapterNameInput.setValue( chapterName );

	this.checkValidity( [ this.chapterNameInput ] ).done( () => {
		dialog.createChapter( this.pageName, chapterName ).done( ( response ) => {
			dfd.resolve( {
				action: 'close',
				data: response
			} );
		} ).fail( ( error ) => {
			dfd.reject( error );
		} );
	} ).fail( () => {
		dfd.resolve( {} );
	} );

	return dfd.promise();
};
