( function ( mw, $, bs ) {

	bs.util.registerNamespace( 'bs.bookshelf.object' );

	bs.bookshelf.object.BookPDFDroplet = function( cfg ) {
		bs.bookshelf.object.BookPDFDroplet.parent.call( this, cfg );
	};

	OO.inheritClass( bs.bookshelf.object.BookPDFDroplet, ext.contentdroplets.object.TransclusionDroplet );

	bs.bookshelf.object.BookPDFDroplet.prototype.templateMatches = function( templateData ) {
		if ( !templateData ) {
			return false;
		}
		var target = templateData.target.wt;
		return target.trim( '\n' ) === 'BookPDFLink';
	};

	bs.bookshelf.object.BookPDFDroplet.prototype.toDataElement = function( domElements, converter  ) {
		return false;
	};

	bs.bookshelf.object.BookPDFDroplet.prototype.getFormItems = function() {
		var config = require( './config.json' );
		var templates = [];

		for ( var entry in config.templates ) {
			var item = {
				data: encodeURI( config.templates[ entry ] ),
				label: config.templates[ entry ]
			};
			templates.push( item );
		}
		var templateSelectDisabled = false;
		if ( templates.length === 1 ) {
			templateSelectDisabled = true;
		}

		return [
			{
				name: 'book',
				label: mw.message( 'bs-bookshelf-droplet-bookpdf-book-label' ).plain(),
				type: 'title',
				namespace: 1504,
			},
			{
				name: 'template',
				label: mw.message( 'bs-bookshelf-droplet-bookpdf-template-label' ).plain(),
				type: 'dropdown',
				default: config.default,
				options: templates,
				disabled: templateSelectDisabled
			},
			{
				name: 'label',
				label: mw.message( 'bs-bookshelf-droplet-bookpdf-link-label' ).plain(),
				type: 'text'
			}
		];
	};

	ext.contentdroplets.object.TransclusionDroplet.prototype.updateMWData =
	function ( newData, mwData ) {
		newData = newData || {};

		// eslint-disable-next-line no-prototype-builtins
		let template = ( mwData.hasOwnProperty( 'parts' ) && mwData.parts.length > 0 &&
			// eslint-disable-next-line no-prototype-builtins
			mwData.parts[ 0 ].hasOwnProperty( 'template' ) ) ? mwData.parts[ 0 ].template : null,
			key;
		if ( !template ) {
			return mwData;
		}
		for ( key in template.params ) {
			// eslint-disable-next-line no-prototype-builtins
			if ( !template.params.hasOwnProperty( key ) ) {
				continue;
			}
			if ( key === 'book' ) {
				if ( typeof template.params[ key ] === 'string' && template.params[ key ].length > 0 ) {
					let bookTitle = mw.Title.newFromText( template.params[ key ], bs.ns.NS_BOOK );
					if ( bookTitle ) {
						template.params[ key ] = bookTitle.getPrefixedText();
					}
				}
				if ( typeof newData[ key ] === 'string' && newData[ key].length > 0 ) {
					let bookTitle = mw.Title.newFromText( newData[ key ], bs.ns.NS_BOOK );
					if ( bookTitle ) {
						newData[ key ] = bookTitle.getPrefixedText();
					}
				}
			}
			if ( typeof template.params[ key ] === 'string' ) {
				template.params[ key ] = { wt: template.params[ key ] };
			}
			// necessary for checkboxes and templates with yes and no
			if ( typeof template.params[ key ] === 'boolean' ) {
				if ( template.params[ key ] === true ) {
					template.params[ key ] = 'yes';
				} else {
					template.params[ key ] = 'no';
				}
				template.params[ key ] = { wt: template.params[ key ].toString() };
			}

			// eslint-disable-next-line no-prototype-builtins
			if ( newData.hasOwnProperty( key ) ) {
				template.params[ key ] = { wt: newData[ key ] };
			}
		}

		mwData.parts[ 0 ].template = template;
		return mwData;
	};

	ext.contentdroplets.registry.register( 'bookpdf', bs.bookshelf.object.BookPDFDroplet );

} )( mediaWiki, jQuery, blueSpice );
