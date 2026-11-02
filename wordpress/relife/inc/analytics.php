<?php
/**
 * Google tag (Google Analytics 4 / Google Tag Manager) s režimom súhlasu (Consent Mode v2).
 *
 * Nastavenie: Vzhľad → Prispôsobiť → RELIFE → Google tag.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Platné ID Google tagu (G-, GT-, AW- alebo GTM-), inak prázdny reťazec.
 */
function relife_google_tag_id(): string {
	$id = strtoupper( trim( (string) get_theme_mod( 'relife_google_tag_id', '' ) ) );
	return (bool) preg_match( '/^(G|GT|AW|GTM)-[A-Z0-9]{4,20}$/', $id ) ? $id : '';
}

/**
 * Má sa tag na tejto stránke vypísať?
 */
function relife_google_tag_enabled(): bool {
	if ( '' === relife_google_tag_id() || is_admin() || is_feed() || is_robots() || is_customize_preview() || is_preview() ) {
		return false;
	}
	if ( get_theme_mod( 'relife_google_tag_skip_admins', true ) && is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return false;
	}
	return (bool) apply_filters( 'relife_google_tag_enabled', true );
}

/**
 * Tag v <head>: najprv predvolený súhlas (odmietnutý), potom načítanie tagu.
 */
function relife_google_tag_head(): void {
	if ( ! relife_google_tag_enabled() ) {
		return;
	}
	$id        = relife_google_tag_id();
	$is_gtm    = str_starts_with( $id, 'GTM-' );
	$consent   = (bool) get_theme_mod( 'relife_google_tag_consent', true );
	$id_js     = wp_json_encode( $id );
	$url_param = rawurlencode( $id );

	echo "<!-- Google tag -->\n<script>\nwindow.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	if ( $consent ) {
		echo "gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',wait_for_update:500});\n";
		// Uložený súhlas návštevníka (nastavuje relifeConsent.grant()).
		echo "try{if(window.localStorage.getItem('relife.consent')==='granted'){gtag('consent','update',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted'})}}catch(e){}\n";
	}
	echo "window.relifeConsent={grant:function(){try{window.localStorage.setItem('relife.consent','granted')}catch(e){}gtag('consent','update',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted'})},deny:function(){try{window.localStorage.setItem('relife.consent','denied')}catch(e){}gtag('consent','update',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied'})}};\n";

	if ( $is_gtm ) {
		echo "dataLayer.push({'gtm.start':new Date().getTime(),event:'gtm.js'});\n</script>\n";
		echo '<script async src="https://www.googletagmanager.com/gtm.js?id=' . esc_attr( $url_param ) . "\"></script>\n";
	} else {
		echo "gtag('js',new Date());gtag('config'," . $id_js . ");\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $url_param ) . "\"></script>\n";
	}
	echo "<!-- /Google tag -->\n";
}
add_action( 'wp_head', 'relife_google_tag_head', 2 );

/**
 * Google Tag Manager: noscript záloha hneď za <body>.
 */
function relife_google_tag_noscript(): void {
	if ( ! relife_google_tag_enabled() ) {
		return;
	}
	$id = relife_google_tag_id();
	if ( ! str_starts_with( $id, 'GTM-' ) ) {
		return;
	}
	echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( rawurlencode( $id ) ) . '" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>' . "\n";
}
add_action( 'wp_body_open', 'relife_google_tag_noscript', 1 );

/**
 * Nastavenia v Customizeri.
 */
function relife_analytics_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_setting(
		'relife_google_tag_id',
		array(
			'default'           => '',
			'sanitize_callback' => static fn( $v ): string => strtoupper( sanitize_text_field( (string) $v ) ),
		)
	);
	$wp_customize->add_control(
		'relife_google_tag_id',
		array(
			'label'       => __( 'Google tag ID', 'relife' ),
			'description' => __( 'Napríklad G-XXXXXXXXXX (Google Analytics 4), GTM-XXXXXXX (Tag Manager) alebo AW-XXXXXXXXX. Prázdne = vypnuté.', 'relife' ),
			'section'     => 'relife_options',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting( 'relife_google_tag_consent', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp_customize->add_control(
		'relife_google_tag_consent',
		array(
			'label'       => __( 'Režim súhlasu (Consent Mode v2): predvolene odmietnuť', 'relife' ),
			'description' => __( 'Google tag sa načíta, ale nesleduje a neukladá cookies, kým návštevník nedá súhlas (cez váš cookie plugin alebo volanie relifeConsent.grant()).', 'relife' ),
			'section'     => 'relife_options',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting( 'relife_google_tag_skip_admins', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp_customize->add_control(
		'relife_google_tag_skip_admins',
		array(
			'label'   => __( 'Nesledovať prihlásených redaktorov a administrátorov', 'relife' ),
			'section' => 'relife_options',
			'type'    => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'relife_analytics_customize_register' );
