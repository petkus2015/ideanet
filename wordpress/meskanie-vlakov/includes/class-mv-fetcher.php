<?php
/**
 * Sťahovanie dát na serveri (PHP verzia scripts/fetch_zsr.py).
 *
 * - poloha a meškanie vlakov z mapa.zsr.sk (nedokumentované API) každé 2 minúty
 * - história súhrnu za 24 h
 * - cestovný poriadok na dnes z otvorených GTFS dát ŽSR (CC0) raz denne
 *
 * Výsledky sú statické JSON súbory v wp-content/uploads/meskanie-vlakov/.
 */

defined( 'ABSPATH' ) || exit;

class MV_Fetcher {

	const BASE         = 'https://mapa.zsr.sk';
	const API          = 'https://mapa.zsr.sk/api/action';
	const GTFS_URL     = 'https://www.zsr.sk/files/pre-cestujucich/cestovny-poriadok/gtfs/gtfs.zip';
	const HISTORY_LEN  = 720; // 24 h po 2 min
	const TT_FORMAT    = 2;   // 2 = stanice s kódmi
	const UA           = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';
	const GROUPS       = array(
		'reg' => array( 'OS', 'REX', 'ZR', 'SP', 'MOS' ),
		'r'   => array( 'R' ),
		'ex'  => array( 'EX', 'IC', 'SC', 'RJ', 'LE' ),
		'int' => array( 'EC', 'EN', 'RJX', 'ICE', 'NJ' ),
	);

	/* ---------- súbory ---------- */

	public static function dir() {
		$u = wp_upload_dir( null, false );
		return trailingslashit( $u['basedir'] ) . 'meskanie-vlakov/';
	}

	public static function file_url( $name ) {
		$u = wp_upload_dir( null, false );
		return set_url_scheme( trailingslashit( $u['baseurl'] ) . 'meskanie-vlakov/' . $name );
	}

	private static function write_json( $name, $data ) {
		$dir = self::dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			throw new RuntimeException( 'Priečinok ' . $dir . ' sa nedá vytvoriť.' );
		}
		$tmp = $dir . $name . '.tmp';
		if ( false === file_put_contents( $tmp, wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			throw new RuntimeException( 'Súbor ' . $name . ' sa nedá zapísať.' );
		}
		rename( $tmp, $dir . $name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
	}

	private static function read_json( $name ) {
		$path = self::dir() . $name;
		if ( ! is_readable( $path ) ) {
			return null;
		}
		return json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	private static function set_status( $values ) {
		update_option( 'mv_status', array_merge( (array) get_option( 'mv_status', array() ), $values ), false );
	}

	private static function today() {
		return new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Bratislava' ) );
	}

	/* ---------- poloha vlakov ---------- */

	/** Úloha plánovača každé 2 minúty. */
	public static function cron_trains() {
		if ( mv_settings()['demo'] ) {
			return;
		}
		try {
			self::fetch_trains();
		} catch ( Exception $e ) {
			self::set_status( array( 'trains_error' => $e->getMessage(), 'trains_error_at' => time() ) );
		}
		// cestovný poriadok je na každý deň iný: po polnoci sa zostaví nový
		$tt = self::read_json( 'timetable.json' );
		if ( ( ! $tt || ( $tt['date'] ?? '' ) !== self::today()->format( 'Y-m-d' ) || ( $tt['v'] ?? 1 ) < self::TT_FORMAT )
			&& ! get_transient( 'mv_tt_running' ) && ! wp_next_scheduled( 'mv_build_timetable' ) ) {
			wp_schedule_single_event( time(), 'mv_build_timetable' );
		}
	}

	private static function fetch_raw() {
		$last = '';
		for ( $i = 0; $i < 2; $i++ ) {
			$page    = wp_remote_get(
				self::BASE . '/index.aspx',
				array( 'timeout' => 30, 'user-agent' => self::UA, 'headers' => array( 'Accept-Language' => 'sk,en;q=0.8' ) )
			);
			$cookies = is_wp_error( $page ) ? array() : wp_remote_retrieve_cookies( $page );
			$res     = wp_remote_post(
				self::API,
				array(
					'timeout'    => 30,
					'user-agent' => self::UA,
					'cookies'    => $cookies,
					'headers'    => array(
						'Accept'           => '*/*',
						'Accept-Language'  => 'sk,en;q=0.8',
						'Origin'           => self::BASE,
						'Referer'          => self::BASE . '/index.aspx',
						'X-Requested-With' => 'XMLHttpRequest',
						'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
					),
					'body'       => array( 'action' => 'gtall' ),
				)
			);
			if ( is_wp_error( $res ) ) {
				$last = $res->get_error_message();
			} elseif ( 200 !== wp_remote_retrieve_response_code( $res ) ) {
				$last = 'HTTP ' . wp_remote_retrieve_response_code( $res );
			} else {
				$data = json_decode( wp_remote_retrieve_body( $res ), true );
				if ( is_array( $data ) && array_is_list( $data ) ) {
					return $data;
				}
				$last = 'neočakávaná odpoveď';
			}
			sleep( 3 );
		}
		throw new RuntimeException( 'API ŽSR nedostupné: ' . $last );
	}

	public static function fetch_trains() {
		$raw    = self::fetch_raw();
		$trains = array_map( array( __CLASS__, 'normalize' ), $raw );
		usort( $trains, fn( $a, $b ) => $b['delay'] <=> $a['delay'] );
		$ts = gmdate( 'Y-m-d\TH:i:s+00:00' );
		self::write_json( 'trains.json', array( 'updated' => $ts, 'source' => self::API, 'trains' => $trains ) );

		$hist   = self::read_json( 'history.json' );
		$hist   = is_array( $hist ) ? $hist : array();
		$hist[] = self::summary( $trains, $ts );
		self::write_json( 'history.json', array_slice( $hist, -self::HISTORY_LEN ) );

		self::set_status( array( 'trains_ok' => time(), 'trains_count' => count( $trains ), 'trains_error' => '' ) );
		return count( $trains );
	}

	private static function clean( $s ) {
		return ( null === $s || '' === $s || is_array( $s ) ) ? '' : trim( preg_replace( '/\s+/u', ' ', (string) $s ) );
	}

	private static function to_int( $v ) {
		if ( is_int( $v ) || is_float( $v ) ) {
			return (int) $v;
		}
		return preg_match( '/-?\d+/', (string) $v, $m ) ? (int) $m[0] : null;
	}

	/** Position býva [lat, lon]; ak je prehodená, otočíme ju. */
	private static function coords( $pos ) {
		if ( ! is_array( $pos ) || ! isset( $pos[0], $pos[1] ) || ! is_numeric( $pos[0] ) || ! is_numeric( $pos[1] ) ) {
			return array( null, null );
		}
		$a = (float) $pos[0];
		$b = (float) $pos[1];
		if ( $a >= 47 && $a <= 50 && $b >= 16 && $b <= 23 ) {
			return array( round( $a, 5 ), round( $b, 5 ) );
		}
		if ( $b >= 47 && $b <= 50 && $a >= 16 && $a <= 23 ) {
			return array( round( $b, 5 ), round( $a, 5 ) );
		}
		return array( null, null );
	}

	/** Trasa vlaku ako zoznam [lat, lon] v poradí jazdy (max. $limit bodov). Formát poľa Trasa nie je zdokumentovaný. */
	private static function route_path( $trasa, $limit = 60 ) {
		$pts  = array();
		$walk = function ( $x ) use ( &$walk, &$pts ) {
			if ( is_string( $x ) && '' !== $x && ( '[' === $x[0] || '{' === $x[0] ) ) {
				$x = json_decode( $x, true );
			}
			if ( ! is_array( $x ) ) {
				return;
			}
			if ( 2 === count( $x ) && array_is_list( $x ) && ( is_int( $x[0] ) || is_float( $x[0] ) ) && ( is_int( $x[1] ) || is_float( $x[1] ) ) ) {
				list( $lat, $lon ) = self::coords( $x );
				$p                 = array( round( (float) $lat, 3 ), round( (float) $lon, 3 ) );
				if ( null !== $lat && ( ! $pts || end( $pts ) !== $p ) ) {
					$pts[] = $p;
				}
				return;
			}
			foreach ( $x as $v ) {
				$walk( $v );
			}
		};
		$walk( $trasa );
		$n = count( $pts );
		if ( $n > $limit ) {
			$step = ( $n - 1 ) / ( $limit - 1 );
			$out  = array();
			for ( $i = 0; $i < $limit; $i++ ) {
				$out[] = $pts[ (int) round( $i * $step ) ];
			}
			$pts = $out;
		}
		return $pts;
	}

	private static function normalize( $t ) {
		$delay = self::to_int( $t['Meska'] ?? null );
		if ( null === $delay ) {
			$delay = (int) self::to_int( $t['MeskaText'] ?? '' );
		}
		list( $lat, $lon ) = self::coords( $t['Position'] ?? null );
		$typ               = self::clean( $t['TypVlaku'] ?? '' );
		$num               = self::clean( $t['CisloVlaku'] ?? '' );
		return array(
			'id'         => ( $typ . $num ) ? $typ . $num : self::clean( $t['Nazov'] ?? '' ),
			'type'       => $typ,
			'number'     => $num,
			'name'       => self::clean( $t['NazovVlaku'] ?? '' ),
			'route'      => self::clean( $t['Nazov'] ?? '' ),
			'carrier'    => self::clean( $t['Dopravca'] ?? '' ),
			'delay'      => max( 0, $delay ),
			'delayText'  => self::clean( $t['MeskaText'] ?? '' ),
			'info'       => self::clean( $t['InfoZoStanice'] ?? '' ),
			'next'       => self::clean( $t['NextStation'] ?? '' ),
			'lat'        => $lat,
			'lon'        => $lon,
			'disruption' => self::clean( $t['Narusenie'] ?? '' ),
			'note'       => self::clean( $t['Poznamka'] ?? '' ),
			'nad'        => array_values( array_filter( array( self::clean( $t['NadOd'] ?? '' ), self::clean( $t['NadDo'] ?? '' ) ) ) ),
			'path'       => self::route_path( $t['Trasa'] ?? null ),
		);
	}

	private static function group_of( $t ) {
		$typ = strtoupper( $t['type'] );
		foreach ( self::GROUPS as $g => $types ) {
			if ( in_array( $typ, $types, true ) ) {
				return $g;
			}
		}
		return 'other';
	}

	private static function stats( $trains ) {
		$n = count( $trains );
		$d = array_column( $trains, 'delay' );
		return array(
			'n'      => $n,
			'avg'    => $n ? round( array_sum( $d ) / $n, 1 ) : 0,
			'onTime' => $n ? round( 100 * count( array_filter( $d, fn( $x ) => $x <= 5 ) ) / $n, 1 ) : 0,
			'over15' => count( array_filter( $d, fn( $x ) => $x > 15 ) ),
		);
	}

	private static function summary( $trains, $ts ) {
		$s = array( 't' => $ts ) + self::stats( $trains );
		foreach ( array_keys( self::GROUPS ) as $g ) {
			$s['g'][ $g ] = self::stats( array_values( array_filter( $trains, fn( $t ) => self::group_of( $t ) === $g ) ) );
		}
		return $s;
	}

	/* ---------- cestovný poriadok z GTFS ---------- */

	private static function gtfs_minutes( $value ) {
		return preg_match( '/^\s*(\d+):(\d\d)/', (string) $value, $m ) ? (int) $m[1] * 60 + (int) $m[2] : null;
	}

	/** Typ, číslo a názov vlaku z GTFS (napr. „Ex 612 Spiš“). */
	private static function train_ident( $trip, $route ) {
		foreach ( array( $trip['trip_short_name'] ?? '', $route['route_short_name'] ?? '', $trip['trip_headsign'] ?? '' ) as $text ) {
			if ( preg_match( '/^\s*([A-Za-z]{1,4})\s*(\d{1,5})\b\s*(.*)$/u', (string) $text, $m ) ) {
				return array( $m[1], $m[2], self::clean( $m[3] ) );
			}
		}
		$num = preg_match( '/\d+/', (string) ( $trip['trip_short_name'] ?? '' ), $m ) ? $m[0] : '';
		$typ = substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $route['route_short_name'] ?? '' ) ), 0, 4 );
		return array( $typ, $num, '' );
	}

	/** Riadky CSV súboru zo ZIP archívu ako asociatívne polia. */
	private static function gtfs_rows( ZipArchive $zip, $name ) {
		$fp = $zip->getStream( $name );
		if ( ! $fp ) {
			return;
		}
		$head = fgetcsv( $fp, 0, ',', '"', '\\' );
		if ( ! $head ) {
			return;
		}
		$head[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $head[0] );
		$head    = array_map( 'trim', $head );
		$cols    = count( $head );
		while ( ( $r = fgetcsv( $fp, 0, ',', '"', '\\' ) ) !== false ) {
			if ( array( null ) === $r ) {
				continue;
			}
			yield array_combine( $head, array_slice( array_pad( $r, $cols, '' ), 0, $cols ) );
		}
		fclose( $fp );
	}

	/**
	 * Cestovný poriadok na dnes vo formáte, ktorý číta assets/app.js:
	 * {"date", "stations": [[názov, lat, lon, [kódy]]], "trips": [[typ, číslo, názov, [[stanica, príchod, odchod]], dopravca]]}
	 */
	public static function build_timetable() {
		if ( get_transient( 'mv_tt_running' ) ) {
			return;
		}
		set_transient( 'mv_tt_running', 1, 15 * MINUTE_IN_SECONDS );
		try {
			$count = self::build_timetable_unlocked();
			self::set_status( array( 'tt_ok' => time(), 'tt_count' => $count, 'tt_error' => '' ) );
		} catch ( Exception $e ) {
			self::set_status( array( 'tt_error' => $e->getMessage(), 'tt_error_at' => time() ) );
		} finally {
			delete_transient( 'mv_tt_running' );
		}
	}

	private static function build_timetable_unlocked() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new RuntimeException( 'Na serveri chýba PHP rozšírenie zip (ZipArchive).' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		wp_raise_memory_limit( 'mv_timetable' );
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		}

		$url = mv_settings()['gtfs_url'];
		$tmp = download_url( $url, 300 );
		if ( is_wp_error( $tmp ) ) {
			throw new RuntimeException( 'GTFS sa nepodarilo stiahnuť: ' . $tmp->get_error_message() );
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $tmp ) ) {
			wp_delete_file( $tmp );
			throw new RuntimeException( 'Stiahnutý súbor nie je platný ZIP archív GTFS.' );
		}

		try {
			$today = self::today();
			$ymd   = $today->format( 'Ymd' );
			$wd    = strtolower( $today->format( 'l' ) );

			$services = array();
			foreach ( self::gtfs_rows( $zip, 'calendar.txt' ) as $r ) {
				if ( ( $r['start_date'] ?? '' ) <= $ymd && $ymd <= ( $r['end_date'] ?? '' ) && '1' === ( $r[ $wd ] ?? '' ) ) {
					$services[ $r['service_id'] ] = true;
				}
			}
			foreach ( self::gtfs_rows( $zip, 'calendar_dates.txt' ) as $r ) {
				if ( ( $r['date'] ?? '' ) === $ymd ) {
					if ( '1' === ( $r['exception_type'] ?? '' ) ) {
						$services[ $r['service_id'] ] = true;
					} else {
						unset( $services[ $r['service_id'] ] );
					}
				}
			}

			$agencies = array();
			foreach ( self::gtfs_rows( $zip, 'agency.txt' ) as $r ) {
				$agencies[ $r['agency_id'] ?? '' ] = self::clean( $r['agency_name'] ?? '' );
			}
			$routes = array();
			foreach ( self::gtfs_rows( $zip, 'routes.txt' ) as $r ) {
				$routes[ $r['route_id'] ] = $r;
			}
			$trips = array();
			foreach ( self::gtfs_rows( $zip, 'trips.txt' ) as $r ) {
				if ( isset( $services[ $r['service_id'] ?? '' ] ) ) {
					$trips[ $r['trip_id'] ] = $r;
				}
			}

			// nástupištia jednej stanice zlúčime podľa názvu
			$stations = array();
			$st_index = array();
			$stop_st  = array();
			foreach ( self::gtfs_rows( $zip, 'stops.txt' ) as $r ) {
				$name = self::clean( $r['stop_name'] ?? '' );
				if ( '' === $name ) {
					continue;
				}
				if ( ! isset( $st_index[ $name ] ) ) {
					$lat                = is_numeric( $r['stop_lat'] ?? '' ) ? round( (float) $r['stop_lat'], 4 ) : null;
					$lon                = is_numeric( $r['stop_lon'] ?? '' ) ? round( (float) $r['stop_lon'], 4 ) : null;
					$st_index[ $name ] = count( $stations );
					$stations[]         = array( $name, $lat, $lon, array() );
				}
				$idx                      = $st_index[ $name ];
				$stop_st[ $r['stop_id'] ] = $idx;
				// číselné kódy stanice: ŽSR ich niekedy posiela namiesto názvu ďalšej stanice
				if ( preg_match_all( '/\d{3,}/', ( $r['stop_id'] ?? '' ) . ' ' . ( $r['stop_code'] ?? '' ), $m ) ) {
					foreach ( $m[0] as $code ) {
						$code = ltrim( $code, '0' );
						if ( ! in_array( $code, $stations[ $idx ][3], true ) ) {
							$stations[ $idx ][3][] = $code;
						}
					}
				}
			}

			$times = array();
			foreach ( self::gtfs_rows( $zip, 'stop_times.txt' ) as $r ) {
				$tid = $r['trip_id'] ?? '';
				if ( isset( $trips[ $tid ], $stop_st[ $r['stop_id'] ?? '' ] ) ) {
					$times[ $tid ][] = array(
						(int) ( $r['stop_sequence'] ?? 0 ),
						$stop_st[ $r['stop_id'] ],
						self::gtfs_minutes( $r['arrival_time'] ?? '' ),
						self::gtfs_minutes( $r['departure_time'] ?? '' ),
					);
				}
			}
		} finally {
			$zip->close();
			wp_delete_file( $tmp );
		}

		$out = array();
		foreach ( $times as $tid => $st ) {
			if ( count( $st ) < 2 ) {
				continue;
			}
			usort( $st, fn( $a, $b ) => $a[0] <=> $b[0] );
			$trip  = $trips[ $tid ];
			$route = $routes[ $trip['route_id'] ?? '' ] ?? array();
			list( $typ, $num, $name ) = self::train_ident( $trip, $route );
			$last  = count( $st ) - 1;
			$stops = array();
			foreach ( $st as $i => $s ) {
				$stops[] = array( $s[1], $i ? $s[2] : null, $i < $last ? $s[3] : null );
			}
			$out[] = array( $typ, $num, $name, $stops, $agencies[ $route['agency_id'] ?? '' ] ?? '' );
		}
		usort( $out, fn( $a, $b ) => ( $a[3][0][2] ?? 0 ) <=> ( $b[3][0][2] ?? 0 ) );

		self::write_json(
			'timetable.json',
			array(
				'date'     => self::today()->format( 'Y-m-d' ),
				'v'        => self::TT_FORMAT,
				'source'   => $url,
				'stations' => $stations,
				'trips'    => $out,
			)
		);
		return count( $out );
	}
}

if ( ! function_exists( 'array_is_list' ) ) {
	// PHP < 8.1
	function array_is_list( array $a ) {
		return array() === $a || array_keys( $a ) === range( 0, count( $a ) - 1 );
	}
}
