<?php
/**
 * RELIFE – funkcie témy.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RELIFE_VERSION', (string) wp_get_theme( get_template() )->get( 'Version' ) ); // Verzia sa berie z hlavičky style.css.
define( 'RELIFE_DIR', get_template_directory() );
define( 'RELIFE_URI', get_template_directory_uri() );

require_once RELIFE_DIR . '/inc/setup.php';
require_once RELIFE_DIR . '/inc/content-model.php';
require_once RELIFE_DIR . '/inc/template-tags.php';
require_once RELIFE_DIR . '/inc/customizer.php';
require_once RELIFE_DIR . '/inc/demo-content.php';
