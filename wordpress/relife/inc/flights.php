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
// Ak je aktívny aj samostatný plugin (načíta sa pred témou), téma použije jeho kód a doplní len vzhľad RELIFE.
define( 'RELIFE_FLIGHTS_STANDALONE', defined( 'LACNE_LETENKY_VERSION' ) );
require_once RELIFE_DIR . '/inc/letenky/letenky.php';

/**
 * Pri samostatnom plugine: vzhľad RELIFE (farby sekcií a odletov) sa načíta po štýle pluginu.
 */
function relife_flights_skin(): void {
	if ( ! RELIFE_FLIGHTS_STANDALONE ) {
		return;
	}
	$post = get_post();
	$need = is_page_template( 'template-flights.php' )
		|| is_active_widget( false, false, 'lacne_letenky' )
		|| ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'lacne_letenky' ) );
	if ( $need ) {
		wp_enqueue_style( 'relife-letenky-skin', RELIFE_URI . '/inc/letenky/assets/letenky-skin.css', array( 'lacne-letenky' ), RELIFE_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_flights_skin', 30 );

/**
 * Upozornenie v administrácii: samostatný plugin nemá úzky widget, výber sekcií ani plný vzhľad RELIFE.
 */
function relife_flights_standalone_notice(): void {
	if ( ! RELIFE_FLIGHTS_STANDALONE || ! current_user_can( 'activate_plugins' ) || get_user_meta( get_current_user_id(), 'relife_flights_notice_off', true ) ) {
		return;
	}
	$dismiss = wp_nonce_url( add_query_arg( 'relife_flights_notice_off', '1' ), 'relife_flights_notice' );
	echo '<div class="notice notice-warning"><p><strong>RELIFE:</strong> ' . esc_html__( 'Je aktívny samostatný plugin „Lacné letenky“. Téma RELIFE má vlastnú vylepšenú kópiu (výber sekcií, úzky widget do bočného stĺpca, vzhľad RELIFE). Deaktivujte samostatný plugin v Pluginy – nastavenia aj ceny sa zachovajú.', 'relife' ) . ' <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Otvoriť Pluginy', 'relife' ) . '</a> · <a href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Skryť upozornenie', 'relife' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'relife_flights_standalone_notice' );

function relife_flights_notice_dismiss(): void {
	if ( isset( $_GET['relife_flights_notice_off'] ) && check_admin_referer( 'relife_flights_notice' ) ) {
		update_user_meta( get_current_user_id(), 'relife_flights_notice_off', 1 );
	}
}
add_action( 'admin_init', 'relife_flights_notice_dismiss' );

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

/**
 * Ľavý stĺpec článku (desktop) ako oblasť pre widgety: Vzhľad → Widgety → „Ľavý stĺpec článku“ (napr. widget Lacné letenky).
 */
function relife_register_sidebars(): void {
	register_sidebar(
		array(
			'id'            => 'relife-article-left',
			'name'          => __( 'Ľavý stĺpec článku', 'relife' ),
			'description'   => __( 'Zobrazí sa na desktope pod odporúčanými článkami v ľavom stĺpci článku. Vhodný je widget „Lacné letenky“.', 'relife' ),
			'before_widget' => '<section id="%1$s" class="rl-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<div class="rl-kicker rl-widget__title">',
			'after_title'   => '</div>',
		)
	);
}
add_action( 'widgets_init', 'relife_register_sidebars' );
