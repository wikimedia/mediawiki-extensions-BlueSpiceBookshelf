/**
 * Filters a tree (or any list of elements) by the text typed into an
 * `.container-filter-search` input.
 */
( function () {
	const WRAPPER_SELECTOR = '.mwstake-components-generictaghandler-tag';

	/**
	 * Text of the node itself, without the text of its child nodes.
	 *
	 * @param {jQuery} $element
	 * @return {string}
	 */
	function getOwnText( $element ) {
		const $label = $element.children( 'div' ).find( '.mws-tree-item-label' );
		if ( $label.length ) {
			return $label.text().toLowerCase();
		}
		return $element.text().toLowerCase();
	}

	/**
	 * Remembers the expanded state of all sub trees, so it can be restored
	 * once the filter is cleared again.
	 *
	 * @param {jQuery} $root
	 */
	function storeExpandedState( $root ) {
		$root.find( '.mws-tree-item-children' ).each( function () {
			const $children = $( this );
			if ( $children.data( 'bsFilterExpanded' ) === undefined ) {
				$children.data( 'bsFilterExpanded', this.classList.contains( 'show' ) );
			}
		} );
	}

	/**
	 * @param {jQuery} $root
	 * @param {jQuery} $elementsToFilter
	 */
	function resetFilter( $root, $elementsToFilter ) {
		$elementsToFilter.css( 'display', '' );
		$root.find( '.mws-tree-item-children' ).each( function () {
			const $children = $( this );
			const wasExpanded = $children.data( 'bsFilterExpanded' );
			if ( wasExpanded === undefined ) {
				return;
			}
			$children.toggleClass( 'show', wasExpanded );
		} );
	}

	/**
	 * Hides all elements that neither match themselves nor contain a match, and
	 * expands the sub trees leading to a match, so that matches nested inside a
	 * collapsed sub tree become visible.
	 *
	 * @param {jQuery} $elementsToFilter
	 * @param {string} value Lower cased search term
	 */
	function applyFilter( $elementsToFilter, value ) {
		$( $elementsToFilter.get().reverse() ).each( function () {
			const $element = $( this );
			const hasMatchingChild = $element
				.find( '.mws-tree-item' )
				.filter( function () {
					return $( this ).data( 'bsFilterMatch' ) === true;
				} ).length > 0;
			const matches = getOwnText( $element ).indexOf( value ) !== -1 || hasMatchingChild;

			$element.data( 'bsFilterMatch', matches );
			$element.css( 'display', matches ? '' : 'none' );

			if ( hasMatchingChild ) {
				$element.children( '.mws-tree-item-children' ).addClass( 'show' );
			}
		} );
	}

	$( '.container-filter-search' ).each( function () { // eslint-disable-line no-jquery/no-global-selector
		const $searchField = $( this ),
			$containerEl = $searchField.parent(),
			searchField = OO.ui.infuse( $searchField );

		const $wrapper = $containerEl.closest( WRAPPER_SELECTOR );
		searchField.$root = $wrapper.length ? $wrapper : $( document.body );
		searchField.selector = $containerEl.data( 'selector' );
		if ( !searchField.selector ) {
			return;
		}

		searchField.on( 'change', function ( value ) {
			const normalValue = value.toLowerCase(),
				$elementsToFilter = this.$root.find( this.selector );

			storeExpandedState( this.$root );

			if ( normalValue === '' ) {
				resetFilter( this.$root, $elementsToFilter );
				return;
			}

			applyFilter( $elementsToFilter, normalValue );
		}, [], searchField );
	} );
}() );
