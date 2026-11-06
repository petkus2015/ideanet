<?php
/**
 * Výkon: Core Web Vitals (LCP, Speed Index, CLS, INP).
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lokálne písma (bez požiadavky na Google Fonts, ktorá blokovala vykreslenie) + preload hlavných súborov.
 * Tiež sa nahradí font-face zo štýlu: @font-face sa vloží priamo do <head>.
 */
function relife_fonts_css(): string {
	$file = RELIFE_DIR . '/assets/fonts/fonts.css';
	if ( ! is_readable( $file ) ) {
		return '';
	}
	$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return str_replace( 'url(', 'url(' . RELIFE_URI . '/assets/fonts/', $css );
}

function relife_font_preloads(): void {
	foreach ( array( 'inter-latin.woff2', 'bricolage-latin.woff2' ) as $f ) {
		echo '<link rel="preload" href="' . esc_url( RELIFE_URI . '/assets/fonts/' . $f ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}
add_action( 'wp_head', 'relife_font_preloads', 3 );

/**
 * Preload LCP obrázka (hero na úvodnej stránke, titulný obrázok v článku) so správnym srcset a sizes.
 */
function relife_preload_lcp_image(): void {
	$post = null;
	if ( is_front_page() && function_exists( 'relife_hero_post' ) ) {
		$post = relife_hero_post();
	} elseif ( is_singular( 'post' ) ) {
		$post = get_post();
	}
	if ( ! $post instanceof WP_Post || ! has_post_thumbnail( $post ) ) {
		return;
	}
	$id    = (int) get_post_thumbnail_id( $post );
	$src   = wp_get_attachment_image_url( $id, 'relife-hero' );
	$set   = wp_get_attachment_image_srcset( $id, 'relife-hero' );
	$sizes = is_front_page() ? '(max-width: 900px) 100vw, 800px' : '(max-width: 800px) 100vw, 1100px';
	if ( ! $src ) {
		return;
	}
	echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '"';
	if ( $set ) {
		echo ' imagesrcset="' . esc_attr( $set ) . '" imagesizes="' . esc_attr( $sizes ) . '"';
	}
	echo ' fetchpriority="high">' . "\n";
}
add_action( 'wp_head', 'relife_preload_lcp_image', 4 );

/**
 * Správny atribút sizes pre hero a mensšiu mobilnú veľkosť.
 *
 * @param array<string, mixed> $attr
 * @return array<string, mixed>
 */
function relife_hero_image_sizes( array $attr, WP_Post $attachment, $size ): array {
	if ( 'relife-hero' === $size && ! empty( $attr['fetchpriority'] ) ) {
		$attr['sizes'] = is_front_page() ? '(max-width: 900px) 100vw, 800px' : '(max-width: 800px) 100vw, 1100px';
	}
	if ( 'relife-card' === $size ) {
		// Karty: 4 stĺpce na desktope (~290 px), 2 na mobile (~50 vw), vedľa seba v širokých kartách ~38 %.
		$attr['sizes'] = '(max-width: 760px) 60vw, 330px';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'relife_hero_image_sizes', 20, 3 );

/**
 * Menej zbytočného z WordPressu na verejnej časti: emoji, wp-embed, RSD, shortlink, generator, dashicons, blokové štýly mimo článkov.
 */
function relife_trim_wp_head(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}
add_action( 'init', 'relife_trim_wp_head' );

function relife_dequeue_assets(): void {
	if ( ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}
	wp_dequeue_style( 'classic-theme-styles' );
	if ( ! is_singular() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_dequeue_assets', 100 );

/**
 * Šablóny štýlov, ktoré nie sú potrebné hneď, sa nenačítavajú blokujúco: komentáre až v článku.
 */
function relife_dequeue_comment_script(): void {
	if ( ! is_singular() || ! comments_open() || ! get_option( 'thread_comments' ) ) {
		wp_dequeue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_dequeue_comment_script', 100 );

/**
 * Hlavičky pre prehliadač: 103 Early Hints nie sú v PHP dostupné, preto aspoň dlhá cache pre statické súbory téme nadáva server (.htaccess ukážka v README).
 */

/**
 * Obrázky: nahrané JPEG/PNG sa ukladajú aj ako WebP (výrazne menšie súbory pri rovnakom vzhľade).
 * Funguje len ak server WebP podporuje; inak WordPress nechá pôvodný formát. Vypnete filtrom `relife_optimize_images`.
 * Týka sa nových nahratí a pregenerovaných miniatúr (Regenerate Thumbnails / `wp media regenerate --yes`).
 *
 * @param array<string, string> $formats
 * @return array<string, string>
 */
function relife_image_output_formats( array $formats ): array {
	if ( ! apply_filters( 'relife_optimize_images', true ) ) {
		return $formats;
	}
	$target = (string) apply_filters( 'relife_image_format', 'image/webp' );
	if ( wp_image_editor_supports( array( 'mime_type' => $target ) ) ) {
		$formats['image/jpeg'] = $target;
		$formats['image/png']  = $target;
	}
	return $formats;
}
add_filter( 'image_editor_output_format', 'relife_image_output_formats' );

/**
 * Kvalita kompresie: vizuálne bez straty, no podstatne menší súbor.
 */
function relife_image_quality( int $quality, string $mime ): int {
	if ( ! apply_filters( 'relife_optimize_images', true ) ) {
		return $quality;
	}
	return match ( $mime ) {
		'image/webp' => 82,
		'image/avif' => 60,
		'image/jpeg' => 85,
		default      => $quality,
	};
}
add_filter( 'wp_editor_set_quality', 'relife_image_quality', 10, 2 );

/**
 * Obrázky pri nahratí nemajú byť väčšie než treba (predvolene 2560 px; tu 2000 px stačí pre magazín).
 */
function relife_big_image_threshold( int $threshold ): int {
	return apply_filters( 'relife_optimize_images', true ) ? 2000 : $threshold;
}
add_filter( 'big_image_size_threshold', 'relife_big_image_threshold' );

/**
 * Obrázky pod záhybom sa načítavajú lenším spôsobom: prvé 2 obrázky v obsahu strany neláduj lenivo (môžu byť LCP), ostatné áno.
 */
add_filter( 'wp_omit_loading_attr_threshold', static fn(): int => 2 );

/**
 * Štýly WordPressu pre bloky (v článkoch) sa nenačítavajú blokujúco: stránku sa vykreslí hneď, štýl sa doplní po načítaní.
 *
 * @param string $tag
 * @param string $handle
 */
function relife_defer_block_css( string $tag, string $handle ): string {
	if ( is_admin() || ! in_array( $handle, array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles' ), true ) ) {
		return $tag;
	}
	if ( false === strpos( $tag, "media='all'" ) ) {
		return $tag;
	}
	$async = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $tag );
	return $async . '<noscript>' . $tag . '</noscript>';
}
add_filter( 'style_loader_tag', 'relife_defer_block_css', 10, 2 );

/**
 * Malé lokálne štýly pluginov (TablePress, kk Star Ratings a pod.) sa vložia priamo do stránky namiesto samostatnej
 * blokujúcej požiadavky. Preskočí sa, ak súbor nie je lokálny, je väčší než limit alebo odkazuje na relatívne adresy (obrázky, písma).
 *
 * @param string $tag
 * @param string $handle
 * @param string $href
 */
function relife_inline_small_styles( string $tag, string $handle, string $href ): string {
	if ( is_admin() || ! apply_filters( 'relife_inline_small_styles', true ) || 'relife' === $handle || false === strpos( $tag, "media='all'" ) ) {
		return $tag;
	}
	if ( in_array( $handle, array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'dashicons', 'admin-bar' ), true ) ) {
		return $tag;
	}
	$clean = strtok( $href, '?' );
	$base  = trailingslashit( site_url() );
	if ( false === $clean || 0 !== strpos( $clean, $base ) ) {
		return $tag;
	}
	$file = wp_normalize_path( ABSPATH . substr( $clean, strlen( $base ) ) );
	if ( '.css' !== substr( $file, -4 ) || false !== strpos( $file, '..' ) || ! is_readable( $file ) || filesize( $file ) > (int) apply_filters( 'relife_inline_style_max', 15360 ) ) {
		return $tag;
	}
	$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( preg_match( '/url\(\s*(?![\'"]?(?:data:|https?:|\/\/|\/))/i', $css ) || false !== stripos( $css, '@import' ) ) {
		return $tag;
	}
	return '<style id="' . esc_attr( $handle ) . '-inline-css">' . str_replace( '</style', '<\\/style', $css ) . "</style>\n";
}
add_filter( 'style_loader_tag', 'relife_inline_small_styles', 20, 3 );
