/* Kopírovanie čísla účtu do schránky.
   Funguje pre každé tlačidlo s atribútom data-ak-copy. */
(function () {
	'use strict';

	function fallback( text ) {
		var ta = document.createElement( 'textarea' );
		ta.value = text;
		ta.style.cssText = 'position:fixed;opacity:0';
		document.body.appendChild( ta );
		ta.select();
		var ok = false;
		try { ok = document.execCommand( 'copy' ); } catch ( e ) {}
		ta.remove();
		return ok;
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-ak-copy]' ) : null;
		if ( ! btn ) { return; }
		e.preventDefault();

		var hodnota = ( btn.getAttribute( 'data-ak-copy' ) || '' ).trim();
		if ( ! hodnota || hodnota.indexOf( 'IBAN-' ) === 0 || hodnota.indexOf( '{{' ) === 0 ) {
			btn.setAttribute( 'data-ak-stav', 'Doplňte IBAN' );
			setTimeout( function () { btn.removeAttribute( 'data-ak-stav' ); }, 2000 );
			return;
		}

		var hotovo = function () {
			var povodne = btn.textContent;
			btn.textContent = 'Skopírované';
			btn.classList.add( 'is-done' );
			setTimeout( function () {
				btn.textContent = povodne;
				btn.classList.remove( 'is-done' );
			}, 2000 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( hodnota ).then( hotovo, function () {
				if ( fallback( hodnota ) ) { hotovo(); }
			} );
		} else if ( fallback( hodnota ) ) {
			hotovo();
		}
	} );
})();
