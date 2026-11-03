<?php
/**
 * Výkon: Core Web Vitals (LCP, Speed Index, CLS, INP).
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lokálne písma (bez požiadavky na Google Fonts, ktorá blokovala vykreslenie) + preload hlavných súborov.
 * Tiež sa nahradí font-face zo štýlu: @font-face sa vloží priamo do <head>.
 */
function relife_fonts_css(): string {
	$file = RELIFE_DIR . '/assets/fonts/fonts.css';
	if ( ! is_readable( $file ) ) {
		return '';
	}
	$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return str_replace( 'url(', 'url(' . RELIFE_URI . '/assets/fonts/', $css );
}

function relife_font_preloads(): void {
	foreach ( array( 'inter-latin.woff2', 'bricolage-latin.woff2' ) as $f ) {
		echo '<link rel="preload" href="' . esc_url( RELIFE_URI . '/assets/fonts/' . $f ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}
add_action( 'wp_head', 'relife_font_preloads', 3 );

/**
 * Preload LCP obrázka (hero na úvodnej stránke, titulný obrázok v článku) so správnym srcset a sizes.
 */
function relife_preload_lcp_image(): void {
	$post = null;
	if ( is_front_page() && function_exists( 'relife_hero_post' ) ) {
		$post = relife_hero_post();
	} elseif ( is_singular( 'post' ) ) {
		$post = get_post();
	}
	if ( ! $post instanceof WP_Post || ! has_post_thumbnail( $post ) ) {
		return;
	}
	$id    = (int) get_post_thumbnail_id( $post );
	$src   = wp_get_attachment_image_url( $id, 'relife-hero' );
	$set   = wp_get_attachment_image_srcset( $id, 'relife-hero' );
	$sizes = is_front_page() ? '(max-width: 900px) 100vw, 800px' : '(max-width: 800px) 100vw, 1100px';
	if ( ! $src ) {
		return;
	}
	echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '"';
	if ( $set ) {
		echo ' imagesrcset="' . esc_attr( $set ) . '" imagesizes="' . esc_attr( $sizes ) . '"';
	}
	echo ' fetchpriority="high">' . "\n";
}
add_action( 'wp_head', 'relife_preload_lcp_image', 4 );

/**
 * Správny atribút sizes pre hero a mensšiu mobilnú veľkosť.
 *
 * @param array<string, mixed> $attr
 * @return array<string, mixed>
 */
function relife_hero_image_sizes( array $attr, WP_Post $attachment, $size ): array {
	if ( 'relife-hero' === $size && ! empty( $attr['fetchpriority'] ) ) {
		$attr['sizes'] = is_front_page() ? '(max-width: 900px) 100vw, 800px' : '(max-width: 800px) 100vw, 1100px';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'relife_hero_image_sizes', 20, 3 );

/**
 * Menej zbytočného z WordPressu na verejnej časti: emoji, wp-embed, RSD, shortlink, generator, dashicons, blokové štýly mimo článkov.
 */
function relife_trim_wp_head(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}
add_action( 'init', 'relife_trim_wp_head' );

function relife_dequeue_assets(): void {
	if ( ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}
	wp_dequeue_style( 'classic-theme-styles' );
	if ( ! is_singular() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_dequeue_assets', 100 );

/**
 * Šablóny štýlov, ktoré nie sú potrebné hneď, sa nenačítavajú blokujúco: komentáre až v článku.
 */
function relife_dequeue_comment_script(): void {
	if ( ! is_singular() || ! comments_open() || ! get_option( 'thread_comments' ) ) {
		wp_dequeue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_dequeue_comment_script', 100 );

/**
 * Hlavičky pre prehliadač: 103 Early Hints nie sú v PHP dostupné, preto aspoň dlhá cache pre statické súbory téme nadáva server (.htaccess ukážka v README).
 */
