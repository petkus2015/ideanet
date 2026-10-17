<?php
/**
 * Zdieľané pomocné funkcie pre všetky IDEANET bloky.
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalizuje hodnotu repeater parametra (typ 'multiple') na pole riadkov.
 * Fusion Builder ju zvyčajne doručí do render() už ako pole, ale pre istotu
 * zvládne aj JSON reťazec alebo prázdnu hodnotu.
 *
 * @param mixed $raw Surová hodnota z $args.
 * @return array<int, array<string, mixed>>
 */
function ideanet_blocks_multiple( $raw ) {
	if ( is_array( $raw ) ) {
		return $raw;
	}
	if ( is_string( $raw ) && '' !== trim( $raw ) ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
		// Niektoré verzie uložia hodnotu s HTML entitami (&quot; namiesto ") —
		// skúsime to ešte raz po dekódovaní entít, kým to vzdáme.
		$decoded = json_decode( html_entity_decode( $raw, ENT_QUOTES ), true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
	}
	return array();
}

/**
 * Vráti riadky repeatera — buď to, čo zadal používateľ v builderi, alebo
 * (ak je prázdne) vstavaný predvolený obsah. Vďaka tomu blok nikdy
 * nevyzerá prázdny, kým si ho používateľ nenaplní vlastným obsahom.
 *
 * @param mixed $raw      Surová hodnota z $args (repeater).
 * @param array $defaults Predvolené riadky, použijú sa len ak je $raw prázdne.
 * @return array<int, array<string, mixed>>
 */
function ideanet_blocks_multiple_or_default( $raw, $defaults ) {
	$rows = ideanet_blocks_multiple( $raw );
	return $rows ? $rows : $defaults;
}

/**
 * Vráti riadky repeatera pre danú sekciu s touto prioritou:
 * 1. obsah uložený cez Nástroje → IDEANET obsah (WP option, nezávisle
 *    od Avada Builderu — pozri includes/admin-content.php),
 * 2. hodnota z Avada Builderu (repeater 'multiple' v $raw),
 * 3. vstavaný predvolený obsah.
 *
 * Krok 1 existuje preto, lebo repeater pole Fusion Builderu („Add Item“)
 * zlyháva na niektorých inštaláciách Avady na úrovni JS buildera — mimo
 * kontroly tohto pluginu. Admin stránka funguje nezávisle od toho.
 *
 * @param string $section_key Kľúč sekcie, pozri ideanet_blocks_content_sections().
 * @param mixed  $raw         Surová hodnota z $args (repeater z buildera).
 * @param array  $defaults    Predvolené riadky (fallback, ak sú prázdne aj 1. aj 2.).
 * @return array<int, array<string, mixed>>
 */
function ideanet_blocks_resolve_items( $section_key, $raw, $defaults ) {
	if ( function_exists( 'ideanet_blocks_content_option_name' ) ) {
		$from_admin = get_option( ideanet_blocks_content_option_name( $section_key ), array() );
		if ( is_array( $from_admin ) && $from_admin ) {
			return $from_admin;
		}
	}
	return ideanet_blocks_multiple_or_default( $raw, $defaults );
}

/**
 * Vygeneruje jemný farebný prechod ako SVG data URI — zástupný "obrázok"
 * pre ukážky, kým používateľ nenahrá vlastné. Bez závislosti na externých
 * súboroch, rovnaký vzhľad pri každom načítaní (seed = deterministický).
 *
 * @param string $seed  Ľubovoľný text, podľa ktorého sa vyberie paleta.
 * @param string $ratio '9/16' alebo '4/5'.
 * @return string data: URI.
 */
function ideanet_blocks_placeholder_image( $seed = '', $ratio = '9/16' ) {
	$palettes = array(
		array( '#2a0f1f', '#0a070c' ),
		array( '#1f2a3a', '#0a070c' ),
		array( '#3a1f14', '#0a070c' ),
		array( '#1a2a28', '#0a070c' ),
		array( '#2a1a3a', '#0a070c' ),
		array( '#1a2438', '#0a070c' ),
		array( '#30221a', '#0a070c' ),
		array( '#241a30', '#0a070c' ),
	);
	$idx          = abs( crc32( (string) $seed ) ) % count( $palettes );
	list( $top, $bot ) = $palettes[ $idx ];
	list( $w, $h )      = '4/5' === $ratio ? array( 480, 600 ) : array( 420, 746 );

	$svg = sprintf(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d">'
		. '<defs><linearGradient id="g" x1="0" y1="0" x2="0.35" y2="1">'
		. '<stop offset="0" stop-color="%3$s"/><stop offset="1" stop-color="%4$s"/>'
		. '</linearGradient></defs>'
		. '<rect width="%1$d" height="%2$d" fill="url(#g)"/>'
		. '<rect width="%1$d" height="%2$d" fill="#ff0056" opacity="0.05"/>'
		. '</svg>',
		$w,
		$h,
		$top,
		$bot
	);
	return 'data:image/svg+xml;base64,' . base64_encode( $svg );
}

/**
 * Predvolené ukážky pre video karusely (Hero aj Portfólio karusel
 * v režime „video“), keď používateľ ešte nič nepridal.
 *
 * @return array<int, array<string, string>>
 */
function ideanet_blocks_default_video_items() {
	$rows = array(
		array( 'category' => 'Gastro', 'title' => 'Ranná káva', 'description' => 'Brand film pre mestskú kaviareň — jeden natáčací deň, šesť reels.', 'meta' => '0:18', 'client' => 'Kaviareň Zrno', 'tags' => 'majiteľka; barista', 'natacal' => 'yes', 'strihal' => 'yes', 'scenar' => 'yes' ),
		array( 'category' => 'Fitness', 'title' => 'Séria pre klub', 'description' => 'Dvanásť tréningových videí natočených počas jedného popoludnia.', 'meta' => '0:22', 'client' => 'Fit klub Nord', 'tags' => 'tréner; členovia klubu', 'natacal' => 'yes', 'strihal' => 'yes', 'scenar' => 'no' ),
		array( 'category' => 'Produkt', 'title' => 'Detail chuti', 'description' => 'Makro produktové zábery pre e-shop s pralinkami.', 'meta' => '0:15', 'client' => 'Pralinky Vera', 'tags' => 'produkt', 'natacal' => 'no', 'strihal' => 'yes', 'scenar' => 'no' ),
		array( 'category' => 'Reality', 'title' => 'Prehliadka bytu', 'description' => 'Plynulá gimbal prehliadka novostavby pre realitnú kanceláriu.', 'meta' => '0:29', 'client' => 'Reality Nord', 'tags' => 'maklér', 'natacal' => 'yes', 'strihal' => 'yes', 'scenar' => 'no' ),
		array( 'category' => 'Event', 'title' => 'Aftermovie', 'description' => 'Zostrih z firemnej konferencie dodaný do 24 hodín.', 'meta' => '0:34', 'client' => 'Konferencia Rast', 'tags' => 'rečníci; účastníci', 'natacal' => 'yes', 'strihal' => 'yes', 'scenar' => 'yes' ),
		array( 'category' => 'Fashion', 'title' => 'Lookbook jeseň', 'description' => 'Vertikálny lookbook pre lokálnu módnu značku.', 'meta' => '0:20', 'client' => 'Štúdio Lumen', 'tags' => 'modelka; stylistka', 'natacal' => 'no', 'strihal' => 'no', 'scenar' => 'yes' ),
	);
	foreach ( $rows as &$row ) {
		$row['poster'] = ideanet_blocks_placeholder_image( $row['title'], '9/16' );
		$row['video']  = '';
	}
	return $rows;
}

/**
 * Predvolené ukážky pre karusel v režime „obrázok“ (grafika).
 *
 * @return array<int, array<string, string>>
 */
function ideanet_blocks_default_graphic_items() {
	$rows = array(
		array( 'category' => 'Identita', 'title' => 'NORDA Studio', 'description' => 'Logo, značkový systém a pravidlá používania.', 'meta' => 'Logo' ),
		array( 'category' => 'Typografia', 'title' => 'Typografický systém', 'description' => 'Výber rezov a hierarchia pre web aj tlač.', 'meta' => 'Brand manuál' ),
		array( 'category' => 'Tlač', 'title' => 'Letná scéna', 'description' => 'Plagátová séria pre mestský festival.', 'meta' => 'Plagát B1' ),
		array( 'category' => 'Obaly', 'title' => 'Roast No. 4', 'description' => 'Obalový dizajn pre pražiareň kávy.', 'meta' => 'Packaging' ),
	);
	foreach ( $rows as &$row ) {
		$row['poster'] = ideanet_blocks_placeholder_image( $row['title'], '4/5' );
		$row['video']  = '';
	}
	return $rows;
}

/**
 * Predvolené karty služieb (Videografia, Grafický dizajn, Správa
 * sociálnych sietí), keď používateľ ešte nenaplnil repeater.
 *
 * @return array<int, array<string, string>>
 */
function ideanet_blocks_default_services() {
	return array(
		array(
			'icon'        => 'video',
			'title'       => 'Videografia',
			'description' => 'Od nápadu po hotový zostrih. Natáčam vertikálny obsah pre siete aj klasické firemné video.',
			'points'      => 'Reels, TikTok, YouTube Shorts; Produktové a imidžové video; Eventy a aftermovie; Strih z vášho materiálu',
			'link_text'   => 'Ukážky videí',
			'link_url'    => '#video',
		),
		array(
			'icon'        => 'design',
			'title'       => 'Grafický dizajn',
			'description' => 'Vizuálna identita, ktorá drží pokope naprieč tlačou aj obrazovkou.',
			'points'      => 'Logo a vizuálna identita; Brand manuál a šablóny; Tlačoviny, obaly, menu; Bannery a kampaňové vizuály',
			'link_text'   => 'Ukážky grafiky',
			'link_url'    => '#grafika',
		),
		array(
			'icon'        => 'megaphone',
			'title'       => 'Správa sociálnych sietí',
			'description' => 'Obsahový plán, príprava príspevkov, publikovanie a vyhodnotenie.',
			'points'      => 'Stratégia a obsahový plán; Copywriting a publikovanie; Komunita a odpovede; Mesačný report výsledkov',
			'link_text'   => 'Ukážky správy',
			'link_url'    => '#social',
		),
	);
}

/**
 * Predvolené kroky procesu.
 *
 * @return array<int, array<string, string>>
 */
function ideanet_blocks_default_steps() {
	return array(
		array( 'number' => '01', 'title' => 'Zoznámenie', 'description' => 'Krátky hovor o tom, čo predávate, komu a čo vám dnes na obsahu nefunguje.' ),
		array( 'number' => '02', 'title' => 'Koncept', 'description' => 'Dostanete návrh formátov, vizuálny smer a rozpočet.' ),
		array( 'number' => '03', 'title' => 'Produkcia', 'description' => 'Natáčanie, strih, grafika — pri eventoch aj priamo na mieste.' ),
		array( 'number' => '04', 'title' => 'Publikovanie', 'description' => 'Export vo všetkých pomeroch a nasadenie na siete ešte v ten istý večer.' ),
	);
}

/**
 * Predvolené cenové balíky.
 *
 * @return array<int, array<string, mixed>>
 */
function ideanet_blocks_default_plans() {
	return array(
		array(
			'title'          => 'Video',
			'price'          => 'od 390 €',
			'price_suffix'   => '/ natáčací deň',
			'description'    => 'Pre eventy a značky, ktoré potrebujú obsah von, kým je téma živá.',
			'features'       => 'Až 8 hotových vertikálnych videí; Scenár, natáčanie, strih; Grading, titulky, hudba',
			'featured'       => 'no',
			'featured_label' => '',
			'button_text'    => 'Mám záujem',
			'button_link'    => '#kontakt',
		),
		array(
			'title'          => 'Kompletná starostlivosť',
			'price'          => 'od 790 €',
			'price_suffix'   => '/ mesiac',
			'description'    => 'Video, grafika aj správa profilov v jednom balíku.',
			'features'       => 'Jeden natáčací deň mesačne; 12 príspevkov vrátane grafiky; Obsahový plán a publikovanie',
			'featured'       => 'yes',
			'featured_label' => 'Najčastejšia voľba',
			'button_text'    => 'Mám záujem',
			'button_link'    => '#kontakt',
		),
		array(
			'title'          => 'Grafika',
			'price'          => 'od 290 €',
			'price_suffix'   => '/ projekt',
			'description'    => 'Keď potrebujete identitu alebo sadu vizuálov bez videa.',
			'features'       => 'Logo a vizuálny štýl; Mini brand manuál; Šablóny pre siete',
			'featured'       => 'no',
			'featured_label' => '',
			'button_text'    => 'Mám záujem',
			'button_link'    => '#kontakt',
		),
	);
}

/**
 * Predvolené formáty školenia.
 *
 * @return array<int, array<string, string>>
 */
function ideanet_blocks_default_formats() {
	return array(
		array( 'name' => 'Poldeň · 4 hodiny', 'price' => 'od 490 €', 'description' => 'Základy natáčania a strihu naživo, max. 8 ľudí, na mieste vo firme.' ),
		array( 'name' => 'Celý deň · 8 hodín', 'price' => 'od 890 €', 'description' => 'Workshop aj praktické natáčanie a strih na reálnom obsahu vašej firmy.' ),
	);
}

/**
 * Predvolené referencie.
 *
 * @return array<int, array<string, string>>
 */
function ideanet_blocks_default_testimonials() {
	return array(
		array( 'quote' => 'Otvárali sme o šiestej a o pol deviatej už boli prvé videá online. Ľudia z toho večera nám písali ešte ten istý večer.', 'name' => 'Lucia H.', 'role' => 'majiteľka kaviarne' ),
		array( 'quote' => 'Zo správy profilov sa stala rutina, ktorú už neriešim. Čísla rastú a všetko vyzerá ako jedna značka.', 'name' => 'Martin K.', 'role' => 'fitness klub' ),
		array( 'quote' => 'Nové logo, obaly aj video k produktu od jedného človeka. Ušetrilo nám to týždne dohadovania.', 'name' => 'Zuzana P.', 'role' => 'e-shop s kávou' ),
	);
}

/**
 * Predvolené položky navigačného menu. Kotvy zodpovedajú predvoleným
 * hodnotám poľa „Kotva pre odkazy“ v jednotlivých blokoch (Služby →
 * sluzby, Portfólio karusel „Videografia“ → video, Cenník → cennik,
 * Referencie → referencie, Kontakt → kontakt).
 *
 * @return array<int, array<string, string>>
 */
function ideanet_blocks_default_nav_items() {
	return array(
		array( 'label' => 'Služby', 'link' => '#sluzby' ),
		array( 'label' => 'Portfólio', 'link' => '#video' ),
		array( 'label' => 'Cenník', 'link' => '#cennik' ),
		array( 'label' => 'Referencie', 'link' => '#referencie' ),
		array( 'label' => 'Kontakt', 'link' => '#kontakt' ),
	);
}

/**
 * Rozdelí zoznam položiek na pole. Hlavný oddeľovač je bodkočiarka (;),
 * pretože Avada Builder pri uložení shortcode atribútov spoľahlivo
 * zachová bodkočiarky, ale skutočné znaky nového riadku v textarea poli
 * sa pri prechode cez builder niekedy stratia/zlúčia do medzery — keby sme
 * sa spoliehali len na \n, položky by sa na reálnej stránke spojili
 * do jednej. Skutočný nový riadok (ak predsa len príde) funguje tiež.
 *
 * @param string $text Text s položkami oddelenými ; alebo novým riadkom.
 * @return array<int, string>
 */
function ideanet_blocks_lines( $text ) {
	if ( '' === trim( (string) $text ) ) {
		return array();
	}
	$lines = preg_split( '/;|\r\n|\r|\n/', (string) $text );
	$lines = array_map( 'trim', $lines );
	return array_values(
		array_filter(
			$lines,
			function ( $l ) {
				return '' !== $l;
			}
		)
	);
}

/**
 * Vráti pole preddefinovaných SVG ikon (rovnaký vizuál ako pôvodný web).
 *
 * @return array<string, string>
 */
function ideanet_blocks_icon_set() {
	return array(
		'video'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="13" height="12" rx="2.5"/><path d="M15 10.5l6-3.5v10l-6-3.5z"/></svg>',
		'design'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="9" cy="9.5" r="1.4"/><circle cx="15" cy="9.5" r="1.4"/><path d="M8.5 15c1.8 1.3 5.2 1.3 7 0"/></svg>',
		'megaphone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l5-6 4 4 3-4 6 7"/><path d="M3 21h18"/><circle cx="7.5" cy="5.5" r="2.5"/></svg>',
		'graduate'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-5 10 5-10 5-10-5z"/><path d="M6 11v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/><path d="M22 9v7"/></svg>',
		'calendar'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
		'star'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.7 5.9 6.3.7-4.7 4.4 1.3 6.3L12 17.6 6.4 20.3l1.3-6.3L3 9.6l6.3-.7z"/></svg>',
	);
}

/**
 * Štítky rolí pri konkrétnej realizácii (čo z toho ste robili vy) —
 * zobrazujú sa ako malé ikonky na dlaždici aj v lightboxe. Nezávisí
 * od toho, či je nahraté reálne video — platí len pre režim „video“.
 *
 * @return array<string, string> Kľúč role => popisok.
 */
function ideanet_blocks_role_labels() {
	return array(
		'natacal' => __( 'Natáčal', 'ideanet-avada-blocks' ),
		'strihal' => __( 'Strihal', 'ideanet-avada-blocks' ),
		'scenar'  => __( 'Scenár', 'ideanet-avada-blocks' ),
	);
}

/**
 * SVG ikona pre danú rolu (rovnaký vizuálny štýl ako ideanet_blocks_icon_set()).
 *
 * @param string $key 'natacal' | 'strihal' | 'scenar'.
 * @return string SVG markup, alebo prázdny reťazec pri neznámom kľúči.
 */
function ideanet_blocks_role_icon_svg( $key ) {
	$icons = array(
		'natacal' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="13" height="12" rx="2.5"/><path d="M15 10.5l6-3.5v10l-6-3.5z"/></svg>',
		'strihal' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="2.5"/><circle cx="6" cy="18" r="2.5"/><path d="M8.5 7.5L20 18M20 6L8.5 16.5"/></svg>',
		'scenar'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/><path d="M9 13h6M9 17h4"/></svg>',
	);
	return $icons[ $key ] ?? '';
}

/**
 * Z položky repeatera (mode = video) vyberie zoznam kľúčov rolí
 * nastavených na „yes“, v pevnom poradí natacal → strihal → scenar.
 *
 * @param array $item Položka s kľúčmi 'natacal'/'strihal'/'scenar' ('yes'/'no').
 * @return array<int, string>
 */
function ideanet_blocks_active_roles( $item ) {
	$active = array();
	foreach ( array_keys( ideanet_blocks_role_labels() ) as $key ) {
		if ( 'yes' === ( $item[ $key ] ?? 'no' ) ) {
			$active[] = $key;
		}
	}
	return $active;
}

/**
 * Vráti pole možností ikon pre 'select' parameter builderu (kľúč => popisok).
 *
 * @return array<string, string>
 */
function ideanet_blocks_icon_choices() {
	return array(
		'video'     => __( 'Kamera / video', 'ideanet-avada-blocks' ),
		'design'    => __( 'Dizajn / paleta', 'ideanet-avada-blocks' ),
		'megaphone' => __( 'Megafón / komunikácia', 'ideanet-avada-blocks' ),
		'graduate'  => __( 'Školenie / absolvent', 'ideanet-avada-blocks' ),
		'calendar'  => __( 'Kalendár', 'ideanet-avada-blocks' ),
		'star'      => __( 'Hviezda', 'ideanet-avada-blocks' ),
		'custom'    => __( 'Vlastné SVG', 'ideanet-avada-blocks' ),
	);
}

/**
 * Vykreslí SVG ikonu podľa kľúča; pri 'custom' použije poskytnutý raw SVG.
 *
 * @param string $key        Kľúč ikony alebo 'custom'.
 * @param string $custom_svg Vlastný SVG markup (len pri 'custom').
 * @return string Bezpečný SVG markup.
 */
function ideanet_blocks_render_icon( $key, $custom_svg = '' ) {
	if ( 'custom' === $key && '' !== trim( $custom_svg ) ) {
		return wp_kses(
			$custom_svg,
			array(
				'svg'      => array(
					'viewbox'         => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'xmlns'           => true,
				),
				'path'     => array(
					'd'    => true,
					'fill' => true,
				),
				'circle'   => array(
					'cx' => true,
					'cy' => true,
					'r'  => true,
				),
				'rect'     => array(
					'x'      => true,
					'y'      => true,
					'width'  => true,
					'height' => true,
					'rx'     => true,
				),
			)
		);
	}
	$icons = ideanet_blocks_icon_set();
	return $icons[ $key ] ?? $icons['video'];
}

/**
 * Vykreslí spoločný nadpisový blok sekcie (kicker + h2 s dôrazom + podnadpis).
 *
 * @param string $kicker    Malý štítok nad nadpisom.
 * @param string $heading   Prvá časť nadpisu.
 * @param string $emphasis  Zvýraznená (kurzívna) časť nadpisu.
 * @param string $sub       Podnadpis / popisný text.
 * @param string $extra_cls Doplnkové CSS triedy pre <header>.
 * @return string HTML.
 */
function ideanet_blocks_head( $kicker, $heading, $emphasis, $sub, $extra_cls = '' ) {
	if ( '' === trim( $heading ) && '' === trim( $kicker ) && '' === trim( $sub ) ) {
		return '';
	}
	ob_start();
	?>
	<header class="ib-head ib-reveal <?php echo esc_attr( $extra_cls ); ?>">
		<?php if ( '' !== trim( $kicker ) ) : ?>
			<p class="ib-kicker"><?php echo esc_html( $kicker ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== trim( $heading ) || '' !== trim( $emphasis ) ) : ?>
			<h2>
				<?php echo esc_html( $heading ); ?>
				<?php if ( '' !== trim( $emphasis ) ) : ?>
					<em><?php echo esc_html( $emphasis ); ?></em>
				<?php endif; ?>
			</h2>
		<?php endif; ?>
		<?php if ( '' !== trim( $sub ) ) : ?>
			<p class="ib-sub"><?php echo wp_kses_post( $sub ); ?></p>
		<?php endif; ?>
	</header>
	<?php
	return ob_get_clean();
}

/**
 * Bezpečne escapuje URL obrázka/videa pre atribút HTML.
 *
 * WordPress `esc_url()` štandardne odstráni `data:` URI (nie je v
 * `wp_allowed_protocols()`) — presne také URI generuje
 * `ideanet_blocks_placeholder_image()` pre defaultné náhľady. Bez tejto
 * výnimky by sa predvolené náhľady na reálnom WordPresse vykresľovali ako
 * rozbitý obrázok (prázdne `src`), aj keď v lokálnom PHP teste (kde
 * `esc_url()` toto správanie nereplikuje) to vyzeralo v poriadku.
 * Hodnota `data:image/...` je vždy vygenerovaná priamo pluginom
 * (`base64_encode()` vlastného SVG), nikdy nepochádza od používateľa —
 * `esc_attr()` je preto dostatočné a bezpečné. Skutočné URL z Knižnice
 * médií (http/https) naďalej prechádzajú cez `esc_url()`.
 *
 * @param string $url Pôvodná hodnota poľa poster/video.
 * @return string Bezpečná hodnota pre atribút.
 */
function ideanet_blocks_esc_media_url( $url ) {
	if ( 0 === strpos( (string) $url, 'data:image/' ) ) {
		return esc_attr( $url );
	}
	return esc_url( $url );
}

/**
 * Vykreslí jednu kartu karuselu (aj hero karusel používa tú istú šablónu).
 *
 * Dlaždica v mriežke vždy zobrazuje len statický náhľadový obrázok (aj
 * pri videu, aj keď je zdrojové video na šírku) — samotné video sa
 * prehráva až v lightboxe po kliknutí, nikdy priamo v mriežke.
 *
 * @param array $item {
 *     @type string $mode     'video' alebo 'image'.
 *     @type string $category Štítok kategórie.
 *     @type string $title    Názov.
 *     @type string $desc     Popis (skrytý v hero variante cez CSS).
 *     @type string $meta     Štítok vpravo hore (dĺžka / formát).
 *     @type string $poster   URL náhľadového obrázka.
 *     @type string $video    URL video súboru (len mode = video).
 *     @type string $client   Pre koho / firma (len mode = video, nepovinné).
 *     @type string $tags     Tagy — kto je na videu, oddelené ; (len mode = video, nepovinné).
 *     @type string $natacal  'yes'/'no' — natáčal (len mode = video, nepovinné).
 *     @type string $strihal  'yes'/'no' — strihal (len mode = video, nepovinné).
 *     @type string $scenar   'yes'/'no' — písal scenár (len mode = video, nepovinné).
 * }
 * @return string HTML.
 */
function ideanet_blocks_render_tile( $item ) {
	$mode   = 'image' === ( $item['mode'] ?? 'video' ) ? 'image' : 'video';
	$title  = $item['title'] ?? '';
	$desc   = $item['desc'] ?? '';
	$cat    = $item['category'] ?? '';
	$meta   = $item['meta'] ?? '';
	$poster = $item['poster'] ?? '';
	$video  = $item['video'] ?? '';
	$client = 'video' === $mode ? ( $item['client'] ?? '' ) : '';
	$tags   = 'video' === $mode ? ( $item['tags'] ?? '' ) : '';
	$roles  = 'video' === $mode ? ideanet_blocks_active_roles( $item ) : array();
	$labels = ideanet_blocks_role_labels();

	ob_start();
	?>
	<article class="ib-slide"
		data-title="<?php echo esc_attr( $title ); ?>"
		data-desc="<?php echo esc_attr( $desc ); ?>"
		data-poster="<?php echo ideanet_blocks_esc_media_url( $poster ); ?>"
		<?php if ( 'video' === $mode && $video ) : ?>
			data-video="<?php echo esc_url( $video ); ?>"
			data-client="<?php echo esc_attr( $client ); ?>"
			data-tags="<?php echo esc_attr( $tags ); ?>"
			data-roles="<?php echo esc_attr( implode( ';', $roles ) ); ?>"
		<?php endif; ?>
		role="option" aria-label="<?php echo esc_attr( $title ); ?>">
		<div class="ib-tile" data-mode="<?php echo esc_attr( $mode ); ?>">
			<?php if ( $poster ) : ?>
				<img src="<?php echo ideanet_blocks_esc_media_url( $poster ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async">
			<?php endif; ?>
			<span class="ib-tile__shade"></span>
			<?php if ( $cat ) : ?><span class="ib-tile__cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
			<?php if ( $meta ) : ?><span class="ib-tile__meta"><?php echo esc_html( $meta ); ?></span><?php endif; ?>
			<?php if ( $roles ) : ?>
				<span class="ib-tile__roles">
					<?php foreach ( $roles as $key ) : ?>
						<span class="ib-role-badge" title="<?php echo esc_attr( $labels[ $key ] ); ?>"><?php echo ideanet_blocks_role_icon_svg( $key ); ?></span>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
			<button class="ib-tile__play" data-act="<?php echo 'video' === $mode ? 'play' : 'open'; ?>"
				aria-label="<?php echo esc_attr( ( 'video' === $mode ? __( 'Prehrať ukážku', 'ideanet-avada-blocks' ) : __( 'Zobraziť', 'ideanet-avada-blocks' ) ) . ' ' . $title ); ?>"></button>
		</div>
		<?php if ( $title || $desc || $client ) : ?>
			<div class="ib-slide__meta">
				<?php if ( $title ) : ?><h4><?php echo esc_html( $title ); ?></h4><?php endif; ?>
				<?php if ( $client ) : ?><span class="ib-slide__client"><?php echo esc_html( $client ); ?></span><?php endif; ?>
				<?php if ( $desc ) : ?><p><?php echo esc_html( $desc ); ?></p><?php endif; ?>
			</div>
		<?php endif; ?>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Zabezpečí, aby boli skripty a štýly bloku vložené na stránku len raz.
 */
function ideanet_blocks_uid( $prefix = 'ib' ) {
	static $count = 0;
	++$count;
	return sanitize_html_class( $prefix ) . '-' . $count;
}

function ideanet_blocks_enqueue_assets() {
	if ( wp_style_is( 'ideanet-blocks', 'enqueued' ) ) {
		return;
	}
	wp_enqueue_style(
		'ideanet-blocks-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'ideanet-blocks',
		IDEANET_BLOCKS_URL . 'assets/css/blocks.css',
		array(),
		IDEANET_BLOCKS_VERSION
	);
	wp_enqueue_script(
		'ideanet-blocks',
		IDEANET_BLOCKS_URL . 'assets/js/blocks.js',
		array(),
		IDEANET_BLOCKS_VERSION,
		true
	);
}
