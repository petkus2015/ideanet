<?php
/**
 * Plugin Name:       Mediálna gramotnosť
 * Description:       Vzdelávacie prvky o podvodoch a dezinformáciách (ukážky SMS, e-mailov, Facebooku, trenažér, kontrola správ a adries, rýchle karty, pomoc obetiam). Prvky sú dostupné v Avada Builderi aj ako shortcody.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            IDEANET
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       medialna-gramotnost
 *
 * @package MedialnaGramotnost
 */

defined( 'ABSPATH' ) || exit;

define( 'MG_VERSION', '1.0.0' );
define( 'MG_FILE', __FILE__ );
define( 'MG_DIR', plugin_dir_path( __FILE__ ) );
define( 'MG_URL', plugin_dir_url( __FILE__ ) );

require_once MG_DIR . 'includes/elements.php';
require_once MG_DIR . 'includes/helpers.php';
require_once MG_DIR . 'includes/mock.php';
require_once MG_DIR . 'includes/render.php';
require_once MG_DIR . 'includes/avada.php';
require_once MG_DIR . 'includes/settings.php';
require_once MG_DIR . 'includes/demo.php';

/**
 * Registrácia shortcodov podľa definície prvkov.
 */
function mg_register_shortcodes() {
	foreach ( mg_elements() as $tag => $el ) {
		$fn = 'mg_render_' . substr( $tag, 3 );
		if ( ! function_exists( $fn ) ) {
			continue;
		}
		add_shortcode(
			$tag,
			function ( $atts, $content = '' ) use ( $tag, $fn ) {
				return call_user_func( $fn, mg_atts( $tag, $atts ), (string) $content );
			}
		);
	}
	// Záložné rozloženie pre stránky bez Avada Buildera.
	add_shortcode( 'mg_section', 'mg_render_section' );
	add_shortcode( 'mg_cols', 'mg_render_cols' );
	add_shortcode( 'mg_col', 'mg_render_col' );
}
add_action( 'init', 'mg_register_shortcodes' );

/**
 * Načítanie štýlov, písma a skriptu.
 */
function mg_enqueue_assets() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	$opt = mg_options();
	if ( $opt['load_fonts'] ) {
		wp_enqueue_style( 'mg-fonts', 'https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@400;700&family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=JetBrains+Mono:wght@400;700&display=swap', array(), null );
	}
	wp_enqueue_style( 'mg', MG_URL . 'assets/css/mg.css', array(), MG_VERSION );
	wp_add_inline_style( 'mg', mg_custom_css( $opt ) );

	wp_enqueue_script( 'mg', MG_URL . 'assets/js/mg.js', array(), MG_VERSION, true );
	wp_localize_script(
		'mg',
		'mgData',
		array(
			'i18n' => array(
				'sizeUp'      => __( 'Zväčšiť písmo', 'medialna-gramotnost' ),
				'sizeReset'   => __( 'Vrátiť bežné písmo', 'medialna-gramotnost' ),
				'sample'      => __( 'Ukážka', 'medialna-gramotnost' ),
				'question'    => __( 'Otázka', 'medialna-gramotnost' ),
				'right'       => __( 'Správne.', 'medialna-gramotnost' ),
				'wrong'       => __( 'Tentoraz nie.', 'medialna-gramotnost' ),
				'notQuite'    => __( 'Nie celkom.', 'medialna-gramotnost' ),
				'seeFlags'    => __( 'Pozrite si zvýraznené varovné znaky pri ukážke.', 'medialna-gramotnost' ),
				'seeSafe'     => __( 'Táto správa je v poriadku. Pri ukážke sú vysvetlené dôvody.', 'medialna-gramotnost' ),
				'next'        => __( 'Ďalšia ukážka', 'medialna-gramotnost' ),
				'result'      => __( 'Zobraziť výsledok', 'medialna-gramotnost' ),
				'resultTitle' => __( 'Výsledok', 'medialna-gramotnost' ),
				'remember'    => __( 'Čo si zapamätať', 'medialna-gramotnost' ),
				'again'       => __( 'Skúsiť znova', 'medialna-gramotnost' ),
				'riskHigh'    => __( 'Vysoké riziko', 'medialna-gramotnost' ),
				'riskMid'     => __( 'Pozor', 'medialna-gramotnost' ),
				'riskLow'     => __( 'Nízke riziko', 'medialna-gramotnost' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'mg_enqueue_assets', 20 );

/**
 * CSS z nastavení (farby, písma témy).
 */
function mg_custom_css( $opt ) {
	$css = ':root{';
	if ( $opt['color_primary'] ) {
		$css .= '--mg-primary:' . $opt['color_primary'] . ';';
	}
	if ( $opt['color_marker'] ) {
		$css .= '--mg-marker:' . $opt['color_marker'] . ';';
	}
	if ( $opt['color_dark'] ) {
		$css .= '--mg-ink:' . $opt['color_dark'] . ';--mg-night:' . $opt['color_dark'] . ';';
	}
	if ( $opt['theme_fonts'] ) {
		$css .= '--mg-f-display:inherit;--mg-f-body:inherit;';
	}
	return $css . '}';
}

/**
 * Lišta pomoci na celom webe (ak je zapnutá).
 */
function mg_print_global_bar() {
	static $printed = false;
	$opt = mg_options();
	if ( $printed || ! $opt['alert_bar'] || is_admin() ) {
		return;
	}
	$printed = true;
	$link    = $opt['alert_page'] ? get_permalink( (int) $opt['alert_page'] ) : '';
	echo mg_render_alert_bar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapované v renderi.
		mg_atts(
			'mg_alert_bar',
			array(
				'text'      => $opt['alert_text'],
				'link_text' => $opt['alert_link_text'],
				'link'      => $link,
			)
		),
		''
	);
}
add_action( 'wp_body_open', 'mg_print_global_bar', 5 );
add_action( 'avada_before_header_wrapper', 'mg_print_global_bar', 5 );

/**
 * Plávajúce tlačidlo A+ na celom webe (ak je zapnuté).
 */
function mg_print_floating_a11y() {
	$opt = mg_options();
	if ( ! $opt['a11y_floating'] ) {
		return;
	}
	echo '<div class="mg mg-a11y mg-floating"><button type="button" class="mg-size-btn" aria-label="' . esc_attr__( 'Zväčšiť písmo', 'medialna-gramotnost' ) . '">A+</button></div>';
}
add_action( 'wp_footer', 'mg_print_floating_a11y' );

/**
 * Odkaz na nastavenia v zozname pluginov.
 */
function mg_plugin_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=medialna-gramotnost' ) ) . '">' . esc_html__( 'Nastavenia', 'medialna-gramotnost' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mg_plugin_links' );
