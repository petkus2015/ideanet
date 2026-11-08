<?php
/**
 * Plugin Name:       IDEANET Optimalizácia obrázkov
 * Description:       Prevedie obrázky použité v článkoch (JPEG/PNG) na webové formáty WebP alebo AVIF, upraví odkazy v obsahu zmaže staré nahradené súbory a umožní vyčistiť nepoužité obrázky z knižnice médií.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            IDEANET
 * License:           GPL-2.0-or-later
 * Text Domain:       ideanet-image-optimizer
 */

defined( 'ABSPATH' ) || exit;

define( 'INO_VERSION', '1.3.0' );
define( 'INO_DIR', plugin_dir_path( __FILE__ ) );
define( 'INO_URL', plugin_dir_url( __FILE__ ) );

require_once INO_DIR . 'includes/class-ino-converter.php';
require_once INO_DIR . 'includes/class-ino-cleaner.php';
require_once INO_DIR . 'includes/class-ino-files.php';
require_once INO_DIR . 'includes/class-ino-admin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once INO_DIR . 'includes/class-ino-cli.php';
}

INO_Admin::init();
