/* Výber loga partnera z knižnice médií a pridávanie riadkov. */
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.ak-vybrat-logo', function ( e ) {
		e.preventDefault();
		var $riadok = $( this ).closest( '.ak-partner-riadok' );

		var ramec = wp.media( {
			title: 'Vyberte logo partnera',
			button: { text: 'Použiť logo' },
			library: { type: 'image' },
			multiple: false
		} );

		ramec.on( 'select', function () {
			var subor = ramec.state().get( 'selection' ).first().toJSON();
			var url = subor.url;
			if ( subor.sizes && subor.sizes.medium ) {
				url = subor.sizes.medium.url;
			}
			$riadok.find( '.ak-logo-id' ).val( subor.id );
			$riadok.find( '.ak-logo-nahlad' ).html(
				$( '<img>' ).attr( 'src', url ).css( { maxWidth: '100px', maxHeight: '44px' } )
			);
			$riadok.find( '.ak-zmazat-logo' ).prop( 'disabled', false );
		} );

		ramec.open();
	} );

	$( document ).on( 'click', '.ak-zmazat-logo', function ( e ) {
		e.preventDefault();
		var $riadok = $( this ).closest( '.ak-partner-riadok' );
		$riadok.find( '.ak-logo-id' ).val( 0 );
		$riadok.find( '.ak-logo-nahlad' ).html( '<span style="color:#787c82">bez loga</span>' );
		$( this ).prop( 'disabled', true );
	} );

	$( '#ak-pridat-partnera' ).on( 'click', function ( e ) {
		e.preventDefault();
		var $telo = $( '#ak-partneri-tabulka tbody' );
		var index = $telo.find( '.ak-partner-riadok' ).length;
		var $novy = $telo.find( '.ak-partner-riadok' ).last().clone();

		$novy.find( 'input' ).each( function () {
			var meno = $( this ).attr( 'name' );
			if ( meno ) {
				$( this ).attr( 'name', meno.replace( /partner\[\d+\]/, 'partner[' + index + ']' ) );
			}
			if ( $( this ).is( ':checkbox' ) ) {
				$( this ).prop( 'checked', false );
			} else if ( $( this ).hasClass( 'ak-logo-id' ) ) {
				$( this ).val( 0 );
			} else {
				$( this ).val( '' );
			}
		} );
		$novy.find( '.ak-logo-nahlad' ).html( '<span style="color:#787c82">bez loga</span>' );
		$novy.find( '.ak-zmazat-logo' ).prop( 'disabled', true );
		$telo.append( $novy );
		$novy.find( 'input[type="text"]' ).trigger( 'focus' );
	} );
} )( jQuery );
