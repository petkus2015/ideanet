/**
 * RELIFE – interakcie v prehliadači čitateľa.
 * Všetko (hlas v ankete, nálada, uložené články) sa ukladá len lokálne:
 *   localStorage: relife.vote.<id>, relife.mood, relife.saved
 *   Cache API:    relife-saved-v1 (kópia uložených stránok pre offline)
 */
( function () {
	'use strict';

	var data = window.relifeData || {};
	var i18n = data.i18n || {};
	var CACHE = 'relife-saved-v1';

	function lsGet( key, fallback ) {
		try {
			var v = window.localStorage.getItem( key );
			return v === null ? fallback : v;
		} catch ( e ) {
			return fallback;
		}
	}
	function lsSet( key, value ) {
		try {
			window.localStorage.setItem( key, value );
		} catch ( e ) { /* súkromný režim */ }
	}
	/** Odošle udalosť do Google tagu (ak je načítaný). Meno a parametre neobsahujú osobné údaje. */
	function track( name, params ) {
		try {
			if ( typeof window.gtag === 'function' ) { window.gtag( 'event', name, params || {} ); }
		} catch ( e ) { /* sledovanie nesmie ovplyvniť stránku */ }
	}
	function qs( sel, root ) { return ( root || document ).querySelector( sel ); }
	function qsa( sel, root ) { return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) ); }

	/* ---------- Uložené články ---------- */
	function getSaved() {
		try {
			var arr = JSON.parse( lsGet( 'relife.saved', '[]' ) );
			return Array.isArray( arr ) ? arr.map( Number ) : [];
		} catch ( e ) {
			return [];
		}
	}
	function setSaved( list ) {
		lsSet( 'relife.saved', JSON.stringify( list ) );
		updateSavedUI();
	}
	function cacheUrl( url, add ) {
		if ( ! window.caches || ! url ) { return; }
		window.caches.open( CACHE ).then( function ( cache ) {
			return add ? cache.add( url ) : cache.delete( url );
		} ).catch( function () {} );
	}
	function updateSavedUI() {
		var saved = getSaved();
		qsa( '.js-saved-count' ).forEach( function ( el ) { el.textContent = String( saved.length ); } );
		qsa( '.js-save' ).forEach( function ( btn ) {
			var on = saved.indexOf( Number( btn.getAttribute( 'data-id' ) ) ) !== -1;
			btn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			btn.setAttribute( 'aria-label', on ? ( i18n.unsave || 'Odstrániť z uložených' ) : ( i18n.save || 'Uložiť článok' ) );
		} );
	}
	document.addEventListener( 'click', function ( ev ) {
		var btn = ev.target.closest ? ev.target.closest( '.js-save' ) : null;
		if ( ! btn ) { return; }
		ev.preventDefault();
		var id = Number( btn.getAttribute( 'data-id' ) );
		var url = btn.getAttribute( 'data-url' );
		var saved = getSaved();
		var idx = saved.indexOf( id );
		if ( idx === -1 ) {
			saved.push( id );
			cacheUrl( url, true );
			track( 'relife_save_article', { article_id: id } );
		} else {
			saved.splice( idx, 1 );
			cacheUrl( url, false );
		}
		setSaved( saved );
		if ( qs( '.js-saved-list' ) ) { renderSavedPage(); }
	} );

	function esc( s ) {
		var d = document.createElement( 'div' );
		d.textContent = s == null ? '' : String( s );
		return d.innerHTML;
	}
	function renderSavedPage() {
		var list = qs( '.js-saved-list' );
		var empty = qs( '.js-saved-empty' );
		if ( ! list ) { return; }
		var ids = getSaved();
		if ( ! ids.length ) {
			list.innerHTML = '';
			if ( empty ) { empty.hidden = false; }
			return;
		}
		if ( empty ) { empty.hidden = true; }
		var url = data.restUrl + ( data.restUrl.indexOf( '?' ) === -1 ? '?' : '&' ) + 'include=' + ids.join( ',' ) + '&per_page=50&_fields=id,link,title,relife';
		window.fetch( url ).then( function ( r ) { return r.json(); } ).then( function ( posts ) {
			var byId = {};
			( posts || [] ).forEach( function ( p ) { byId[ p.id ] = p; } );
			list.innerHTML = ids.map( function ( id ) {
				var p = byId[ id ];
				if ( ! p ) { return ''; }
				var r = p.relife || {};
				var title = p.title && p.title.rendered ? p.title.rendered : '';
				var media = r.thumbnail ? '<img src="' + esc( r.thumbnail ) + '" alt="" loading="lazy">' : '<span class="rl-card__initial" aria-hidden="true">' + esc( ( r.section || '' ).charAt( 0 ) ) + '</span>';
				return '<div class="rl-card js-card" data-id="' + id + '"><a class="rl-card__link" href="' + esc( p.link ) + '">' +
					'<span class="rl-card__media" style="background:' + esc( r.color || '#3CFFD0' ) + '">' + media + '<span class="rl-tag rl-tag--dark">' + esc( r.section ) + '</span></span>' +
					'<h3 class="rl-card__title">' + title + '</h3>' +
					'<span class="rl-meta">' + esc( ( r.format ? r.format + ' · ' : '' ) + ( r.readTime || 1 ) + ' min' ) + '</span></a>' +
					'<button type="button" class="rl-save js-save" data-id="' + id + '" data-url="' + esc( p.link ) + '" aria-pressed="true" aria-label="' + esc( i18n.unsave ) + '">' +
					'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-6-4-6 4z"/></svg></button></div>';
			} ).join( '' );
			if ( empty && ! list.children.length ) { empty.hidden = false; }
		} ).catch( function () { if ( empty ) { empty.hidden = false; } } );
	}

	/* ---------- Anketa ---------- */
	function initPoll() {
		var poll = qs( '.js-poll' );
		if ( ! poll ) { return; }
		var key = 'relife.vote.' + poll.getAttribute( 'data-poll' );
		var opts = qsa( '.js-opt', poll );
		var note = qs( '.js-poll-note', poll );

		function show( voted, mine ) {
			poll.classList.toggle( 'is-voted', voted );
			opts.forEach( function ( o, i ) {
				var pct = Number( o.getAttribute( 'data-pct' ) ) || 0;
				var on = voted && i === mine;
				o.classList.toggle( 'is-mine', on );
				o.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				qs( '.js-pct', o ).textContent = voted ? pct + ' %' : '';
				qs( '.js-bar', o ).style.width = voted ? pct + '%' : '0%';
			} );
			if ( note ) { note.textContent = voted ? ( i18n.thanks || '' ) : ( i18n.beforeVote || '' ); }
		}

		var stored = lsGet( key, '' );
		var mine = stored === '' ? -1 : parseInt( stored, 10 );
		show( mine >= 0, mine );

		opts.forEach( function ( o, i ) {
			o.addEventListener( 'click', function () {
				if ( poll.classList.contains( 'is-voted' ) ) { return; }
				lsSet( key, String( i ) );
				show( true, i );
				track( 'relife_poll_vote', { poll_id: poll.getAttribute( 'data-poll' ), option_index: i } );
			} );
		} );
	}

	/* ---------- Karta: anketa / nálada ---------- */
	function initSidecard() {
		var card = qs( '.js-sidecard' );
		if ( ! card ) { return; }
		var tabPoll = qs( '.js-tab-poll', card );
		var tabMood = qs( '.js-tab-mood', card );
		var pPoll = qs( '.js-panel-poll', card );
		var pMood = qs( '.js-panel-mood', card );
		function tab( mood ) {
			pPoll.hidden = mood;
			pMood.hidden = ! mood;
			tabPoll.classList.toggle( 'is-active', ! mood );
			tabMood.classList.toggle( 'is-active', mood );
			tabPoll.setAttribute( 'aria-selected', mood ? 'false' : 'true' );
			tabMood.setAttribute( 'aria-selected', mood ? 'true' : 'false' );
		}
		tabPoll.addEventListener( 'click', function () { tab( false ); } );
		tabMood.addEventListener( 'click', function () { tab( true ); } );
	}

	/* ---------- Nálada: filtrovanie článkov na úvodnej stránke ---------- */
	function initMood() {
		var grid = qs( '.js-today' );
		var moodBtns = qsa( '.js-mood' );
		if ( ! grid || ! moodBtns.length ) { return; }
		var limit = Number( grid.getAttribute( 'data-limit' ) ) || 4;
		var titleEl = qs( '.js-today-title' );
		var status = qs( '.js-mood-status' );
		var statusText = qs( '.js-mood-text' );
		var emptyEl = qs( '.js-today-empty' );
		var moods = data.moods || [];

		function apply( id ) {
			var mood = moods.filter( function ( m ) { return m.id === id; } )[ 0 ] || null;
			var shown = 0;
			qsa( '.js-card', grid ).forEach( function ( card ) {
				var secs = ( card.getAttribute( 'data-sections' ) || '' ).split( ' ' );
				var match = ! mood || mood.sections.some( function ( s ) { return secs.indexOf( s ) !== -1; } );
				var visible = match && shown < limit;
				if ( visible ) { shown++; }
				card.classList.toggle( 'is-hidden', ! visible );
			} );
			moodBtns.forEach( function ( b ) { b.setAttribute( 'aria-pressed', mood && b.getAttribute( 'data-mood' ) === mood.id ? 'true' : 'false' ); } );
			if ( titleEl ) { titleEl.textContent = mood ? ( titleEl.getAttribute( 'data-mood' ) || 'Pre tvoju náladu' ) : titleEl.getAttribute( 'data-default' ); }
			if ( status ) {
				status.hidden = ! mood;
				if ( mood && statusText ) { statusText.innerHTML = ( i18n.moodNow || '' ) + ' <b>' + esc( mood.label ) + '</b> · ' + esc( mood.sections.join( ' · ' ).toUpperCase() ); }
			}
			if ( emptyEl ) {
				emptyEl.hidden = shown > 0;
				emptyEl.textContent = shown > 0 ? '' : ( i18n.moodNone || '' );
			}
		}

		var current = lsGet( 'relife.mood', '' ) || null;
		apply( current );
		moodBtns.forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var id = b.getAttribute( 'data-mood' );
				current = current === id ? null : id;
				lsSet( 'relife.mood', current || '' );
				apply( current );
				if ( current ) { track( 'relife_mood_select', { mood: current } ); }
			} );
		} );
		var clear = qs( '.js-mood-clear' );
		if ( clear ) {
			clear.addEventListener( 'click', function () {
				current = null;
				lsSet( 'relife.mood', '' );
				apply( null );
			} );
		}
	}

	/* ---------- Filter starších ankiet ---------- */
	function initPollFilters() {
		var wrap = qs( '.js-poll-filters' );
		if ( ! wrap ) { return; }
		wrap.addEventListener( 'click', function ( ev ) {
			var b = ev.target.closest ? ev.target.closest( '.rl-filter' ) : null;
			if ( ! b ) { return; }
			var f = b.getAttribute( 'data-filter' );
			qsa( '.rl-filter', wrap ).forEach( function ( x ) {
				var on = x === b;
				x.classList.toggle( 'is-active', on );
				x.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			qsa( '.js-pollcard' ).forEach( function ( c ) {
				c.classList.toggle( 'is-hidden', f !== 'all' && c.getAttribute( 'data-section' ) !== f );
			} );
		} );
	}

	/* ---------- Hľadanie, zdieľanie, progres, spodné menu ---------- */
	function initMisc() {
		var panel = qs( '#rl-search' );
		qsa( '.js-search-toggle' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( ! panel ) { return; }
				var mp = qs( '#rl-menu' ), mb = qs( '.js-menu-toggle' );
				if ( mp && ! mp.hidden && mb ) { mb.click(); }
				var open = panel.hidden;
				panel.hidden = ! open;
				qsa( '.js-search-toggle' ).forEach( function ( b ) { b.setAttribute( 'aria-expanded', open ? 'true' : 'false' ); } );
				if ( open ) {
					var input = qs( 'input[type="search"]', panel );
					if ( input ) { input.focus(); window.scrollTo( { top: 0, behavior: 'smooth' } ); }
				}
			} );
		} );

		qsa( '.js-share' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var payload = { title: btn.getAttribute( 'data-title' ), url: btn.getAttribute( 'data-url' ) };
				if ( navigator.share ) {
					navigator.share( payload ).catch( function () {} );
				} else if ( navigator.clipboard ) {
					navigator.clipboard.writeText( payload.url ).then( function () { btn.textContent = 'Odkaz skopírovaný'; } );
				}
			} );
		} );

		var bar = qs( '.js-progress' );
		var art = qs( '.rl-article__main' );
		if ( bar && art ) {
			var onScroll = function () {
				var r = art.getBoundingClientRect();
				var total = r.height - window.innerHeight * 0.5;
				var pct = Math.min( 100, Math.max( 0, ( -r.top + window.innerHeight * 0.3 ) / total * 100 ) );
				bar.style.width = pct + '%';
			};
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			onScroll();
		}

		var cls = document.body.classList;
		qsa( '.js-tab' ).forEach( function ( a ) {
			var m = a.getAttribute( 'data-match' );
			var active = ( 'home' === m && cls.contains( 'home' ) ) || ( 'saved' === m && cls.contains( 'page-template-template-saved' ) );
			a.classList.toggle( 'is-active', active );
		} );
	}

	/* ---------- Rozbaľovacie menu Sekcie (mobil) ---------- */
	function initMenu() {
		var btn = qs( '.js-menu-toggle' );
		var panel = qs( '#rl-menu' );
		if ( ! btn || ! panel ) { return; }
		function setOpen( open ) {
			panel.hidden = ! open;
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			btn.classList.toggle( 'is-active', open );
			btn.setAttribute( 'aria-label', btn.getAttribute( open ? 'data-open-label' : 'data-closed-label' ) || '' );
			document.documentElement.classList.toggle( 'rl-lock', open );
			if ( open ) { panel.scrollTop = 0; }
		}
		btn.addEventListener( 'click', function () { setOpen( panel.hidden ); } );
		panel.addEventListener( 'click', function ( ev ) {
			if ( ev.target.closest && ev.target.closest( 'a' ) ) { setOpen( false ); }
		} );
		document.addEventListener( 'keydown', function ( ev ) {
			if ( ev.key === 'Escape' && ! panel.hidden ) { setOpen( false ); btn.focus(); }
		} );
		window.addEventListener( 'resize', function () {
			if ( window.innerWidth > 760 && ! panel.hidden ) { setOpen( false ); }
		} );
	}

	function init() {
		updateSavedUI();
		initPoll();
		initSidecard();
		initMood();
		initPollFilters();
		initMisc();
		initMenu();
		renderSavedPage();
		document.documentElement.classList.add( 'rl-ready' );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
