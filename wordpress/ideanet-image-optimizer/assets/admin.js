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

( function () {
	var scan = document.getElementById( 'ino-scan' );
	if ( ! scan ) { return; }
	var del = document.getElementById( 'ino-del' );
	var st = document.getElementById( 'ino-scan-status' );
	var table = document.getElementById( 'ino-unused' );
	var body = table.querySelector( 'tbody' );
	var all = document.getElementById( 'ino-all' );
	var busy = false;

	function size( b ) { return b > 1048576 ? ( b / 1048576 ).toFixed( 1 ) + ' MB' : Math.round( b / 1024 ) + ' kB'; }
	function post( data ) {
		data.nonce = INO.nonce;
		return fetch( INO.ajax, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams( data ) } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) { if ( ! res.success ) { throw new Error( res.data || 'Chyba' ); } return res.data; } );
	}
	function boxes() { return Array.prototype.slice.call( body.querySelectorAll( 'input[type=checkbox]' ) ); }
	function updateBtn() {
		var n = boxes().filter( function ( b ) { return b.checked; } ).length;
		del.textContent = 'Zmazať vybrané (' + n + ')';
		del.disabled = ! n || busy;
	}
	function addRow( it ) {
		var tr = document.createElement( 'tr' );
		tr.dataset.id = it.id;
		var c = document.createElement( 'th' ); c.className = 'check-column';
		var cb = document.createElement( 'input' ); cb.type = 'checkbox'; cb.checked = true; cb.value = it.id;
		cb.addEventListener( 'change', updateBtn ); c.appendChild( cb ); tr.appendChild( c );
		var t = document.createElement( 'td' );
		if ( it.thumb ) { var im = document.createElement( 'img' ); im.src = it.thumb; im.width = 60; im.height = 60; im.style.objectFit = 'cover'; t.appendChild( im ); }
		tr.appendChild( t );
		[ '#' + it.id + ' ' + it.title, it.date, size( it.bytes ) ].forEach( function ( x ) {
			var td = document.createElement( 'td' ); td.textContent = x; tr.appendChild( td );
		} );
		body.appendChild( tr );
	}

	var total = 0, seen = 0;
	function step( cursor ) {
		post( { action: 'ino_scan', cursor: cursor } ).then( function ( d ) {
			d.items.forEach( function ( it ) { addRow( it ); total++; } );
			seen += d.seen;
			st.textContent = 'Skontrolovaných ' + seen + ', nepoužitých ' + total + ( d.done ? '. Hotovo.' : '…' );
			table.hidden = ! total;
			if ( d.done ) { busy = false; scan.disabled = false; del.hidden = ! total; updateBtn(); return; }
			step( d.cursor );
		} ).catch( function ( e ) { busy = false; scan.disabled = false; st.textContent = 'Chyba: ' + e.message; } );
	}
	scan.addEventListener( 'click', function () {
		busy = true; scan.disabled = true; del.hidden = true; body.innerHTML = ''; total = 0; seen = 0;
		st.textContent = 'Hľadám…'; step( 0 );
	} );
	all.addEventListener( 'change', function () { boxes().forEach( function ( b ) { b.checked = all.checked; } ); updateBtn(); } );

	var armed = false;
	del.addEventListener( 'click', function () {
		var ids = boxes().filter( function ( b ) { return b.checked; } ).map( function ( b ) { return b.value; } );
		if ( ! ids.length ) { return; }
		// potvrdenie priamo v stránke (confirm() nemusí byť dostupné)
		if ( ! armed ) {
			armed = true;
			del.textContent = 'Kliknite znova: natrvalo zmazať ' + ids.length + ' obrázkov';
			setTimeout( function () { armed = false; updateBtn(); }, 6000 );
			return;
		}
		armed = false; busy = true; scan.disabled = true; del.disabled = true;
		var done = 0, kept = 0;
		( function next() {
			var chunk = ids.splice( 0, 25 );
			if ( ! chunk.length ) {
				busy = false; scan.disabled = false; updateBtn();
				st.textContent = 'Zmazaných ' + done + ', ponechaných ' + kept + ' (medzičasom sa použili).';
				return;
			}
			var p = new URLSearchParams(); p.append( 'action', 'ino_delete' ); p.append( 'nonce', INO.nonce );
			chunk.forEach( function ( i ) { p.append( 'ids[]', i ); } );
			fetch( INO.ajax, { method: 'POST', credentials: 'same-origin', body: p } ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
				if ( ! res.success ) { throw new Error( res.data ); }
				res.data.deleted.forEach( function ( id ) { var r = body.querySelector( 'tr[data-id="' + id + '"]' ); if ( r ) { r.remove(); } done++; } );
				kept += res.data.kept.length;
				st.textContent = 'Mažem… ' + done;
				next();
			} ).catch( function ( e ) { busy = false; scan.disabled = false; st.textContent = 'Chyba: ' + e.message; } );
		}() );
	} );
}() );
