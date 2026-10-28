<?php
/**
 * Plugin Name:       Meškanie vlakov
 * Description:       Tabuľa odchodov a príchodov s aktuálnym meškaním vlakov na Slovensku. Na stránku vložte shortcode [meskanie_vlakov].
 * Version:           1.2.9
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            IDEANET
 * License:           GPL-2.0-or-later
 * Text Domain:       meskanie-vlakov
 */

defined( 'ABSPATH' ) || exit;

define( 'MV_VERSION', '1.2.9' );
define( 'MV_FILE', __FILE__ );
define( 'MV_DIR', plugin_dir_path( __FILE__ ) );
define( 'MV_URL', plugin_dir_url( __FILE__ ) );

require_once MV_DIR . 'includes/class-mv-fetcher.php';
require_once MV_DIR . 'includes/class-mv-admin.php';

/**
 * Nastavenia pluginu s predvolenými hodnotami.
 */
function mv_settings() {
	$defaults = array(
		'demo'           => 0,
		'google_fonts'   => 1,
		'quick_stations' => 'Bratislava, Žilina, Košice, Banská Bystrica, Poprad-Tatry, Nové Zámky',
		'gtfs_url'       => MV_Fetcher::GTFS_URL,
	);
	return wp_parse_args( (array) get_option( 'mv_settings', array() ), $defaults );
}

/* ---------- plánovač: poloha vlakov každé 2 minúty, cestovný poriadok raz denne ---------- */

add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['mv_2min'] = array(
			'interval' => 2 * MINUTE_IN_SECONDS,
			'display'  => 'Každé 2 minúty (Meškanie vlakov)',
		);
		return $schedules;
	}
);

function mv_schedule_events() {
	// staršia verzia pluginu plánovala každých 5 minút: prenastavíme na 2 minúty
	if ( wp_next_scheduled( 'mv_fetch_trains' ) && 'mv_2min' !== wp_get_schedule( 'mv_fetch_trains' ) ) {
		wp_clear_scheduled_hook( 'mv_fetch_trains' );
	}
	if ( ! wp_next_scheduled( 'mv_fetch_trains' ) ) {
		wp_schedule_event( time(), 'mv_2min', 'mv_fetch_trains' );
	}
}

register_activation_hook(
	__FILE__,
	function () {
		mv_schedule_events();
		wp_schedule_single_event( time(), 'mv_build_timetable' );
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'mv_fetch_trains' );
		wp_clear_scheduled_hook( 'mv_build_timetable' );
	}
);

// ak by udalosť z nejakého dôvodu chýbala (napr. po aktualizácii pluginu), naplánuje sa znova
add_action( 'init', 'mv_schedule_events' );
add_action( 'mv_fetch_trains', array( 'MV_Fetcher', 'cron_trains' ) );
add_action( 'mv_build_timetable', array( 'MV_Fetcher', 'build_timetable' ) );

/* ---------- shortcode [meskanie_vlakov] ---------- */

// registrácia už pri init: blokové témy vykresľujú obsah stránky (a shortcode) ešte pred wp_enqueue_scripts
add_action(
	'init',
	function () {
		wp_register_script( 'mv-app', MV_URL . 'assets/meskanie-vlakov.js', array(), MV_VERSION, true );
		wp_register_style(
			'mv-fonts',
			'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap',
			array(),
			null
		);
	}
);

/* ---------- optimalizačné pluginy: skript tabule neodkladať, nezlučovať, neupravovať ---------- */

// atribúty, ktoré rešpektujú Cloudflare Rocket Loader, WP Rocket, LiteSpeed Cache, Jetpack Boost a ďalšie
add_filter(
	'script_loader_tag',
	function ( $tag, $handle ) {
		if ( 'mv-app' !== $handle ) {
			return $tag;
		}
		return str_replace( ' src=', ' data-cfasync="false" data-no-optimize="1" data-no-defer="1" data-no-minify="1" data-noptimize="1" data-jetpack-boost="ignore" nowprocket src=', $tag );
	},
	10,
	2
);

function mv_exclude_list( $list ) {
	$list   = is_array( $list ) ? $list : array();
	$list[] = 'meskanie-vlakov/assets/';
	return $list;
}
foreach ( array(
	'rocket_delay_js_exclusions',      // WP Rocket: oneskorenie JS
	'rocket_exclude_defer_js',         // WP Rocket: defer
	'rocket_exclude_js',               // WP Rocket: minifikácia/zlučovanie
	'litespeed_optm_js_defer_exc',     // LiteSpeed Cache: defer/delay
	'litespeed_optimize_js_excludes',  // LiteSpeed Cache: minifikácia/zlučovanie
	'perfmatters_delay_js_exclusions', // Perfmatters
) as $mv_filter ) {
	add_filter( $mv_filter, 'mv_exclude_list' );
}
foreach ( array( 'sgo_js_minify_exclude', 'sgo_javascript_combine_exclude', 'sgo_js_async_exclude' ) as $mv_filter ) {
	// SiteGround Optimizer: zoznam handle-ov
	add_filter(
		$mv_filter,
		function ( $list ) {
			$list   = is_array( $list ) ? $list : array();
			$list[] = 'mv-app';
			return $list;
		}
	);
}
add_filter(
	'autoptimize_filter_js_exclude',
	function ( $exclude ) {
		return ( is_string( $exclude ) && '' !== $exclude ? $exclude . ', ' : '' ) . 'meskanie-vlakov/assets/';
	}
);

add_shortcode( 'meskanie_vlakov', 'mv_shortcode' );

/**
 * [meskanie_vlakov sirka="plna" stanice="Žilina, Košice"]
 */
function mv_shortcode( $atts ) {
	static $rendered = false;
	if ( $rendered ) {
		return '<!-- Meškanie vlakov: na jednej stránke môže byť len raz -->';
	}
	$rendered = true;

	$atts     = shortcode_atts(
		array(
			'sirka'   => '',
			'stanice' => '',
		),
		$atts,
		'meskanie_vlakov'
	);
	$settings = mv_settings();
	$stations = $atts['stanice'] ? $atts['stanice'] : $settings['quick_stations'];

	$config = array(
		'trainsUrl'     => MV_Fetcher::file_url( 'trains.json' ),
		'historyUrl'    => MV_Fetcher::file_url( 'history.json' ),
		'timetableUrl'  => MV_Fetcher::file_url( 'timetable.json' ),
		'cssUrl'        => MV_URL . 'assets/app.css?ver=' . MV_VERSION,
		'demo'          => (bool) $settings['demo'],
		'quickStations' => array_values( array_filter( array_map( 'trim', explode( ',', $stations ) ) ) ),
		// odkaz na nahratie cestovného poriadku vidí len správca (keď chýba)
		'adminUrl'      => current_user_can( 'manage_options' ) ? admin_url( 'options-general.php?page=meskanie-vlakov' ) : '',
		'noDataNotice'  => '<b>Ukážkové dáta.</b> Údaje zo ŽSR sa ešte nestiahli. Prvé stiahnutie prebehne do 2 minút po aktivácii pluginu.',
	);

	if ( $settings['google_fonts'] ) {
		wp_enqueue_style( 'mv-fonts' );
	}
	wp_enqueue_script( 'mv-app' );

	$template = file_get_contents( MV_DIR . 'templates/app.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	// alignfull: štandardná trieda WordPressu na roztiahnutie bloku na celú šírku (téma má prednosť pred :host)
	$full = 'plna' === $atts['sirka'];
	$width = $full ? ' data-width="plna"' : '';

	// na mobile od kraja po kraj: téma obsah odsadzuje, tak tabuľu roztiahneme na šírku okna
	$css = '<style>@media (max-width: 860px){#meskanie-vlakov.mv-host{width:100vw!important;max-width:100vw!important;'
		. 'margin-left:calc(50% - 50vw)!important;margin-right:calc(50% - 50vw)!important;border-radius:0!important}}</style>';

	// text vo vnútri sa ukazuje len kým sa tabuľa nespustí (potom ho prekryje Shadow DOM)
	return $css . '<div id="meskanie-vlakov" class="mv-host' . ( $full ? ' alignfull' : '' ) . '"' . $width . ' data-config="' . esc_attr( wp_json_encode( $config ) ) . '">'
		. '<p style="padding:24px;border-radius:14px;background:#0a0f1c;color:#e7ecf7;font:17px/1.5 system-ui,sans-serif">Načítavam meškanie vlakov…</p></div>'
		. '<template id="mv-template">' . $template . '</template>'
		. '<noscript><p>Na zobrazenie meškania vlakov je potrebný zapnutý JavaScript.</p></noscript>';
}
