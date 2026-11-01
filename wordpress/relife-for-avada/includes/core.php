<?php
/**
 * Základ: nastavenia, nálady, assety.
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nastavenia pluginu s predvolenými hodnotami.
 *
 * @return array<string, mixed>
 */
function rlav_options(): array {
	$defaults = array(
		'accent'            => '#3CFFD0',
		'tabbar'            => 1,
		'fonts'             => 1,
		'schema'            => 1,
		'single'            => 1,
		'dark_avada'        => 0,
		'newsletter_action' => '',
		'saved_page'        => 0,
		'polls_page'        => 0,
	);
	$saved    = get_option( 'rlav_options', array() );
	return array_merge( $defaults, is_array( $saved ) ? $saved : array() );
}

function rlav_option( string $key ): mixed {
	$o = rlav_options();
	return $o[ $key ] ?? null;
}

/**
 * Nálady a im priradené sekcie (slugy kategórií). Filtrovateľné.
 *
 * @return array<int, array{id:string,label:string,sections:array<int,string>}>
 */
function rlav_moods(): array {
	$moods = array(
		array( 'id' => 'curious', 'label' => __( 'Zvedavý', 'relife-avada' ), 'sections' => array( 'tech', 'trendy' ) ),
		array( 'id' => 'tired',   'label' => __( 'Unavený', 'relife-avada' ), 'sections' => array( 'health', 'life' ) ),
		array( 'id' => 'driven',  'label' => __( 'Ambiciózny', 'relife-avada' ), 'sections' => array( 'money', 'tech' ) ),
		array( 'id' => 'escape',  'label' => __( 'Túžim po úniku', 'relife-avada' ), 'sections' => array( 'travel', 'style' ) ),
		array( 'id' => 'calm',    'label' => __( 'Chcem pokoj', 'relife-avada' ), 'sections' => array( 'life', 'health' ) ),
	);
	/** @var array<int, array{id:string,label:string,sections:array<int,string>}> $moods */
	$moods = apply_filters( 'rlav_moods', $moods );
	return $moods;
}

/**
 * URL stránky podľa kľúča nastavenia (saved | polls). Bez nastavenia vráti domovskú adresu.
 */
function rlav_page_url( string $key ): string {
	$id = (int) rlav_option( $key . '_page' );
	if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
		return (string) get_permalink( $id );
	}
	return home_url( '/' );
}

function rlav_accent_css(): string {
	$accent = sanitize_hex_color( (string) rlav_option( 'accent' ) );
	if ( null === $accent || '' === $accent ) {
		$accent = '#3CFFD0';
	}
	return ':root{--rl-accent:' . $accent . ';}';
}

/**
 * Dark režim pre Avadu (voliteľný, najlepšie doladiť v Avada → Options → Colors).
 */
function rlav_dark_avada_css(): string {
	return 'body.rlav-dark,body.rlav-dark #boxed-wrapper,body.rlav-dark #wrapper,body.rlav-dark #main,body.rlav-dark .fusion-page-title-bar,body.rlav-dark .fusion-footer-widget-area,body.rlav-dark .fusion-footer-copyright-area,body.rlav-dark .fusion-header-wrapper,body.rlav-dark .fusion-header,body.rlav-dark .fusion-secondary-header{background-color:#131313 !important;color:#F5F5F2}'
		. 'body.rlav-dark h1,body.rlav-dark h2,body.rlav-dark h3,body.rlav-dark h4,body.rlav-dark h5,body.rlav-dark h6,body.rlav-dark .fusion-main-menu>ul>li>a,body.rlav-dark .fusion-page-title-bar h1{color:#F5F5F2 !important}'
		. 'body.rlav-dark p,body.rlav-dark li,body.rlav-dark .post-content,body.rlav-dark .fusion-footer-widget-area{color:#E6E6E6}'
		. 'body.rlav-dark .fusion-main-menu a:hover,body.rlav-dark #main a:hover{color:var(--rl-accent)}';
}

function rlav_enqueue_assets(): void {
	$o = rlav_options();
	if ( ! empty( $o['fonts'] ) && apply_filters( 'rlav_load_google_fonts', true ) ) {
		wp_enqueue_style(
			'rlav-fonts',
			'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,800&family=Inter:wght@400;500;600;700&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		);
	}
	wp_enqueue_style( 'rlav', RLAV_URI . 'assets/css/relife-avada.css', array(), RLAV_VERSION );
	$inline = rlav_accent_css();
	if ( ! empty( $o['dark_avada'] ) ) {
		$inline .= rlav_dark_avada_css();
	}
	wp_add_inline_style( 'rlav', $inline );

	wp_enqueue_script( 'rlav', RLAV_URI . 'assets/js/relife-avada.js', array(), RLAV_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'rlav',
		'relifeData',
		array(
			'restUrl'  => esc_url_raw( rest_url( 'wp/v2/posts' ) ),
			'savedUrl' => esc_url_raw( rlav_page_url( 'saved' ) ),
			'moods'    => rlav_moods(),
			'i18n'     => array(
				'save'       => __( 'Uložiť článok', 'relife-avada' ),
				'unsave'     => __( 'Odstrániť z uložených', 'relife-avada' ),
				'thanks'     => __( 'Ďakujeme za hlas. Tvoj hlas sa ukladá len v tvojom prehliadači.', 'relife-avada' ),
				'beforeVote' => __( 'Výsledky sa zobrazia po tvojom hlasovaní.', 'relife-avada' ),
				'empty'      => __( 'Zatiaľ nemáš nič uložené. Klepni na záložku pri článku.', 'relife-avada' ),
				'moodNow'    => __( 'Podľa tvojej nálady:', 'relife-avada' ),
				'moodNone'   => __( 'Pre túto náladu zatiaľ nemáme dosť článkov.', 'relife-avada' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'rlav_enqueue_assets' );

function rlav_body_class( array $classes ): array {
	if ( ! empty( rlav_option( 'dark_avada' ) ) ) {
		$classes[] = 'rlav-dark';
	}
	if ( ! empty( rlav_option( 'tabbar' ) ) ) {
		$classes[] = 'rlav-has-tabbar';
	}
	return $classes;
}
add_filter( 'body_class', 'rlav_body_class' );
