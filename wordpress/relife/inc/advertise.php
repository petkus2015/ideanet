<?php
/**
 * Stránka „Inzerujte u nás“: interaktívny graf, ponuka a kontaktný formulár.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dáta grafu z Customizeru: riadok „mesiac|hodnota“.
 *
 * @return array<int, array{label:string,value:int}>
 */
function relife_adv_data(): array {
	$default = "aug 2026|640\nsep 2026|990\nokt 2026|1500";
	$out     = array();
	foreach ( preg_split( '/\R/', (string) get_theme_mod( 'relife_adv_data', $default ) ) ?: array() as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( 2 === count( $parts ) && '' !== $parts[0] && is_numeric( $parts[1] ) ) {
			$out[] = array( 'label' => $parts[0], 'value' => (int) $parts[1] );
		}
	}
	return (array) apply_filters( 'relife_adv_data', $out );
}

function relife_adv_enqueue(): void {
	if ( is_page_template( 'template-advertise.php' ) ) {
		wp_enqueue_script( 'relife-advertise', RELIFE_URI . '/assets/js/advertise.js', array(), RELIFE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_adv_enqueue' );

/**
 * Spracovanie formulára (bez pluginu): kontrola, ochrana pred spamom, e-mail redakcii.
 */
function relife_adv_submit(): void {
	$back = wp_get_referer() ?: home_url( '/' );
	if ( ! isset( $_POST['relife_adv_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['relife_adv_nonce'] ) ), 'relife_adv' ) || ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( add_query_arg( 'adv', 'err', $back ) . '#kontakt' );
		exit;
	}
	$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$company = sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) );
	$topic   = sanitize_text_field( wp_unslash( $_POST['topic'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$key = 'relife_adv_' . md5( $ip );
	if ( '' === $name || ! is_email( $email ) || '' === $message || get_transient( $key ) ) {
		wp_safe_redirect( add_query_arg( 'adv', 'err', $back ) . '#kontakt' );
		exit;
	}
	set_transient( $key, 1, MINUTE_IN_SECONDS );

	$to = sanitize_email( (string) get_theme_mod( 'relife_adv_email', '' ) );
	$to = is_email( $to ) ? $to : (string) get_option( 'admin_email' );
	$ok = wp_mail(
		$to,
		sprintf( '[RELIFE inzercia] %s – %s', $topic ?: 'Dopyt', $name ),
		sprintf( "Meno: %s\nFirma: %s\nE-mail: %s\nZáujem: %s\n\n%s", $name, $company, $email, $topic, $message ),
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);
	wp_safe_redirect( add_query_arg( 'adv', $ok ? 'ok' : 'err', $back ) . '#kontakt' );
	exit;
}
add_action( 'admin_post_nopriv_relife_adv', 'relife_adv_submit' );
add_action( 'admin_post_relife_adv', 'relife_adv_submit' );

function relife_adv_customize( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section( 'relife_adv', array( 'title' => __( 'RELIFE – Inzercia', 'relife' ), 'priority' => 32 ) );

	$wp_customize->add_setting( 'relife_adv_data', array( 'default' => "aug 2026|640\nsep 2026|990\nokt 2026|1500", 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp_customize->add_control( 'relife_adv_data', array(
		'label'       => __( 'Údaje grafu (mesiac|hodnota)', 'relife' ),
		'description' => __( 'Jeden mesiac na riadok, napr. „nov 2026|2100“. Zobrazenia v generatívnych funkciách AI (Google Search Console).', 'relife' ),
		'section'     => 'relife_adv',
		'type'        => 'textarea',
	) );

	$wp_customize->add_setting( 'relife_adv_email', array( 'default' => '', 'sanitize_callback' => 'sanitize_email' ) );
	$wp_customize->add_control( 'relife_adv_email', array(
		'label'       => __( 'E-mail pre dopyty inzercie', 'relife' ),
		'description' => __( 'Kam sa pošle formulár. Ak je prázdne, použije sa e-mail správcu webu. Adresa sa na stránke nezobrazuje.', 'relife' ),
		'section'     => 'relife_adv',
		'type'        => 'email',
	) );
}
add_action( 'customize_register', 'relife_adv_customize' );

/**
 * Stránku „Inzerujte u nás“ vytvorí raz (pri aktivácii alebo po aktualizácii).
 */
function relife_create_adv_page(): void {
	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'template-advertise.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $existing ) {
		return;
	}
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Inzerujte u nás', 'post_name' => 'inzercia', 'post_content' => '' ) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( (int) $id, '_wp_page_template', 'template-advertise.php' );
	}
}
add_action( 'after_switch_theme', 'relife_create_adv_page' );

function relife_maybe_create_adv_page(): void {
	if ( get_option( 'relife_adv_page_done' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	relife_create_adv_page();
	update_option( 'relife_adv_page_done', 1, false );
}
add_action( 'admin_init', 'relife_maybe_create_adv_page' );

/**
 * Adresa stránky „Inzerujte u nás“ alebo prázdny reťazec, ak ešte neexistuje.
 */
function relife_adv_url(): string {
	$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'template-advertise.php', 'number' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	return $pages ? (string) get_permalink( $pages[0] ) : '';
}
