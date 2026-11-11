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
