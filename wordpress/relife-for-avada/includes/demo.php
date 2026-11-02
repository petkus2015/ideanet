<?php
/**
 * Ukážkový obsah (kategórie, články, ankety, menu). Spustenie:
 *  - Vzhľad → RELIFE ukážkový obsah (tlačidlo), alebo
 *  - WP-CLI: wp relife demo
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nájde príspevok podľa presného názvu (náhrada za zastaranú get_page_by_title).
 */
function rlav_find_by_title( string $title, string $post_type ): ?WP_Post {
	$q = new WP_Query(
		array(
			'post_type'      => $post_type,
			'title'          => $title,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	return $q->have_posts() ? $q->posts[0] : null;
}

/**
 * Vytvorí ukážkový obsah. Bezpečné spustiť opakovane (nič neduplikuje).
 *
 * @return array<string, int> Počty vytvorených položiek.
 */
function rlav_import_demo( bool $set_front = false ): array {
	$created = array( 'sections' => 0, 'formats' => 0, 'posts' => 0, 'polls' => 0, 'pages' => 0 );

	// Sekcie (kategórie).
	$cat_ids = array();
	foreach ( rlav_sections() as $slug => $s ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term instanceof WP_Term ) {
			$res = wp_insert_term( $s['name'], 'category', array( 'slug' => $slug, 'description' => $s['desc'] ) );
			if ( is_wp_error( $res ) ) {
				continue;
			}
			update_term_meta( (int) $res['term_id'], 'relife_color', $s['color'] );
			$cat_ids[ $slug ] = (int) $res['term_id'];
			++$created['sections'];
		} else {
			$cat_ids[ $slug ] = $term->term_id;
		}
	}

	// Formáty.
	foreach ( rlav_formats() as $slug => $f ) {
		if ( ! term_exists( $slug, 'relife_format' ) ) {
			wp_insert_term( $f['name'], 'relife_format', array( 'slug' => $slug, 'description' => $f['desc'] ) );
			++$created['formats'];
		}
	}

	// Články: [sekcia, formát, názov, odpoveď].
	$posts = array(
		array( 'tech', 'explained', 'AI agenti vysvetlené jednoducho: čo už vedia, čo nie a ako ich začať používať', 'AI agent je program, ktorý nielen odpovedá, ale aj samostatne vykoná sériu krokov na splnenie zadania.' ),
		array( 'tech', 'how-to', '5 AI nástrojov, ktoré ti ušetria hodinu denne', 'Najviac času ušetria nástroje na písanie, prepisy, preklady, zhrnutia a plánovanie.' ),
		array( 'tech', 'versus', 'ChatGPT, Gemini, Claude: čo vybrať na prácu a čo na školu', 'Najlepší nástroj závisí od úlohy. Porovnávaj podľa typu zadania, súkromia a ceny.' ),
		array( 'tech', 'trend-report', 'Gadgety, ktoré stoja za pozornosť, a tie, ktoré nie', '' ),
		array( 'health', 'explained', 'Longevity bez mýtov: čo má oporu vo výskume', 'Najlepšie podložené sú spánok, pohyb, strava a vzťahy, nie zázračné doplnky.' ),
		array( 'health', 'how-to', 'Večerná rutina na lepší spánok krok za krokom', '' ),
		array( 'health', 'how-to', 'Silový tréning 3× týždenne: plán pre začiatočníkov', '' ),
		array( 'health', 'explained', 'Kortizol jednoducho: stres, spánok a energia', '' ),
		array( 'money', 'explained', 'Prvé investície: ako začať s ETF a nespraviť zbytočné chyby', 'ETF je fond, ktorý sa obchoduje na burze a v jednej položke drží veľa akcií alebo dlhopisov.' ),
		array( 'money', 'how-to', 'Osobný rozpočet za 20 minút', '' ),
		array( 'money', 'versus', 'ETF vs. sporiaci účet: kam s prvými peniazmi', 'ETF sú na dlhé ciele s kolísaním hodnoty, sporiaci účet na rezervu a krátke ciele.' ),
		array( 'money', 'trend-report', 'Kariéra v ére AI: zručnosti, ktoré budú mať cenu', '' ),
		array( 'travel', 'how-to', 'Low-cost cestovanie: ako si naplánovať lacný víkend', '' ),
		array( 'travel', 'trend-report', 'Mestá, ktoré sa oplatí zažiť mimo sezóny', '' ),
		array( 'trendy', 'quick-take', 'Čo stojí za novým virálnym trendom a či ho má zmysel sledovať', '' ),
		array( 'trendy', 'quick-take', 'Filmy a seriály tohto týždňa: čo sa rieši a čo preskočiť', '' ),
		array( 'health', 'quick-take', 'Spánok pred kondíciou: prečo sa longevity začína večer', '' ),
		array( 'money', 'quick-take', 'ETF pre začiatočníkov: kde začať s prvými 50 eurami', '' ),
		array( 'travel', 'quick-take', 'Víkend v Európe za pár eur: mestá, ktoré dávajú zmysel', '' ),
		array( 'style', 'trend-report', 'Bývanie na malom priestore: nápady, ktoré fungujú', '' ),
		array( 'style', 'how-to', 'Grooming rutina bez zbytočností', '' ),
		array( 'life', 'how-to', 'Vzťahy a hranice: ako ich nastaviť', '' ),
		array( 'life', 'ultimate-guide', 'Produktivita: systém, ktorý zvládne aj chaotik', '' ),
		array( 'tech', 'ultimate-guide', 'AI pre začiatočníkov: kompletný sprievodca', '' ),
		array( 'health', 'ultimate-guide', 'Longevity: kompletný sprievodca', '' ),
		array( 'money', 'ultimate-guide', 'Investovanie od nuly: kompletný sprievodca', '' ),
		array( 'travel', 'ultimate-guide', 'Low-cost cestovanie: kompletný sprievodca', '' ),
	);

	$body = '<!-- wp:paragraph --><p>[Úvod: o čom text je a prečo na tom čitateľovi záleží.]</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading --><h2>Čo to je</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>[Definícia jednoduchým jazykom s jedným príkladom z bežného života.]</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading --><h2>Ako začať krok za krokom</h2><!-- /wp:heading -->'
		. '<!-- wp:list {"ordered":true} --><ol><li>Urči si cieľ.</li><li>Vyber nástroj alebo postup.</li><li>Začni v malom a priebežne vyhodnocuj.</li></ol><!-- /wp:list -->'
		. '<!-- wp:heading --><h2>Najčastejšie chyby</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>[Tri až päť chýb, každá s krátkym vysvetlením.]</p><!-- /wp:paragraph -->';

	$first_id = 0;
	foreach ( $posts as $i => $row ) {
		list( $section, $format, $title, $answer ) = $row;
		$existing = rlav_find_by_title( $title, 'post' );
		if ( $existing instanceof WP_Post ) {
			if ( 0 === $first_id ) {
				$first_id = $existing->ID;
			}
			continue;
		}
		$post_id = wp_insert_post(
			array(
				'post_title'    => $title,
				'post_content'  => $body,
				'post_excerpt'  => '' !== $answer ? $answer : 'Praktický prehľad bez zbytočného žargónu.',
				'post_status'   => 'publish',
				'post_type'     => 'post',
				'post_date'     => gmdate( 'Y-m-d H:i:s', time() - $i * 3600 * 7 ),
				'post_category' => isset( $cat_ids[ $section ] ) ? array( $cat_ids[ $section ] ) : array(),
				'meta_input'    => array(
					'relife_answer'     => $answer,
					'relife_sources'    => "[Zdroj 1, názov, rok]\n[Zdroj 2, názov, rok]",
					'relife_disclosure' => 'Tento článok neobsahuje affiliate odkazy.',
				),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			continue;
		}
		wp_set_object_terms( $post_id, $format, 'relife_format' );
		++$created['posts'];
		if ( 0 === $first_id ) {
			$first_id = $post_id;
		}
	}
	if ( $first_id > 0 && empty( get_option( 'sticky_posts' ) ) ) {
		stick_post( $first_id );
	}

	// Ankety.
	$polls = array(
		array( 'Ktorú tému chceš na RELIFE vidieť častejšie?', 'active', "AI nástroje | 38\nFitness a zdravie | 24\nPeniaze a investovanie | 22\nCestovanie a zážitky | 16", '' ),
		array( 'Ktorý AI nástroj používaš najčastejšie?', 'closed', "Chatbot na písanie a otázky | 41\nGenerátor obrázkov | 17\nPrekladač a jazykové nástroje | 26\nAI nepoužívam | 16", 'tech' ),
		array( 'Čo ti najviac pomáha pri spánku?', 'closed', "Večerná rutina | 34\nČas bez obrazoviek | 29\nPravidelný režim | 27\nNič z toho | 10", 'health' ),
		array( 'Ako si odkladáš peniaze?', 'closed', "Sporiaci účet | 44\nETF | 21\nHotovosť | 9\nNeodkladám | 26", 'money' ),
		array( 'Kam by si išiel na víkend?', 'closed', "Do mesta v Európe | 37\nDo hôr | 31\nK moru | 22\nZostanem doma | 10", 'travel' ),
		array( 'Čo ťa najviac brzdí v produktivite?', 'closed', "Telefón a notifikácie | 46\nNejasné priority | 23\nNedostatok energie | 21\nIné | 10", 'life' ),
	);
	foreach ( $polls as $p ) {
		$exists = rlav_find_by_title( $p[0], 'relife_poll' );
		if ( $exists instanceof WP_Post ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_title'  => $p[0],
				'post_status' => 'publish',
				'post_type'   => 'relife_poll',
				'meta_input'  => array(
					'relife_poll_status'  => $p[1],
					'relife_poll_options' => $p[2],
					'relife_poll_section' => $p[3],
				),
			),
			true
		);
		if ( ! is_wp_error( $id ) ) {
			++$created['polls'];
		}
	}

	// Stránky v Avada Builderi: Domov, Ankety, Uložené.
	$created['pages'] = rlav_create_builder_pages( $set_front );

	return $created;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'relife-avada demo',
		static function (): void {
			$r = rlav_import_demo( true );
			WP_CLI::success( wp_json_encode( $r ) ?: 'ok' );
		}
	);
}
