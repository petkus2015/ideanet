/* Blok „RELIFE graf“ v editore: riadky sa píšu priamo v bloku, náhľad sa vykresľuje podľa webu; nastavenia sú v pravom paneli. */
( function ( wp ) {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor || wp.editor;
	var c = wp.components;
	var SSR = wp.serverSideRender;

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
					el( c.TextareaControl, {
						label: 'Riadky grafu: jeden na riadok, tvar  Názov | hodnota | popis',
						help: 'Hodnota môže byť 9, 9/10, 45 % alebo 1,2 mld. €. Za hodnotu pridajte ! pre rizikový riadok (vyššie číslo = horšie), napr. „Rizikovosť | 7!“. Popis je nepovinný a rozbalí sa klepnutím.',
						rows: 7, value: a.rows,
						onChange: function ( v ) { set( { rows: v } ); }
					} ),
					el( SSR, { block: 'relife/chart', attributes: a } )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
