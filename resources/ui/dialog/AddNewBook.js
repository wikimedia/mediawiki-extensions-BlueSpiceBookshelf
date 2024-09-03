bs.util.registerNamespace( 'ext.bookshelf.ui.dialog' );

ext.bookshelf.ui.dialog.AddNewBookDialog = function ( config ) {
	config = config || {};
	ext.bookshelf.ui.dialog.AddNewBookDialog.super.call( this, config );
}
OO.inheritClass( ext.bookshelf.ui.dialog.AddNewBookDialog, OO.ui.ProcessDialog );

ext.bookshelf.ui.dialog.AddNewBookDialog.static.name = 'AddNewBookDialog';
ext.bookshelf.ui.dialog.AddNewBookDialog.static.title = mw.message( 'bs-bookshelf-newbook-dlg-title' ).text();
ext.bookshelf.ui.dialog.AddNewBookDialog.static.size = 'large';
ext.bookshelf.ui.dialog.AddNewBookDialog.static.actions = [
	{
		action: 'save',
		label: mw.message( 'bs-bookshelf-metadata-dlg-action-save-label' ).text(),
		flags: [ 'primary', 'progressive' ]
	},
	{
		label: mw.message( 'cancel' ).text(),
		flags: 'safe'
	}
];

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.getSetupProcess = function( data ) {
	return ext.bookshelf.ui.dialog.AddNewBookDialog.parent.prototype.getSetupProcess.call( this, data )
	.next( function() {
		this.saveAction = this.actions.getSpecial().primary;
		this.saveAction.setDisabled( true );
		this.uploadFile = false;
	}.bind( this ) )
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.initialize = function () {
	ext.bookshelf.ui.dialog.AddNewBookDialog.super.prototype.initialize.apply( this, arguments );
	var bookshelfdata = require( './bookshelfdata.json' );
	var options = [];
	if ( bookshelfdata.length > 0 ) {
		bookshelfdata.forEach( function ( val ) {
			options.push( {
				data: val
			} );
		} );
	}
	this.panel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false
	} );
	this.bookTitleInput = new OO.ui.TextInputWidget( {
		placeholder: mw.message( 'bs-bookshelf-newbook-dlg-input-title-placeholder' ).text(),
		required: true
	} );
	this.bookTitleInput.connect( this, {
		change: function () {
			if ( this.bookTitleInput.getValue().length > 0 ) {
				this.saveAction.setDisabled( false );
				return;
			}
			this.saveAction.setDisabled( true );
		}
	} );
	var bookTitleLayout = new OO.ui.FieldLayout( this.bookTitleInput, {
		label: mw.message( 'bs-bookshelf-newbook-dlg-input-title-label' ).text(),
	} );

	this.subTitleInput = new OO.ui.TextInputWidget( {
		placeholder: mw.message( 'bs-bookshelf-newbook-dlg-input-subtitle-placeholder' ).text(),
	} );
	var subTitleLayout = new OO.ui.FieldLayout( this.subTitleInput, {
		label: mw.message( 'bs-bookshelf-newbook-dlg-input-subtitle-label' ).text()
	} );

	this.bookshelfInput = new OO.ui.ComboBoxInputWidget( {
		options: options,
		$overlay: this.$overlay,
		placeholder: mw.message( 'bs-bookshelf-newbook-dlg-input-bookshelf-placeholder' ).text()
	} );
	var bookshelfLayout = new OO.ui.FieldLayout( this.bookshelfInput, {
		label: mw.message( 'bs-bookshelf-newbook-dlg-input-bookshelf-label' ).text()
	} );

	this.panel.$element.append( bookTitleLayout.$element );
	this.panel.$element.append( subTitleLayout.$element );
	this.panel.$element.append( bookshelfLayout.$element );
	this.addCoverImageLayout();
	mw.hook( 'bs.bookshelf.newbook.initPanel' ).fire( this.panel );
	this.$body.append( this.panel.$element );
	this.updateSize();
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.addCoverImageLayout = function () {
	var imageLayout = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true,
		classes: [ 'bs-bookshelf-new-book-image-panel' ]
	} );
	this.coverImageInput = new OO.ui.SelectFileWidget( {
		showDropTarget: true
	} );
	this.coverImageInput.connect( this, {
		change: function () {
			if ( this.coverImageInput.getFilename() === '' ) {
				this.coverImageFile.setDisabled( false );
			} else {
				this.coverImageFile.setDisabled( true );
			}
		}
	})
	var coverImageLayout = new OO.ui.FieldLayout( this.coverImageInput, {
		label: mw.message( 'bs-bookshelf-newbook-dlg-input-image-label' ).text(),
	} );
	this.coverImageFile = new OOJSPlus.ui.widget.FileSearchWidget( {
		placeholder: mw.message( 'bs-bookshelf-newbook-dlg-input-image-select-placeholder' ).text(),
		extensions: [ 'svg', 'png', 'jpg' ]
	} );
	var coverImageText = new OO.ui.FieldLayout( new OO.ui.LabelWidget( {
		label: mw.message( 'bs-bookshelf-newbook-dlg-cover-image-text' ).text()
	}), {
		label: ' '
	} );
	this.coverImageFile.connect( this, {
		choose: function () {
			this.coverImageInput.setDisabled( true );
		},
		change: function () {
			if ( this.coverImageFile.getValue() === '' ) {
				this.coverImageInput.setDisabled( false );
			}
		}
	} );
	var coverImageFileLayout = new OO.ui.FieldLayout( this.coverImageFile, {
		label: ' '
	} );

	imageLayout.$element.append( coverImageLayout.$element );
	imageLayout.$element.append( coverImageText.$element );
	imageLayout.$element.append( coverImageFileLayout.$element );
	this.panel.$element.append( imageLayout.$element );
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.getActionProcess = function ( action ) {
	var dialog = this;
	if ( action ) {
		dialog.bookData = dialog.getBookData();
		var process = new OO.ui.Process( function () {
			var dfd = new $.Deferred();
			dialog.pushPending();
			var uploadDfd = dialog.uploadImage();
			uploadDfd.done( function () {
				dialog.save().done( function () {
					dfd.resolve();
				}).fail( function ( error ) {
					dfd.reject( [ new OO.ui.Error( error, { recoverable: false } ) ]  );
				} );
			} ).fail( function ( error ) {
				dialog.handleUploadErrors( error, arguments );
				dfd.reject( [ new OO.ui.Error( error, { recoverable: false } ) ] );
			} );
			return dfd.promise();
		} );
		mw.hook( 'bs.bookshelf.newbook.actionprocess' ).fire( process, dialog );
		return process.next( function () {
			dialog.close( { action: 'done' } );
			dialog.emit( 'book_created', dialog.bookTitle );
		} );
	}
	return ext.bookshelf.ui.dialog.AddNewBookDialog.super.prototype.getActionProcess.call( this, action );
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.getBookData = function () {
	var bookdata = {};
	bookdata[ 'title' ] = this.bookTitleInput.getValue();

	var subtitleValue = this.subTitleInput.getValue()
	if ( subtitleValue.length > 0 ) {
		bookdata[ 'subtitle' ] = subtitleValue;
	}
	var bookshelf = this.bookshelfInput.getValue()
	if ( bookshelf.length > 0 ) {
		bookdata[ 'bookshelf' ] = bookshelf;
	}
	var file = this.coverImageInput.getFilename()
	if ( file.length > 0 ) {
		bookdata[ 'bookshelfimage' ] = 'File:' + file;
		this.uploadFile = true;
	}
	var filename = this.coverImageFile.getValue();
	if ( filename.length > 0 && !this.uploadFile ) {
		bookdata.bookshelfimage = filename;
	}

	return bookdata;
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.save = function () {
	var dfd = new $.Deferred();
	this.saveBook().done( function () {
		var saveMetadataDfd = this.saveMetadata();
		saveMetadataDfd.done( function () {
			this.popPending();
			dfd.resolve();
		}.bind( this ) )
		.fail( function () {
			this.popPending();
			dfd.reject( [
				new OO.ui.Error(
					mw.message( 'bs-bookshelf-newbook-dlg-error-metadata-save' ).text(),
					{ recoverable: false }
			) ] );
		}.bind( this ) );
	}.bind( this ) ).fail( function ( error ) {
		this.popPending();
		var errorMsg = mw.message( 'bs-bookshelf-newbook-dlg-error-book-save' ).text();
		if ( error[ 0 ] === 'articleexists' ) {
			errorMsg = mw.message( 'bs-bookshelf-newbook-dlg-error-book-exists' ).text();
		} else {
			console.error( error );
		}
		dfd.reject(
			[ new OO.ui.Error(
				errorMsg,
				{ recoverable: false }
		) ] );
	}.bind( this ) );
	return dfd.promise();
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.saveBook = function () {
	var dfd = new $.Deferred();
	this.bookTitle = 'Book:' + this.bookData.title;
	mw.loader.using( 'mediawiki.api' ).done( function () {
		const mwApi = new mw.Api();
		mwApi.postWithToken( 'csrf', {
			action: 'edit',
			title: this.bookTitle,
			text: '',
			createonly: true,
			summary: mw.message( 'bs-bookshelf-newbook-dlg-book-save-summary' ).text(),
			contentmodel: 'book'
		} ).fail( function () {
			dfd.reject( arguments );
		} )
			.done( function ( resp ) {
				dfd.resolve( arguments );
		} );
	}.bind( this ) );
	return dfd.promise();
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.saveMetadata = function () {
	var dfd = $.Deferred();
	mw.loader.using( [ 'bluespice.bookshelf.api' ] ).done( function () {
		var api = new ext.bookshelf.api.Api();
		api.saveBookMetadata( this.bookTitle, this.bookData ).done( function () {
			dfd.resolve();
		} ).fail( function () {
			dfd.reject();
		} );
	}.bind( this ) );

	return dfd.promise();
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.uploadImage = function () {
	var dfd = $.Deferred();
	if ( !this.bookData.hasOwnProperty( 'bookshelfimage' ) || !this.uploadFile ) {
		dfd.resolve();
		return dfd.promise();
	}
	mw.loader.using( [ 'mediawiki.api' ] ).done( function () {
		var file = this.coverImageInput.getValue();
		const mwApi = new mw.Api();
		var params = {
			filename: file.name,
			format: file.type
		};

		mwApi.upload( file, params ).done( function ( resp ) {
			dfd.resolve();
		} ).fail( function ( error, result ) {
				var warnings = arguments[ 1 ].upload.warnings,
					errorMessage = mw.message( 'bs-bookshelf-newbook-dlg-upload-error-unhandled' ).plain();
				if ( 'exists' in warnings || 'exists-normalized' in warnings ) {
					errorMessage = 'exists';
					if ( 'nochange' in warnings ) {
						errorMessage = 'fileexists-no-change';
					}
				} else if ( 'duplicate' in warnings ) {
					errorMessage = 'duplicate';
				} else if ( 'duplicate-archive' in warnings ) {
					errorMessage = mw.message( 'bs-bookshelf-newbook-dlg-upload-error-duplicate', params.filename ).plain();
				} else if ( 'badfilename' in warnings ) {
					errorMessage = mw.message( 'bs-bookshelf-newbook-dlg-upload-error-badfilename', params.filename ).plain();
				}
				dfd.reject( errorMessage, result );
			} );
	}.bind( this ) );
	return dfd.promise();
};

ext.bookshelf.ui.dialog.AddNewBookDialog.prototype.handleUploadErrors = function ( error, arguments ) {
	if ( error === 'fileexists-no-change' ) {
		this.save();
	}
	if ( error === 'duplicate' ) {
		var origFileName = arguments[ 1 ].upload.warnings.duplicate[ 0 ];
		OO.ui.confirm(
			mw.message( 'bs-bookshelf-newbook-dlg-upload-duplicate-confirm-label' ).text() )
			.done( function ( confirmed ) {
				if ( confirmed ) {
					this.bookData.bookshelfimage = origFileName;
					this.save();
				} else {
					this.popPending();
				}
			}.bind( this ) );
		return;
	}
	if ( error === 'exists' ) {
		OO.ui.prompt(
			mw.message( 'bs-bookshelf-newbook-dlg-upload-title-exists' ).plain(),
			{
				textInput: {
					value: 'Cover-' + this.bookData.title
				}
			} )
			.done( function ( result ) {
				if ( result !== null ) {
					var file = this.coverImageInput.getValue();
					var fileFormat = file.name.substring( this.file.name.indexOf( '.' ) + 1 );
					var newFileName = result + '.' + fileFormat;
					const mwApi = new mw.Api();
					var params = {
						filename: newFileName,
						format: file.type,
						ignorewarnings: true
					};
					mwApi.upload( file, params ).done( function ( resp ) {
						this.save();
						dfd.resolve();
					}.bind( this ) ).fail( function ( error, result ) {
						if ( error === 'fileexists-no-change' || error === 'duplicate' || error === 'exists' ) {
							this.handleUploadErrors( error, result );
						} else {
							this.popPending();
							this.showErrors( [ new OO.ui.Error( error, { recoverable: false } ) ] );
						}
					}.bind( this ) );
				} else {
					this.popPending();
				}
			}.bind( this ) );
		return;
	}
	this.popPending();
	this.showErrors( new OO.ui.Error( [ error, { recoverable: false } ] ) );
};
