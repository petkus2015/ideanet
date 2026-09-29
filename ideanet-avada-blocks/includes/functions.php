<?php
/**
 * Načítanie všetkých blokov (Fusion Builder elementov).
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

require_once IDEANET_BLOCKS_DIR . 'includes/helpers.php';

/**
 * Zaregistruje všetky IDEANET bloky. Volané na 'plugins_loaded', teda
 * bezpečne pred tým, ako Avada/Fusion Builder spustí svoj vlastný
 * inicializačný hook 'fusion_builder_before_init', na ktorý sa každý
 * blok napojí sám (pozri jednotlivé súbory v includes/elements/).
 */
function ideanet_blocks_load_elements() {
	$elements = array(
		'hero',
		'services',
		'carousel',
		'process',
		'pricing',
		'training',
		'testimonials',
		'contact',
	);

	foreach ( $elements as $element ) {
		$file = IDEANET_BLOCKS_DIR . 'includes/elements/' . $element . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
add_action( 'plugins_loaded', 'ideanet_blocks_load_elements', 20 );
