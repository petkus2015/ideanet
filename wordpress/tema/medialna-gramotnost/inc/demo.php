<?php
/**
 * Vzhľad → Ukážkový obsah: naplní web príkladmi, testami, kartami, slovníkom,
 * stránkami a menu. Opakované spustenie existujúce položky aktualizuje (podľa slugu),
 * nevytvára duplikáty.
 *
 * @package MedialnaGramotnostTema
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/demo-data.php';

function mgt_demo_menu() {
	add_theme_page( 'Ukážkový obsah', 'Ukážkový obsah', 'manage_options', 'mgt-demo', 'mgt_demo_screen' );
}
add_action( 'admin_menu', 'mgt_demo_menu' );

function mgt_demo_screen() {
	?>
	<div class="wrap">
		<h1>Ukážkový obsah</h1>
		<?php if ( isset( $_GET['done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success"><p>Hotovo. Obsah nájdete v menu <strong>Príklady podvodov</strong>, <strong>Testy</strong>, <strong>Rýchle karty</strong>, <strong>Slovník</strong> a <strong>Stránky</strong>. <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Zobraziť web</a></p></div>
		<?php endif; ?>
		<p>Vytvorí alebo aktualizuje:</p>
		<ul style="list-style:disc;padding-left:20px">
			<li>12 príkladov podvodov s ukážkami a varovnými znakmi (kanály a skupiny pre filter),</li>
			<li>3 testy: trenažér s 12 správami, test pre seniorov z príkladov v atlase a kvíz o manipulácii,</li>
			<li>12 rýchlych kariet a 24 pojmov v slovníku,</li>
			<li>stránky Domov, Overiť správu, Dezinformácie, Pre seniorov, Pre mladých, Stalo sa mi to, Zdroje,</li>
			<li>hlavné menu a menu v pätičke, texty domovskej stránky.</li>
		</ul>
		<p>Položky s rovnakým názvom v adrese (slug) sa prepíšu. Vaše vlastné príklady a testy zostanú.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mgt_import">
			<?php wp_nonce_field( 'mgt_import' ); ?>
			<?php submit_button( 'Importovať ukážkový obsah' ); ?>
		</form>
	</div>
	<?php
}

function mgt_demo_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemáte oprávnenie.' );
	}
	check_admin_referer( 'mgt_import' );
	mgt_theme_import();
	wp_safe_redirect( admin_url( 'themes.php?page=mgt-demo&done=1' ) );
	exit;
}
add_action( 'admin_post_mgt_import', 'mgt_demo_handle' );

/**
 * Po aktivácii témy obnoviť adresy (archívy typov obsahu).
 */
function mgt_after_switch() {
	mgt_register_types();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'mgt_after_switch' );

/**
 * Vytvorí alebo aktualizuje príspevok podľa slugu.
 */
function mgt_upsert( $type, $slug, $title, $args = array() ) {
	$existing = get_page_by_path( $slug, OBJECT, $type );
	$data     = array_merge(
		array(
			'post_type'   => $type,
			'post_name'   => $slug,
			'post_title'  => $title,
			'post_status' => 'publish',
		),
		$args
	);
	if ( $existing ) {
		$data['ID'] = $existing->ID;
		return wp_update_post( $data );
	}
	return wp_insert_post( $data );
}

function mgt_set_meta( $id, $data ) {
	foreach ( $data as $k => $v ) {
		update_post_meta( $id, '_mg_' . $k, $v );
	}
}

function mgt_term( $tax, $slug, $name ) {
	$t = get_term_by( 'slug', $slug, $tax );
	if ( ! $t ) {
		$r = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
		return is_wp_error( $r ) ? 0 : $r['term_id'];
	}
	return $t->term_id;
}

/**
 * Rozdelí obsah „správa --- vysvetlenia“.
 */
function mgt_split_demo_message( $content ) {
	$parts = preg_split( '/\n-{3,}\n/', $content, 2 );
	return array( trim( $parts[0] ), isset( $parts[1] ) ? trim( $parts[1] ) : '' );
}

/**
 * Hlavný import.
 */
function mgt_theme_import() {
	mgt_register_types();
	kses_remove_filters();

	/* ---------- Kategórie ---------- */
	$kanaly = array( 'sms' => 'SMS', 'email' => 'E-mail', 'telefon' => 'Telefonát', 'socialne' => 'Sociálne siete', 'chat' => 'Chat', 'web' => 'Web' );
	$skup   = array( 'seniori' => 'Seniori', 'mladi' => 'Mladí', 'media' => 'Médiá a dezinformácie' );
	foreach ( $kanaly as $s => $n ) {
		mgt_term( 'mg_kanal', $s, $n );
	}
	foreach ( $skup as $s => $n ) {
		mgt_term( 'mg_skupina', $s, $n );
	}

	/* ---------- Príklady podvodov ---------- */
	$home = array(
		'sms-balik'           => array( 'SMS', 'SMS o balíku a doplatku. Cieľom je karta a kód zo SMS. Varuje SK-CERT.' ),
		'falosny-bankar'      => array( 'Telefonát', '„Váš účet je napadnutý, presuňte peniaze na bezpečný účet.“ Varuje Polícia SR.' ),
		'investicie-deepfake' => array( 'Facebook', 'Vymyslené rozhovory a videá politikov a moderátorov. Varuje NBS.' ),
		'ahoj-mami'           => array( 'WhatsApp', 'Podvodník sa vydáva za vaše dieťa a pýta peniaze. Varuje Polícia SR.' ),
		'bazar'               => array( 'Bazár', '„Pošlem kuriéra, peniaze si prevezmite cez odkaz.“ Varuje Polícia SR.' ),
	);
	$ids  = array();
	foreach ( mgt_demo_scams() as $i => $s ) {
		$id                 = mgt_upsert( 'mg_priklad', $s['id'], $s['title'], array( 'post_content' => $s['text'], 'menu_order' => $i + 1 ) );
		$ids[ $s['id'] ]    = $id;
		list( $msg, $notes ) = mgt_split_demo_message( $s['message'] );
		$meta               = array(
			'labels'    => $s['labels'],
			'source'    => $s['source'],
			'label'     => $s['example']['label'],
			'message'   => $msg,
			'notes'     => $notes,
			'home'      => isset( $home[ $s['id'] ] ) ? '1' : '',
			'home_tag'  => isset( $home[ $s['id'] ] ) ? $home[ $s['id'] ][0] : '',
			'home_text' => isset( $home[ $s['id'] ] ) ? $home[ $s['id'] ][1] : '',
		);
		foreach ( array_keys( mgt_mock_fields() ) as $k ) {
			if ( ! isset( $meta[ $k ] ) ) {
				$meta[ $k ] = isset( $s['example'][ $k ] ) ? $s['example'][ $k ] : '';
			}
		}
		mgt_set_meta( $id, $meta );
		$tags = explode( ',', $s['tags'] );
		wp_set_object_terms( $id, array_values( array_intersect( $tags, array_keys( $kanaly ) ) ), 'mg_kanal' );
		wp_set_object_terms( $id, array_values( array_intersect( $tags, array_keys( $skup ) ) ), 'mg_skupina' );
	}

	/* ---------- Testy ---------- */
	$qs = array();
	foreach ( mgt_demo_trainer_items() as $row ) {
		list( $msg, $notes ) = mgt_split_demo_message( $row[1] );
		$q = array( 'message' => $msg, 'notes' => $notes );
		foreach ( array_keys( mgt_question_fields() ) as $k ) {
			if ( ! isset( $q[ $k ] ) ) {
				$q[ $k ] = isset( $row[0][ $k ] ) ? $row[0][ $k ] : '';
			}
		}
		$qs[] = $q;
	}
	$summary = "Nátlak a strach sú hlavné nástroje podvodníkov.\nKód zo SMS, PIN ani heslo nikomu nedávajte.\nNa prijatie peňazí nikdy netreba údaje z karty.\nOverujte inou cestou: zavolajte na známe číslo.";
	$t1      = mgt_upsert( 'mg_test', 'podvod-alebo-nie', 'Podvod, alebo nie?', array( 'post_excerpt' => 'Dvanásť správ, aké chodia do mobilov a e-mailov. Niektoré sú podvod, niektoré nie.', 'menu_order' => 1 ) );
	mgt_set_meta(
		$t1,
		array(
			'test_type'   => 'trenazer',
			'intro'       => 'Pri každej správe sa rozhodnite. Potom uvidíte, čo ste mohli prehliadnuť. Niektoré správy sú zámerne v poriadku, nie každá správa je podvod.',
			'question'    => 'Je táto správa podvod?',
			'hint'        => 'Pozorne si ju prečítajte. Všímajte si odosielateľa, odkaz a čo od vás chce.',
			'scam_label'  => 'Podvod',
			'safe_label'  => 'Je v poriadku',
			'result_high' => 'Výborne. Podvodníci to s vami budú mať ťažké. Pošlite test aj rodine.',
			'result_mid'  => 'Dobrý základ. Pozrite si v atlase typy podvodov, pri ktorých ste sa pomýlili.',
			'result_low'  => 'Nevadí, presne na to je test. Prejdite si atlas podvodov a skúste to znova.',
			'summary'     => $summary,
			'examples'    => array(),
			'questions'   => $qs,
		)
	);

	// Test pre seniorov: príklady z atlasu + dve správy, ktoré sú v poriadku.
	$safe = array_values(
		array_filter(
			$qs,
			function ( $q ) {
				return 'safe' === $q['verdict'] && in_array( $q['sender'], array( 'Moja banka', 'Google' ), true );
			}
		)
	);
	$t2 = mgt_upsert( 'mg_test', 'test-pre-seniorov', 'Krátky test pre seniorov', array( 'post_excerpt' => 'Šesť situácií z telefónu: balík, banka, vnúča, reklama. Zloženie testu si určíte výberom príkladov z atlasu.', 'menu_order' => 2 ) );
	wp_set_object_terms( $t2, array( 'seniori' ), 'mg_skupina' );
	mgt_set_meta(
		$t2,
		array(
			'test_type'   => 'trenazer',
			'intro'       => 'Šesť správ, s ktorými sa stretávajú najmä starší ľudia. Pri každej sa rozhodnite, či ide o podvod.',
			'question'    => 'Je to podvod?',
			'hint'        => 'Nikam sa neponáhľajte. Kto chce kód, PIN alebo peniaze?',
			'scam_label'  => 'Podvod',
			'safe_label'  => 'Je v poriadku',
			'result_high' => 'Výborne. Ukážte test aj priateľom.',
			'result_mid'  => 'Dobre. Prečítajte si stránku Pre seniorov.',
			'result_low'  => 'Nevadí. Prečítajte si stránku Pre seniorov a skúste to znova.',
			'summary'     => "Banka ani polícia nikdy nepýtajú kód ani PIN.\nKeď volajú, zložte a zavolajte sami.\nNové číslo „dieťaťa“? Zavolajte na staré.",
			'examples'    => array_values( array_filter( array( $ids['sms-balik'], $ids['falosny-bankar'], $ids['ahoj-mami'], $ids['investicie-deepfake'] ) ) ),
			'questions'   => $safe,
		)
	);

	// Kvíz z dezinformácií.
	$pages = mgt_demo_pages();
	$quiz  = array();
	foreach ( $pages['dezinformacie']['sections'] as $sec ) {
		foreach ( $sec['cols'] as $col ) {
			if ( preg_match_all( '/\[mg_quiz_question([^\]]*)\](.*?)\[\/mg_quiz_question\]/s', $col, $m, PREG_SET_ORDER ) ) {
				foreach ( $m as $qm ) {
					$at     = shortcode_parse_atts( $qm[1] );
					$quiz[] = array(
						'text'        => html_entity_decode( $qm[2], ENT_QUOTES, 'UTF-8' ),
						'answers'     => implode( "\n", array_map( 'trim', explode( ';', html_entity_decode( $at['answers'], ENT_QUOTES, 'UTF-8' ) ) ) ),
						'correct'     => $at['correct'],
						'explanation' => html_entity_decode( $at['explanation'], ENT_QUOTES, 'UTF-8' ),
					);
				}
			}
		}
	}
	$t3 = mgt_upsert( 'mg_test', 'spoznate-manipulaciu', 'Spoznáte manipuláciu?', array( 'post_excerpt' => 'Päť vymyslených príspevkov. Ku každému vyberte, aký trik autor použil.', 'menu_order' => 3 ) );
	wp_set_object_terms( $t3, array( 'media', 'mladi' ), 'mg_skupina' );
	mgt_set_meta(
		$t3,
		array(
			'test_type'  => 'kviz',
			'intro'      => 'Kto pozná techniky manipulácie, ľahšie ich odhalí. Výskum tomu hovorí mentálne očkovanie.',
			'question'   => 'Akú techniku manipulácie použil autor?',
			'result_mid' => 'Kto pozná techniky manipulácie, ľahšie ich odhalí.',
			'questions'  => $quiz,
		)
	);

	/* ---------- Stránky ---------- */
	$page_ids = array();
	$wanted   = array(
		'overit-spravu'  => 'Overiť správu',
		'dezinformacie'  => 'Dezinformácie a AI',
		'pre-seniorov'   => 'Pre seniorov',
		'pre-mladych'    => 'Pre mladých',
		'stalo-sa-mi-to' => 'Stalo sa mi to',
		'zdroje'         => 'Zdroje a metodika',
	);
	foreach ( $wanted as $slug => $title ) {
		$page_ids[ $slug ] = mgt_upsert( 'page', $slug, $title, array( 'post_status' => 'publish' ) );
	}
	$page_ids['domov']            = mgt_upsert( 'page', 'domov', 'Domov' );
	$page_ids['atlas-podvodov']   = mgt_upsert( 'page', 'atlas-podvodov', 'Atlas podvodov', array( 'post_status' => 'private', 'post_excerpt' => 'Dvanásť podvodov, s ktorými sa ľudia na Slovensku stretávajú najčastejšie. Pri každom je ukážka, varovné znaky a čo robiť. Tlačidlom Ukázať varovné znaky ich zvýrazníte priamo v ukážke.' ) );
	$page_ids['rychle-materialy'] = mgt_upsert( 'page', 'rychle-materialy', 'Rýchle materiály', array( 'post_status' => 'private', 'post_excerpt' => 'Krátke karty: príklad, varovné znaky, čo robiť a jedna veta na zapamätanie. Do školy aj na rozhovor so starými rodičmi.' ) );

	/* ---------- Rýchle karty ---------- */
	$card_slugs = array();
	foreach ( mgt_demo_lessons( function ( $s ) { return '{{url:' . $s . '}}'; } ) as $i => $row ) {
		$a                         = $row[0];
		$slug                      = sanitize_title( $a['title'] );
		$card_slugs[ $a['anchor'] ] = $slug;
		$id                        = mgt_upsert( 'mg_karta', $slug, $a['title'], array( 'menu_order' => $i + 1 ) );
		mgt_set_meta(
			$id,
			array(
				'number'     => $a['number'],
				'tag'        => $a['tag'],
				'tag_red'    => 'yes' === $a['tag_red'] ? '1' : '',
				'looks'      => $a['looks'],
				'col1_title' => $a['col1_title'],
				'col1_style' => $a['col1_style'],
				'col1'       => implode( "\n", array_map( 'trim', explode( ';', $a['col1'] ) ) ),
				'col2_title' => $a['col2_title'],
				'col2_style' => $a['col2_style'],
				'col2'       => implode( "\n", array_map( 'trim', explode( ';', $a['col2'] ) ) ),
				'note'       => $a['note'],
				'remember'   => $a['remember'],
				'question'   => preg_replace( '/^Rýchla otázka:\s*/u', '', $a['question'] ),
				'answer'     => $a['answer'],
				'link_text'  => $a['link_text'],
				'link'       => $a['link'],
			)
		);
		wp_set_object_terms( $id, explode( ',', $a['filter_tags'] ), 'mg_skupina' );
	}

	/* ---------- Slovník ---------- */
	foreach ( $pages['slovnik']['sections'][0]['cols'] as $col ) {
		if ( preg_match_all( '/\[mg_term term="([^"]*)"\](.*?)\[\/mg_term\]/s', $col, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $tm ) {
				$title = html_entity_decode( $tm[1], ENT_QUOTES, 'UTF-8' );
				mgt_upsert( 'mg_pojem', sanitize_title( $title ), $title, array( 'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $tm[2] ) . '</p><!-- /wp:paragraph -->' ) );
			}
		}
	}

	/* ---------- Obsah stránok (bloky) ---------- */
	$url_map = array(
		'atlas-podvodov'      => get_post_type_archive_link( 'mg_priklad' ),
		'trenazer'            => get_permalink( $t1 ),
		'rychle-materialy'    => get_post_type_archive_link( 'mg_karta' ),
		'slovnik'             => get_post_type_archive_link( 'mg_pojem' ),
		'medialna-gramotnost' => home_url( '/' ),
	);
	foreach ( $page_ids as $slug => $pid ) {
		if ( ! isset( $url_map[ $slug ] ) ) {
			$url_map[ $slug ] = get_permalink( $pid );
		}
	}
	foreach ( $wanted as $slug => $title ) {
		$excerpt = '';
		$content = mgt_sections_to_blocks( $pages[ $slug ]['sections'], $excerpt, $slug, $t3 );
		$content = mgt_replace_links( $content, $url_map, $card_slugs );
		wp_update_post( array( 'ID' => $page_ids[ $slug ], 'post_content' => $content, 'post_excerpt' => $excerpt ) );
	}
	// Kartička do peňaženky pod kartami.
	wp_update_post( array( 'ID' => $page_ids['rychle-materialy'], 'post_content' => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\" id=\"penazenka\">Kartička do peňaženky</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p>Vytlačte, vystrihnite a doplňte čísla perom. Kartička patrí k platobnej karte, aby bola po ruke, keď niekto volá.</p>\n<!-- /wp:paragraph -->\n<!-- wp:shortcode -->\n[mg_wallet]\n<!-- /wp:shortcode -->" ) );

	// Odkazy v kartách.
	foreach ( get_posts( array( 'post_type' => 'mg_karta', 'numberposts' => -1 ) ) as $k ) {
		update_post_meta( $k->ID, '_mg_link', mgt_replace_links( get_post_meta( $k->ID, '_mg_link', true ), $url_map, $card_slugs ) );
	}

	/* ---------- Domovská stránka a nastavenia ---------- */
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page_ids['domov'] );
	set_theme_mod( 'mg_hero_example', $ids['sms-balik'] );
	set_theme_mod( 'mg_cta_test', $t1 );
	set_theme_mod( 'mg_alert_link', get_permalink( $page_ids['stalo-sa-mi-to'] ) );
	if ( ! get_option( 'blogdescription' ) || 'Just another WordPress site' === get_option( 'blogdescription' ) ) {
		update_option( 'blogdescription', 'Zastav sa. Over si to.' );
	}

	/* ---------- Menu ---------- */
	mgt_build_menu(
		'Hlavné menu',
		'primary',
		array(
			array( 'Podvody', get_post_type_archive_link( 'mg_priklad' ) ),
			array( 'Testy', get_post_type_archive_link( 'mg_test' ) ),
			array( 'Overiť správu', get_permalink( $page_ids['overit-spravu'] ) ),
			array( 'Dezinformácie', get_permalink( $page_ids['dezinformacie'] ) ),
			array( 'Materiály', get_post_type_archive_link( 'mg_karta' ) ),
			array( 'Seniori', get_permalink( $page_ids['pre-seniorov'] ) ),
			array( 'Mladí', get_permalink( $page_ids['pre-mladych'] ) ),
			array( 'Stalo sa mi to', get_permalink( $page_ids['stalo-sa-mi-to'] ), 'mgt-urgent' ),
		)
	);
	mgt_build_menu(
		'Pätička',
		'footer',
		array(
			array( 'Atlas podvodov', get_post_type_archive_link( 'mg_priklad' ) ),
			array( 'Testy', get_post_type_archive_link( 'mg_test' ) ),
			array( 'Rýchle materiály', get_post_type_archive_link( 'mg_karta' ) ),
			array( 'Slovník pojmov', get_post_type_archive_link( 'mg_pojem' ) ),
			array( 'Zdroje a metodika', get_permalink( $page_ids['zdroje'] ) ),
		)
	);

	kses_init();
	// Archívy (/podvody/, /testy/…) potrebujú pekné adresy.
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	flush_rewrite_rules();
	update_option( 'mgt_demo_done', 1 );
}

/**
 * Menu podľa názvu (prepíše položky).
 */
function mgt_build_menu( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	$id   = $menu ? $menu->term_id : wp_create_nav_menu( $name );
	foreach ( (array) wp_get_nav_menu_items( $id ) as $it ) {
		wp_delete_post( $it->ID, true );
	}
	foreach ( $items as $i => $it ) {
		wp_update_nav_menu_item(
			$id,
			0,
			array(
				'menu-item-title'   => $it[0],
				'menu-item-url'     => $it[1],
				'menu-item-status'  => 'publish',
				'menu-item-type'    => 'custom',
				'menu-item-classes' => isset( $it[2] ) ? $it[2] : '',
				'menu-item-position'=> $i + 1,
			)
		);
	}
	$loc              = get_theme_mod( 'nav_menu_locations', array() );
	$loc[ $location ] = $id;
	set_theme_mod( 'nav_menu_locations', $loc );
}

/**
 * Odkazy {{url:slug}} a kotvy kariet.
 */
function mgt_replace_links( $content, $url_map, $card_slugs ) {
	$content = preg_replace_callback(
		'/\{\{url:([a-z0-9-]+)\}\}(#k\d+)?/',
		function ( $m ) use ( $url_map, $card_slugs ) {
			$url = isset( $url_map[ $m[1] ] ) ? $url_map[ $m[1] ] : home_url( '/' );
			if ( ! empty( $m[2] ) ) {
				$k    = substr( $m[2], 1 );
				$url .= '#' . ( isset( $card_slugs[ $k ] ) ? $card_slugs[ $k ] : $k );
			}
			return $url;
		},
		(string) $content
	);
	return str_replace( '{{pdf}}', MGT_URI . '/assets/pdf/rychle-karty.pdf', $content );
}

/**
 * Sekcie z dát webu → bloky editora. Nadpisy sekcií sú bežné bloky Nadpis a Odsek,
 * interaktívne nástroje bloky Shortcode.
 */
function mgt_sections_to_blocks( $sections, &$excerpt, $slug, $quiz_id ) {
	$out = array();
	foreach ( $sections as $si => $sec ) {
		$cols = array();
		foreach ( $sec['cols'] as $col ) {
			$cols[] = mgt_col_to_blocks( $col, $excerpt, $quiz_id );
		}
		$cols = array_values( array_filter( $cols, 'strlen' ) );
		if ( ! $cols ) {
			continue;
		}
		$anchor = ! empty( $sec['id'] ) ? $sec['id'] : '';
		$block  = '';
		if ( 1 === count( $cols ) ) {
			$block = $cols[0];
		} else {
			$block = "<!-- wp:columns {\"className\":\"mgt-cols\"} -->\n<div class=\"wp-block-columns mgt-cols\">";
			foreach ( $cols as $c ) {
				$block .= "<!-- wp:column -->\n<div class=\"wp-block-column\">" . $c . "</div>\n<!-- /wp:column -->";
			}
			$block .= "</div>\n<!-- /wp:columns -->";
		}
		$cls   = 'mgt-block-section' . ( isset( $sec['style'] ) && 'alt' === $sec['style'] ? ' mgt-alt' : '' );
		$attrs = array( 'className' => $cls );
		if ( $anchor ) {
			$attrs['anchor'] = $anchor;
		}
		$out[] = '<!-- wp:group ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . " -->\n<div" . ( $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '' ) . ' class="wp-block-group ' . esc_attr( $cls ) . '">' . $block . "</div>\n<!-- /wp:group -->";
	}
	return implode( "\n\n", $out );
}

function mgt_col_to_blocks( $col, &$excerpt, $quiz_id ) {
	$tags = array_keys( mgt_elements() );
	preg_match_all( '/' . get_shortcode_regex( $tags ) . '/s', $col, $m, PREG_SET_ORDER );
	$html = '';
	foreach ( $m as $sc ) {
		$tag  = $sc[2];
		$atts = shortcode_parse_atts( $sc[3] );
		$atts = is_array( $atts ) ? array_map(
			function ( $v ) {
				return html_entity_decode( $v, ENT_QUOTES, 'UTF-8' );
			},
			$atts
		) : array();
		if ( 'mg_hero' === $tag ) {
			if ( ! $excerpt && ! empty( $atts['lead'] ) ) {
				$excerpt = $atts['lead'];
			}
			continue;
		}
		if ( 'mg_quiz' === $tag ) {
			$html .= "<!-- wp:shortcode -->\n[mg_test id=\"" . (int) $quiz_id . "\"]\n<!-- /wp:shortcode -->\n";
			continue;
		}
		if ( 'mg_heading' === $tag ) {
			$html .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $atts['title'] ) . "</h2>\n<!-- /wp:heading -->\n";
			if ( ! empty( $atts['text'] ) ) {
				$html .= "<!-- wp:paragraph -->\n<p>" . esc_html( $atts['text'] ) . "</p>\n<!-- /wp:paragraph -->\n";
			}
			if ( ! empty( $atts['button_text'] ) ) {
				$html .= "<!-- wp:paragraph {\"className\":\"mgt-more\"} -->\n<p class=\"mgt-more\"><a class=\"mgt-link-arrow\" href=\"" . esc_attr( $atts['button_link'] ) . '">' . esc_html( $atts['button_text'] ) . "</a></p>\n<!-- /wp:paragraph -->\n";
			}
			continue;
		}
		$html .= "<!-- wp:shortcode -->\n" . $sc[0] . "\n<!-- /wp:shortcode -->\n";
	}
	return $html;
}
