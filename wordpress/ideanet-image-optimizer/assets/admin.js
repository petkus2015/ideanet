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
	function size( b ) { return b > 1048576 ? ( b / 1048576 ).toFixed( 1 ) + ' MB' : Math.round( b / 1024 ) + ' kB'; }
	function post( params ) {
		var p = new URLSearchParams();
		Object.keys( params ).forEach( function ( k ) {
			if ( Array.isArray( params[ k ] ) ) { params[ k ].forEach( function ( v ) { p.append( k + '[]', v ); } ); }
			else { p.append( k, params[ k ] ); }
		} );
		p.append( 'nonce', INO.nonce );
		return fetch( INO.ajax, { method: 'POST', credentials: 'same-origin', body: p } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) { if ( ! res.success ) { throw new Error( res.data || 'Chyba' ); } return res.data; } );
	}

	// Jeden blok: vyhľadať → vybrať → zmazať.
	function list( key, cfg ) {
		var scan = document.getElementById( 'ino-' + key + '-scan' );
		if ( ! scan ) { return; }
		var del = document.getElementById( 'ino-' + key + '-del' );
		var st = document.getElementById( 'ino-' + key + '-st' );
		var table = document.getElementById( 'ino-' + key + '-table' );
		var body = table.querySelector( 'tbody' );
		var all = document.getElementById( 'ino-' + key + '-all' );
		var busy = false, armed = false, total = 0, seen = 0, bytes = 0;

		function boxes() { return Array.prototype.slice.call( body.querySelectorAll( 'input[type=checkbox]' ) ); }
		function updateBtn() {
			var n = boxes().filter( function ( b ) { return b.checked; } ).length;
			del.textContent = 'Zmazať vybrané (' + n + ')';
			del.disabled = ! n || busy;
		}
		function addRow( it ) {
			var tr = document.createElement( 'tr' );
			var c = document.createElement( 'th' ); c.className = 'check-column';
			var cb = document.createElement( 'input' ); cb.type = 'checkbox'; cb.checked = true; cb.value = it.key;
			cb.addEventListener( 'change', updateBtn ); c.appendChild( cb ); tr.appendChild( c );
			var t = document.createElement( 'td' );
			if ( it.thumb ) { var im = document.createElement( 'img' ); im.src = it.thumb; im.width = 60; im.height = 60; im.loading = 'lazy'; im.style.objectFit = 'cover'; t.appendChild( im ); }
			tr.appendChild( t );
			cfg.cells( it ).forEach( function ( x ) { var td = document.createElement( 'td' ); td.textContent = x; tr.appendChild( td ); } );
			tr.dataset.key = it.key;
			body.appendChild( tr );
		}
		function fail( e ) { busy = false; scan.disabled = false; updateBtn(); st.textContent = 'Chyba: ' + e.message; }

		function step( cursor ) {
			post( { action: cfg.scan, cursor: cursor } ).then( function ( d ) {
				d.items.forEach( function ( it ) { addRow( it ); total++; bytes += it.bytes || 0; } );
				seen += d.seen;
				st.textContent = 'Skontrolovaných ' + seen + ', nájdených ' + total + ' (' + size( bytes ) + ')' + ( d.done ? '. Hotovo.' : '…' );
				table.hidden = ! total;
				if ( d.done ) { busy = false; scan.disabled = false; del.hidden = ! total; updateBtn(); return; }
				step( d.cursor );
			} ).catch( fail );
		}
		scan.addEventListener( 'click', function () {
			busy = true; scan.disabled = true; del.hidden = true; body.innerHTML = ''; total = 0; seen = 0; bytes = 0;
			st.textContent = 'Hľadám…'; step( 0 );
		} );
		all.addEventListener( 'change', function () { boxes().forEach( function ( b ) { b.checked = all.checked; } ); updateBtn(); } );

		del.addEventListener( 'click', function () {
			var keys = boxes().filter( function ( b ) { return b.checked; } ).map( function ( b ) { return b.value; } );
			if ( ! keys.length ) { return; }
			if ( ! armed ) { // potvrdenie priamo v stránke
				armed = true;
				del.textContent = 'Kliknite znova: natrvalo zmazať ' + keys.length + ' položiek';
				setTimeout( function () { armed = false; updateBtn(); }, 6000 );
				return;
			}
			armed = false; busy = true; scan.disabled = true; del.disabled = true;
			var done = 0, kept = 0;
			( function next() {
				var chunk = keys.splice( 0, cfg.chunk );
				if ( ! chunk.length ) {
					busy = false; scan.disabled = false; updateBtn();
					st.textContent = 'Zmazaných ' + done + ', ponechaných ' + kept + ' (medzičasom sa použili).';
					return;
				}
				var params = { action: cfg.del }; params[ cfg.param ] = chunk;
				post( params ).then( function ( d ) {
					d.deleted.forEach( function ( k ) { var r = body.querySelector( 'tr[data-key="' + String( k ).replace( /"/g, '\\"' ) + '"]' ); if ( r ) { r.remove(); } done++; } );
					kept += d.kept.length;
					st.textContent = 'Mažem… ' + done;
					next();
				} ).catch( fail );
			}() );
		} );
	}

	list( 'unused', { scan: 'ino_scan', del: 'ino_delete', param: 'ids', chunk: 25,
		cells: function ( it ) { return [ '#' + it.id + ' ' + it.title, it.date, size( it.bytes ) ]; } } );
	list( 'sizes', { scan: 'ino_sizes_scan', del: 'ino_sizes_delete', param: 'files', chunk: 10,
		cells: function ( it ) { return [ it.title, String( it.count ), size( it.bytes ) ]; } } );
	list( 'orphans', { scan: 'ino_orphans_scan', del: 'ino_orphans_delete', param: 'files', chunk: 25,
		cells: function ( it ) { return [ it.title, it.date, size( it.bytes ) ]; } } );
}() );
