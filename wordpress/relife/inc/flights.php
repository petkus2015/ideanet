<?php
/**
 * Lacné letenky v téme RELIFE: načítanie zabudovaného bloku, stránka „Lacné letenky“ a odkazy.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Zabudovaný blok (shortcode [lacne_letenky], blok v editore, widget, Nastavenia → Lacné letenky).
require_once RELIFE_DIR . '/inc/letenky/letenky.php';

/**
 * Adresa stránky Lacné letenky alebo prázdny reťazec.
 */
function relife_flights_url(): string {
	$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'template-flights.php', 'number' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	return $pages ? (string) get_permalink( $pages[0] ) : '';
}

function relife_create_flights_page(): void {
	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'template-flights.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $existing ) {
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Lacné letenky',
			'post_name'    => 'lacne-letenky',
			'post_content' => "<!-- wp:shortcode -->\n[lacne_letenky]\n<!-- /wp:shortcode -->",
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( (int) $id, '_wp_page_template', 'template-flights.php' );
	}
}
add_action( 'after_switch_theme', 'relife_create_flights_page' );

function relife_maybe_create_flights_page(): void {
	if ( get_option( 'relife_flights_page_done' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	relife_create_flights_page();
	update_option( 'relife_flights_page_done', 1, false );
}
add_action( 'admin_init', 'relife_maybe_create_flights_page' );

/**
 * Štýl a skript bloku sa načítajú len tam, kde je blok/shortcode (to rieši samotný blok); na stránke Lacné letenky nič navyše.
 * Hlavný štýl témy sa vkladá inline, preto blok dostane vlastný súbor len keď je potrebný.
 */
