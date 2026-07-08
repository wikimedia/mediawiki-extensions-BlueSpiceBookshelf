bs.util.registerNamespace( 'bs.bookshelf.ui.pages' );

bs.bookshelf.ui.pages.CreateChapterPage = function ( cfg ) {
	cfg = cfg || {};
	bs.bookshelf.ui.pages.CreateChapterPage.parent.call( this, 'page', cfg );
	this.$overlay = cfg.$overlay || null;
};

OO.inheritClass( bs.bookshelf.ui.pages.CreateChapterPage, OOJSPlus.ui.booklet.DialogBookletPage );

bs.bookshelf.ui.pages.CreateChapterPage.prototype.getTitle = function () {
	return mw.message( 'bs-bookshelf-create-chapter-book-title' ).text();
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.getItems = function () {
	const dialog = this.getDialog();
	const overlay = this.$overlay || ( dialog && dialog.$overlay ) || $( document.body );
	this.labelWidget = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-bookshelf-create-chapter-page-intro-label' ).text()
	} );
	this.pageNameInput = new OOJSPlus.ui.widget.TitleInputWidget( {
		$overlay: overlay,
		mustExist: true,
		contentPagesOnly: false
	} );
	this.pageNameInput.validationOverride = true;

	this.pageNameField = new OO.ui.FieldLayout( this.pageNameInput, {
		label: mw.message( 'bs-bookshelf-create-chapter-page-name-input-label' ).text(),
		help: mw.message( 'bs-bookshelf-create-chapter-page-name-input-help' ).text(),
		$overlay: overlay,
		align: 'top'
	} );

	this.pageNameInput.connect( this, {
		change: 'onTitleChange'
	} );

	return [ this.labelWidget, this.pageNameField ];
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.setData = function ( data ) {
	bs.bookshelf.ui.pages.CreateChapterPage.parent.prototype.setData.call( this, data );
	if ( data && data.pageName ) {
		this.pageNameInput.setValue( data.pageName );
		this.clearError();
		this.setNextAbility( true );
		return;
	}
	this.setNextAbility( false );
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.getActionKeys = function () {
	return [ 'cancel', 'next' ];
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.getAbilities = function () {
	return { cancel: true, next: false };
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.onTitleChange = function ( value ) {
	this.pageNameInput.validationOverride = true;
	if ( this.typeTimeout ) {
		clearTimeout( this.typeTimeout );
	}
	this.typeTimeout = setTimeout( () => {
		this.validateTitleNotExist( value );
	}, 500 );
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.setError = function ( error ) {
	this.pageNameInput.setValidityFlag( false );
	this.pageNameField.setWarnings( [] );
	this.pageNameField.setErrors( [ error ] );
	this.updateDialogSize();
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.clearError = function () {
	this.pageNameField.setWarnings( [] );
	this.pageNameField.setErrors( [] );
	this.pageNameInput.setValidityFlag( true );
	this.updateDialogSize();
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.setExistWarning = function () {
	this.pageNameInput.setValidityFlag( false );
	this.pageNameField.setErrors( [] );
	this.pageNameField.setWarnings( [ mw.msg( 'bs-bookshelf-create-chapter-page-exists' ) ] );
	this.updateDialogSize();
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.setNextAbility = function ( enabled ) {
	const dialog = this.getDialog();
	if ( dialog && dialog.actions ) {
		dialog.actions.setAbilities( { next: enabled } );
	}
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.validateTitleNotExist = function ( value ) {
	const dfd = $.Deferred();
	this.clearError();

	if ( !value ) {
		this.setNextAbility( false );
		dfd.reject( '' );
		return dfd.promise();
	}

	new mw.Api().get( {
		action: 'query',
		prop: 'pageprops',
		titles: value
	} ).done( ( data ) => {
		if ( data.query && data.query.pages && data.query.pages[ -1 ] ) {
			if ( data.query.pages[ -1 ].hasOwnProperty( 'invalid' ) ) {
				this.setNextAbility( false );
				this.setError( data.query.pages[ -1 ].invalidreason );
				dfd.reject( data.query.pages[ -1 ].invalidreason );
			} else {
				this.clearError();
				this.setNextAbility( true );
				dfd.resolve( value );
			}
		} else {
			this.setNextAbility( false );
			this.setExistWarning();
			dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-page-exists' ) );
		}
	} ).fail( () => {
		this.clearError();
		this.setNextAbility( true );
		dfd.resolve( value );
	} );

	return dfd.promise();
};

bs.bookshelf.ui.pages.CreateChapterPage.prototype.onAction = function ( action ) {
	if ( action !== 'next' ) {
		return bs.bookshelf.ui.pages.CreateChapterPage.parent.prototype.onAction.call( this, action );
	}

	const dfd = $.Deferred();
	const pageName = this.pageNameInput.getValue().trim();
	this.pageNameField.setErrors( [] );
	this.pageNameField.setWarnings( [] );
	this.pageNameInput.setValue( pageName );

	this.checkValidity( [ this.pageNameInput ] ).done( () => {
		this.validateTitleNotExist( pageName ).done( ( validatedPageName ) => {
			dfd.resolve( {
				action: 'switchPanel',
				page: 'chapter',
				data: { pageName: validatedPageName }
			} );
		} ).fail( ( error ) => {
			if ( error ) {
				if ( error === mw.msg( 'bs-bookshelf-create-chapter-page-exists' ) ) {
					this.setExistWarning();
				} else {
					this.setError( error );
				}
			}
			dfd.resolve( {} );
		} );
	} ).fail( () => {
		dfd.resolve( {} );
	} );

	return dfd.promise();
};
