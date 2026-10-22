<?php
/**
 * IDEANET — vstupný bod témy.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'inc/defaults.php' );
require_once get_theme_file_path( 'inc/helpers.php' );
require_once get_theme_file_path( 'inc/content-types.php' );
require_once get_theme_file_path( 'inc/setup.php' );
require_once get_theme_file_path( 'inc/customizer.php' );
require_once get_theme_file_path( 'inc/pricing.php' );
require_once get_theme_file_path( 'inc/form.php' );
require_once get_theme_file_path( 'inc/demo.php' );

/**
 * Popis stránky pre vyhľadávače, keď ho WordPress sám nedoplní.
 */
function ideanet_meta_description() {
	if ( ! is_front_page() ) {
		return;
	}

	$desc = get_bloginfo( 'description' );
	if ( ! $desc ) {
		$desc = wp_strip_all_tags( ideanet_opt( 'hero_lead' ) );
	}

	printf(
		'<meta name="description" content="%s">' . "\n" . '<meta name="theme-color" content="#05060f">' . "\n",
		esc_attr( wp_trim_words( $desc, 32, '' ) )
	);
}
add_action( 'wp_head', 'ideanet_meta_description', 1 );

/**
 * Triedy na tele stránky.
 *
 * @param string[] $classes Doterajšie triedy.
 * @return string[]
 */
function ideanet_body_class( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'ideanet-front';
	}

	return $classes;
}
add_filter( 'body_class', 'ideanet_body_class' );
