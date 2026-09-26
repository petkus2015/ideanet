<?php
/**
 * Načítanie cien: stiahne deals.json zo zdroja, uloží do cache a sprístupní cez REST API
 * (tlačidlo „Vyhľadať lacné letenky“ sa pýta na /wp-json/lacne-letenky/v1/deals).
 */

defined( 'ABSPATH' ) || exit;

class Lacne_Letenky_Data {

	const CACHE_KEY  = 'lacne_letenky_data';
	const BACKUP_KEY = 'lacne_letenky_last_good';
	const STATUS_KEY = 'lacne_letenky_status';

	/** Aktuálne dáta: z cache, inak zo zdroja; keď zdroj zlyhá, posledné úspešne načítané. */
	public static function get( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$data = self::fetch();
		if ( is_array( $data ) ) {
			$minutes = max( 5, (int) Lacne_Letenky_Settings::get( 'cache_minutes' ) );
			set_transient( self::CACHE_KEY, $data, $minutes * MINUTE_IN_SECONDS );
			update_option( self::BACKUP_KEY, $data, false );
			return $data;
		}

		// Zdroj je nedostupný – aby web neostal bez ponúk, použijeme poslednú úspešnú kópiu
		// a skúsime to znova o 5 minút. Blok sám skryje ponuky staršie ako 36 hodín.
		$backup = get_option( self::BACKUP_KEY );
		if ( is_array( $backup ) ) {
			set_transient( self::CACHE_KEY, $backup, 5 * MINUTE_IN_SECONDS );
			return $backup;
		}
		return null;
	}

	private static function fetch() {
		$src = Lacne_Letenky_Settings::get( 'src' );
		$res = wp_remote_get(
			add_query_arg( 't', time(), $src ),
			array(
				'timeout' => 10,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		$status = array( 'time' => time(), 'ok' => false, 'message' => '' );
		if ( is_wp_error( $res ) ) {
			$status['message'] = $res->get_error_message();
		} elseif ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			$status['message'] = 'HTTP ' . wp_remote_retrieve_response_code( $res );
		} else {
			$data = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( is_array( $data ) && isset( $data['deals'] ) && is_array( $data['deals'] ) ) {
				$status['ok']      = true;
				$status['message'] = sprintf( '%d ponúk', count( $data['deals'] ) );
				update_option( self::STATUS_KEY, $status, false );
				return $data;
			}
			$status['message'] = 'Súbor nemá očakávaný formát (chýba "deals").';
		}
		update_option( self::STATUS_KEY, $status, false );
		return null;
	}

	public static function flush() {
		delete_transient( self::CACHE_KEY );
	}

	public static function register_rest() {
		register_rest_route(
			'lacne-letenky/v1',
			'/deals',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					$data = self::get();
					if ( ! is_array( $data ) ) {
						return new WP_Error( 'lacne_letenky_unavailable', 'Ponuky nie sú k dispozícii.', array( 'status' => 503 ) );
					}
					$res = rest_ensure_response( $data );
					$res->header( 'Cache-Control', 'no-cache, max-age=0' );
					return $res;
				},
			)
		);
	}

	public static function rest_url() {
		return rest_url( 'lacne-letenky/v1/deals' );
	}
}
