/* Kam na jedlo: filtrovanie podľa kategórie a ceny, hľadanie a zoradenie bez opätovného načítania stránky. */
( function () {
	'use strict';
	var root = document.querySelector( '.js-places' );
	if ( ! root ) { return; }
	var grid = root.querySelector( '.js-places-grid' );
	if ( ! grid ) { return; }
	var cards = Array.prototype.slice.call( grid.querySelectorAll( '.js-place' ) );
	var q = root.querySelector( '.js-places-q' );
	var sortSel = root.querySelector( '.js-places-sort' );
	var nEl = root.querySelector( '.js-places-n' );
	var empty = root.querySelector( '.js-places-empty' );
	var state = { cat: '', price: '', q: '', sort: 'rating' };

	function setPressed( btns, attr, val ) {
		btns.forEach( function ( b ) {
			var on = b.getAttribute( attr ) === val;
			b.classList.toggle( 'is-on', on );
			b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
	}

	function apply() {
		var term = state.q.trim().toLowerCase();
		var shown = 0;
		cards.forEach( function ( c ) {
			var cats = ( c.getAttribute( 'data-cats' ) || '' ).split( ' ' );
			var ok = ( ! state.cat || cats.indexOf( state.cat ) !== -1 ) &&
				( ! state.price || c.getAttribute( 'data-price' ) === state.price ) &&
				( ! term || ( c.getAttribute( 'data-name' ) || '' ).indexOf( term ) !== -1 || ( c.textContent || '' ).toLowerCase().indexOf( term ) !== -1 );
			c.hidden = ! ok;
			if ( ok ) { shown++; }
		} );
		var sorted = cards.slice().sort( function ( a, b ) {
			if ( state.sort === 'name' ) { return ( a.getAttribute( 'data-name' ) || '' ).localeCompare( b.getAttribute( 'data-name' ) || '', 'sk' ); }
			return ( parseFloat( b.getAttribute( 'data-rating' ) ) || 0 ) - ( parseFloat( a.getAttribute( 'data-rating' ) ) || 0 );
		} );
		sorted.forEach( function ( c ) { grid.appendChild( c ); } );
		if ( nEl ) { nEl.textContent = String( shown ); }
		if ( empty ) { empty.hidden = shown !== 0; }
	}

	var catBtns = Array.prototype.slice.call( root.querySelectorAll( '.js-places-cat' ) );
	catBtns.forEach( function ( b ) {
		b.addEventListener( 'click', function () { state.cat = b.getAttribute( 'data-cat' ) || ''; setPressed( catBtns, 'data-cat', state.cat ); apply(); } );
	} );
	var priceBtns = Array.prototype.slice.call( root.querySelectorAll( '.js-places-price' ) );
	priceBtns.forEach( function ( b ) {
		b.addEventListener( 'click', function () { state.price = b.getAttribute( 'data-price' ) || ''; setPressed( priceBtns, 'data-price', state.price ); apply(); } );
	} );
	if ( q ) { q.addEventListener( 'input', function () { state.q = q.value; apply(); } ); }
	if ( sortSel ) { sortSel.addEventListener( 'change', function () { state.sort = sortSel.value; apply(); } ); }
	apply();
} )();
