<?php
/**
 * Hlasovanie v ankete: skutočné počítanie hlasov čitateľov a aktuálne výsledky.
 *
 * Čitateľ si svoj výber pamätá aj v prehliadači (localStorage). Na server ide len číslo ankety a poradie možnosti;
 * server si nepamätá, kto hlasoval (len krátkodobý počítač zneužitia podľa nevratného odtlačku IP).
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const RELIFE_VOTES_META = 'relife_poll_votes';

function relife_register_poll_routes(): void {
	register_rest_route(
		'relife/v1',
		'/vote',
		array(
			'methods'             => 'POST',
			'callback'            => 'relife_rest_vote',
			'permission_callback' => '__return_true',
			'args'                => array(
				'poll'   => array( 'required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint' ),
				'option' => array( 'required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint' ),
			),
		)
	);
	register_rest_route(
		'relife/v1',
		'/poll/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => static function ( WP_REST_Request $req ) {
				$res = relife_poll_results( (int) $req['id'] );
				return is_array( $res ) ? relife_nocache( new WP_REST_Response( $res, 200 ) ) : new WP_Error( 'relife_bad_poll', 'Neplatná anketa.', array( 'status' => 404 ) );
			},
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'relife_register_poll_routes' );

function relife_nocache( WP_REST_Response $res ): WP_REST_Response {
	$res->header( 'Cache-Control', 'no-store, max-age=0' );
	return $res;
}

/**
 * @return array{pct:int[],votes:int[],total:int}|null
 */
function relife_poll_results( int $poll_id ): ?array {
	$poll = get_post( $poll_id );
	if ( ! $poll instanceof WP_Post || 'relife_poll' !== $poll->post_type || 'publish' !== $poll->post_status ) {
		return null;
	}
	$opts = relife_poll_options( $poll_id );
	return array(
		'pct'   => array_map( 'intval', array_column( $opts, 'pct' ) ),
		'votes' => array_map( 'intval', array_column( $opts, 'votes' ) ),
		'total' => (int) array_sum( array_column( $opts, 'votes' ) ),
	);
}

/**
 * @return WP_REST_Response|WP_Error
 */
function relife_rest_vote( WP_REST_Request $req ) {
	$poll_id = (int) $req['poll'];
	$idx     = (int) $req['option'];
	$res     = relife_poll_results( $poll_id );
	if ( null === $res || ! isset( $res['pct'][ $idx ] ) ) {
		return new WP_Error( 'relife_bad_vote', 'Neplatný hlas.', array( 'status' => 400 ) );
	}
	if ( 'closed' === get_post_meta( $poll_id, 'relife_poll_status', true ) ) {
		return relife_nocache( new WP_REST_Response( $res + array( 'counted' => false ), 200 ) );
	}

	// Zneužitie: najviac 5 hlasov v ankete za 24 hodín z jedného zariadenia (spoločná sieť, napr. firma, sa tým neblokuje).
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$key = 'rl_vt_' . md5( $ip . wp_salt( 'nonce' ) . '_' . $poll_id );
	$n   = (int) get_transient( $key );
	if ( $n >= 5 ) {
		return relife_nocache( new WP_REST_Response( $res + array( 'counted' => false ), 200 ) );
	}
	set_transient( $key, $n + 1, DAY_IN_SECONDS );

	$votes = get_post_meta( $poll_id, RELIFE_VOTES_META, true );
	$votes = is_array( $votes ) ? $votes : array();
	$votes[ $idx ] = ( isset( $votes[ $idx ] ) ? (int) $votes[ $idx ] : 0 ) + 1;
	update_post_meta( $poll_id, RELIFE_VOTES_META, $votes );

	$res = relife_poll_results( $poll_id );
	return relife_nocache( new WP_REST_Response( (array) $res + array( 'counted' => true ), 200 ) );
}
