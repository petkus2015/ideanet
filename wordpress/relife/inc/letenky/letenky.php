<?php
/**
 * Lacné letenky VIE · BTS – zabudované v téme RELIFE (pôvodne samostatný plugin „Lacné letenky“, verzia 1.16.0).
 *
 * Shortcode [lacne_letenky], blok „Lacné letenky“ v editore, widget a stránka Lacné letenky. Vzhľad je prispôsobený dizajnu RELIFE.
 * Ak je nainštalovaný aj samostatný plugin, téma svoju kópiu nenačíta (aby sa nič neduplikovalo).
 *
 * @package Relife
 */

declare(strict_types=0);

defined( 'ABSPATH' ) || exit;

if ( defined( 'LACNE_LETENKY_VERSION' ) ) {
	return; // Aktívny je samostatný plugin.
}

define( 'LACNE_LETENKY_VERSION', '1.16.0-relife.6' );
define( 'LACNE_LETENKY_FILE', __FILE__ );
define( 'LACNE_LETENKY_DIR', trailingslashit( __DIR__ ) );
define( 'LACNE_LETENKY_URL', trailingslashit( RELIFE_URI . '/inc/letenky' ) );

// Ceny aktualizuje GitHub Actions 3× denne do tohto súboru (HEAD = predvolená vetva repozitára).
define( 'LACNE_LETENKY_DEFAULT_SRC', 'https://raw.githubusercontent.com/petkus2015/ideanet/HEAD/plugins/lacne-letenky/data/deals.json' );
// Záloha, kým sa zmeny nezlúčia do hlavnej vetvy (dovtedy je súbor len vo vetve).
define( 'LACNE_LETENKY_FALLBACK_SRC', 'https://raw.githubusercontent.com/petkus2015/ideanet/claude/cheap-flights-vienna-plugin-3ehoah/plugins/lacne-letenky/data/deals.json' );

require_once LACNE_LETENKY_DIR . 'includes/class-data.php';
require_once LACNE_LETENKY_DIR . 'includes/class-weather.php';
require_once LACNE_LETENKY_DIR . 'includes/class-live.php';
require_once LACNE_LETENKY_DIR . 'includes/class-render.php';
require_once LACNE_LETENKY_DIR . 'includes/class-settings.php';
require_once LACNE_LETENKY_DIR . 'includes/class-widget.php';

// Po aktualizácii zahodíme uložený výsledok živého hľadania – nová verzia môže pridávať údaje.
add_action(
	'init',
	static function (): void {
		if ( get_option( 'lacne_letenky_version' ) !== LACNE_LETENKY_VERSION ) {
			delete_transient( 'lacne_letenky_live' );
			delete_transient( 'lacne_letenky_wx' );
			delete_transient( 'lacne_letenky_wx_fail' );
			delete_transient( 'lacne_letenky_data' );
			delete_transient( 'lacne_letenky_fail' );
			delete_option( 'lacne_letenky_live_last' );
			update_option( 'lacne_letenky_version', LACNE_LETENKY_VERSION, false );
		}
	},
	5
);
add_action( 'init', array( 'Lacne_Letenky_Render', 'init' ) );
add_action( 'rest_api_init', array( 'Lacne_Letenky_Data', 'register_rest' ) );
add_action(
	'widgets_init',
	static function (): void {
		register_widget( 'Lacne_Letenky_Widget' );
	}
);
Lacne_Letenky_Settings::init();
