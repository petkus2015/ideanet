<?php
/**
 * Plugin Name:       Meškanie vlakov
 * Description:       Tabuľa odchodov a príchodov s aktuálnym meškaním vlakov na Slovensku. Na stránku vložte shortcode [meskanie_vlakov].
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            IDEANET
 * License:           GPL-2.0-or-later
 * Text Domain:       meskanie-vlakov
 */

defined( 'ABSPATH' ) || exit;

define( 'MV_VERSION', '1.0.0' );
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

/* ---------- plánovač: poloha vlakov každých 5 minút, cestovný poriadok raz denne ---------- */

add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['mv_5min'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => 'Každých 5 minút (Meškanie vlakov)',
		);
		return $schedules;
	}
);

function mv_schedule_events() {
	if ( ! wp_next_scheduled( 'mv_fetch_trains' ) ) {
		wp_schedule_event( time(), 'mv_5min', 'mv_fetch_trains' );
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
		wp_register_script( 'mv-boot', MV_URL . 'assets/boot.js', array(), MV_VERSION, true );
		wp_register_script( 'mv-app', MV_URL . 'assets/app.js', array( 'mv-boot' ), MV_VERSION, true );
		wp_register_style(
			'mv-fonts',
			'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap',
			array(),
			null
		);
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
		'noDataNotice'  => '<b>Ukážkové dáta.</b> Údaje zo ŽSR sa ešte nestiahli. Prvé stiahnutie prebehne do 5 minút po aktivácii pluginu.',
	);

	if ( $settings['google_fonts'] ) {
		wp_enqueue_style( 'mv-fonts' );
	}
	wp_enqueue_script( 'mv-app' );

	$template = file_get_contents( MV_DIR . 'templates/app.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	// alignfull: štandardná trieda WordPressu na roztiahnutie bloku na celú šírku (téma má prednosť pred :host)
	$full = 'plna' === $atts['sirka'];
	$width = $full ? ' data-width="plna"' : '';

	return '<div id="meskanie-vlakov" class="mv-host' . ( $full ? ' alignfull' : '' ) . '"' . $width . ' data-config="' . esc_attr( wp_json_encode( $config ) ) . '"></div>'
		. '<template id="mv-template">' . $template . '</template>'
		. '<noscript><p>Na zobrazenie meškania vlakov je potrebný zapnutý JavaScript.</p></noscript>';
}
