bs.util.registerNamespace( 'ext.bookshelf.formelement' );

ext.bookshelf.formelement.BookInputWidgetElement = function () {
	ext.bookshelf.formelement.BookInputWidgetElement.parent.call( this );
};

OO.inheritClass( ext.bookshelf.formelement.BookInputWidgetElement, mw.ext.forms.formElement.InputFormElement );

ext.bookshelf.formelement.BookInputWidgetElement.prototype.getElementConfig = function () {
	// TODO: Custom configs
	const config = ext.bookshelf.formelement.BookInputWidgetElement.parent.prototype.getElementConfigInternal.call( this );
	config.returnProperty = {
		type: 'text',
		name: 'returnProperty',
		label: mw.message( 'bs-bookshelf-formelement-book-input-return-value-label' ).text(),
		widget_data: { // eslint-disable-line camelcase
			tab: 'main'
		}
	};
	return this.returnConfig( config );
};

ext.bookshelf.formelement.BookInputWidgetElement.prototype.getType = function () {
	return 'book';
};

ext.bookshelf.formelement.BookInputWidgetElement.prototype.getWidgets = function () {
	return {
		view: OO.ui.LabelWidget,
		edit: ext.bookshelf.ui.widget.BookInputWidget
	};
};

ext.bookshelf.formelement.BookInputWidgetElement.prototype.getDisplayName = function () {
	return mw.message( 'bs-bookshelf-formelement-book-input' ).text();
};

mw.ext.forms.registry.Type.register( 'book', new ext.bookshelf.formelement.BookInputWidgetElement() );
