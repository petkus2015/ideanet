<?php
/**
 * Vzhľad → Prispôsobiť: texty domovskej stránky, lišta pomoci, kontakty, farby.
 *
 * @package MedialnaGramotnostTema
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definícia sekcií a polí. [ id, typ, popis, predvolená hodnota ]
 */
function mgt_customizer_fields() {
	$f = array(
		'mgt_colors'   => array(
			'title'  => 'Farby',
			'fields' => array(
				array( 'color_primary', 'color', 'Hlavná farba (tlačidlá, odkazy)', '#1f4fbf' ),
				array( 'color_marker', 'color', 'Zvýrazňovač varovných znakov', '#ffd23f' ),
				array( 'color_ink', 'color', 'Text a tmavé plochy', '#14213d' ),
			),
		),
		'mgt_alert'    => array(
			'title'  => 'Lišta pomoci',
			'fields' => array(
				array( 'alert_show', 'checkbox', 'Zobraziť lištu nad hlavičkou', '1' ),
				array( 'alert_text', 'text', 'Text', 'Prišli ste o peniaze alebo údaje? <b>Hneď volajte svojej banke</b> a polícii na <b>158</b>.' ),
				array( 'alert_link_text', 'text', 'Text odkazu', 'Čo robiť krok za krokom' ),
				array( 'alert_link', 'text', 'Odkaz (adresa)', '' ),
			),
		),
		'mgt_hero'     => array(
			'title'  => 'Domov: úvod',
			'fields' => array(
				array( 'hero_eyebrow', 'text', 'Nadtitulok', 'Podvody, dezinformácie a ako sa brániť' ),
				array( 'hero_title', 'text', 'Nadpis', 'Neklikni hneď.' ),
				array( 'hero_highlight', 'text', 'Zvýraznená časť nadpisu', 'Over si to.' ),
				array( 'hero_lead', 'textarea', 'Úvodný text', 'Ukážeme vám na typoch správ, ktoré dnes chodia ľuďom na Slovensku, ako vyzerá podvodná SMS, e-mail, reklama na Facebooku či falošný telefonát. A čo robiť, keď sa to stane.' ),
				array( 'hero_btn1_text', 'text', 'Tlačidlo 1 – text', 'Vyskúšať test' ),
				array( 'hero_btn1_link', 'text', 'Tlačidlo 1 – odkaz', '' ),
				array( 'hero_btn2_text', 'text', 'Tlačidlo 2 – text', 'Prišla mi podozrivá správa' ),
				array( 'hero_btn2_link', 'text', 'Tlačidlo 2 – odkaz', '' ),
				array( 'hero_example', 'priklad', 'Ukážka vedľa nadpisu (príklad podvodu)', '' ),
			),
		),
		'mgt_paths'    => array(
			'title'  => 'Domov: s čím pomôžeme',
			'fields' => array(
				array( 'paths_title', 'text', 'Nadpis', 'S čím vám pomôžeme?' ),
			),
		),
		'mgt_rules'    => array(
			'title'  => 'Domov: tri pravidlá',
			'fields' => array(
				array( 'rules_title', 'text', 'Nadpis', 'Tri pravidlá, ktoré platia vždy' ),
				array( 'rules_text', 'textarea', 'Popis', 'Podvodníci menia príbehy každý týždeň. Tieto tri veci sa nemenia pri SMS, e-maile, telefonáte ani na sociálnych sieťach.' ),
			),
		),
		'mgt_warnings' => array(
			'title'  => 'Domov: aktuálne podvody',
			'fields' => array(
				array( 'warnings_title', 'text', 'Nadpis', 'Čo práve chodí ľuďom na Slovensku' ),
				array( 'warnings_text', 'textarea', 'Popis', 'Podľa varovaní SK-CERT, Národnej banky Slovenska a Polície SR. Položky vyberiete v príkladoch podvodov políčkom „Zobraziť v Aktuálne podvody“.' ),
			),
		),
		'mgt_stats'    => array(
			'title'  => 'Domov: čísla',
			'fields' => array(
				array( 'stats_title', 'text', 'Nadpis', 'Prečo na tom záleží' ),
			),
		),
		'mgt_more'     => array(
			'title'  => 'Domov: karty, publikum, test',
			'fields' => array(
				array( 'cards_title', 'text', 'Nadpis rýchlych kariet', 'Nemáte čas? Pochopte to za minútu.' ),
				array( 'cards_count', 'text', 'Počet kariet na domovskej stránke', '3' ),
				array( 'cta_title', 'text', 'Výzva – nadpis', 'Spoznáte podvod? Vyskúšajte si to.' ),
				array( 'cta_text', 'textarea', 'Výzva – text', 'Dvanásť správ, aké chodia do mobilov. Niektoré sú podvod, niektoré nie. Po každej uvidíte, čo ste mohli prehliadnuť.' ),
				array( 'cta_test', 'test', 'Výzva – test', '' ),
			),
		),
		'mgt_footer'   => array(
			'title'  => 'Kontakty a pätička',
			'fields' => array(
				array( 'contacts', 'textarea', 'Kontakty (Názov | číslo | popis, jeden na riadok)', "Polícia SR | 158 | Nahlásenie podvodu\nTiesňová linka | 112 | Pri ohrození\nLinka pomoci obetiam | 0850 111 321 | Nonstop\nLinka detskej istoty | 116 111 | Pre deti a mladých, nonstop" ),
				array( 'footer_text', 'textarea', 'Text v pätičke', 'Vzdelávací web o podvodoch, dezinformáciách a overovaní informácií. Obsah vychádza z verejných zdrojov EÚ a slovenských inštitúcií.' ),
			),
		),
	);

	$paths = array(
		1 => array( 'Prišla mi podozrivá správa', 'Sedem otázok, po ktorých budete vedieť, či ide o podvod.' ),
		2 => array( 'Chcem spoznať podvody', 'Dvanásť najčastejších podvodov, každý s ukážkou.' ),
		3 => array( 'Nerozumiem, čomu veriť', 'Dezinformácie, manipulácia, deepfakes a algoritmy.' ),
		4 => array( 'Už sa mi to stalo', 'Zadali ste kartu alebo poslali peniaze? Konajte hneď.' ),
	);
	foreach ( $paths as $i => $p ) {
		$f['mgt_paths']['fields'][] = array( "path{$i}_title", 'text', "Položka $i – nadpis", $p[0] );
		$f['mgt_paths']['fields'][] = array( "path{$i}_text", 'text', "Položka $i – text", $p[1] );
		$f['mgt_paths']['fields'][] = array( "path{$i}_link", 'text', "Položka $i – odkaz", '' );
	}
	$rules = array(
		1 => array( 'Zastav sa', 'Strach, nádej alebo súrnosť sú signál spomaliť. Banka ani úrad od vás nechce rozhodnutie do desiatich minút.' ),
		2 => array( 'Over inou cestou', 'Nevolajte na číslo zo správy a neklikajte na odkaz. Zavolajte na číslo z karty, zmluvy alebo oficiálneho webu.' ),
		3 => array( 'Kódy nikomu', 'PIN, heslo, kód zo SMS ani údaje z karty nikomu nediktujte. Ani „banke“, ani „polícii“.' ),
	);
	foreach ( $rules as $i => $r ) {
		$f['mgt_rules']['fields'][] = array( "rule{$i}_title", 'text', "Pravidlo $i – nadpis", $r[0] );
		$f['mgt_rules']['fields'][] = array( "rule{$i}_text", 'textarea', "Pravidlo $i – text", $r[1] );
	}
	$stats = array(
		1 => array( '60 %', 'kybernetických útokov v EÚ začína phishingom, teda podvodnou správou.', 'ENISA Threat Landscape 2025' ),
		2 => array( '36 %', 'Európanov sa za posledný týždeň stretlo s dezinformáciami „často“ alebo „veľmi často“.', 'Flash Eurobarometer FL014EP, 2025' ),
		3 => array( 'č. 1', 'Podvody sú podľa Europolu najrýchlejšie rastúcou oblasťou organizovanej kriminality na internete.', 'Europol IOCTA 2025 a 2026' ),
	);
	foreach ( $stats as $i => $s ) {
		$f['mgt_stats']['fields'][] = array( "stat{$i}_num", 'text', "Číslo $i", $s[0] );
		$f['mgt_stats']['fields'][] = array( "stat{$i}_text", 'textarea', "Číslo $i – popis", $s[1] );
		$f['mgt_stats']['fields'][] = array( "stat{$i}_source", 'text', "Číslo $i – zdroj", $s[2] );
	}
	$aud = array(
		1 => array( 'Pre seniorov a ich rodiny', 'Telefonáty od „banky“, „polície“ a „vnúčat“. Čo povedať a kedy zložiť. Rodinné heslo.' ),
		2 => array( 'Pre mladých', 'Vydieranie fotkami, „brigáda“, z ktorej je pranie peňazí, influenceri a algoritmy.' ),
	);
	foreach ( $aud as $i => $a ) {
		$f['mgt_more']['fields'][] = array( "aud{$i}_title", 'text', "Publikum $i – nadpis", $a[0] );
		$f['mgt_more']['fields'][] = array( "aud{$i}_text", 'textarea', "Publikum $i – text", $a[1] );
		$f['mgt_more']['fields'][] = array( "aud{$i}_link", 'text', "Publikum $i – odkaz", '' );
	}
	return $f;
}

/**
 * Hodnota nastavenia (s predvolenou hodnotou z definície).
 */
function mgt_mod( $id ) {
	static $defaults = null;
	if ( null === $defaults ) {
		$defaults = array();
		foreach ( mgt_customizer_fields() as $sec ) {
			foreach ( $sec['fields'] as $fl ) {
				$defaults[ $fl[0] ] = $fl[3];
			}
		}
	}
	return get_theme_mod( 'mg_' . $id, isset( $defaults[ $id ] ) ? $defaults[ $id ] : '' );
}

function mgt_customize_register( $wp ) {
	$wp->add_panel( 'mgt_panel', array( 'title' => 'Mediálna gramotnosť', 'priority' => 30 ) );
	foreach ( mgt_customizer_fields() as $sid => $sec ) {
		$wp->add_section( $sid, array( 'title' => $sec['title'], 'panel' => 'mgt_panel' ) );
		foreach ( $sec['fields'] as $fl ) {
			list( $id, $type, $label, $default ) = $fl;
			$setting = 'mg_' . $id;
			$sanitize = 'mgt_sanitize_text';
			if ( 'color' === $type ) {
				$sanitize = 'sanitize_hex_color';
			} elseif ( 'checkbox' === $type ) {
				$sanitize = 'mgt_sanitize_checkbox';
			} elseif ( in_array( $type, array( 'priklad', 'test' ), true ) ) {
				$sanitize = 'absint';
			}
			$wp->add_setting( $setting, array( 'default' => $default, 'sanitize_callback' => $sanitize, 'transport' => 'refresh' ) );

			if ( 'color' === $type ) {
				$wp->add_control( new WP_Customize_Color_Control( $wp, $setting, array( 'label' => $label, 'section' => $sid ) ) );
				continue;
			}
			$args = array( 'label' => $label, 'section' => $sid, 'type' => $type );
			if ( in_array( $type, array( 'priklad', 'test' ), true ) ) {
				$pt              = 'priklad' === $type ? 'mg_priklad' : 'mg_test';
				$args['type']    = 'select';
				$args['choices'] = array( 0 => '— vyberte —' );
				foreach ( get_posts( array( 'post_type' => $pt, 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) as $p ) {
					$args['choices'][ $p->ID ] = get_the_title( $p );
				}
			}
			$wp->add_control( $setting, $args );
		}
	}
}
add_action( 'customize_register', 'mgt_customize_register' );

function mgt_sanitize_text( $v ) {
	return wp_kses( (string) $v, mgt_inline_tags() );
}
function mgt_sanitize_checkbox( $v ) {
	return $v ? '1' : '';
}

/**
 * Farby z nastavení do CSS premenných.
 */
function mgt_custom_colors() {
	$css = ':root{';
	foreach ( array( 'color_primary' => '--mg-primary', 'color_marker' => '--mg-marker', 'color_ink' => '--mg-ink' ) as $k => $var ) {
		$v = sanitize_hex_color( mgt_mod( $k ) );
		if ( $v ) {
			$css .= $var . ':' . $v . ';';
			if ( '--mg-ink' === $var ) {
				$css .= '--mg-night:' . $v . ';';
			}
		}
	}
	return $css . '}';
}

/**
 * Kontakty z nastavení: [ [názov, číslo, popis], … ].
 */
function mgt_contacts() {
	$out = array();
	foreach ( preg_split( '/\r?\n/', (string) mgt_mod( 'contacts' ) ) as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}
		$p     = array_map( 'trim', explode( '|', $line ) );
		$out[] = array( $p[0], isset( $p[1] ) ? $p[1] : '', isset( $p[2] ) ? $p[2] : '' );
	}
	return $out;
}
