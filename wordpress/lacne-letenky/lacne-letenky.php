<?php
/**
 * Plugin Name:       Lacné letenky VIE · BTS
 * Description:       Blok s najlacnejšími letenkami z Viedne a Bratislavy kamkoľvek (momondo, Ryanair, Wizz Air). Vloženie cez shortcode [lacne_letenky], blok v editore, widget alebo prvok v tagDiv Composer (téma Newspaper).
 * Version:           1.0.3
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            IDEANET
 * License:           GPL-2.0-or-later
 * Text Domain:       lacne-letenky
 */

defined( 'ABSPATH' ) || exit;

define( 'LACNE_LETENKY_VERSION', '1.0.3' );
define( 'LACNE_LETENKY_FILE', __FILE__ );
define( 'LACNE_LETENKY_DIR', plugin_dir_path( __FILE__ ) );
define( 'LACNE_LETENKY_URL', plugin_dir_url( __FILE__ ) );

// Ceny aktualizuje GitHub Actions 3× denne (7:00, 12:00, 18:00) do tohto súboru.
// HEAD = predvolená vetva repozitára. Adresa sa dá zmeniť v Nastavenia → Lacné letenky.
define( 'LACNE_LETENKY_DEFAULT_SRC', 'https://raw.githubusercontent.com/petkus2015/ideanet/HEAD/plugins/lacne-letenky/data/deals.json' );

require_once LACNE_LETENKY_DIR . 'includes/class-data.php';
require_once LACNE_LETENKY_DIR . 'includes/class-render.php';
require_once LACNE_LETENKY_DIR . 'includes/class-settings.php';
require_once LACNE_LETENKY_DIR . 'includes/class-widget.php';
require_once LACNE_LETENKY_DIR . 'tagdiv/integration.php';

add_action( 'init', array( 'Lacne_Letenky_Render', 'init' ) );
add_action( 'rest_api_init', array( 'Lacne_Letenky_Data', 'register_rest' ) );
add_action( 'widgets_init', function () {
	register_widget( 'Lacne_Letenky_Widget' );
} );
Lacne_Letenky_Settings::init();
Lacne_Letenky_TagDiv::init();

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=lacne-letenky' ) ) . '">' . esc_html__( 'Nastavenia', 'lacne-letenky' ) . '</a>' );
	return $links;
} );
