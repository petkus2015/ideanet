<?php
/**
 * Prispôsobiť — všetky texty, čísla a odkazy webu.
 *
 * Polia sú popísané v jednom poli a zaregistrované v cykle, aby sa nový
 * text pridával na jednom mieste a nie na troch.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Popis sekcií a polí v Prispôsobiť.
 *
 * Každé pole je: kľúč => array( popis, typ, nápoveda ).
 * Typ je text, area, url, email, number, check alebo image.
 *
 * @return array
 */
function ideanet_customizer_map() {
	$lines = __( 'Jedna položka na riadok.', 'ideanet' );
	$cols  = __( 'Jedna položka na riadok, stĺpce oddeľte zvislou čiarou |', 'ideanet' );

	return array(

		'brand' => array(
			'title'  => __( '1 · Značka a hlavička', 'ideanet' ),
			'fields' => array(
				'brand_photo'   => array( __( 'Fotka vedľa mena', 'ideanet' ), 'image', __( 'Štvorcová, aspoň 320 × 320 px.', 'ideanet' ) ),
				'brand_name'    => array( __( 'Meno a priezvisko', 'ideanet' ), 'text' ),
				'brand_co'      => array( __( 'Názov firmy — prvá časť', 'ideanet' ), 'text', __( 'Napríklad IDEA.', 'ideanet' ) ),
				'brand_co_bold' => array( __( 'Názov firmy — zvýraznená časť', 'ideanet' ), 'text', __( 'Napríklad NET.', 'ideanet' ) ),
				'nav_cta'       => array( __( 'Tlačidlo v hlavičke', 'ideanet' ), 'text' ),
				'skip_label'    => array( __( 'Odkaz pre čítačky obrazovky', 'ideanet' ), 'text' ),
			),
		),

		'hero' => array(
			'title'  => __( '2 · Úvodná obrazovka', 'ideanet' ),
			'fields' => array(
				'hero_eyebrow'  => array( __( 'Riadok nad nadpisom', 'ideanet' ), 'text' ),
				'hero_title'    => array( __( 'Nadpis — prvý riadok', 'ideanet' ), 'text' ),
				'hero_title_2'  => array( __( 'Nadpis — druhý riadok', 'ideanet' ), 'text' ),
				'hero_title_hl' => array( __( 'Nadpis — farebná časť', 'ideanet' ), 'text', __( 'Doplní sa na koniec druhého riadku.', 'ideanet' ) ),
				'hero_lead'     => array( __( 'Odsek pod nadpisom', 'ideanet' ), 'area' ),
				'hero_flow'     => array( __( 'Štyri kroky v úvode', 'ideanet' ), 'area', $lines ),
				'hero_cta1'     => array( __( 'Hlavné tlačidlo', 'ideanet' ), 'text' ),
				'hero_cta1_url' => array( __( 'Hlavné tlačidlo — odkaz', 'ideanet' ), 'text' ),
				'hero_cta2'     => array( __( 'Druhé tlačidlo', 'ideanet' ), 'text' ),
				'hero_cta2_url' => array( __( 'Druhé tlačidlo — odkaz', 'ideanet' ), 'text' ),
				'hero_chips'    => array( __( 'Štítky okolo telefónu', 'ideanet' ), 'area', $lines . ' ' . __( 'Najviac šesť.', 'ideanet' ) ),
				'hero_caption'  => array( __( 'Text na obrazovke telefónu', 'ideanet' ), 'text' ),
				'hero_video'    => array( __( 'Video v telefóne (MP4)', 'ideanet' ), 'video', __( 'Vertikál 9:16, krátka slučka bez zvuku. Prázdne = zostane animovaná obrazovka s tlačidlom. Okno sa otvorí na záložke Nahrať súbory — ak už súbor máte v knižnici, prepnite na Knižnica médií. Po výbere kliknite hore na Zverejniť.', 'ideanet' ) ),
				'hero_webm'     => array( __( 'Video v telefóne (WebM) — nepovinné', 'ideanet' ), 'video', __( 'Druhý formát pre staršie prehliadače.', 'ideanet' ) ),
				'hero_poster'   => array( __( 'Prvý obrázok videa', 'ideanet' ), 'image', __( 'Zobrazí sa, kým sa video načíta, a tiež ľuďom, ktorí majú vypnuté animácie.', 'ideanet' ) ),
				'hero_marquee'  => array( __( 'Bežiaci pás odborov', 'ideanet' ), 'text', __( 'Oddeľte čiarkami.', 'ideanet' ) ),
			),
		),

		'sekcie' => array(
			'title'  => __( '3 · Nadpisy sekcií', 'ideanet' ),
			'fields' => ideanet_customizer_sections_fields(),
		),

		'sluzby' => array(
			'title'  => __( '4 · Služby a „čo je v cene"', 'ideanet' ),
			'fields' => array(
				'vcene_title' => array( __( 'Nadpis bloku', 'ideanet' ), 'text' ),
				'vcene_note'  => array( __( 'Veta pod nadpisom', 'ideanet' ), 'text' ),
				'vcene_items' => array( __( 'Čo je vždy v cene', 'ideanet' ), 'area', $lines ),
			),
		),

		'prekoho' => array(
			'title'  => __( '5 · Pre koho', 'ideanet' ),
			'fields' => array(
				'prekoho_items' => array( __( 'Odbory', 'ideanet' ), 'area', __( 'Riadok: emoji | text', 'ideanet' ) ),
			),
		),

		'cennik' => array(
			'title'  => __( '6 · Cenník — záložky a texty', 'ideanet' ),
			'fields' => array(
				'tab_firmy'        => array( __( 'Záložka 1 — názov', 'ideanet' ), 'text' ),
				'tab_firmy_sub'    => array( __( 'Záložka 1 — podtitul', 'ideanet' ), 'text' ),
				'firmy_intro'      => array( __( 'Záložka 1 — úvodná veta', 'ideanet' ), 'area' ),
				'tab_agentury'     => array( __( 'Záložka 2 — názov', 'ideanet' ), 'text' ),
				'tab_agentury_sub' => array( __( 'Záložka 2 — podtitul', 'ideanet' ), 'text' ),
				'agentury_intro'   => array( __( 'Záložka 2 — úvodná veta', 'ideanet' ), 'area' ),
				'tab_eventy'       => array( __( 'Záložka 3 — názov', 'ideanet' ), 'text' ),
				'tab_eventy_sub'   => array( __( 'Záložka 3 — podtitul', 'ideanet' ), 'text' ),
				'eventy_intro'     => array( __( 'Záložka 3 — úvodná veta', 'ideanet' ), 'area' ),
				'ponuka_firmy_url'    => array( __( 'Ponuka pre firmy — odkaz', 'ideanet' ), 'text', __( 'Prázdne = odkaz sa nezobrazí.', 'ideanet' ) ),
				'ponuka_firmy_label'  => array( __( 'Ponuka pre firmy — názov tlačidla', 'ideanet' ), 'text' ),
				'ponuka_agentury_url'   => array( __( 'Ponuka pre agentúry — odkaz', 'ideanet' ), 'text' ),
				'ponuka_agentury_label' => array( __( 'Ponuka pre agentúry — názov tlačidla', 'ideanet' ), 'text' ),
			),
		),

		'cennik_firmy' => array(
			'title'  => __( '6a · Cenník — medzinadpisy pre firmy', 'ideanet' ),
			'fields' => array(
				'firmy_jednorazovo_title' => array( __( 'Jednorazová produkcia — nadpis', 'ideanet' ), 'text' ),
				'firmy_jednorazovo_note'  => array( __( 'Jednorazová produkcia — veta', 'ideanet' ), 'area' ),
				'firmy_podcast_title'     => array( __( 'Podcast — nadpis', 'ideanet' ), 'text' ),
				'firmy_podcast_note'      => array( __( 'Podcast — veta', 'ideanet' ), 'area' ),
				'firmy_podcast_foot'      => array( __( 'Podcast — poznámka pod zoznamom', 'ideanet' ), 'area' ),
				'firmy_vizualy_title'     => array( __( 'Vizuály — nadpis', 'ideanet' ), 'text' ),
				'firmy_vizualy_note'      => array( __( 'Vizuály — veta', 'ideanet' ), 'area' ),
				'firmy_vizualy_foot'      => array( __( 'Vizuály — poznámka pod zoznamom', 'ideanet' ), 'area' ),
				'addon_tag'   => array( __( 'Doplnok — štítok', 'ideanet' ), 'text' ),
				'addon_title' => array( __( 'Doplnok — nadpis', 'ideanet' ), 'text' ),
				'addon_text'  => array( __( 'Doplnok — text', 'ideanet' ), 'area' ),
				'addon_price' => array( __( 'Doplnok — cena', 'ideanet' ), 'text' ),
				'addon_unit'  => array( __( 'Doplnok — za čo', 'ideanet' ), 'text' ),
			),
		),

		'cennik_agentury' => array(
			'title'  => __( '6b · Cenník — agentúry', 'ideanet' ),
			'fields' => array(
				'partner_tag'   => array( __( 'Partner — štítok', 'ideanet' ), 'text' ),
				'partner_title' => array( __( 'Partner — nadpis', 'ideanet' ), 'text' ),
				'partner_text'  => array( __( 'Partner — text', 'ideanet' ), 'area' ),
				'partner_items' => array( __( 'Partner — odrážky', 'ideanet' ), 'area', $lines ),
				'partner_pre'   => array( __( 'Partner — slovo pred cenou', 'ideanet' ), 'text' ),
				'partner_price' => array( __( 'Partner — cena', 'ideanet' ), 'text' ),
				'partner_unit'  => array( __( 'Partner — za čo', 'ideanet' ), 'text' ),
				'partner_cta'   => array( __( 'Partner — tlačidlo', 'ideanet' ), 'text' ),
				'partner_note'  => array( __( 'Partner — poznámka', 'ideanet' ), 'area' ),
				'agentury_notes_title' => array( __( 'Dôležité — nadpis', 'ideanet' ), 'text' ),
				'agentury_notes'       => array( __( 'Dôležité — karty', 'ideanet' ), 'area', $cols ),
			),
		),

		'cennik_eventy' => array(
			'title'  => __( '6c · Cenník — eventy', 'ideanet' ),
			'fields' => array(
				'eventy_hour_title' => array( __( 'Hodina navyše — názov', 'ideanet' ), 'text' ),
				'eventy_hour_note'  => array( __( 'Hodina navyše — poznámka', 'ideanet' ), 'text' ),
				'eventy_hour_price' => array( __( 'Hodina navyše — cena', 'ideanet' ), 'text' ),
				'eventy_graf_title' => array( __( 'Grafika na event — nadpis', 'ideanet' ), 'text' ),
				'eventy_graf_note'  => array( __( 'Grafika na event — veta', 'ideanet' ), 'area' ),
				'eventy_foot'       => array( __( 'Poznámka na konci záložky', 'ideanet' ), 'area' ),
			),
		),

		'kalkulacka' => array(
			'title'  => __( '7 · Kalkulačka — ceny', 'ideanet' ),
			'fields' => array(
				'calc_first'       => array( __( 'Prvý Reel — cena v €', 'ideanet' ), 'number' ),
				'calc_first_hours' => array( __( 'Prvý Reel — hodín natáčania', 'ideanet' ), 'number' ),
				'calc_next'        => array( __( 'Každý ďalší Reel — cena v €', 'ideanet' ), 'number', __( 'Každý ďalší pridá aj jednu hodinu natáčania.', 'ideanet' ) ),
				'calc_half'        => array( __( 'Poldeň — cena v €', 'ideanet' ), 'number' ),
				'calc_half_reels'  => array( __( 'Poldeň — koľko Reels je v cene', 'ideanet' ), 'number' ),
				'calc_half_hours'  => array( __( 'Poldeň — počet hodín', 'ideanet' ), 'number' ),
				'calc_day'         => array( __( 'Celý deň — cena v €', 'ideanet' ), 'number' ),
				'calc_day_reels'   => array( __( 'Celý deň — koľko Reels je v cene', 'ideanet' ), 'number' ),
				'calc_day_hours'   => array( __( 'Celý deň — počet hodín', 'ideanet' ), 'number' ),
				'calc_km_rate'     => array( __( 'Doprava — € za km', 'ideanet' ), 'text', __( 'Počíta sa tam aj späť.', 'ideanet' ) ),
				'calc_free_cities' => array( __( 'Mestá bez poplatku za dopravu', 'ideanet' ), 'text' ),
				'calc_min'         => array( __( 'Najmenej Reels', 'ideanet' ), 'number' ),
				'calc_max'         => array( __( 'Najviac Reels', 'ideanet' ), 'number' ),
				'calc_start'       => array( __( 'Prednastavený počet Reels', 'ideanet' ), 'number' ),
				'calc_addons'      => array( __( 'Doplnky', 'ideanet' ), 'area', __( 'Riadok: názov | suma | typ. Typ je jednorazovo, percento alebo mesacne.', 'ideanet' ) ),
				'calc_tip_from'    => array( __( 'Od koľkých Reels ponúknuť mesačný balík', 'ideanet' ), 'number' ),
			),
		),

		'kalkulacka_texty' => array(
			'title'  => __( '7a · Kalkulačka — texty', 'ideanet' ),
			'fields' => array(
				'calc_reels_label'  => array( __( 'Popis posuvníka', 'ideanet' ), 'text' ),
				'calc_modes_label'  => array( __( 'Nadpis — rezervácia času', 'ideanet' ), 'text' ),
				'calc_mode_none'    => array( __( 'Možnosť — bez rezervácie', 'ideanet' ), 'text' ),
				'calc_mode_half'    => array( __( 'Možnosť — poldeň', 'ideanet' ), 'text' ),
				'calc_mode_day'     => array( __( 'Možnosť — celý deň', 'ideanet' ), 'text' ),
				'calc_modes_hint'   => array( __( 'Vysvetlenie pod možnosťami', 'ideanet' ), 'area' ),
				'calc_addons_label' => array( __( 'Nadpis — doplnky', 'ideanet' ), 'text' ),
				'calc_km_label'     => array( __( 'Popis poľa so vzdialenosťou', 'ideanet' ), 'text', __( 'Mestá tu bývajú v druhom páde („mimo Bratislavy"), preto sa nedopĺňajú samy — napíšte ich rovno do textu.', 'ideanet' ) ),
				'calc_km_hint'      => array( __( 'Vysvetlenie pod poľom', 'ideanet' ), 'area', __( '{mesta} a {sadzba} sa doplnia samy.', 'ideanet' ) ),
				'calc_out_label'    => array( __( 'Popis nad sumou', 'ideanet' ), 'text' ),
				'calc_unit'         => array( __( 'Text pod sumou', 'ideanet' ), 'text' ),
				'calc_tip'          => array( __( 'Tip pri väčšom objeme', 'ideanet' ), 'area' ),
				'calc_cta'          => array( __( 'Tlačidlo', 'ideanet' ), 'text' ),
				'calc_disc'         => array( __( 'Poznámka pod tlačidlom', 'ideanet' ), 'area' ),
			),
		),

		'podmienky' => array(
			'title'  => __( '8 · Podmienky — pätka', 'ideanet' ),
			'fields' => array(
				'podmienky_grab' => array( __( 'Veta nad odkazmi na ponuku', 'ideanet' ), 'area' ),
			),
		),

		'kontakt' => array(
			'title'  => __( '9 · Kontakt — e-mail, telefón, siete', 'ideanet' ),
			'fields' => array(
				'contact_email'      => array( __( 'E-mail', 'ideanet' ), 'email' ),
				'contact_phone'      => array( __( 'Telefón', 'ideanet' ), 'text' ),
				'contact_social'     => array( __( 'Prezývka na sieťach', 'ideanet' ), 'text' ),
				'contact_social_url' => array( __( 'Prezývka — odkaz', 'ideanet' ), 'text' ),
				'contact_facts'      => array( __( 'Tri fakty pod kontaktom', 'ideanet' ), 'area', $cols ),
			),
		),

		'formular' => array(
			'title'  => __( '9a · Formulár — kam chodia dopyty', 'ideanet' ),
			'fields' => array(
				'form_to'      => array( __( 'Kam chodia dopyty', 'ideanet' ), 'email', __( 'Prázdne = e-mail z kontaktu vyššie.', 'ideanet' ) ),
				'form_subject' => array( __( 'Predmet e-mailu', 'ideanet' ), 'text' ),
				'form_name'    => array( __( 'Popis poľa — meno', 'ideanet' ), 'text' ),
				'form_name_ph' => array( __( 'Náhľadový text — meno', 'ideanet' ), 'text' ),
				'form_email'   => array( __( 'Popis poľa — e-mail', 'ideanet' ), 'text' ),
				'form_email_ph' => array( __( 'Náhľadový text — e-mail', 'ideanet' ), 'text' ),
				'form_type'    => array( __( 'Popis poľa — o čo má záujem', 'ideanet' ), 'text' ),
				'form_msg'     => array( __( 'Popis poľa — detaily', 'ideanet' ), 'text' ),
				'form_msg_ph'  => array( __( 'Náhľadový text — detaily', 'ideanet' ), 'text' ),
				'form_submit'  => array( __( 'Tlačidlo', 'ideanet' ), 'text' ),
				'form_gdpr'    => array( __( 'Veta o spracovaní údajov', 'ideanet' ), 'area', __( 'Prázdne = nezobrazí sa.', 'ideanet' ) ),
				'form_ok'      => array( __( 'Správa po odoslaní', 'ideanet' ), 'area' ),
				'form_err'     => array( __( 'Správa pri chybe', 'ideanet' ), 'area', __( '{email} doplní vašu adresu.', 'ideanet' ) ),
			),
		),

		'siete' => array(
			'title'  => __( '10 · Sociálne siete', 'ideanet' ),
			'fields' => array(
				'social_instagram' => array( 'Instagram', 'text', __( 'Prázdne = ikona sa nezobrazí.', 'ideanet' ) ),
				'social_tiktok'    => array( 'TikTok', 'text' ),
				'social_youtube'   => array( 'YouTube', 'text' ),
				'social_facebook'  => array( 'Facebook', 'text' ),
			),
		),

		'pata' => array(
			'title'  => __( '11 · Pätička', 'ideanet' ),
			'fields' => array(
				'footer_text' => array( __( 'Text v pätičke', 'ideanet' ), 'area', __( '{rok} doplní aktuálny rok.', 'ideanet' ) ),
			),
		),
	);
}

/**
 * Nadpisy sekcií — pre každú rovnaká trojica polí.
 *
 * @return array
 */
function ideanet_customizer_sections_fields() {
	$sections = array(
		'portfolio'  => __( 'Portfólio', 'ideanet' ),
		'vizualy'    => __( 'Vizuály', 'ideanet' ),
		'sluzby'     => __( 'Služby', 'ideanet' ),
		'prekoho'    => __( 'Pre koho', 'ideanet' ),
		'proces'     => __( 'Proces', 'ideanet' ),
		'referencie' => __( 'Referencie', 'ideanet' ),
		'cennik'     => __( 'Cenník', 'ideanet' ),
		'podmienky'  => __( 'Podmienky', 'ideanet' ),
		'kontakt'    => __( 'Kontakt', 'ideanet' ),
	);

	$fields = array();

	foreach ( $sections as $slug => $name ) {
		$fields[ 'show_' . $slug ]    = array(
			/* translators: %s: názov sekcie. */
			sprintf( __( '%s — zobraziť sekciu', 'ideanet' ), $name ),
			'check',
		);
		$fields[ $slug . '_eyebrow' ] = array(
			/* translators: %s: názov sekcie. */
			sprintf( __( '%s — malý riadok', 'ideanet' ), $name ),
			'text',
		);
		$fields[ $slug . '_title' ]   = array(
			/* translators: %s: názov sekcie. */
			sprintf( __( '%s — nadpis', 'ideanet' ), $name ),
			'text',
		);
		$fields[ $slug . '_note' ]    = array(
			/* translators: %s: názov sekcie. */
			sprintf( __( '%s — veta vpravo', 'ideanet' ), $name ),
			'area',
		);
	}

	return $fields;
}

/**
 * Zaregistruje panel, sekcie a polia.
 *
 * @param WP_Customize_Manager $wp_customize Správca Prispôsobiť.
 */
function ideanet_customize_register( $wp_customize ) {
	$defaults = ideanet_defaults();

	$wp_customize->add_panel(
		'ideanet',
		array(
			'title'       => 'IDEANET',
			'description' => __( 'Všetky texty, ceny a odkazy webu. Zmeny vidíte hneď v náhľade vpravo.', 'ideanet' ),
			'priority'    => 20,
		)
	);

	$priority = 10;

	foreach ( ideanet_customizer_map() as $slug => $section ) {
		$wp_customize->add_section(
			'ideanet_' . $slug,
			array(
				'title'    => $section['title'],
				'panel'    => 'ideanet',
				'priority' => $priority,
			)
		);
		$priority += 10;

		foreach ( $section['fields'] as $key => $field ) {
			$label = $field[0];
			$type  = isset( $field[1] ) ? $field[1] : 'text';
			$hint  = isset( $field[2] ) ? $field[2] : '';
			$id    = 'ideanet_' . $key;

			$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
			if ( 'check' === $type ) {
				$default = true;
			}

			$wp_customize->add_setting(
				$id,
				array(
					'default'           => $default,
					'type'              => 'theme_mod',
					'transport'         => 'refresh',
					'sanitize_callback' => ideanet_sanitizer( $type ),
				)
			);

			$args = array(
				'label'       => $label,
				'section'     => 'ideanet_' . $slug,
				'description' => $hint,
			);

			if ( 'image' === $type ) {
				$wp_customize->add_control(
					new WP_Customize_Image_Control( $wp_customize, $id, $args )
				);
				continue;
			}

			if ( 'video' === $type ) {
				$args['mime_type'] = 'video';
				$wp_customize->add_control(
					new WP_Customize_Upload_Control( $wp_customize, $id, $args )
				);
				continue;
			}

			$map = array(
				'area'   => 'textarea',
				'url'    => 'url',
				'email'  => 'email',
				'number' => 'number',
				'check'  => 'checkbox',
			);

			$args['type'] = isset( $map[ $type ] ) ? $map[ $type ] : 'text';
			$wp_customize->add_control( $id, $args );
		}
	}

	/* Nadpisy a odsek v úvode sa prekreslia bez načítania celej stránky. */
	foreach ( array( 'hero_title', 'hero_title_2', 'hero_lead', 'brand_name' ) as $live ) {
		$setting = $wp_customize->get_setting( 'ideanet_' . $live );
		if ( $setting ) {
			$setting->transport = 'postMessage';
		}
	}

	$wp_customize->selective_refresh->add_partial(
		'ideanet_hero_lead',
		array(
			'selector'        => '.hero__copy .lead',
			'render_callback' => function () {
				return wp_kses( ideanet_opt( 'hero_lead' ), ideanet_kses() );
			},
		)
	);
}
add_action( 'customize_register', 'ideanet_customize_register' );

/**
 * Vráti čistiacu funkciu podľa typu poľa.
 *
 * @param string $type Typ poľa.
 * @return callable
 */
function ideanet_sanitizer( $type ) {
	switch ( $type ) {
		case 'email':
			return 'sanitize_email';
		case 'url':
			return 'esc_url_raw';
		case 'number':
			return 'ideanet_sanitize_number';
		case 'check':
			return 'ideanet_sanitize_check';
		case 'image':
		case 'video':
			return 'esc_url_raw';
		case 'area':
			return 'ideanet_sanitize_area';
		default:
			return 'ideanet_sanitize_text';
	}
}

/**
 * Krátky text — povolíme len jednoduché formátovanie.
 *
 * @param string $value Vstup.
 * @return string
 */
function ideanet_sanitize_text( $value ) {
	return wp_kses( (string) $value, ideanet_kses() );
}

/**
 * Viacriadkový text — to isté, ale zachová zalomenia.
 *
 * @param string $value Vstup.
 * @return string
 */
function ideanet_sanitize_area( $value ) {
	return wp_kses( (string) $value, ideanet_kses() );
}

/**
 * Číslo, ktoré môže mať desatinnú čiarku.
 *
 * @param string $value Vstup.
 * @return string
 */
function ideanet_sanitize_number( $value ) {
	$value = preg_replace( '/[^0-9,.\-]/', '', (string) $value );
	return (string) $value;
}

/**
 * Zaškrtávacie pole.
 *
 * @param mixed $value Vstup.
 * @return bool
 */
function ideanet_sanitize_check( $value ) {
	return (bool) $value;
}

/**
 * Živý náhľad vybraných textov.
 */
function ideanet_customize_preview_js() {
	wp_add_inline_script(
		'customize-preview',
		"(function($){
			wp.customize('ideanet_hero_title', function(v){ v.bind(function(t){ $('.hero h1 .l1').text(t); }); });
			wp.customize('ideanet_hero_title_2', function(v){ v.bind(function(t){ $('.hero h1 .l2').text(t+' '); }); });
			wp.customize('ideanet_brand_name', function(v){ v.bind(function(t){ $('.brand__name').text(t); }); });
		})(jQuery);"
	);
}
add_action( 'customize_preview_init', 'ideanet_customize_preview_js' );

/**
 * Ceruzky v náhľade.
 *
 * Panel má vyše sto polí a hľadať v ňom, kde sa mení konkrétna veta, je
 * otrava. WordPress vie k časti stránky priradiť ovládače — v náhľade sa
 * pri nej objaví ceruzka a kliknutím sa otvorí práve to pole.
 *
 * Časti sa tu vypisujú bez vlastného vykresľovania. Po zmene sa náhľad
 * načíta celý — o nič nejde, zmyslom je tá ceruzka.
 *
 * @return array<string,string[]> Výber v stránke => zoznam nastavení.
 */
function ideanet_edit_map() {
	$sekcie = array( 'portfolio', 'vizualy', 'sluzby', 'prekoho', 'proces', 'referencie', 'cennik', 'podmienky' );

	$map = array(
		'.nav .brand'       => array( 'brand_name', 'brand_co', 'brand_co_bold', 'brand_photo' ),
		'.nav__cta'         => array( 'nav_cta' ),

		'.hero .eyebrow'    => array( 'hero_eyebrow' ),
		'.hero h1'          => array( 'hero_title', 'hero_title_2', 'hero_title_hl' ),
		'.hero__flow'       => array( 'hero_flow' ),
		'.hero__cta'        => array( 'hero_cta1', 'hero_cta1_url', 'hero_cta2', 'hero_cta2_url' ),
		'.hero__visual'     => array( 'hero_video', 'hero_webm', 'hero_poster', 'hero_chips', 'hero_caption' ),
		'.marquee'          => array( 'hero_marquee' ),

		'.incl'             => array( 'vcene_title', 'vcene_note', 'vcene_items' ),
		'.segments'         => array( 'prekoho_items' ),

		'.tabs__list'       => array( 'tab_firmy', 'tab_firmy_sub', 'tab_agentury', 'tab_agentury_sub', 'tab_eventy', 'tab_eventy_sub' ),
		'#panel-firmy .panel__intro'    => array( 'firmy_intro' ),
		'#panel-agentury .panel__intro' => array( 'agentury_intro' ),
		'#panel-eventy .panel__intro'   => array( 'eventy_intro' ),
		'.addon'            => array( 'addon_tag', 'addon_title', 'addon_text', 'addon_price', 'addon_unit' ),
		'.partner'          => array( 'partner_tag', 'partner_title', 'partner_text', 'partner_items', 'partner_pre', 'partner_price', 'partner_unit', 'partner_cta', 'partner_note' ),
		'.notes'            => array( 'agentury_notes_title', 'agentury_notes' ),
		'.tiers--tight'     => array( 'eventy_hour_title', 'eventy_hour_note', 'eventy_hour_price' ),

		'.calc__form .modes'  => array( 'calc_modes_label', 'calc_mode_none', 'calc_mode_half', 'calc_mode_day', 'calc_modes_hint' ),
		'.calc__form .addons' => array( 'calc_addons_label', 'calc_addons' ),
		'.calc__out'          => array( 'calc_out_label', 'calc_unit', 'calc_cta', 'calc_disc', 'calc_tip' ),
		'.stepper'            => array( 'calc_reels_label', 'calc_first', 'calc_next', 'calc_min', 'calc_max', 'calc_start' ),

		'.grab'             => array( 'podmienky_grab', 'ponuka_firmy_url', 'ponuka_firmy_label', 'ponuka_agentury_url', 'ponuka_agentury_label' ),

		'.contact__copy'    => array( 'kontakt_eyebrow', 'kontakt_title', 'kontakt_note', 'contact_email', 'contact_phone', 'contact_social', 'contact_social_url', 'contact_facts' ),
		'#contactForm'      => array( 'form_name', 'form_name_ph', 'form_email', 'form_email_ph', 'form_type', 'form_msg', 'form_msg_ph', 'form_submit', 'form_gdpr', 'form_to', 'form_subject', 'form_ok', 'form_err' ),

		'.footer__inner p'  => array( 'footer_text' ),
		'.footer .socials'  => array( 'social_instagram', 'social_tiktok', 'social_youtube', 'social_facebook' ),
	);

	/* hlavičky sekcií — pri každej tie isté tri polia */
	foreach ( $sekcie as $slug ) {
		$map[ '#' . $slug . ' .section__head' ] = array( $slug . '_eyebrow', $slug . '_title', $slug . '_note' );
	}

	/* medzinadpisy v cenníku si nesú kľúč so sebou */
	$bloky = array(
		'firmy_jednorazovo_title' => array( 'firmy_jednorazovo_title', 'firmy_jednorazovo_note' ),
		'firmy_podcast_title'     => array( 'firmy_podcast_title', 'firmy_podcast_note', 'firmy_podcast_foot' ),
		'firmy_vizualy_title'     => array( 'firmy_vizualy_title', 'firmy_vizualy_note', 'firmy_vizualy_foot' ),
		'eventy_graf_title'       => array( 'eventy_graf_title', 'eventy_graf_note' ),
	);
	foreach ( $bloky as $kluc => $polia ) {
		$map[ '[data-ideanet-edit="' . $kluc . '"]' ] = $polia;
	}

	return $map;
}

/**
 * Zaregistruje tie časti.
 *
 * @param WP_Customize_Manager $wp_customize Správca Prispôsobiť.
 */
function ideanet_register_partials( $wp_customize ) {
	if ( ! isset( $wp_customize->selective_refresh ) ) {
		return;
	}

	$i = 0;
	foreach ( ideanet_edit_map() as $vyber => $polia ) {
		$nastavenia = array();
		foreach ( $polia as $pole ) {
			$id = 'ideanet_' . $pole;
			if ( $wp_customize->get_setting( $id ) ) {
				$nastavenia[] = $id;
			}
		}

		if ( ! $nastavenia ) {
			continue;
		}

		$wp_customize->selective_refresh->add_partial(
			'ideanet_edit_' . ( ++$i ),
			array(
				'selector'            => $vyber,
				'settings'            => $nastavenia,
				'container_inclusive' => false,
				'fallback_refresh'    => true,
			)
		);
	}
}
add_action( 'customize_register', 'ideanet_register_partials', 20 );
