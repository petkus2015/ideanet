<?php
/**
 * Plugin Name:       IDEANET — bloky pre Avada Builder
 * Plugin URI:        https://ideanet.sk
 * Description:       Editovateľné bloky (Fusion Builder elementy) pre web IDEANET — hero, služby, portfóliové karusely, proces, cenník, školenia, referencie a kontakt. Texty, obrázky, videá aj referencie sa dopĺňajú priamo v Avada Builderi.
 * Version:           1.0.1
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Peter Miškus
 * Text Domain:       ideanet-avada-blocks
 * Domain Path:       /languages
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

define( 'IDEANET_BLOCKS_VERSION', '1.0.1' );
define( 'IDEANET_BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'IDEANET_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require_once IDEANET_BLOCKS_DIR . 'includes/functions.php';

/**
 * Načíta preklady pluginu.
 */
function ideanet_blocks_load_textdomain() {
	load_plugin_textdomain( 'ideanet-avada-blocks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'ideanet_blocks_load_textdomain' );

/**
 * Vloží CSS a JS bloku na každú stránku frontendu.
 *
 * Zámerne NEČAKÁ na to, kým sa niektorý blok skutočne vykreslí (to sa deje
 * až v <body>, dávno po tom, čo WordPress vytlačí <head> a s ním aj queue
 * štýlov) — musí byť zavesené na 'wp_enqueue_scripts', ktoré beží ešte
 * pred 'wp_head'. Inak by sa štýly do stránky nikdy nedostali a bloky by
 * sa zobrazili úplne bez dizajnu.
 */
add_action( 'wp_enqueue_scripts', 'ideanet_blocks_enqueue_assets' );

/**
 * Ak Avada / Fusion Builder nie je aktívny, zobrazí upozornenie v administrácii.
 * Plugin sa aj tak bezpečne načíta — shortcody fungujú, len sa nezobrazia
 * ako prvky v builderi, kým Fusion Builder nebude aktívny.
 */
function ideanet_blocks_admin_notice() {
	if ( function_exists( 'fusion_builder_map' ) ) {
		return;
	}
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<?php
			esc_html_e(
				'IDEANET — bloky pre Avada Builder: nenašiel sa aktívny Fusion Builder (súčasť témy Avada). Bloky sa objavia v Avada Builderi, keď bude téma Avada aktívna.',
				'ideanet-avada-blocks'
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'ideanet_blocks_admin_notice' );
