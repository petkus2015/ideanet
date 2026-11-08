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
	add_image_size( 'relife-hero-m', 800, 450, true );
	add_image_size( 'relife-card-s', 320, 300, true );
	add_image_size( 'relife-card-m', 480, 450, true );

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
	// Hlavný štýl sa vloží priamo do <head> (bez blokujúcej požiadavky). Vypnutie: add_filter( 'relife_inline_css', '__return_false' ).
	$css_file = RELIFE_DIR . '/assets/css/relife.css';
	if ( apply_filters( 'relife_inline_css', true ) && is_readable( $css_file ) ) {
		wp_register_style( 'relife', false, array(), RELIFE_VERSION );
		wp_enqueue_style( 'relife' );
		wp_add_inline_style( 'relife', relife_fonts_css() . (string) file_get_contents( $css_file ) . relife_accent_css() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	} else {
		wp_enqueue_style( 'relife', RELIFE_URI . '/assets/css/relife.css', array(), RELIFE_VERSION );
		wp_add_inline_style( 'relife', relife_fonts_css() . relife_accent_css() );
	}

	wp_enqueue_script( 'relife', RELIFE_URI . '/assets/js/relife.js', array(), RELIFE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'relife',
		'relifeData',
		array(
			'restUrl'  => esc_url_raw( rest_url( 'wp/v2/posts' ) ),
			'savedUrl' => esc_url_raw( relife_page_url_by_template( 'template-saved.php' ) ),
			'saveUrl'  => esc_url_raw( rest_url( 'relife/v1/save' ) ),
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
	$logo_h = max( 24, min( 140, (int) get_theme_mod( 'relife_logo_height', 72 ) ) );
	// Rozmery loga vopred: rezervuje sa miesto v hlavičke ešte pred načítaním obrázka (bez posunu stránky).
	$logo_vars = '';
	$logo_id   = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id > 0 ) {
		$img = wp_get_attachment_image_src( $logo_id, 'full' );
		if ( is_array( $img ) && (int) $img[1] > 0 && (int) $img[2] > 0 ) {
			$logo_vars = '--rl-logo-ratio:' . (int) $img[1] . '/' . (int) $img[2] . ';--rl-logo-nat-h:' . (int) $img[2] . 'px;';
		}
	}
	// Logo v pätičke (rovnaká logika: rezervované miesto vopred).
	$flogo_h    = max( 24, min( 140, (int) get_theme_mod( 'relife_footer_logo_height', 56 ) ) );
	$flogo_vars = '';
	$flogo_id   = (int) get_theme_mod( 'relife_footer_logo', 0 );
	if ( $flogo_id > 0 ) {
		$fimg = wp_get_attachment_image_src( $flogo_id, 'full' );
		if ( is_array( $fimg ) && (int) $fimg[1] > 0 && (int) $fimg[2] > 0 ) {
			$flogo_vars = '--rl-flogo-ratio:' . (int) $fimg[1] . '/' . (int) $fimg[2] . ';--rl-flogo-nat-h:' . (int) $fimg[2] . 'px;';
		}
	}
	return ':root{--rl-accent:' . $accent . ';--rl-logo-h:' . $logo_h . 'px;' . $logo_vars . '--rl-flogo-h:' . $flogo_h . 'px;' . $flogo_vars . '}';
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

/**
 * Logo v hlavičke: menší `sizes`, aby prehliadač nesťahoval obrovský súbor, a bez lenivého načítania.
 *
 * @param array<string, mixed> $attr
 * @return array<string, mixed>
 */
function relife_custom_logo_attributes( array $attr ): array {
	$attr['sizes']         = '(max-width: 760px) 190px, 260px';
	$attr['loading']       = 'eager';
	return $attr;
}
add_filter( 'get_custom_logo_image_attributes', 'relife_custom_logo_attributes' );

/**
 * Správny atribút `sizes` pre obrázky v texte článku, aby prehliadač nesťahoval zbytočne veľký súbor.
 *
 * @param string $sizes
 */
function relife_content_image_sizes( string $sizes ): string {
	if ( is_singular( 'post' ) && in_the_loop() ) {
		return '(max-width: 800px) 100vw, 760px';
	}
	return $sizes;
}
add_filter( 'wp_calculate_image_sizes', 'relife_content_image_sizes' );

/**
 * Stav čitateľa (nálada, uložené) sa zistí ešte pred vykreslením, aby sa rozloženie nemenilo až po načítaní skriptu.
 */
function relife_early_state_script(): void {
	echo "<script>try{var d=document.documentElement,s=window.localStorage;if(s.getItem('relife.mood')){d.classList.add('rl-has-mood')}var v=JSON.parse(s.getItem('relife.saved')||'[]');if(v&&v.length){d.classList.add('rl-has-saved')}}catch(e){}</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_head', 'relife_early_state_script', 1 );

/**
 * Obrázky v kartách: asynchrónne dekódovanie. Všetky obrázky z WordPressu majú width/height, takže majú rezervované miesto.
 *
 * @param array<string, mixed> $attr
 * @return array<string, mixed>
 */
function relife_image_attributes( array $attr ): array {
	if ( ! isset( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'relife_image_attributes' );

/**
 * Štýly zoznamov v editore blokov (Zoznam → Štýly).
 */
function relife_register_block_styles(): void {
	register_block_style( 'core/list', array( 'name' => 'rl-check', 'label' => __( 'Zaškrtnutia', 'relife' ) ) );
	register_block_style( 'core/list', array( 'name' => 'rl-cross', 'label' => __( 'Krížiky (bez)', 'relife' ) ) );
	register_block_style( 'core/list', array( 'name' => 'rl-steps', 'label' => __( 'Kroky (číslovaný)', 'relife' ) ) );
	register_block_style( 'core/list', array( 'name' => 'rl-box', 'label' => __( 'V rámčeku', 'relife' ) ) );
}
add_action( 'init', 'relife_register_block_styles' );

/**
 * Položky zoznamu, ktoré začínajú ✓ / ✔ alebo ✗ / ✘ / „x-", dostanú zaškrtnutie alebo krížik.
 * Značka sa z textu odstráni, takže funguje aj pri vložení textu z iného systému.
 */
function relife_list_markers( string $content ): string {
	if ( ! is_singular() || false === strpos( $content, '<li' ) ) {
		return $content;
	}
	$apply = static function ( string $html, string $markers, string $class ): string {
		return (string) preg_replace_callback(
			'#<li(\s[^>]*)?>\\s*(?:' . $markers . ')\\s*#u',
			static function ( array $m ) use ( $class ): string {
				$attrs = $m[1] ?? '';
				if ( preg_match( '/\sclass=(["\'])(.*?)\1/', $attrs ) ) {
					$attrs = (string) preg_replace( '/\sclass=(["\'])(.*?)\1/', ' class="$2 ' . $class . '"', $attrs, 1 );
					return '<li' . $attrs . '>';
				}
				return '<li class="' . $class . '"' . $attrs . '>';
			},
			$html
		);
	};
	$content = $apply( $content, '✓|✔|☑', 'rl-li-yes' );
	return $apply( $content, '✗|✘|✕|☒|x-\\s', 'rl-li-no' );
}
add_filter( 'the_content', 'relife_list_markers', 12 );

/**
 * Po aktivácii témy vytvorí stránku „Stránka nenájdená“ (/stranka-404/), ak ešte neexistuje.
 * Skutočná chyba 404 pre neexistujúce adresy sa zobrazuje sama (404.php), táto stránka je navyše.
 */
function relife_create_404_page(): void {
	// WordPress slug „404“ nevie použiť (zmení ho na 404-2), preto stranka-404.
	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'template-404.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $existing ) {
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Stránka nenájdená',
			'post_name'    => 'stranka-404',
			'post_content' => '',
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( (int) $id, '_wp_page_template', 'template-404.php' );
	}
}
add_action( 'after_switch_theme', 'relife_create_404_page' );

/**
 * Stránka /404/ (šablóna Stránka 404) sa nemá indexovať.
 *
 * @param array<string, mixed> $robots
 * @return array<string, mixed>
 */
function relife_404_page_noindex( array $robots ): array {
	if ( is_page_template( 'template-404.php' ) ) {
		$robots['noindex'] = true;
		unset( $robots['index'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'relife_404_page_noindex' );

/**
 * Pri aktualizácii témy (bez opätovnej aktivácie) sa stránka vytvorí raz po prvom otvorení administrácie.
 */
function relife_maybe_create_404_page(): void {
	if ( get_option( 'relife_404_page_done' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	relife_create_404_page();
	update_option( 'relife_404_page_done', 1, false );
}
add_action( 'admin_init', 'relife_maybe_create_404_page' );
