( function ( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.bookshelf.ui.dialog' );

	require( './../pages/CreateChapterPage.js' );
	require( './../pages/CreateChapterDetails.js' );

	bs.bookshelf.ui.dialog.CreateChapter = function ( config ) {
		config = config || {};
		config.size = 'medium';
		config.pages = [];
		bs.bookshelf.ui.dialog.CreateChapter.parent.call( this, config );
		this.bookId = config.bookId || mw.config.get( 'bsActiveBookId' );
		this.chapters = config.chapters || [];
		this.chapterNumber = config.chapterNumber || '';
		this.relatedChapterNumbers = [];
		if ( this.chapterNumber !== '' ) {
			let index = this.chapters.findIndex( ( obj ) => obj.chapter_number === this.chapterNumber );
			index += 1;
			for ( let i = index; i < this.chapters.length; i++ ) {
				const info = this.chapters[ i ];
				if ( info.chapter_number.includes( this.chapterNumber + '.' ) ) { // eslint-disable-line es-x/no-array-prototype-includes
					this.relatedChapterNumbers.push( info.chapter_number );
					continue;
				}
				break;
			}
		} else {
			this.relatedChapterNumbers = this.chapters
				.filter( ( chapter ) => /^\d$/.test( chapter.chapter_number ) )
				.map( ( chapter ) => chapter.chapter_number );
		}

		if ( this.relatedChapterNumbers.length === 0 ) {
			if ( this.chapterNumber === '' ) {
				this.relatedChapterNumbers.push( '1' );
			} else {
				this.relatedChapterNumbers.push( this.chapterNumber + '.1' );
			}
		} else {
			const lastElement = this.relatedChapterNumbers[ this.relatedChapterNumbers.length - 1 ];
			const parts = lastElement.split( '.' );
			const lastIndex = parts.length - 1;
			parts[ lastIndex ] = ( parseInt( parts[ lastIndex ] ) + 1 ).toString();
			this.relatedChapterNumbers.push( parts.join( '.' ) );
		}

		this.chapterNumberToCreate = this.relatedChapterNumbers[ this.relatedChapterNumbers.length - 1 ];
		this.api = new mw.Api();
		this.bookApi = null;
		this.pages = [
			new bs.bookshelf.ui.pages.CreateChapterPage( {
				$overlay: this.$overlay
			} ),
			new bs.bookshelf.ui.pages.CreateChapterDetails( {
				$overlay: this.$overlay
			} )
		];
	};

	OO.inheritClass( bs.bookshelf.ui.dialog.CreateChapter, OOJSPlus.ui.dialog.BookletDialog );

	bs.bookshelf.ui.dialog.CreateChapter.prototype.getActionDefinitions = function () {
		const definitions = bs.bookshelf.ui.dialog.CreateChapter.parent.prototype.getActionDefinitions.call( this );
		definitions.next = {
			action: 'next',
			label: mw.msg( 'bs-bookshelf-create-chapter-action-next-label' ),
			flags: [ 'primary', 'progressive' ]
		};
		definitions.back = {
			action: 'back',
			icon: 'previous',
			invisibleLabel: true,
			label: mw.message( 'bs-bookshelf-create-chapter-action-back-label' ).text(),
			flags: [ 'safe' ]
		};

		return definitions;
	};

	bs.bookshelf.ui.dialog.CreateChapter.prototype.makeApi = function () {
		const dfd = $.Deferred();
		mw.loader.using( 'bluespice.bookshelf.api' ).done( () => {
			this.bookApi = new ext.bookshelf.api.Api();
			dfd.resolve();
		} ).fail( () => {
			dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-error' ) );
		} );
		return dfd.promise();
	};

	bs.bookshelf.ui.dialog.CreateChapter.prototype.getSetupProcess = function ( data ) {
		return bs.bookshelf.ui.dialog.CreateChapter.parent.prototype.getSetupProcess.call( this, data ).next( function () {
			return this.makeApi();
		}, this );
	};

	bs.bookshelf.ui.dialog.CreateChapter.prototype.validatePageName = function ( pageName ) {
		const dfd = $.Deferred();
		const title = mw.Title.newFromText( pageName );

		if ( !title ) {
			dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-invalid-page-name' ) );
			return dfd.promise();
		}

		const validatedPageName = title.getPrefixedText();
		this.api.get( {
			action: 'query',
			formatversion: 2,
			titles: validatedPageName
		} ).done( ( response ) => {
			const page = response.query && response.query.pages ? response.query.pages[ 0 ] : null;
			if ( page && page.missing !== true ) {
				dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-page-exists' ) );
				return;
			}
			dfd.resolve( validatedPageName );
		} ).fail( () => {
			dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-error' ) );
		} );

		return dfd.promise();
	};

	bs.bookshelf.ui.dialog.CreateChapter.prototype.getPreviousNumber = function ( chapterNumber ) {
		const parts = chapterNumber.split( '.' );
		const lastIndex = parts.length - 1;
		const previousSegment = parseInt( parts[ lastIndex ] ) - 1;

		if ( previousSegment <= 0 ) {
			if ( parts.length === 1 ) {
				return null;
			}
			parts.pop();
			return parts.join( '.' );
		}

		parts[ lastIndex ] = previousSegment.toString();
		return parts.join( '.' );
	};

	bs.bookshelf.ui.dialog.CreateChapter.prototype.createChapter = function ( pageName, chapterName ) {
		const dfd = $.Deferred();

		if ( !this.bookApi ) {
			dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-error' ) );
			return dfd.promise();
		}

		this.bookApi.getBookInfo( mw.config.get( 'bsActiveBook' ) ).done( ( info ) => {
			info.name = mw.config.get( 'bsActiveBook' );
			this.selectedBook = info;

			const chapterNumber = this.chapterNumberToCreate.toString();
			const level = chapterNumber.split( '.' ).length;
			const prev = this.getPreviousNumber( chapterNumber );
			const data = {
				nodes: [ {
					type: 'bs-bookshelf-chapter-wikilink-with-alias',
					label: chapterName,
					level: level,
					target: pageName
				} ],
				after: prev || null,
				metadata: this.selectedBook.meta
			};

			mw.loader.using( 'ext.menuEditor.api' ).done( () => {
				const api = new ext.menueditor.api.Api();
				const bookTitle = mw.util.rawurlencode( mw.util.rawurlencode( this.selectedBook.name ) );
				api.post( `append/${ bookTitle }`, data ).done( () => {
					dfd.resolve( { action: 'create', page: pageName } );
				} ).fail( () => {
					dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-error' ) );
				} );
			} ).fail( () => {
				dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-error' ) );
			} );
		} ).fail( () => {
			dfd.reject( mw.msg( 'bs-bookshelf-create-chapter-error' ) );
		} );

		return dfd.promise();
	};

}( mediaWiki, jQuery, blueSpice ) );
