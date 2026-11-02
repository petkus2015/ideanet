<?php
/**
 * Základné nastavenie témy, menu a načítanie assetov.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Podpora funkcií WordPressu.
 */
function relife_setup(): void {
	load_theme_textdomain( 'relife', RELIFE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 112,
			'width'       => 480,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_editor_style( 'assets/css/editor.css' );

	add_image_size( 'relife-card', 640, 600, true );
	add_image_size( 'relife-hero', 1600, 900, true );

	register_nav_menus(
		array(
			'primary' => __( 'Hlavná navigácia (7 sekcií)', 'relife' ),
			'footer'  => __( 'Pätička', 'relife' ),
		)
	);
}
add_action( 'after_setup_theme', 'relife_setup' );

/**
 * Šírka obsahu pre embeddy.
 */
function relife_content_width(): void {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'relife_content_width', 0 );

/**
 * Štýly a skripty.
 */
function relife_enqueue_assets(): void {
	if ( apply_filters( 'relife_load_google_fonts', true ) ) {
		wp_enqueue_style(
			'relife-fonts',
			'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,800&family=Inter:wght@400;500;600;700&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		);
	}

	wp_enqueue_style( 'relife', RELIFE_URI . '/assets/css/relife.css', array(), RELIFE_VERSION );
	wp_add_inline_style( 'relife', relife_accent_css() );

	wp_enqueue_script( 'relife', RELIFE_URI . '/assets/js/relife.js', array(), RELIFE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'relife',
		'relifeData',
		array(
			'restUrl'  => esc_url_raw( rest_url( 'wp/v2/posts' ) ),
			'savedUrl' => esc_url_raw( relife_page_url_by_template( 'template-saved.php' ) ),
			'moods'    => relife_moods(),
			'i18n'     => array(
				'save'      => __( 'Uložiť článok', 'relife' ),
				'unsave'    => __( 'Odstrániť z uložených', 'relife' ),
				'thanks'    => __( 'Ďakujeme za hlas. Tvoj hlas sa ukladá len v tvojom prehliadači.', 'relife' ),
				'beforeVote' => __( 'Výsledky sa zobrazia po tvojom hlasovaní.', 'relife' ),
				'empty'     => __( 'Zatiaľ nemáš nič uložené. Klepni na záložku pri článku.', 'relife' ),
				'moodNow'   => __( 'Podľa tvojej nálady:', 'relife' ),
				'moodNone'  => __( 'Pre túto náladu zatiaľ nemáme dosť článkov.', 'relife' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'relife_enqueue_assets' );

/**
 * Nálady a im priradené sekcie (slugy kategórií). Filtrovateľné.
 *
 * @return array<int, array{id:string,label:string,sections:array<int,string>}>
 */
function relife_moods(): array {
	$moods = array(
		array( 'id' => 'curious', 'label' => __( 'Zvedavý', 'relife' ), 'sections' => array( 'tech', 'trendy' ) ),
		array( 'id' => 'tired',   'label' => __( 'Unavený', 'relife' ), 'sections' => array( 'health', 'life' ) ),
		array( 'id' => 'driven',  'label' => __( 'Ambiciózny', 'relife' ), 'sections' => array( 'money', 'tech' ) ),
		array( 'id' => 'escape',  'label' => __( 'Túžim po úniku', 'relife' ), 'sections' => array( 'travel', 'style' ) ),
		array( 'id' => 'calm',    'label' => __( 'Chcem pokoj', 'relife' ), 'sections' => array( 'life', 'health' ) ),
	);

	/** @var array<int, array{id:string,label:string,sections:array<int,string>}> $moods */
	$moods = apply_filters( 'relife_moods', $moods );
	return $moods;
}

/**
 * Akcentová farba z Customizeru.
 */
function relife_accent_css(): string {
	$accent = sanitize_hex_color( (string) get_theme_mod( 'relife_accent', '#3CFFD0' ) );
	if ( null === $accent || '' === $accent ) {
		$accent = '#3CFFD0';
	}
	$logo_h = max( 24, min( 120, (int) get_theme_mod( 'relife_logo_height', 56 ) ) );
	return ':root{--rl-accent:' . $accent . ';--rl-logo-h:' . $logo_h . 'px;}';
}

/**
 * Vráti URL stránky, ktorá používa danú šablónu (alebo domovskú URL).
 */
function relife_page_url_by_template( string $template ): string {
	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'     => 1,
		)
	);
	if ( ! empty( $pages ) ) {
		return (string) get_permalink( $pages[0] );
	}
	return home_url( '/' );
}

/**
 * Záložný obsah hlavného menu, kým si ho admin nevytvorí.
 */
function relife_fallback_menu(): void {
	$cats = get_categories(
		array(
			'hide_empty' => false,
			'orderby'    => 'term_id',
			'exclude'    => array( (int) get_option( 'default_category' ) ),
		)
	);
	echo '<ul class="rl-nav__list">';
	foreach ( $cats as $cat ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( get_category_link( $cat ) ), esc_html( $cat->name ) );
	}
	echo '</ul>';
}

/**
 * Prístupnosť: odkaz „Preskočiť na obsah".
 */
function relife_body_class( array $classes ): array {
	$classes[] = 'rl-dark';
	return $classes;
}
add_filter( 'body_class', 'relife_body_class' );

/**
 * Dĺžka výťahu.
 */
function relife_excerpt_length(): int {
	return 24;
}
add_filter( 'excerpt_length', 'relife_excerpt_length' );

function relife_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'relife_excerpt_more' );

/**
 * Nadpis archívu bez predpony („Kategória: ", „Štítok: " …).
 */
function relife_archive_title( string $title ): string {
	if ( is_category() || is_tag() || is_tax() ) {
		return single_term_title( '', false );
	}
	if ( is_post_type_archive() ) {
		return post_type_archive_title( '', false );
	}
	if ( is_author() ) {
		return get_the_author();
	}
	return $title;
}
add_filter( 'get_the_archive_title', 'relife_archive_title' );
