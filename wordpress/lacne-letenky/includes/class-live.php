<?php
/**
 * Živé hľadanie po kliknutí na „Vyhľadaj aktuálne lacné letenky“.
 *
 * WordPress server sa priamo opýta momondo a Ryanairu na najlacnejšie spiatočné letenky
 * z VIE a BTS s odletom do 3 mesiacov. Ceny Wizz Air (ich hľadanie trvá minúty) sa berú
 * z poslednej aktualizácie na GitHube. Výsledok platí niekoľko minút (Nastavenia), aby
 * opakované kliknutia návštevníkov nezahltili zdroje.
 *
 * Logika zodpovedá plugins/lacne-letenky/scripts/update_deals.py.
 */

defined( 'ABSPATH' ) || exit;

class Lacne_Letenky_Live {

	const THROTTLE_KEY = 'lacne_letenky_live';      // posledné živé hľadanie (platí X minút)
	const LAST_KEY     = 'lacne_letenky_live_last'; // posledné úspešné živé hľadanie (trvalo)
	const LOCK_KEY     = 'lacne_letenky_live_lock'; // prebieha hľadanie
	const FAIL_KEY     = 'lacne_letenky_live_fail'; // hľadanie práve zlyhalo – chvíľu neskúšať znova
	const RATE_KEY     = 'lacne_letenky_gbp_rate';  // kurz ECB EUR/GBP

	const MOMONDO  = 'https://www.momondo.co.uk';
	const RYANAIR  = 'https://www.ryanair.com/api/farfnd/v4/roundTripFares';
	const ECB      = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
	const CURRENCY = 'EUR';

	private static $geo;
	private static $used_rate = 0; // kurz GBP použitý pri poslednom hľadaní

	private static function geo() {
		if ( null === self::$geo ) {
			self::$geo = require __DIR__ . '/geo.php';
		}
		return self::$geo;
	}

	/** Dáta na zobrazenie: posledné živé hľadanie, ak je novšie než aktualizácia z GitHubu. */
	public static function overlay( $base ) {
		$last = get_option( self::LAST_KEY );
		if ( is_array( $last ) && ! empty( $last['updatedAt'] ) &&
			( ! is_array( $base ) || empty( $base['updatedAt'] ) || strtotime( $last['updatedAt'] ) > strtotime( $base['updatedAt'] ) ) ) {
			return $last;
		}
		return $base;
	}

	/** Živé hľadanie (alebo výsledok spred chvíle). $base = dáta z GitHubu. */
	public static function search( $base ) {
		$recent = get_transient( self::THROTTLE_KEY );
		if ( is_array( $recent ) ) {
			$recent['liveAgeMin'] = (int) floor( ( time() - (int) $recent['liveAt'] ) / 60 );
			return $recent;
		}
		// Blok hľadá pri každom otvorení stránky – po zlyhaní zdrojov 3 minúty neskúšame znova.
		if ( get_transient( self::FAIL_KEY ) ) {
			$shown = self::overlay( $base );
			return array_merge( is_array( $shown ) ? $shown : array( 'deals' => array() ), array( 'liveError' => true ) );
		}
		if ( get_transient( self::LOCK_KEY ) ) {
			$shown = self::overlay( $base );
			return array_merge( is_array( $shown ) ? $shown : array( 'deals' => array() ), array( 'liveBusy' => true ) );
		}
		set_transient( self::LOCK_KEY, 1, 90 );

		$errors = array();
		$deals  = self::fetch_all( $base, $errors );
		delete_transient( self::LOCK_KEY );

		if ( ! $deals ) {
			set_transient( self::FAIL_KEY, 1, 3 * MINUTE_IN_SECONDS );
			$shown = self::overlay( $base );
			self::status( false, implode( '; ', $errors ) );
			return array_merge( is_array( $shown ) ? $shown : array( 'deals' => array() ), array( 'liveError' => true ) );
		}

		$data = self::build( $deals, self::overlay( $base ), $errors, $base );
		$min  = max( 5, (int) Lacne_Letenky_Settings::get( 'live_minutes' ) );
		set_transient( self::THROTTLE_KEY, $data, $min * MINUTE_IN_SECONDS );
		update_option( self::LAST_KEY, $data, false );
		self::status( true, sprintf( '%d ponúk (%s)', count( $data['deals'] ), implode( ', ', array_map(
			function ( $k, $v ) { return $k . ' ' . $v; },
			array_keys( $data['liveSources'] ),
			$data['liveSources']
		) ) ) );
		return $data;
	}

	private static function status( $ok, $message ) {
		update_option( 'lacne_letenky_live_status', array( 'time' => time(), 'ok' => $ok, 'message' => $message ), false );
	}

	// --------------------------------------------------------------- sťahovanie ---

	private static function headers( $referer ) {
		return array(
			'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36',
			'Accept'          => 'application/json, text/plain, */*',
			'Accept-Language' => 'en-GB,en;q=0.9',
			'Referer'         => $referer,
		);
	}

	/** Paralelné GET požiadavky (rýchlejšie), inak postupne cez wp_remote_get. */
	private static function get_many( $requests ) {
		// Filter na testy alebo vlastný zdroj: vráťte pole odpovedí [ kľúč => [ 'code' => 200, 'body' => '…' ] ].
		$pre = apply_filters( 'lacne_letenky_pre_live_requests', null, $requests );
		if ( is_array( $pre ) ) {
			return $pre;
		}
		$out = array();
		$cls = class_exists( '\WpOrg\Requests\Requests' ) ? '\WpOrg\Requests\Requests' : ( class_exists( 'Requests' ) ? 'Requests' : null );
		if ( $cls ) {
			$list = array();
			foreach ( $requests as $key => $r ) {
				$list[ $key ] = array( 'url' => $r['url'], 'headers' => $r['headers'], 'type' => 'GET' );
			}
			$opts = array( 'timeout' => 12, 'connect_timeout' => 8 );
			if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) && file_exists( ABSPATH . WPINC . '/certificates/ca-bundle.crt' ) ) {
				$opts['verify'] = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
			}
			try {
				$responses = $cls::request_multiple( $list, $opts );
				foreach ( $responses as $key => $res ) {
					if ( is_object( $res ) && isset( $res->status_code ) ) {
						$out[ $key ] = array( 'code' => (int) $res->status_code, 'body' => (string) $res->body );
					} else {
						$out[ $key ] = array( 'code' => 0, 'body' => '', 'error' => is_object( $res ) && method_exists( $res, 'getMessage' ) ? $res->getMessage() : 'chyba spojenia' );
					}
				}
				return $out;
			} catch ( Exception $e ) {
				$out = array(); // skúsime postupne nižšie
			}
		}
		foreach ( $requests as $key => $r ) {
			$res = wp_remote_get( $r['url'], array( 'timeout' => 12, 'headers' => $r['headers'] ) );
			$out[ $key ] = is_wp_error( $res )
				? array( 'code' => 0, 'body' => '', 'error' => $res->get_error_message() )
				: array( 'code' => (int) wp_remote_retrieve_response_code( $res ), 'body' => (string) wp_remote_retrieve_body( $res ) );
		}
		return $out;
	}

	private static function fetch_all( $base, &$errors ) {
		$geo   = self::geo();
		$today = new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Vienna' ) );
		$reqs  = array();

		foreach ( array_keys( $geo['origins'] ) as $o ) {
			$reqs[ "momondo|$o|" ] = array( 'url' => self::momondo_url( $o, '' ), 'headers' => self::headers( self::MOMONDO . '/explore' ) );
			foreach ( $geo['watch'] as $w ) {
				if ( $w['featured'] || ! empty( $w['list'] ) ) {
					foreach ( $w['airports'] as $a ) {
						$reqs[ "momondo|$o|$a" ] = array( 'url' => self::momondo_url( $o, $a ), 'headers' => self::headers( self::MOMONDO . '/explore' ) );
					}
				}
			}
			$reqs[ "ryanair|$o|" ] = array( 'url' => self::ryanair_url( $o, $today ), 'headers' => self::headers( 'https://www.ryanair.com/' ) );
		}

		$deals = array();
		foreach ( self::get_many( $reqs ) as $key => $res ) {
			list( $src, $origin ) = explode( '|', $key );
			if ( 200 !== $res['code'] ) {
				$errors[] = "$src $origin: " . ( ! empty( $res['error'] ) ? $res['error'] : 'HTTP ' . $res['code'] );
				continue;
			}
			$json = json_decode( $res['body'], true );
			if ( ! is_array( $json ) ) {
				$errors[] = "$src $origin: neplatná odpoveď";
				continue;
			}
			$deals = array_merge( $deals, 'ryanair' === $src ? self::parse_ryanair( $json, $origin ) : self::parse_momondo( $json, $origin ) );
		}
		if ( ! $deals ) {
			return array();
		}

		// momondo.co.uk vracia libry – prepočet na eurá kurzom ECB.
		$rate = self::gbp_rate( $base );
		foreach ( $deals as $i => $d ) {
			if ( self::CURRENCY !== $d['currency'] ) {
				if ( 'GBP' !== $d['currency'] || ! $rate ) {
					unset( $deals[ $i ] ); // bez kurzu cenu v eurách neuvedieme
					continue;
				}
				self::$used_rate         = $rate;
				$deals[ $i ]['price']    = max( 1, (int) round( $d['price'] / $rate ) );
				$deals[ $i ]['currency'] = self::CURRENCY;
			}
		}
		return array_values( $deals );
	}

	private static function gbp_rate( $base ) {
		$rate = get_transient( self::RATE_KEY );
		if ( $rate ) {
			return (float) $rate;
		}
		$res = wp_remote_get( self::ECB, array( 'timeout' => 8 ) );
		if ( ! is_wp_error( $res ) && preg_match( "/currency=['\"]GBP['\"]\\s+rate=['\"]([\\d.]+)['\"]/", wp_remote_retrieve_body( $res ), $m ) ) {
			set_transient( self::RATE_KEY, (float) $m[1], 12 * HOUR_IN_SECONDS );
			return (float) $m[1];
		}
		// záloha: kurz z poslednej aktualizácie na GitHube
		return isset( $base['fx']['rates']['GBP'] ) ? (float) $base['fx']['rates']['GBP'] : 0;
	}

	// ------------------------------------------------------------------ zdroje ---

	private static function momondo_url( $origin, $selected ) {
		return self::MOMONDO . '/s/horizon/exploreapi/destinations?' . http_build_query( array(
			'airport' => $origin, 'budget' => '', 'depart' => '', 'return' => '', 'duration' => '',
			'exactDates' => 'false', 'flightMaxStops' => '', 'stopsFilterActive' => 'false',
			'topRightLat' => '', 'topRightLon' => '', 'bottomLeftLat' => '', 'bottomLeftLon' => '',
			'zoomLevel' => '2', 'selectedMarker' => '', 'themeCode' => '', 'selectedDestination' => $selected,
			'currency' => self::CURRENCY,
		) );
	}

	private static function ryanair_url( $origin, DateTimeImmutable $today ) {
		$h = self::geo()['horizon_days'];
		return self::RYANAIR . '?' . http_build_query( array(
			'departureAirportIataCode'  => $origin,
			'outboundDepartureDateFrom' => $today->modify( '+1 day' )->format( 'Y-m-d' ),
			'outboundDepartureDateTo'   => $today->modify( "+$h days" )->format( 'Y-m-d' ),
			'inboundDepartureDateFrom'  => $today->modify( '+3 days' )->format( 'Y-m-d' ),
			'inboundDepartureDateTo'    => $today->modify( '+' . ( $h + 14 ) . ' days' )->format( 'Y-m-d' ),
			'durationFrom' => 2, 'durationTo' => 14,
			'adultPaxCount' => 1, 'currency' => self::CURRENCY, 'market' => 'en-gb', 'searchMode' => 'ALL',
		) );
	}

	private static function pick( $obj, ...$paths ) {
		foreach ( $paths as $path ) {
			$cur = $obj;
			foreach ( explode( '.', $path ) as $k ) {
				if ( is_array( $cur ) && array_key_exists( $k, $cur ) ) {
					$cur = $cur[ $k ];
				} else {
					$cur = null;
					break;
				}
			}
			if ( null !== $cur && '' !== $cur ) {
				return $cur;
			}
		}
		return null;
	}

	private static function date( $v ) {
		if ( ! $v ) {
			return null;
		}
		$s = str_replace( '-', '', substr( (string) $v, 0, 10 ) );
		return preg_match( '/^(\d{4})(\d{2})(\d{2})/', $s, $m ) ? "$m[1]-$m[2]-$m[3]" : null;
	}

	private static function country( $code, $name ) {
		$geo  = self::geo();
		$code = strtoupper( (string) $code );
		if ( ! isset( $geo['countries'][ $code ] ) && $name && isset( $geo['by_en'][ strtolower( trim( $name ) ) ] ) ) {
			$code = $geo['by_en'][ strtolower( trim( $name ) ) ];
		}
		if ( isset( $geo['countries'][ $code ] ) ) {
			return array( $code, $geo['countries'][ $code ][0], $geo['countries'][ $code ][1] );
		}
		return array( $code, (string) $name, 'Svet' );
	}

	private static function nights( $dep, $ret ) {
		return $dep && $ret ? (int) ( ( strtotime( $ret ) - strtotime( $dep ) ) / DAY_IN_SECONDS ) : null;
	}

	private static function parse_momondo( $json, $origin ) {
		$items = isset( $json['destinations'] ) ? $json['destinations'] : ( isset( $json['results'] ) ? $json['results'] : array() );
		$out   = array();
		foreach ( (array) $items as $d ) {
			$dest  = self::pick( $d, 'airport.shortName', 'airport.code', 'airportCode', 'destination' );
			$price = self::pick( $d, 'flightInfo.price', 'price', 'flightInfo.lowestPrice' );
			if ( ! $dest || null === $price || ! is_numeric( $price ) ) {
				continue;
			}
			$dest  = strtoupper( $dest );
			$dep   = self::date( self::pick( $d, 'departd', 'departDate', 'flightInfo.departDate' ) );
			$ret   = self::date( self::pick( $d, 'returnd', 'returnDate', 'flightInfo.returnDate' ) );
			$stops = self::pick( $d, 'flightInfo.maxStops', 'flightMaxStops', 'maxStops', 'stops' );
			list( $code, $country, $region ) = self::country( self::pick( $d, 'country.id', 'country.code', 'countryCode' ), self::pick( $d, 'country.name', 'countryName' ) );
			$out[] = array(
				'origin' => $origin, 'dest' => $dest,
				'city' => (string) self::pick( $d, 'city.name', 'cityName' ) ?: $dest,
				'country' => $country, 'countryCode' => $code, 'region' => $region,
				'price' => (int) round( (float) $price ),
				'currency' => strtoupper( (string) ( self::pick( $d, 'flightInfo.currencyCode', 'currency', 'currencyCode' ) ?: ( isset( $json['currency'] ) ? $json['currency'] : 'GBP' ) ) ),
				'depart' => $dep, 'return' => $ret, 'nights' => self::nights( $dep, $ret ),
				'stops' => is_numeric( $stops ) ? (int) $stops : null,
				'url' => $dep && $ret ? self::MOMONDO . "/flight-search/$origin-$dest/$dep/$ret?sort=price_a"
					: ( $dep ? self::MOMONDO . "/flight-search/$origin-$dest/$dep?sort=price_a" : self::MOMONDO . "/explore/$origin-$dest" ),
				'source' => 'momondo',
			);
		}
		return $out;
	}

	private static function parse_ryanair( $json, $origin ) {
		$out = array();
		foreach ( isset( $json['fares'] ) ? (array) $json['fares'] : array() as $f ) {
			$o     = isset( $f['outbound'] ) ? $f['outbound'] : array();
			$in    = isset( $f['inbound'] ) ? $f['inbound'] : array();
			$dest  = self::pick( $o, 'arrivalAirport.iataCode' );
			$price = self::pick( $f, 'summary.price.value', 'outbound.price.value' );
			$dep   = self::date( isset( $o['departureDate'] ) ? $o['departureDate'] : null );
			$ret   = self::date( isset( $in['departureDate'] ) ? $in['departureDate'] : null );
			if ( ! $dest || null === $price || ! $dep ) {
				continue;
			}
			$dest = strtoupper( $dest );
			list( $code, $country, $region ) = self::country( self::pick( $o, 'arrivalAirport.city.countryCode', 'arrivalAirport.countryCode' ), self::pick( $o, 'arrivalAirport.countryName' ) );
			$out[] = array(
				'origin' => $origin, 'dest' => $dest,
				'city' => (string) ( self::pick( $o, 'arrivalAirport.city.name', 'arrivalAirport.name' ) ?: $dest ),
				'country' => $country, 'countryCode' => $code, 'region' => $region,
				'price' => (int) round( (float) $price ),
				'currency' => strtoupper( (string) ( self::pick( $f, 'summary.price.currencyCode', 'outbound.price.currencyCode' ) ?: self::CURRENCY ) ),
				'depart' => $dep, 'return' => $ret, 'nights' => self::nights( $dep, $ret ),
				'stops' => 0,
				'url' => 'https://www.ryanair.com/gb/en/trip/flights/select?' . http_build_query( array(
					'adults' => 1, 'teens' => 0, 'children' => 0, 'infants' => 0,
					'dateOut' => $dep, 'dateIn' => $ret ? $ret : '', 'isConnectedFlight' => 'false',
					'isReturn' => $ret ? 'true' : 'false', 'discount' => 0, 'originIata' => $origin, 'destinationIata' => $dest,
				) ),
				'source' => 'ryanair',
			);
		}
		return $out;
	}

	// ------------------------------------------------------------------ výsledok ---

	/** Najviac $n najlacnejších v Európe a $n mimo Európy (zoradené podľa ceny). */
	private static function per_group( $deals, $n ) {
		$eu = array_values( array_filter( $deals, function ( $d ) { return 'Európa' === $d['region']; } ) );
		$wo = array_values( array_filter( $deals, function ( $d ) { return 'Európa' !== $d['region']; } ) );
		$out = array_merge( array_slice( $eu, 0, $n ), array_slice( $wo, 0, $n ) );
		usort( $out, function ( $a, $b ) { return $a['price'] - $b['price']; } );
		return $out;
	}

	private static function flight_key( $d ) {
		return $d['origin'] . '|' . $d['dest'] . '|' . ( isset( $d['depart'] ) ? $d['depart'] : '' ) . '|' . ( isset( $d['return'] ) ? $d['return'] : '' );
	}

	/** Ponuky z dát vrátane sledovaných destinácií (Bangkok, zoznam Ázia a SAE). */
	private static function all_deals( $data ) {
		$list = is_array( $data ) && isset( $data['deals'] ) ? $data['deals'] : array();
		foreach ( is_array( $data ) && isset( $data['watch'] ) ? $data['watch'] : array() as $w ) {
			if ( ! empty( $w['deal'] ) ) {
				$list[] = $w['deal'];
			}
		}
		return $list;
	}

	/**
	 * Počasie v destinácii (Open-Meteo) sa počíta pri aktualizácii na GitHube. Živé hľadanie
	 * ho prevezme: rovnaký deň príletu = rovnaké počasie, iný deň do 7 dní = odhad (k = "c").
	 */
	private static function with_weather( $deals, $base, $prev = null ) {
		$known = array();
		$list  = array_merge( self::all_deals( $prev ), self::all_deals( $base ) ); // GitHub dáta majú prednosť
		foreach ( $list as $d ) {
			if ( ! empty( $d['weather'] ) && ! empty( $d['depart'] ) && ! empty( $d['dest'] ) ) {
				$known[ $d['dest'] ][ $d['depart'] ] = $d['weather'];
			}
		}
		foreach ( $deals as $i => $d ) {
			unset( $deals[ $i ]['weather'] );
			if ( empty( $known[ $d['dest'] ] ) || empty( $d['depart'] ) ) {
				continue;
			}
			if ( isset( $known[ $d['dest'] ][ $d['depart'] ] ) ) {
				$deals[ $i ]['weather'] = $known[ $d['dest'] ][ $d['depart'] ];
				continue;
			}
			$best = null;
			$gap  = 8;
			foreach ( $known[ $d['dest'] ] as $day => $wx ) {
				$diff = abs( ( strtotime( $day ) - strtotime( $d['depart'] ) ) / DAY_IN_SECONDS );
				if ( $diff < $gap ) {
					$gap  = $diff;
					$best = $wx;
				}
			}
			if ( $best ) {
				$best['k']              = 'c';
				$deals[ $i ]['weather'] = $best;
			}
		}
		return $deals;
	}

	private static function build( $deals, $prev, $errors, $base = null ) {
		$geo  = self::geo();
		$tz   = new DateTimeZone( 'Europe/Vienna' );
		$now  = new DateTimeImmutable( 'now', $tz );
		$from = $now->format( 'Y-m-d' );
		$to   = $now->modify( '+' . $geo['horizon_days'] . ' days' )->format( 'Y-m-d' );

		$sources = array();
		foreach ( $deals as $d ) {
			$sources[ $d['source'] ] = isset( $sources[ $d['source'] ] ) ? $sources[ $d['source'] ] + 1 : 1;
		}
		// Wizz Air sa naživo nehľadá (trvá minúty) – berieme ho z poslednej aktualizácie.
		$prev_deals = is_array( $prev ) && isset( $prev['deals'] ) ? $prev['deals'] : array();
		foreach ( $prev_deals as $d ) {
			if ( isset( $d['source'] ) && 'wizzair' === $d['source'] ) {
				$deals[] = $d;
			}
		}
		foreach ( is_array( $prev ) && isset( $prev['watch'] ) ? $prev['watch'] : array() as $w ) {
			if ( ! empty( $w['deal']['source'] ) && 'wizzair' === $w['deal']['source'] ) {
				$deals[] = $w['deal'];
			}
		}

		$deals = array_filter( $deals, function ( $d ) use ( $from, $to ) {
			return ! empty( $d['depart'] ) && $d['depart'] > $from && $d['depart'] <= $to;
		} );

		// najlacnejšia ponuka na každú trasu
		$best = array();
		foreach ( $deals as $d ) {
			$k = $d['origin'] . '-' . $d['dest'];
			if ( ! isset( $best[ $k ] ) || $d['price'] < $best[ $k ]['price'] ) {
				$best[ $k ] = $d;
			}
		}
		$out = array_values( $best );
		usort( $out, function ( $a, $b ) { return $a['price'] - $b['price']; } );

		// zmena ceny iba pri tom istom lete z predchádzajúceho hľadania (letiská aj dátumy)
		$prev_price = array();
		foreach ( self::all_deals( $prev ) as $d ) {
			$prev_price[ self::flight_key( $d ) ] = $d['price'];
		}
		$seen   = array();
		$unique = array();
		foreach ( $out as $d ) {
			$d['city']      = isset( $geo['city_sk'][ $d['city'] ] ) ? $geo['city_sk'][ $d['city'] ] : $d['city'];
			$d['prevPrice'] = isset( $prev_price[ self::flight_key( $d ) ] ) ? $prev_price[ self::flight_key( $d ) ] : null;
			$k              = $d['origin'] . '|' . $d['city'] . '|' . $d['price'] . '|' . $d['depart'] . '|' . $d['return'];
			if ( ! isset( $seen[ $k ] ) ) {
				$seen[ $k ] = 1;
				$unique[]   = $d;
			}
		}

		// počasie z dát z GitHubu (predošlé živé hľadanie ho nemusí mať – napr. z verzie bez počasia)
		$unique = self::with_weather( $unique, is_array( $base ) ? $base : $prev, $prev );

		// Bangkok, Dubaj, Abu Dhabí – iba spiatočné letenky
		$prev_alt   = array(); // ponuky z náhradného letiska (SAE z Budapešti) – naživo sa nehľadajú
		foreach ( is_array( $prev ) && isset( $prev['watch'] ) ? $prev['watch'] : array() as $w ) {
			if ( ! empty( $w['deal'] ) ) {
				$d = $w['deal'];
				if ( ! isset( $geo['origins'][ $d['origin'] ] ) && ! empty( $d['return'] ) && ! empty( $d['depart'] ) &&
					$d['depart'] > $from && $d['depart'] <= $to ) {
					$prev_alt[ $w['name'] ] = $d;
				}
			}
		}
		$watch = array();
		foreach ( $geo['watch'] as $name => $w ) {
			$deal = null;
			foreach ( $unique as $d ) {
				if ( in_array( $d['dest'], $w['airports'], true ) && $d['return'] && ( ! $deal || $d['price'] < $deal['price'] ) ) {
					$deal = $d;
				}
			}
			if ( ! $deal && isset( $prev_alt[ $name ] ) ) {
				$deal = $prev_alt[ $name ]; // prevPrice ostáva z aktualizácie na GitHube
			} elseif ( $deal ) {
				if ( $w['featured'] ) {
					$deal['city'] = $name; // pri zozname ostane skutočné mesto (Tokio, Phuket…)
				}
				$deal['prevPrice'] = isset( $prev_price[ self::flight_key( $deal ) ] ) ? $prev_price[ self::flight_key( $deal ) ] : null;
			}
			$watch[] = array(
				'name' => $name, 'airports' => $w['airports'], 'featured' => $w['featured'],
				'list' => ! empty( $w['list'] ), 'note' => isset( $w['note'] ) ? $w['note'] : null, 'deal' => $deal,
				'searchUrl' => self::MOMONDO . '/explore/VIE-' . $w['airports'][0],
			);
		}

		$origins = array();
		foreach ( $geo['origins'] as $code => $city ) {
			$origins[] = array( 'code' => $code, 'city' => $city );
		}
		return array(
			'source'      => 'live',
			'sources'     => array( 'momondo', 'ryanair', 'wizzair' ),
			'currency'    => self::CURRENCY,
			'fx'          => self::$used_rate ? array( 'source' => 'ECB', 'date' => '', 'rates' => array( 'GBP' => self::$used_rate ) ) : null,
			'updatedAt'   => $now->format( 'Y-m-d\TH:iP' ),
			'prevUpdatedAt' => is_array( $prev ) && isset( $prev['updatedAt'] ) ? $prev['updatedAt'] : null,
			'nextUpdate'  => null,
			'origins'     => $origins,
			'errors'      => array_slice( $errors, 0, 10 ),
			'watch'       => $watch,
			'deals'       => self::per_group( $unique, $geo['max_per_group'] ),
			'live'        => true,
			'liveAt'      => time(),
			'liveSources' => $sources,
		);
	}
}
