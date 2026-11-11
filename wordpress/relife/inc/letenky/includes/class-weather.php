<?php
/**
 * Počasie v destinácii v deň odletu.
 *
 * Aktualizácia na GitHube ukladá vedľa deals.json aj weather.json: pre každú destináciu
 * predpoveď na 16 dní a tabuľku typického počasia po 10-dňových úsekoch roka (Open-Meteo).
 * Z nej sa dá dopočítať počasie ľubovoľnej ponuky – aj takej, ktorú našlo až živé hľadanie
 * a v deals.json nie je. Hodnota v tabuľke je text „teplota + písmeno počasia“, napr. "32r".
 */

defined( 'ABSPATH' ) || exit;

class Lacne_Letenky_Weather {

	const CACHE_KEY = 'lacne_letenky_wx';        // tabuľky (platia pár hodín)
	const LAST_KEY  = 'lacne_letenky_wx_last';   // posledné úspešne načítané (trvalo)
	const FAIL_KEY  = 'lacne_letenky_wx_fail';   // načítanie práve zlyhalo
	const STATUS    = 'lacne_letenky_wx_status';

	private static $kinds = array(
		's' => 'sun', 'p' => 'partly', 'c' => 'cloud', 'f' => 'fog', 'r' => 'rain', 'n' => 'snow', 't' => 'storm',
	);

	/** Adresa weather.json vedľa zdroja s cenami. */
	public static function url() {
		$src = Lacne_Letenky_Settings::get( 'src' );
		if ( ! is_string( $src ) || ! preg_match( '#/[^/?]*\.json(\?.*)?$#', $src ) ) {
			return '';
		}
		return preg_replace( '#/[^/?]*\.json(\?.*)?$#', '/weather.json', $src );
	}

	/** Tabuľky počasia (z cache, inak zo zdroja, inak posledné známe); null keď nie sú. */
	public static function tables() {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$last = get_option( self::LAST_KEY );
		if ( get_transient( self::FAIL_KEY ) ) {
			return is_array( $last ) ? $last : null;
		}
		$url = self::url();
		if ( '' === $url ) {
			return is_array( $last ) ? $last : null;
		}
		$res    = wp_remote_get( add_query_arg( 't', time(), $url ), array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/json' ) ) );
		$status = array( 'time' => time(), 'ok' => false, 'message' => '' );
		if ( is_wp_error( $res ) ) {
			$status['message'] = $res->get_error_message();
		} elseif ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			$status['message'] = 'HTTP ' . wp_remote_retrieve_response_code( $res );
		} else {
			$data = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( is_array( $data ) && isset( $data['d'], $data['from'] ) && is_array( $data['d'] ) ) {
				$status['ok']      = true;
				$status['message'] = sprintf( 'tabuľky pre %d letísk', count( $data['d'] ) );
				update_option( self::STATUS, $status, false );
				set_transient( self::CACHE_KEY, $data, 3 * HOUR_IN_SECONDS );
				update_option( self::LAST_KEY, $data, false );
				return $data;
			}
			$status['message'] = 'Súbor nemá očakávaný formát.';
		}
		update_option( self::STATUS, $status, false );
		set_transient( self::FAIL_KEY, 1, 10 * MINUTE_IN_SECONDS );
		return is_array( $last ) ? $last : null;
	}

	public static function flush() {
		delete_transient( self::CACHE_KEY );
		delete_transient( self::FAIL_KEY );
	}

	private static function parse( $text ) {
		if ( is_string( $text ) && preg_match( '/^(-?\d+)([spcfrnt])$/', $text, $m ) ) {
			return array( (int) $m[1], self::$kinds[ $m[2] ] );
		}
		return null;
	}

	/** Počasie destinácie v daný deň (Y-m-d): predpoveď, inak typické počasie. */
	public static function lookup( $tables, $dest, $day ) {
		if ( ! is_array( $tables ) || empty( $tables['d'][ $dest ] ) || ! $day ) {
			return null;
		}
		$row = $tables['d'][ $dest ];
		$ts  = strtotime( $day . ' 12:00:00 UTC' );
		$idx = (int) round( ( $ts - strtotime( $tables['from'] . ' 12:00:00 UTC' ) ) / DAY_IN_SECONDS );
		if ( ! empty( $row['f'] ) && $idx >= 0 && $idx < count( $row['f'] ) ) {
			$p = self::parse( $row['f'][ $idx ] );
			if ( $p ) {
				return array( 't' => $p[0], 'c' => $p[1], 'k' => 'f' );
			}
		}
		if ( ! empty( $row['c'] ) && 36 === count( $row['c'] ) ) {
			$b = ( (int) gmdate( 'n', $ts ) - 1 ) * 3 + min( intdiv( (int) gmdate( 'j', $ts ) - 1, 10 ), 2 );
			$p = self::parse( $row['c'][ $b ] );
			if ( $p ) {
				return array( 't' => $p[0], 'c' => $p[1], 'k' => 'c' );
			}
		}
		return null;
	}

	/** Doplní ponukám počasie z tabuliek (ponuky, ktoré tabuľky nepoznajú, ostanú nezmenené). */
	public static function apply( $deals ) {
		$tables = self::tables();
		if ( ! is_array( $tables ) ) {
			return $deals;
		}
		foreach ( $deals as $i => $d ) {
			if ( ! is_array( $d ) || empty( $d['dest'] ) || empty( $d['depart'] ) ) {
				continue;
			}
			$w = self::lookup( $tables, $d['dest'], $d['depart'] );
			if ( $w ) {
				$deals[ $i ]['weather'] = $w;
			}
		}
		return $deals;
	}
}
