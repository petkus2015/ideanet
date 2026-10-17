<?php
/**
 * Načítanie všetkých blokov (Fusion Builder elementov).
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

require_once IDEANET_BLOCKS_DIR . 'includes/helpers.php';
require_once IDEANET_BLOCKS_DIR . 'includes/diagnostics.php';
require_once IDEANET_BLOCKS_DIR . 'includes/admin-content.php';

/**
 * Centrálny zoznam blokov: názov súboru => shortcode, ktorý registruje.
 * Používa ho loader nižšie aj diagnostická stránka (Nástroje → IDEANET bloky),
 * aby vedela overiť, že každý shortcode sa naozaj zaregistroval.
 *
 * @return array<string, string>
 */
function ideanet_blocks_element_map() {
	return array(
		'nav'          => 'ideanet_nav',
		'hero'         => 'ideanet_hero',
		'about'        => 'ideanet_about',
		'services'     => 'ideanet_services',
		'carousel'     => 'ideanet_carousel',
		'process'      => 'ideanet_process',
		'pricing'      => 'ideanet_pricing',
		'training'     => 'ideanet_training',
		'testimonials' => 'ideanet_testimonials',
		'contact'      => 'ideanet_contact',
	);
}

/**
 * Zaregistruje všetky IDEANET bloky. Volané na 'plugins_loaded', teda
 * bezpečne pred tým, ako Avada/Fusion Builder spustí svoj vlastný
 * inicializačný hook 'fusion_builder_before_init', na ktorý sa každý
 * blok napojí sám (pozri jednotlivé súbory v includes/elements/).
 */
function ideanet_blocks_load_elements() {
	foreach ( array_keys( ideanet_blocks_element_map() ) as $element ) {
		$file = IDEANET_BLOCKS_DIR . 'includes/elements/' . $element . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
add_action( 'plugins_loaded', 'ideanet_blocks_load_elements', 20 );
