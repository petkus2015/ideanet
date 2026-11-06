( function () {
	var start = document.getElementById( 'ino-start' );
	if ( ! start ) { return; }
	var stop = document.getElementById( 'ino-stop' );
	var status = document.getElementById( 'ino-status' );
	var log = document.getElementById( 'ino-log' );
	var body = log.querySelector( 'tbody' );
	var pending = document.getElementById( 'ino-pending' );
	var running = false;
	var stats = { ok: 0, skipped: 0, error: 0, saved: 0 };

	function size( b ) {
		if ( b > 1048576 ) { return ( b / 1048576 ).toFixed( 1 ) + ' MB'; }
		return Math.round( b / 1024 ) + ' kB';
	}
	function row( it ) {
		var tr = document.createElement( 'tr' );
		var cells = [ '#' + it.id + ' ' + it.title, it.message, it.status === 'ok' ? size( it.before ) + ' → ' + size( it.after ) : '' ];
		cells.forEach( function ( t ) {
			var td = document.createElement( 'td' );
			td.textContent = t;
			tr.appendChild( td );
		} );
		if ( it.status === 'error' ) { tr.style.color = '#b32d2e'; }
		body.appendChild( tr );
	}
	function summary( prefix ) {
		status.textContent = prefix + ' Prevedených: ' + stats.ok + ', preskočených: ' + stats.skipped +
			', chýb: ' + stats.error + ', ušetrené: ' + size( stats.saved );
	}
	function finish( msg ) {
		running = false;
		start.disabled = false;
		stop.hidden = true;
		summary( msg );
	}
	function step( cursor ) {
		if ( ! running ) { return finish( 'Zastavené.' ); }
		var data = new URLSearchParams( { action: 'ino_run', nonce: INO.nonce, cursor: cursor } );
		fetch( INO.ajax, { method: 'POST', credentials: 'same-origin', body: data } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( ! res.success ) { throw new Error( res.data || 'Chyba' ); }
				res.data.items.forEach( function ( it ) {
					row( it );
					stats[ it.status ] = ( stats[ it.status ] || 0 ) + 1;
					if ( it.status === 'ok' ) { stats.saved += Math.max( 0, it.before - it.after ); }
				} );
				summary( 'Spracúvam…' );
				if ( res.data.done ) {
					pending.textContent = '0';
					return finish( 'Hotovo.' );
				}
				step( res.data.cursor );
			} )
			.catch( function ( e ) { finish( 'Chyba: ' + e.message + '.' ); } );
	}

	start.addEventListener( 'click', function () {
		if ( ! window.confirm( 'Spustiť optimalizáciu? Pôvodné súbory sa podľa nastavenia natrvalo zmažú. Máte zálohu?' ) ) { return; }
		running = true;
		stats = { ok: 0, skipped: 0, error: 0, saved: 0 };
		body.innerHTML = '';
		log.hidden = false;
		start.disabled = true;
		stop.hidden = false;
		step( 0 );
	} );
	stop.addEventListener( 'click', function () { running = false; } );
}() );
