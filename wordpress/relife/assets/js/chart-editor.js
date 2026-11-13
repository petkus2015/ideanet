/*
 * Blok „RELIFE graf“ v editore: riadky grafu sa upravujú priamo v bloku (názov, hodnota posuvníkom, popis, rizikový riadok,
 * presun hore/dole, pridanie a odstránenie riadku). Pod nimi je živý náhľad podľa vzhľadu webu; nastavenia sú v pravom paneli.
 * Údaje sa ukladajú ako text „Názov | hodnota | popis“ (rovnaký tvar ako v shortcode [relife_graf]).
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor || wp.editor;
	var c = wp.components;
	var SSR = wp.serverSideRender;

	function clean( s ) { return String( s == null ? '' : s ).replace( /\|/g, '/' ).replace( /[\r\n]+/g, ' ' ); }

	function parse( text ) {
		var items = [];
		String( text || '' ).split( /\r?\n/ ).forEach( function ( line ) {
			if ( ! line.trim() ) { return; }
			var p = line.split( '|' );
			var label = ( p[ 0 ] || '' ).trim();
			var raw = ( p[ 1 ] || '' ).trim();
			var desc = p.slice( 2 ).join( '|' ).trim();
			var risk = false;
			if ( raw.slice( -1 ) === '!' ) { risk = true; raw = raw.slice( 0, -1 ).trim(); }
			items.push( { label: label, value: raw, desc: desc, risk: risk } );
		} );
		return items;
	}

	function serialize( items ) {
		return items.map( function ( it ) {
			var line = clean( it.label ) + ' | ' + clean( it.value ) + ( it.risk ? '!' : '' );
			if ( it.desc ) { line += ' | ' + clean( it.desc ); }
			return line;
		} ).join( '\n' );
	}

	function num( v ) {
		var m = /-?\d+(?:[.,]\d+)?/.exec( String( v ).replace( /\s/g, '' ) );
		return m ? parseFloat( m[ 0 ].replace( ',', '.' ) ) : 0;
	}

	function Rows( props ) {
		var a = props.attributes;
		var items = parse( a.rows );
		var isScore = a.mode === 'score';
		var max = a.max > 0 ? a.max : 10;

		function commit( next ) { props.setAttributes( { rows: serialize( next ) } ); }
		function change( i, patch ) {
			var next = items.map( function ( it, k ) { return k === i ? Object.assign( {}, it, patch ) : it; } );
			commit( next );
		}
		function move( i, d ) {
			var j = i + d;
			if ( j < 0 || j >= items.length ) { return; }
			var next = items.slice();
			var t = next[ i ]; next[ i ] = next[ j ]; next[ j ] = t;
			commit( next );
		}
		function remove( i ) { commit( items.filter( function ( _, k ) { return k !== i; } ) ); }
		function add() { commit( items.concat( [ { label: 'Nový riadok', value: isScore ? String( Math.round( max / 2 ) ) : '10', desc: '', risk: false } ] ) ); }

		return el( 'div', { className: 'rl-chart-rows' },
			items.map( function ( it, i ) {
				return el( 'div', { className: 'rl-chart-rows__row', key: i },
					el( 'div', { className: 'rl-chart-rows__main' },
						el( c.TextControl, { label: 'Názov', value: it.label, onChange: function ( v ) { change( i, { label: v } ); }, __nextHasNoMarginBottom: true } ),
						isScore
							? el( c.RangeControl, { label: 'Hodnota (' + num( it.value ) + ' / ' + max + ')', value: num( it.value ), min: 0, max: max, step: 0.5, withInputField: true, onChange: function ( v ) { change( i, { value: String( v == null ? 0 : v ) } ); }, __nextHasNoMarginBottom: true } )
							: el( c.TextControl, { label: 'Hodnota (napr. 45 %, 1,2 mld. €)', value: it.value, onChange: function ( v ) { change( i, { value: v } ); }, __nextHasNoMarginBottom: true } ),
						el( c.TextControl, { label: 'Popis (rozbalí sa po klepnutí, nepovinné)', value: it.desc, onChange: function ( v ) { change( i, { desc: v } ); }, __nextHasNoMarginBottom: true } ),
						isScore ? el( c.ToggleControl, { label: 'Rizikový riadok (vyššie číslo = horšie)', checked: it.risk, onChange: function ( v ) { change( i, { risk: v } ); }, __nextHasNoMarginBottom: true } ) : null
					),
					el( 'div', { className: 'rl-chart-rows__tools' },
						el( c.Button, { icon: 'arrow-up-alt2', label: 'Posunúť vyššie', disabled: i === 0, onClick: function () { move( i, -1 ); } } ),
						el( c.Button, { icon: 'arrow-down-alt2', label: 'Posunúť nižšie', disabled: i === items.length - 1, onClick: function () { move( i, 1 ); } } ),
						el( c.Button, { icon: 'trash', label: 'Odstrániť riadok', isDestructive: true, onClick: function () { remove( i ); } } )
					)
				);
			} ),
			el( c.Button, { variant: 'secondary', onClick: add, className: 'rl-chart-rows__add' }, '+ Pridať riadok' )
		);
	}

	wp.blocks.registerBlockType( 'relife/chart', {
		apiVersion: 3,
		title: 'RELIFE graf',
		description: 'Interaktívny graf s pruhmi: hodnotenie (napr. 8,5 / 10) alebo ľubovoľné hodnoty.',
		icon: 'chart-bar',
		category: 'widgets',
		keywords: [ 'graf', 'hodnotenie', 'skóre', 'pruhy', 'chart' ],
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = be.useBlockProps( { className: 'rl-chart-edit' } );
			return el( Fragment, null,
				el( be.InspectorControls, null,
					el( c.PanelBody, { title: 'Nastavenia grafu', initialOpen: true },
						el( c.SelectControl, {
							label: 'Typ grafu', value: a.mode,
							options: [ { label: 'Hodnotenie (x / 10, farby podľa výsledku)', value: 'score' }, { label: 'Hodnoty (percentá, čísla, farby RELIFE)', value: 'values' } ],
							onChange: function ( v ) { set( { mode: v } ); }
						} ),
						el( c.TextControl, { label: 'Nadpis (nepovinné)', value: a.title, onChange: function ( v ) { set( { title: v } ); } } ),
						el( c.TextControl, { label: 'Maximum (0 = automaticky)', type: 'number', value: a.max, onChange: function ( v ) { set( { max: parseFloat( v ) || 0 } ); } } ),
						a.mode === 'score' ? el( c.ToggleControl, { label: 'Zobraziť celkové skóre', checked: a.showTotal, onChange: function ( v ) { set( { showTotal: v } ); } } ) : null,
						a.mode === 'score' ? el( c.TextControl, { label: 'Celkové skóre ručne (prázdne = priemer)', value: a.score, onChange: function ( v ) { set( { score: v } ); } } ) : null,
						el( c.TextareaControl, { label: 'Poznámka pod grafom', value: a.note, onChange: function ( v ) { set( { note: v } ); } } )
					)
				),
				el( 'div', blockProps,
					el( 'div', { className: 'rl-chart-edit__title' }, 'RELIFE graf: upravte riadky nižšie, náhľad sa aktualizuje hneď' ),
					el( Rows, props ),
					el( 'div', { className: 'rl-chart-edit__preview' }, el( SSR, { block: 'relife/chart', attributes: a } ) )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
