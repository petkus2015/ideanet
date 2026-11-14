<?php
/**
 * Nastavenia v Customizeri.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upraviteľné texty úvodnej stránky: kľúč => [popis v Customizeri, predvolený text].
 *
 * @return array<string, array{0:string,1:string}>
 */
function relife_home_texts(): array {
	return array(
		'hero_badge'    => array( 'Hlavný článok: štítok', 'VÝBER REDAKCIE' ),
		'quick_title'   => array( 'Quick Take: nadpis', 'QUICK TAKE' ),
		'quick_note'    => array( 'Quick Take: popis', 'Najnovšie za 60 sekúnd' ),
		'today_title'   => array( 'Aktuálne články: nadpis', 'Čo dnes stojí za tvoju pozornosť?' ),
		'today_mood'    => array( 'Aktuálne články: nadpis pri vybranej nálade', 'Pre tvoju náladu' ),
		'today_link'    => array( 'Aktuálne články: odkaz „všetky“', 'Všetky články →' ),
		'explained_title' => array( 'Vysvetlené: nadpis', 'Vysvetlené jednoducho' ),
		'explained_lead'  => array( 'Vysvetlené: popis', 'Formáty Explained a How to: odpovede na otázky, ktoré skutočne hľadáš.' ),
		'pillars_title' => array( 'Tri stĺpce: nadpis', 'Vedieť viac. Žiť lepšie.' ),
		'pillars_note'  => array( 'Tri stĺpce: popis', 'Tri hlavné témy RELIFE.' ),
		'versus_badge'  => array( 'Versus: štítok', 'VERSUS' ),
		'versus_button' => array( 'Versus: text tlačidla', 'Prečítať porovnanie →' ),
		'tiles_title'   => array( 'Dlaždice sekcií: nadpis', 'Objav svoj ďalší krok' ),
		'around_title'  => array( 'Štyri sekcie: nadpis', 'Okolo sveta a okolo teba' ),
		'formats_title' => array( 'Formáty: nadpis', 'Šesť formátov. Vždy vieš, čo dostaneš.' ),
		'guides_title'  => array( 'Ultimate Guides: nadpis', 'Ultimate Guides' ),
	);
}

/**
 * Text úvodnej stránky (z Customizeru, inak predvolený).
 */
function relife_home_text( string $key ): string {
	$all     = relife_home_texts();
	$default = $all[ $key ][1] ?? '';
	$val     = trim( (string) get_theme_mod( 'relife_t_' . $key, '' ) );
	return '' !== $val ? $val : $default;
}

function relife_versus_default( string $which ): string {
	return 'a' === $which
		? "ETF\nRIZIKO | Vyššie, hodnota kolíše\nČASOVÝ HORIZONT | Skôr roky než mesiace\nHODÍ SA NA | Dlhodobé ciele"
		: "Sporiaci účet\nRIZIKO | Nízke\nČASOVÝ HORIZONT | Kedykoľvek\nHODÍ SA NA | Rezervu a krátke ciele";
}

/**
 * Karta porovnania Versus: prvý riadok je názov, ďalšie „POPIS | hodnota“.
 *
 * @return array{title:string,rows:array<int,array{0:string,1:string}>}
 */
function relife_versus_card( string $which ): array {
	$raw   = trim( (string) get_theme_mod( 'relife_versus_' . $which, '' ) );
	$raw   = '' !== $raw ? $raw : relife_versus_default( $which );
	$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\R/', $raw ) ?: array() ), static fn( string $l ): bool => '' !== $l ) );
	$title = (string) array_shift( $lines );
	$rows  = array();
	foreach ( $lines as $l ) {
		$p      = array_map( 'trim', explode( '|', $l, 2 ) );
		$rows[] = array( $p[0], $p[1] ?? '' );
	}
	return array( 'title' => $title, 'rows' => $rows );
}

/**
 * Článok v sekcii Versus: vybraný v Customizeri, inak najnovší vo formáte Versus.
 *
 * @return int[]
 */
function relife_versus_posts(): array {
	$chosen = (int) get_theme_mod( 'relife_versus_post', 0 );
	if ( $chosen > 0 && 'publish' === get_post_status( $chosen ) ) {
		return array( $chosen );
	}
	return relife_posts_by_format( 'versus', 1 );
}

function relife_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'relife_options',
		array(
			'title'    => __( 'RELIFE', 'relife' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'relife_accent',
		array(
			'default'           => '#3CFFD0',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'relife_accent',
			array(
				'label'   => __( 'Akcentová farba', 'relife' ),
				'section' => 'relife_options',
			)
		)
	);

	$wp_customize->add_setting(
		'relife_logo_height',
		array(
			'default'           => 72,
			'sanitize_callback' => static fn( $v ): int => max( 24, min( 140, (int) $v ) ),
		)
	);
	$wp_customize->add_control(
		'relife_logo_height',
		array(
			'label'       => __( 'Maximálna výška loga (px)', 'relife' ),
			'description' => __( 'Nahrané logo sa prispôsobí tejto výške, šírka sa dopočíta. Na mobile je výška o štvrtinu menšia.', 'relife' ),
			'section'     => 'relife_options',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 24, 'max' => 140, 'step' => 2 ),
		)
	);

	$wp_customize->add_setting(
		'relife_footer_logo',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'relife_footer_logo',
			array(
				'label'       => __( 'Logo v pätičke', 'relife' ),
				'description' => __( 'Ak nie je vybrané, v pätičke sa zobrazí text RELIFE a heslo.', 'relife' ),
				'section'     => 'relife_options',
				'mime_type'   => 'image',
			)
		)
	);

	$wp_customize->add_setting(
		'relife_footer_logo_height',
		array(
			'default'           => 56,
			'sanitize_callback' => static fn( $v ): int => max( 24, min( 140, (int) $v ) ),
		)
	);
	$wp_customize->add_control(
		'relife_footer_logo_height',
		array(
			'label'       => __( 'Maximálna výška loga v pätičke (px)', 'relife' ),
			'section'     => 'relife_options',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 24, 'max' => 140, 'step' => 2 ),
		)
	);

	$wp_customize->add_setting(
		'relife_newsletter_action',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'relife_newsletter_action',
		array(
			'label'       => __( 'Adresa formulára newslettra (action)', 'relife' ),
			'description' => __( 'URL, na ktorú sa odošle e-mail (napr. z vašej e-mailovej služby).', 'relife' ),
			'section'     => 'relife_options',
			'type'        => 'url',
		)
	);

	// Úvodná stránka: ktoré kategórie sa zobrazia v troch pilieroch „Vedieť viac. Žiť lepšie.“.
	$cats    = array( 0 => __( 'Automaticky (predvolená sekcia RELIFE)', 'relife' ) );
	foreach ( get_categories( array( 'hide_empty' => false ) ) as $cat ) {
		$cats[ (int) $cat->term_id ] = sprintf( '%s (%d)', $cat->name, (int) $cat->count );
	}
	$wp_customize->add_section(
		'relife_home',
		array(
			'title'       => __( 'RELIFE – Úvodná stránka', 'relife' ),
			'description' => __( 'Vyberte kategórie, ktoré sa zobrazia v sekcii „Vedieť viac. Žiť lepšie.“ na úvodnej stránke. Číslo v zátvorke je počet článkov.', 'relife' ),
			'priority'    => 29,
		)
	);
	foreach ( array( 1 => 'TECH', 2 => 'HEALTH', 3 => 'MONEY' ) as $i => $default_name ) {
		$wp_customize->add_setting( 'relife_pillar_' . $i, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
		$wp_customize->add_control(
			'relife_pillar_' . $i,
			array(
				/* translators: 1: pillar number, 2: default section */
				'label'   => sprintf( __( 'Stĺpec %1$d (predvolene %2$s)', 'relife' ), $i, $default_name ),
				'section' => 'relife_home',
				'type'    => 'select',
				'choices' => $cats,
			)
		);
	}

	// Texty sekcií úvodnej stránky.
	foreach ( relife_home_texts() as $key => $def ) {
		$wp_customize->add_setting( 'relife_t_' . $key, array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control(
			'relife_t_' . $key,
			array(
				'label'       => $def[0],
				'description' => __( 'Prázdne = predvolený text:', 'relife' ) . ' ' . $def[1],
				'section'     => 'relife_home',
				'type'        => 'text',
			)
		);
	}

	// Versus: výber článku a obsah dvoch kariet.
	$vposts = array( 0 => __( 'Automaticky (najnovší článok vo formáte Versus)', 'relife' ) );
	$vq     = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 120,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		)
	);
	foreach ( $vq->posts as $vp ) {
		$vposts[ (int) $vp->ID ] = html_entity_decode( get_the_title( $vp ), ENT_QUOTES, 'UTF-8' );
	}
	$wp_customize->add_setting( 'relife_versus_post', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control(
		'relife_versus_post',
		array(
			'label'       => __( 'Versus: článok v sekcii', 'relife' ),
			'description' => __( 'Zoznam obsahuje 120 najnovších článkov.', 'relife' ),
			'section'     => 'relife_home',
			'type'        => 'select',
			'choices'     => $vposts,
		)
	);
	foreach ( array( 'a' => 'A', 'b' => 'B' ) as $w => $label ) {
		$wp_customize->add_setting( 'relife_versus_' . $w, array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
		$wp_customize->add_control(
			'relife_versus_' . $w,
			array(
				/* translators: %s: A or B */
				'label'       => sprintf( __( 'Versus: karta %s', 'relife' ), $label ),
				'description' => __( 'Prvý riadok je názov, ďalšie riadky „POPIS | hodnota“. Prázdne = ukážka:', 'relife' ) . ' ' . str_replace( "\n", ' / ', relife_versus_default( $w ) ),
				'section'     => 'relife_home',
				'type'        => 'textarea',
			)
		);
	}

	$wp_customize->add_setting(
		'relife_tagline_footer',
		array(
			'default'           => 'Digitálny magazín pre generáciu, ktorá chce vedieť viac, žiť lepšie a byť o krok vpred.',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'relife_tagline_footer',
		array(
			'label'   => __( 'Text v pätičke', 'relife' ),
			'section' => 'relife_options',
			'type'    => 'textarea',
		)
	);
}
add_action( 'customize_register', 'relife_customize_register' );
