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

	/* ── portfólio ───────────────────────────
	   Dĺžka sa tu nevypĺňa zámerne: prehliadač si ju prečíta z videa. */
	$reels = array(
		array( 'Ranná káva', 'Gastro', 'Brand film pre mestskú kaviareň — jeden natáčací deň, šesť reels.', "Brand film\nGrading\nZvuk" ),
		array( 'Séria pre klub', 'Fitness', 'Dvanásť tréningových reels natočených počas jedného popoludnia.', "Séria\nTitulky\nSlow-mo" ),
		array( 'Detail chuti', 'Produkt', 'Makro produktové zábery pre e-shop s pralinkami.', "Makro\nProdukt\nSvetlo" ),
		array( 'Prehliadka bytu', 'Reality', 'Plynulá gimbal prehliadka novostavby pre realitnú kanceláriu.', "Gimbal\nTour\nHudba" ),
		array( 'Aftermovie', 'Event', 'Zostrih z firemnej konferencie dodaný do 24 hodín.', "Event\nRýchle dodanie" ),
		array( 'Lookbook jeseň', 'Fashion', 'Vertikálny lookbook pre lokálnu módnu značku.', "Fashion\nGrading\nMotion" ),
		array( 'Pred a po', 'Beauty', 'Premenový formát pre kadernícky salón, kompletne na kľúč.', "Transformácia\nTitulky" ),
		array( 'Ako to funguje', 'Startup', 'Vysvetľovacie video k aplikácii — scenár, natáčanie, motion.', "Explainer\nMotion\nScenár" ),
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
				'webm'   => $assets . '/videos/reel-' . $n . '.webm',
				'tags'   => $r[3],
			),
			$i,
			$r[2]
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
		array( 'Same-day', 'Dodanie ešte v deň natáčania + 30 %. Z 390 € je tak 507 €.' ),
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
	ideanet_seed_redizajn( $assets );
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
 * Podstránka s ponukou redizajnu webov a dve ukážky pred/po.
 *
 * Texty, cena aj počet miest sú v Prispôsobiť — tu vzniká len samotná
 * stránka, aby ju nemusel nikto zakladať ručne a hľadať, ktorú šablónu
 * jej priradiť.
 *
 * @param string $assets Adresa priečinka s ukážkovými súbormi.
 */
function ideanet_seed_redizajn( $assets ) {
	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Redizajn webov',
			'post_name'    => 'redizajn-webov',
			'post_content' => '',
		),
		true
	);

	if ( ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_wp_page_template', 'page-templates/redizajn.php' );
	}

	/* Keď už nejaké ukážky sú, druhú sadu netreba. */
	if ( get_posts( array( 'post_type' => 'ideanet_redizajn', 'post_status' => 'any', 'posts_per_page' => 1 ) ) ) {
		return;
	}

	/* Jedna ukážka stačí — ostatné pribudnú, keď budú skutočné snímky. */
	$ukazky = array(
		array( 'Kaviareň', 'Z 5,8 sekundy načítania na 1,2 sekundy. Menu a otváracie hodiny sú na mobile hneď na prvej obrazovke.' ),
	);

	foreach ( $ukazky as $i => $u ) {
		$n = $i + 1;
		ideanet_seed_post(
			'ideanet_redizajn',
			$u[0],
			$u[1],
			array( 'web' => $assets . '/web/web-' . $n . '.svg' ),
			$i
		);
	}
}

/**
 * Jednorazové upratanie po zrušených ponukách na jednu stranu.
 *
 * Šablóna „Ponuka na jednu stranu" v téme už nie je — cenník aj
 * podmienky sú na úvodnej stránke. Stránky, ktoré tú šablónu používali,
 * by inak zostali visieť ako prázdne. Idú do koša, nie natvrdo preč,
 * takže sa dajú v Stránky → Kôš vrátiť.
 */
function ideanet_zrus_ponuky() {
	if ( get_option( 'ideanet_ponuky_zrusene' ) ) {
		return;
	}
	update_option( 'ideanet_ponuky_zrusene', 1 );

	$stranky = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 50,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- jednorazovo, pri pár stránkach.
			'meta_value'     => 'page-templates/ponuka.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- jednorazovo, pri pár stránkach.
		)
	);

	foreach ( $stranky as $stranka ) {
		wp_trash_post( $stranka->ID );
	}

	foreach ( array( 'ideanet_ponuka_firmy_url', 'ideanet_ponuka_agentury_url' ) as $mod ) {
		remove_theme_mod( $mod );
	}
}
add_action( 'admin_init', 'ideanet_zrus_ponuky' );

/**
 * Vloží položku menu hneď za danú adresu.
 *
 * @param array  $polozky Položky ako adresa => názov.
 * @param string $za      Adresa, za ktorú sa vkladá.
 * @param string $url     Adresa novej položky.
 * @param string $label   Názov novej položky.
 * @return array
 */
function ideanet_vloz_za( $polozky, $za, $url, $label ) {
	$kluce = array_keys( $polozky );
	$kde   = array_search( $za, $kluce, true );
	$kde   = ( false === $kde ) ? count( $kluce ) : $kde + 1;

	return array_slice( $polozky, 0, $kde, true )
		+ array( $url => $label )
		+ array_slice( $polozky, $kde, null, true );
}

/**
 * Nájde podstránku s ponukou redizajnu.
 *
 * Hľadá podľa šablóny, nie podľa adresy — stránku si môže niekto
 * premenovať a odkaz na ňu má zostať tam, kde je.
 *
 * @return WP_Post|null
 */
function ideanet_redizajn_stranka() {
	$podla_sablony = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- raz za čas, pri pár stránkach.
			'meta_value'     => 'page-templates/redizajn.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- raz za čas, pri pár stránkach.
		)
	);

	if ( $podla_sablony ) {
		return $podla_sablony[0];
	}

	$podla_adresy = get_page_by_path( 'redizajn-webov' );

	return $podla_adresy ? $podla_adresy : null;
}

/**
 * Jednorazové dorobenie podstránky s ponukou redizajnu.
 *
 * Ukážkový obsah sa plní len pri prvej aktivácii témy. Weby, ktoré ju
 * mali zapnutú skôr, než podstránka vznikla, by ju teda nikdy nedostali
 * — ani stránku, ani odkaz v menu. Toto to dorobí pri prvom otvorení
 * administrácie po aktualizácii.
 *
 * Keď si niekto stránku alebo položku menu zmaže, späť sa nepridá:
 * číslo v nastavení ostáva uložené.
 */
function ideanet_redizajn_setup() {
	$hotovo = (int) get_option( 'ideanet_redizajn_setup' );
	if ( $hotovo >= 6 ) {
		return;
	}

	$stranka = ideanet_redizajn_stranka();

	if ( ! $stranka ) {
		ideanet_register_post_types();
		ideanet_seed_redizajn( get_theme_file_uri( 'assets/media' ) );
		$stranka = ideanet_redizajn_stranka();
	}

	if ( ! $stranka ) {
		return; // nepodarilo sa; skúsi sa pri ďalšom načítaní.
	}

	if ( $hotovo < 2 ) {
		ideanet_redizajn_do_menu( $stranka );
	}

	if ( $hotovo < 4 ) {
		ideanet_redizajn_jeden_obrazok();
		ideanet_redizajn_nadpisy_ukazok( $stranka->ID );
	}

	/* $hotovo > 0 = web túto podstránku už mal. Na čerstvej inštalácii
	   sa počet voľných miest nemá prečo znižovať, tam sú ešte všetky. */
	if ( $hotovo > 0 && $hotovo < 6 ) {
		ideanet_redizajn_bez_podstranok( $stranka->ID );
	}

	/* Texty sa už neupravujú v Prispôsobiť, ale pri stránke. */
	ideanet_red_prefill( $stranka->ID );
	update_option( 'ideanet_red_page', $stranka->ID, false );

	update_option( 'ideanet_redizajn_setup', 6 );
}
add_action( 'admin_init', 'ideanet_redizajn_setup' );

/**
 * Z ponuky vypadol limit na počet podstránok.
 *
 * Prepisujú sa len vety, ktoré nikto nemenil — vlastný text zostáva.
 * Zároveň sa doplní, koľko miest je ešte voľných; aj to len vtedy, keď
 * tam ešte stojí pôvodná hodnota a nikto s ňou nehýbal.
 *
 * @param int $page_id ID podstránky.
 */
function ideanet_redizajn_bez_podstranok( $page_id ) {
	$vety = array(
		'red_gets'    => array(
			'Nový dizajn vášho WordPress webu až do {podstranok} podstránok',
			'Nový dizajn vášho WordPress webu',
		),
		'red_prekoho' => array(
			'WordPress prezentačné weby bez e-shopu, do {podstranok} podstránok',
			'WordPress prezentačné weby bez e-shopu',
		),
	);

	foreach ( $vety as $key => $def ) {
		list( $stara, $nova ) = $def;
		$name  = '_ideanet_' . $key;
		$teraz = (string) get_post_meta( $page_id, $name, true );

		if ( '' !== $teraz && false !== strpos( $teraz, $stara ) ) {
			update_post_meta( $page_id, $name, str_replace( $stara, $nova, $teraz ) );
		}
	}

	delete_post_meta( $page_id, '_ideanet_red_pages' );

	/* Prvé miesta sú obsadené — ale len ak s číslom nikto nehýbal. */
	$volne = (string) get_post_meta( $page_id, '_ideanet_red_slots_left', true );
	if ( '' === $volne || '10' === $volne ) {
		update_post_meta( $page_id, '_ideanet_red_slots_left', '4' );
	}
}

/**
 * Premenovanie sekcie s ukážkami.
 *
 * Sekcia sa volala „Pred a po", lebo ukazovala aj pôvodný web. Teraz sú
 * to ukážky hotových webov. Prepisuje sa len text, ktorý nikto nemenil
 * — vlastný text zostáva, ako je.
 *
 * @param int $page_id ID podstránky.
 */
function ideanet_redizajn_nadpisy_ukazok( $page_id ) {
	$texty = array(
		'red_ukazky_eyebrow' => array(
			array( 'Ukážky' ),
			'Redizajn webu',
		),
		'red_ukazky_title'   => array(
			array( 'Pred a po' ),
			'Ukážky klientov',
		),
		'red_ukazky_note'    => array(
			array(
				'Rovnaká firma, rovnaká adresa, len prehľadnejšie, rýchlejšie a čitateľne na mobile.',
				'Rovnaká firma, rovnaká adresa — len prehľadnejšie, rýchlejšie a čitateľne na mobile.',
			),
			'Weby, ktoré som robil. Prehľadné, rýchle a čitateľné na mobile.',
		),
	);

	foreach ( $texty as $key => $def ) {
		list( $stare, $nove ) = $def;
		$teraz = (string) get_post_meta( $page_id, '_ideanet_' . $key, true );

		if ( '' === $teraz || in_array( $teraz, $stare, true ) ) {
			update_post_meta( $page_id, '_ideanet_' . $key, $nove );
		}
	}

	/* Štítky Pred a Po už nemajú kde svietiť. */
	delete_post_meta( $page_id, '_ideanet_red_label_pred' );
	delete_post_meta( $page_id, '_ideanet_red_label_po' );
}

/**
 * Z dvojice obrázkov zostáva jeden — hotový web.
 *
 * Ukážky najprv ukazovali web pred aj po. Pôvodný web sa už neukazuje,
 * tak sa snímka hotového webu presunie do nového poľa a tá pôvodná
 * zmizne. Obrázky v knižnici médií zostávajú nedotknuté.
 */
function ideanet_redizajn_jeden_obrazok() {
	$ukazky = get_posts(
		array(
			'post_type'      => 'ideanet_redizajn',
			'post_status'    => 'any',
			'posts_per_page' => 100,
		)
	);

	foreach ( $ukazky as $ukazka ) {
		$po = (string) get_post_meta( $ukazka->ID, '_ideanet_po', true );

		/* Ukážkový súbor v téme sa pri tej zmene premenoval. */
		$po = str_replace( '/assets/media/web/po-', '/assets/media/web/web-', $po );

		if ( '' !== $po && '' === (string) get_post_meta( $ukazka->ID, '_ideanet_web', true ) ) {
			update_post_meta( $ukazka->ID, '_ideanet_web', $po );
		}

		delete_post_meta( $ukazka->ID, '_ideanet_po' );
		delete_post_meta( $ukazka->ID, '_ideanet_pred' );
	}
}

/**
 * Doplní odkaz na podstránku do horného menu a do menu na mobile.
 *
 * @param WP_Post $stranka Podstránka s ponukou redizajnu.
 */
function ideanet_redizajn_do_menu( $stranka ) {
	$odkaz = get_permalink( $stranka );

	$locations = get_nav_menu_locations();
	$kam       = array(
		'primary' => __( 'Redizajn', 'ideanet' ),
		'mobile'  => __( 'Redizajn webov', 'ideanet' ),
	);

	foreach ( $kam as $location => $label ) {
		if ( empty( $locations[ $location ] ) ) {
			continue;
		}

		$menu_id = (int) $locations[ $location ];
		$polozky = wp_get_nav_menu_items( $menu_id );
		if ( ! is_array( $polozky ) ) {
			continue;
		}

		/* Keď tam už odkaz je — či ako stránka, či ako ručne napísaná
		   adresa —, druhý netreba. */
		foreach ( $polozky as $polozka ) {
			$je_stranka = 'post_type' === $polozka->type && (int) $polozka->object_id === $stranka->ID;
			$je_adresa  = untrailingslashit( $polozka->url ) === untrailingslashit( $odkaz );
			if ( $je_stranka || $je_adresa ) {
				continue 2;
			}
		}

		/* Hneď za cenník — ponuka patrí k cenám, nie na koniec. */
		$kde = count( $polozky ) + 1;
		foreach ( $polozky as $polozka ) {
			if ( false !== strpos( $polozka->url, '#cennik' ) ) {
				$kde = (int) $polozka->menu_order + 1;
				break;
			}
		}

		foreach ( $polozky as $polozka ) {
			if ( (int) $polozka->menu_order >= $kde ) {
				wp_update_post(
					array(
						'ID'         => $polozka->ID,
						'menu_order' => (int) $polozka->menu_order + 1,
					)
				);
			}
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $label,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $stranka->ID,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $kde,
			)
		);
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
		'#podmienky'  => 'Podmienky',
		'#kontakt'    => 'Kontakt',
	);

	$pata = array(
		'#portfolio' => 'Portfólio',
		'#vizualy'   => 'Vizuály',
		'#cennik'    => 'Cenník',
		'#podmienky' => 'Podmienky',
		'#kontakt'   => 'Kontakt',
	);

	$locations = get_theme_mod( 'nav_menu_locations', array() );

	/* Podstránka s ponukou redizajnu nie je kotva na úvodnej stránke, tak
	   sa pridá ako odkaz na stránku — inak by sa na ňu nedalo nikde
	   kliknúť. V hornom menu je skrátená, nech sa riadok nerozsype.
	   V spodnej lište nie je: tam sú štyri ikony a tlačidlo Viac. */
	$mobil    = $sekcie;
	$redizajn = get_page_by_path( 'redizajn-webov' );

	if ( $redizajn ) {
		$odkaz  = get_permalink( $redizajn );
		$sekcie = ideanet_vloz_za( $sekcie, '#cennik', $odkaz, 'Redizajn' );
		$mobil  = ideanet_vloz_za( $mobil, '#cennik', $odkaz, 'Redizajn webov' );
		$pata   = ideanet_vloz_za( $pata, '#cennik', $odkaz, 'Redizajn webov' );
	}

	$menus = array(
		'primary' => array( 'Hlavné menu', $sekcie ),
		'mobile'  => array( 'Mobilné menu', $mobil ),
		'tabbar'  => array(
			'Spodná lišta',
			array(
				'#portfolio' => 'Portfólio',
				'#vizualy'   => 'Vizuály',
				'#cennik'    => 'Cenník',
				'#kontakt'   => 'Kontakt',
			),
		),
		'footer'  => array(
			'Pätička',
			$pata,
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
					'menu-item-url'    => 0 === strpos( $url, 'http' ) ? $url : home_url( '/' ) . $url,
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
