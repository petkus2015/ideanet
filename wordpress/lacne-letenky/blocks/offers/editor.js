/* Blok „Lacné letenky“ pre editor blokov. Na stránke ho vykreslí PHP (render_callback),
   v editore ukazujeme len náhľadový štítok s nastaveniami – ceny sa načítajú až na webe. */
( function ( wp ) {
	var el = wp.element.createElement;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var TextControl = wp.components.TextControl;
	var Placeholder = wp.components.Placeholder;

	wp.blocks.registerBlockType( 'lacne-letenky/offers', {
		edit: function ( props ) {
			var a = props.attributes;
			return el( 'div', useBlockProps(),
				el( InspectorControls, null,
					el( PanelBody, { title: 'Nastavenia bloku', initialOpen: true },
						el( TextControl, {
							label: 'Nadpis',
							placeholder: 'bez nadpisu',
							value: a.title,
							onChange: function ( v ) { props.setAttributes( { title: v } ); }
						} ),
						el( RangeControl, {
							label: 'Počet kariet (0 = podľa nastavení pluginu)',
							min: 0, max: 24,
							value: a.limit,
							onChange: function ( v ) { props.setAttributes( { limit: v || 0 } ); }
						} )
					)
				),
				el( Placeholder, {
					icon: 'airplane',
					label: 'Lacné letenky',
					instructions: 'Najlacnejšia spiatočná letenka do Bangkoku, Dubaj, Abu Dhabí a ponuky kamkoľvek z Viedne a Bratislavy. Ceny sa zobrazia na zverejnenej stránke.'
				} )
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
