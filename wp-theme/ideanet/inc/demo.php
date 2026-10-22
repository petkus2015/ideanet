<?php
/**
 * Ukážkový obsah po aktivácii témy.
 *
 * Aby web nebol po zapnutí témy prázdny, naplnia sa typy obsahu tým, čo
 * bolo na statickej verzii. Všetko sa dá v administrácii prepísať alebo
 * zmazať. Spustí sa to iba raz.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Naplní obsah pri prvej aktivácii témy.
 */
function ideanet_seed() {
	if ( get_option( 'ideanet_seeded' ) ) {
		return;
	}
	update_option( 'ideanet_seeded', wp_get_theme()->get( 'Version' ) );

	ideanet_register_post_types();

	$assets = get_theme_file_uri( 'assets/media' );

	/* ── portfólio ─────────────────────────── */
	$reels = array(
		array( 'Ranná káva', 'Gastro', '0:18', 'Brand film pre mestskú kaviareň — jeden natáčací deň, šesť reels.', "Brand film\nGrading\nZvuk" ),
		array( 'Séria pre klub', 'Fitness', '0:22', 'Dvanásť tréningových reels natočených počas jedného popoludnia.', "Séria\nTitulky\nSlow-mo" ),
		array( 'Detail chuti', 'Produkt', '0:15', 'Makro produktové zábery pre e-shop s pralinkami.', "Makro\nProdukt\nSvetlo" ),
		array( 'Prehliadka bytu', 'Reality', '0:29', 'Plynulá gimbal prehliadka novostavby pre realitnú kanceláriu.', "Gimbal\nTour\nHudba" ),
		array( 'Aftermovie', 'Event', '0:34', 'Zostrih z firemnej konferencie dodaný do 24 hodín.', "Event\nRýchle dodanie" ),
		array( 'Lookbook jeseň', 'Fashion', '0:20', 'Vertikálny lookbook pre lokálnu módnu značku.', "Fashion\nGrading\nMotion" ),
		array( 'Pred a po', 'Beauty', '0:24', 'Premenový formát pre kadernícky salón, kompletne na kľúč.', "Transformácia\nTitulky" ),
		array( 'Ako to funguje', 'Startup', '0:31', 'Vysvetľovacie video k aplikácii — scenár, natáčanie, motion.', "Explainer\nMotion\nScenár" ),
	);

	foreach ( $reels as $i => $r ) {
		$n = str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT );
		ideanet_seed_post(
			'ideanet_reel',
			$r[0],
			'',
			array(
				'poster' => $assets . '/posters/reel-' . $n . '.svg',
				'cat'    => $r[1],
				'dur'    => $r[2],
				'webm'   => $assets . '/videos/reel-' . $n . '.webm',
				'tags'   => $r[4],
			),
			$i,
			$r[3]
		);
	}

	/* ── vizuály ───────────────────────────── */
	$vizualy = array(
		array( 'Identita kaviarne', 'Identita', 'Logo, farby a šablóny pre mestskú kaviareň.' ),
		array( 'Šablóny pre fitness', 'Vizuály', 'Sada príspevkov na tréningy a rozpisy.' ),
		array( 'Rebranding salónu', 'Identita', 'Nové logo a vizuálny rukopis pre kaderníctvo.' ),
		array( 'Vizuál pre kliniku', 'Vizuály', 'Pokojný set pre zubnú ambulanciu.' ),
		array( 'Set pre e-shop', 'Vizuály', 'Grafiky na akcie, novinky a recenzie.' ),
		array( 'Identita podujatia', 'Event', 'Pozvánka, oznámenie a cover pre konferenciu.' ),
	);

	foreach ( $vizualy as $i => $v ) {
		$n = str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT );
		ideanet_seed_post(
			'ideanet_vizual',
			$v[0],
			'',
			array(
				'image' => $assets . '/visuals/vizual-' . $n . '.svg',
				'cat'   => $v[1],
			),
			$i,
			$v[2]
		);
	}

	/* ── referencie ────────────────────────── */
	$ref = array(
		array( 'Kaviareň', 'Prvá séria reels nám za dva týždne zdvihla dosah o 340 %. Konečne nemusíme riešiť, kto to natočí.', 'Marek H.', 'majiteľ kaviarne' ),
		array( 'Agentúra', 'Prišiel, natočil, o tri dni poslal osem hotových videí. Nulová réžia z našej strany.', 'Lucia K.', 'marketing manager' ),
		array( 'E-shop', 'Náš profil konečne vyzerá ako jedna značka. Grading je presne to, čo nám chýbalo.', 'Tomáš B.', 'zakladateľ e-shopu' ),
	);

	foreach ( $ref as $i => $r ) {
		ideanet_seed_post( 'ideanet_referencia', $r[0], $r[1], array( 'autor' => $r[2], 'rola' => $r[3] ), $i );
	}

	/* ── služby ────────────────────────────── */
	$sluzby = array(
		array(
			'Mesačná tvorba obsahu',
			'Poviete mi, čo potrebujete komunikovať. Navrhnem témy, raz mesačne prídem a za pár hodín vznikne obsah na celý mesiac.',
			'Pre firmy',
			"1 – 2 Reels mesačne\nNámety v cene\nFixná mesačná cena",
			'od <b>390 €</b> / mesiac',
			'firmy',
			'',
		),
		array(
			'White-label produkcia',
			'Preberám celú produkčnú časť Reels pre vašich klientov. Vy komunikujete s klientom, ja natáčam a strihám. Fakturujem vám, nie im.',
			'Pre agentúry',
			"Natáčanie + hotové Reels\nDodanie do 24 hodín\nNDA za 30 €",
			'od <b>120 €</b> / produkcia',
			'agentury',
			'1',
		),
		array(
			'Same-day Reel',
			'Konferencia, otvorenie prevádzky alebo launch — hlavné video dostanete ešte v deň eventu, pripravené na IG aj TikTok.',
			'Eventy',
			"Same-day Reel alebo do 24 h\nRezervovaný termín",
			'od <b>350 €</b> / event',
			'eventy',
			'',
		),
	);

	foreach ( $sluzby as $i => $s ) {
		ideanet_seed_post(
			'ideanet_sluzba',
			$s[0],
			$s[1],
			array(
				'tag'      => $s[2],
				'features' => $s[3],
				'price'    => $s[4],
				'target'   => $s[5],
				'best'     => $s[6],
			),
			$i
		);
	}

	/* ── proces ────────────────────────────── */
	$kroky = array(
		array( 'Témy a zadanie', 'Poviete mi, čo potrebujete komunikovať. Vrátim sa s návrhom tém a zoznamom záberov.' ),
		array( 'Natáčací blok', 'Prídem na dohodnutý čas — podľa balíka 1 až 4 hodiny — a natočím materiál na celú sériu.' ),
		array( 'Strih a titulky', 'Strih, color, hudba, titulky. Pripomienky pošlete naraz v jednom kole revízií.' ),
		array( 'Dodanie do 24 hodín', 'Hotové Reels 1080 × 1920 cez cloud, pripravené na publikovanie. Same-day ešte v deň natáčania na požiadanie.' ),
	);

	foreach ( $kroky as $i => $k ) {
		ideanet_seed_post( 'ideanet_krok', $k[0], $k[1], array(), $i );
	}

	/* ── podmienky ─────────────────────────── */
	$podmienky = array(
		array( 'Revízie', '1 kolo v cene, pri PRO a pri grafike dve. Ďalšie úpravy 40 € / h pre firmy, 35 € / h pre agentúry.' ),
		array( 'Dodanie', 'Video do 24 hodín od natáčania. Grafika a podcast majú vlastné termíny — od 3 do 10 pracovných dní podľa rozsahu.' ),
		array( 'Same-day', 'Dodanie ešte v deň natáčania + 50 %. Z 390 € je tak 585 €.' ),
		array( 'Doprava', 'Bratislava, Žilina a Brno sú v cene. Mimo nich 0,40 € / km tam aj späť, alebo podľa dohody.' ),
		array( 'Storno', 'Nad 48 h zdarma, 24 – 48 h 30 %, pod 24 h 50 %, nedostavenie 100 %.' ),
		array( 'Surový materiál', 'Zábery z natáčania nie sú v cene. Na požiadanie od + 80 €.' ),
		array( 'Portfólio a NDA', 'Publikované videá môžem ukázať v portfóliu. NDA rešpektujem bez príplatku.' ),
		array( 'Platba', 'Jednorazovo 50 / 50, pri pravidelnej spolupráci faktúra so splatnosťou 14 dní.' ),
	);

	foreach ( $podmienky as $i => $p ) {
		ideanet_seed_post( 'ideanet_podmienka', $p[0], $p[1], array(), $i );
	}

	ideanet_seed_baliky();
	ideanet_seed_ponuky();
	ideanet_seed_menus();
}
add_action( 'after_switch_theme', 'ideanet_seed' );

/**
 * Ceny a balíky.
 */
function ideanet_seed_baliky() {
	$i = 0;

	/* Firmy — mesačné balíky. */
	$plany = array(
		array( 'START', '390 €', '/ mesiac', 'Vstup do pravidelného obsahu.', "1× natáčanie do 2 hodín\n3 hotové Reels\nNámety a témy\nStrih, color, titulky, hudba\n1 kolo revízií\nDodanie do 24 hodín", '', '' ),
		array( 'GROW', '690 €', '/ mesiac', 'Obsah na celý mesiac z jedného natáčania.', "1× natáčanie do 3 hodín\n6 hotových Reels\nNámety a témy\nStrih, color, titulky, hudba\n1 kolo revízií\nDodanie do 24 hodín", 'Najčastejšia voľba', '1' ),
		array( 'PRO', '990 €', '/ mesiac', 'Pre značky, ktoré publikujú často.', "2× natáčanie do 3 hodín\n10 hotových Reels\nNámety a témy\nStrih, color, titulky, hudba\n2 kolá revízií\nDodanie do 24 hodín\nPrioritné termíny", '', '' ),
	);

	foreach ( $plany as $p ) {
		ideanet_seed_post(
			'ideanet_balik',
			$p[0],
			'',
			array(
				'group'    => 'firmy',
				'layout'   => 'plan',
				'price'    => $p[1],
				'unit'     => $p[2],
				'desc'     => $p[3],
				'features' => $p[4],
				'badge'    => $p[5],
				'best'     => $p[6],
			),
			$i++
		);
	}

	/* Firmy — jednorazovo, podcast, vizuály. */
	$riadky = array(
		array( '1 Reel', '160 €', 'natáčanie do 2 hodín + strih', 'jednorazovo', '', '' ),
		array( '3 Reels', '350 €', 'natáčanie do 4 hodín', 'jednorazovo', '', '' ),
		array( 'Podcast u vás', '400 €', 'natáčanie do 3 hodín · epizóda do 70 minút · zostrih 16:9 · 3 vertikálne výstrižky na Reels · zvuk z mikroportov · dodanie do 3 dní', 'podcast', '1', '' ),
		array( 'Vizuály pre siete', '280 €', 'sada šablón pre Instagram a Facebook · formáty 9:16, 1:1 a 16:9 · citáty, oznamy, akcie · vo vašich farbách a písmach · 2 kolá revízií', 'vizualy', '', '' ),
		array( 'Vizuálna identita', '690 €', 'logo a jeho varianty · farebná paleta a písma · sada šablón pre siete · jednoduchý manuál používania · 2 kolá revízií · dodanie do 10 pracovných dní', 'vizualy', '1', '' ),
	);

	foreach ( $riadky as $r ) {
		ideanet_seed_post(
			'ideanet_balik',
			$r[0],
			'',
			array(
				'group'  => 'firmy',
				'layout' => 'tier',
				'price'  => $r[1],
				'desc'   => $r[2],
				'block'  => $r[3],
				'best'   => $r[4],
				'star'   => $r[5],
			),
			$i++
		);
	}

	/* Agentúry — white-label. */
	$wl = array(
		array( 'Reel Basic', '120 €', 'natáčanie do 1 h + 1 Reel', '', '' ),
		array( 'Content Mini', '250 €', 'natáčanie do 2 h + 3 Reels', '', '' ),
		array( 'Content Day', '390 €', 'natáčanie do 3 h + 5 Reels', '1', 'najžiadanejšie' ),
		array( 'Content Plus', '550 €', 'natáčanie do 4 h + 8 Reels', '', '' ),
		array( 'Same-Day Event', '290 €', 'natáčanie do 3 h + 1 Reel v deň eventu', '', '' ),
		array( 'Event Plus', '450 €', 'natáčanie do 5 h + 1 same-day + 2 ďalšie Reels', '', '' ),
	);

	foreach ( $wl as $w ) {
		ideanet_seed_post(
			'ideanet_balik',
			$w[0],
			'',
			array(
				'group'  => 'agentury',
				'layout' => 'tier',
				'price'  => $w[1],
				'desc'   => $w[2],
				'best'   => $w[3],
				'star'   => $w[4],
			),
			$i++
		);
	}

	/* Eventy. */
	$ev = array(
		array( 'Event Mini', '290 €', '/ event', 'Kratšia akcia, jedno zhrnutie.', "Natáčanie do 2 hodín\n1 hotový Reel\nStrih, titulky, hudba\nDodanie do 24 hodín", '', '' ),
		array( 'Event', '450 €', '/ event', 'Celá akcia, dva výstupy.', "Natáčanie do 4 hodín\n2 hotové Reels\nStrih, titulky, hudba\nDodanie do 24 hodín", '', '' ),
		array( 'Event Same-Day', '590 €', '/ event', 'Hlavné video ešte počas eventu.', "Natáčanie do 4 hodín\n1 hlavný Reel dodaný v deň eventu\n2 následné Reels\nStrih, titulky, hudba\nPripravené na IG aj TikTok", 'Najsilnejší formát', '1' ),
	);

	foreach ( $ev as $e ) {
		ideanet_seed_post(
			'ideanet_balik',
			$e[0],
			'',
			array(
				'group'    => 'eventy',
				'layout'   => 'plan',
				'price'    => $e[1],
				'unit'     => $e[2],
				'desc'     => $e[3],
				'features' => $e[4],
				'badge'    => $e[5],
				'best'     => $e[6],
			),
			$i++
		);
	}

	ideanet_seed_post(
		'ideanet_balik',
		'Grafika na event',
		'',
		array(
			'group'  => 'eventy',
			'layout' => 'tier',
			'block'  => 'grafika',
			'price'  => '180 €',
			'desc'   => 'jeden vizuálny koncept · formáty pre siete 9:16, 1:1 a 16:9 · pozvánka, oznámenie a pripomienka termínu · 2 kolá revízií · dodanie do 3 pracovných dní',
			'best'   => '1',
		),
		$i++
	);
}

/**
 * Dve stránky s celou ponukou na jednej strane — na prečítanie alebo
 * uloženie do PDF. Ceny si berú z rovnakých balíkov ako cenník, takže
 * sa to nemá ako rozísť.
 */
function ideanet_seed_ponuky() {
	$stranky = array(
		'firmy'    => array(
			'Ponuka pre firmy',
			'Mesačná tvorba vertikálneho videa, jednorazová produkcia, podcast a vizuály. Ceny sú bez DPH — nie som platca DPH.',
			'ponuka_firmy_url',
		),
		'agentury' => array(
			'Ponuka pre agentúry',
			'White-label produkcia Reels pre vašich klientov. S klientom komunikujete vy, faktúru dostávate odo mňa.',
			'ponuka_agentury_url',
		),
	);

	foreach ( $stranky as $group => $def ) {
		list( $title, $intro, $mod ) = $def;

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $intro ) . '</p><!-- /wp:paragraph -->',
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			continue;
		}

		update_post_meta( $page_id, '_wp_page_template', 'page-templates/ponuka.php' );
		update_post_meta( $page_id, '_ideanet_ponuka_group', $group );
		set_theme_mod( 'ideanet_' . $mod, get_permalink( $page_id ) );
	}
}

/**
 * Vytvorí menu a priradí ich k umiestneniam.
 */
function ideanet_seed_menus() {
	$sekcie = array(
		'#portfolio'  => 'Portfólio',
		'#sluzby'     => 'Služby',
		'#cennik'     => 'Cenník',
		'#kalkulacka' => 'Kalkulačka',
		'#podmienky'  => 'Podmienky',
		'#kontakt'    => 'Kontakt',
	);

	$locations = get_theme_mod( 'nav_menu_locations', array() );

	$menus = array(
		'primary' => array( 'Hlavné menu', $sekcie ),
		'mobile'  => array( 'Mobilné menu', $sekcie ),
		'tabbar'  => array(
			'Spodná lišta',
			array(
				'#portfolio'  => 'Portfólio',
				'#cennik'     => 'Cenník',
				'#kalkulacka' => 'Kalkulačka',
				'#kontakt'    => 'Kontakt',
			),
		),
		'footer'  => array(
			'Pätička',
			array(
				'#portfolio' => 'Portfólio',
				'#vizualy'   => 'Vizuály',
				'#cennik'    => 'Cenník',
				'#podmienky' => 'Podmienky',
				'#kontakt'   => 'Kontakt',
			),
		),
	);

	foreach ( $menus as $location => $def ) {
		list( $name, $items ) = $def;

		if ( wp_get_nav_menu_object( $name ) ) {
			continue;
		}

		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			continue;
		}

		foreach ( $items as $url => $label ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $label,
					'menu-item-url'    => home_url( '/' ) . $url,
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				)
			);
		}

		$locations[ $location ] = $menu_id;
	}

	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Založí jednu položku obsahu aj s vlastnými poľami.
 *
 * @param string $type    Typ obsahu.
 * @param string $title   Nadpis.
 * @param string $content Obsah.
 * @param array  $meta    Vlastné polia bez predpony.
 * @param int    $order   Poradie.
 * @param string $excerpt Výpis.
 * @return int|WP_Error
 */
function ideanet_seed_post( $type, $title, $content = '', $meta = array(), $order = 0, $excerpt = '' ) {
	$id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'menu_order'   => $order,
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		return $id;
	}

	foreach ( $meta as $key => $value ) {
		if ( '' !== $value ) {
			update_post_meta( $id, '_ideanet_' . $key, $value );
		}
	}

	return $id;
}
