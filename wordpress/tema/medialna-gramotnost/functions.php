<?php
/**
 * Téma Mediálna gramotnosť.
 *
 * @package MedialnaGramotnostTema
 */

defined( 'ABSPATH' ) || exit;

define( 'MGT_VERSION', '1.0.0' );
define( 'MGT_DIR', get_template_directory() );
define( 'MGT_URI', get_template_directory_uri() );

require MGT_DIR . '/inc/elements.php';
require MGT_DIR . '/inc/helpers.php';
require MGT_DIR . '/inc/mock.php';
require MGT_DIR . '/inc/render.php';
require MGT_DIR . '/inc/cpt.php';
require MGT_DIR . '/inc/meta.php';
require MGT_DIR . '/inc/customizer.php';
require MGT_DIR . '/inc/template-tags.php';
require MGT_DIR . '/inc/demo.php';

function mgt_setup() {
	load_theme_textdomain( 'mg-tema', MGT_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/components.css', 'assets/css/theme.css', 'assets/css/editor.css' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 96,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	register_nav_menus(
		array(
			'primary' => 'Hlavné menu',
			'footer'  => 'Menu v pätičke',
		)
	);
}
add_action( 'after_setup_theme', 'mgt_setup' );

/**
 * Shortcody nástrojov (kontrola správy, adresy, kroky, kontakty…).
 * Vkladajú sa do stránok cez blok „Shortcode“ alebo vzory blokov.
 */
function mgt_register_shortcodes() {
	foreach ( mgt_elements() as $tag => $el ) {
		$fn = 'mgt_render_' . substr( $tag, 3 );
		if ( ! function_exists( $fn ) ) {
			continue;
		}
		add_shortcode(
			$tag,
			function ( $atts, $content = '' ) use ( $tag, $fn ) {
				return call_user_func( $fn, mgt_atts( $tag, $atts ), (string) $content );
			}
		);
	}
	add_shortcode( 'mg_section', 'mgt_render_section' );
	add_shortcode( 'mg_cols', 'mgt_render_cols' );
	add_shortcode( 'mg_col', 'mgt_render_col' );
	// Obsah z typov obsahu.
	add_shortcode( 'mg_test', 'mgt_shortcode_test' );
	add_shortcode( 'mg_priklad', 'mgt_shortcode_priklad' );
	add_shortcode( 'mg_kontakty', 'mgt_shortcode_contacts' );
}
add_action( 'init', 'mgt_register_shortcodes' );

function mgt_assets() {
	wp_enqueue_style( 'mgt-fonts', 'https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@400;600;700&family=Newsreader:opsz,wght@6..72,500;6..72,600;6..72,700&family=JetBrains+Mono:wght@400;600&display=swap', array(), null );
	wp_enqueue_style( 'mgt-components', MGT_URI . '/assets/css/components.css', array(), MGT_VERSION );
	wp_enqueue_style( 'mgt', MGT_URI . '/assets/css/theme.css', array( 'mgt-components' ), MGT_VERSION );
	wp_add_inline_style( 'mgt', mgt_custom_colors() );
	wp_enqueue_script( 'mgt', MGT_URI . '/assets/js/theme.js', array(), MGT_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'mgt_assets' );

/**
 * Celá stránka je v kontexte .mg, aby platili štýly prvkov.
 */
function mgt_body_class( $c ) {
	$c[] = 'mg';
	$c[] = 'mgt';
	return $c;
}
add_filter( 'body_class', 'mgt_body_class' );

/**
 * Odkaz s náhradou: ak je pole prázdne, použije sa archív alebo stránka.
 */
function mgt_link( $value, $fallback = '' ) {
	$value = trim( (string) $value );
	return '' !== $value ? $value : $fallback;
}

/**
 * Adresa stránky podľa slugu (alebo prázdny reťazec).
 */
function mgt_page_url( $slug ) {
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : '';
}

/**
 * Upozornenie po aktivácii témy.
 */
function mgt_activation_notice() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'mgt_demo_done' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'appearance_page_mgt-demo' === $screen->id ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>Mediálna gramotnosť:</strong> chcete naplniť web ukážkovým obsahom (príklady podvodov, testy, karty, slovník, stránky a menu)? <a class="button button-primary" href="' . esc_url( admin_url( 'themes.php?page=mgt-demo' ) ) . '">Pokračovať</a></p></div>';
}
add_action( 'admin_notices', 'mgt_activation_notice' );
