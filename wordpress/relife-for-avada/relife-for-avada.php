<?php
/**
 * Plugin Name:       RELIFE pre Avadu
 * Plugin URI:        https://relife.sk
 * Description:       Magazínové prvky RELIFE (anketa, výber nálady, uložené články, formáty, sekcie, mobilné spodné menu) ako plugin pre tému Avada. Bežia aj v Avada Builderi.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            RELIFE
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       relife-avada
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RLAV_VERSION', '1.0.0' );
define( 'RLAV_FILE', __FILE__ );
define( 'RLAV_DIR', plugin_dir_path( __FILE__ ) );
define( 'RLAV_URI', plugin_dir_url( __FILE__ ) );

/**
 * Plugin sa nenačíta, ak je aktívna samostatná téma RELIFE (obe používajú rovnaké typy obsahu).
 */
if ( function_exists( 'relife_setup' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Plugin „RELIFE pre Avadu“ je vypnutý, pretože je aktívna téma RELIFE. Použite jedno alebo druhé.', 'relife-avada' ) . '</p></div>';
		}
	);
	return;
}

require_once RLAV_DIR . 'includes/core.php';
require_once RLAV_DIR . 'includes/content-model.php';
require_once RLAV_DIR . 'includes/template-tags.php';
require_once RLAV_DIR . 'includes/render.php';
require_once RLAV_DIR . 'includes/shortcodes.php';
require_once RLAV_DIR . 'includes/single.php';
require_once RLAV_DIR . 'includes/settings.php';
require_once RLAV_DIR . 'includes/avada.php';
require_once RLAV_DIR . 'includes/demo.php';

register_activation_hook(
	__FILE__,
	static function (): void {
		rlav_register_content_model();
		flush_rewrite_rules();
	}
);
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
