<?php
/**
 * Štatistika uložených článkov: anonymné počítadlo (bez osobných údajov) a miniaplikácia na Nástenke.
 *
 * Čitateľ si články ukladá v prehliadači (localStorage). Pri uložení/odstránení prehliadač pošle na server len ID
 * článku a smer (+1/−1). Server si nepamätá, kto čo uložil; počet je preto orientačný.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const RELIFE_SAVES_META = 'relife_saves';

function relife_register_saves_route(): void {
	register_rest_route(
		'relife/v1',
		'/save',
		array(
			'methods'             => 'POST',
			'callback'            => 'relife_rest_save',
			'permission_callback' => '__return_true',
			'args'                => array(
				'id'     => array( 'required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint' ),
				'action' => array( 'required' => true, 'type' => 'string', 'enum' => array( 'save', 'unsave' ) ),
			),
		)
	);
}
add_action( 'rest_api_init', 'relife_register_saves_route' );

/**
 * @return WP_REST_Response|WP_Error
 */
function relife_rest_save( WP_REST_Request $req ) {
	$id   = (int) $req['id'];
	$post = get_post( $id );
	if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return new WP_Error( 'relife_bad_post', 'Neplatný článok.', array( 'status' => 404 ) );
	}

	// Ochrana pred zneužitím: nanajvýš 1 zmena za 5 s na článok a 60 zmien za hodinu na zariadenie (IP bez ukladania).
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$h    = md5( $ip . wp_salt( 'nonce' ) );
	$k1   = 'rl_sv_' . $h . '_' . $id;
	$k2   = 'rl_svh_' . $h;
	$hour = (int) get_transient( $k2 );
	if ( get_transient( $k1 ) || $hour >= 60 ) {
		return new WP_REST_Response( array( 'ok' => false ), 429 );
	}
	set_transient( $k1, 1, 5 );
	set_transient( $k2, $hour + 1, HOUR_IN_SECONDS );

	$n = (int) get_post_meta( $id, RELIFE_SAVES_META, true );
	$n = 'save' === $req['action'] ? $n + 1 : max( 0, $n - 1 );
	update_post_meta( $id, RELIFE_SAVES_META, $n );

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Miniaplikácia na Nástenke: TOP 15 najčastejšie ukladaných článkov.
 */
function relife_saves_dashboard_setup(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	wp_add_dashboard_widget( 'relife_saves_widget', __( 'RELIFE – Najviac ukladané články (TOP 15)', 'relife' ), 'relife_saves_dashboard_widget' );
}
add_action( 'wp_dashboard_setup', 'relife_saves_dashboard_setup' );

function relife_saves_dashboard_widget(): void {
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 15,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_key'            => RELIFE_SAVES_META, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value_num'      => 0, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_compare'        => '>',
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
		)
	);
	if ( ! $q->have_posts() ) {
		echo '<p>' . esc_html__( 'Zatiaľ nikto nič neuložil. Počítanie sa začalo spustením tejto verzie témy; hodnoty pribúdajú, keď čitatelia kliknú na záložku pri článku.', 'relife' ) . '</p>';
		return;
	}
	$max = 1;
	$rows = array();
	foreach ( $q->posts as $p ) {
		$n      = (int) get_post_meta( $p->ID, RELIFE_SAVES_META, true );
		$max    = max( $max, $n );
		$rows[] = array( 'post' => $p, 'n' => $n );
	}
	echo '<style>.rl-top{width:100%;border-collapse:collapse}.rl-top td{padding:6px 4px;border-bottom:1px solid #f0f0f1;vertical-align:middle}.rl-top .n{width:24px;color:#787c82;font-weight:600}.rl-top .c{width:44px;text-align:right;font-weight:700}.rl-top .bar{height:6px;background:#2271b1;border-radius:3px;margin-top:4px}.rl-top small{color:#787c82}</style>';
	echo '<table class="rl-top">';
	foreach ( $rows as $i => $r ) {
		$p    = $r['post'];
		$link = current_user_can( 'edit_post', $p->ID ) ? get_edit_post_link( $p->ID ) : get_permalink( $p );
		$sec  = function_exists( 'relife_primary_section' ) ? relife_primary_section( $p->ID ) : null;
		printf(
			'<tr><td class="n">%1$d.</td><td><a href="%2$s">%3$s</a><br><small>%4$s</small><div class="bar" style="width:%5$d%%"></div></td><td class="c">%6$s</td></tr>',
			(int) $i + 1,
			esc_url( (string) $link ),
			esc_html( get_the_title( $p ) ),
			esc_html( $sec instanceof WP_Term ? $sec->name : '' ),
			(int) round( ( $r['n'] / $max ) * 100 ),
			esc_html( number_format_i18n( $r['n'] ) )
		);
	}
	echo '</table>';
	echo '<p class="description" style="margin-top:10px">' . esc_html__( 'Orientačný počet uložení z prehliadačov čitateľov (bez osobných údajov). Počíta sa od spustenia tejto verzie témy.', 'relife' ) . '</p>';
}
