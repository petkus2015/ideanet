<?php
/**
 * Videá z YouTube a Vimeo v článkoch: automatické vloženie z odkazu a responzívne zobrazenie.
 *
 * Stačí vložiť adresu videa na samostatný riadok (alebo ju prilepiť v editore blokov).
 * Odkaz, ktorý je v odseku sám, sa tiež zmení na video.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Obalí video do kontajnera s pomerom 16:9, odstráni pevné rozmery a použije youtube-nocookie.
 *
 * @param string $html Kód vloženia z oEmbed.
 * @param string $url  Adresa videa.
 */
function relife_wrap_video_embed( string $html, string $url ): string {
	if ( false === stripos( $html, '<iframe' ) || ! preg_match( '~(youtube\.com|youtu\.be|youtube-nocookie\.com|vimeo\.com)~i', $url ) ) {
		return $html;
	}
	if ( false !== strpos( $html, 'rl-embed' ) ) {
		return $html;
	}
	if ( apply_filters( 'relife_youtube_nocookie', true ) ) {
		$html = str_replace( array( '://www.youtube.com/embed/', '://youtube.com/embed/' ), '://www.youtube-nocookie.com/embed/', $html );
	}
	$html = (string) preg_replace( '/\s(?:width|height)="[^"]*"/i', '', $html );
	return '<div class="rl-embed rl-embed--video">' . $html . '</div>';
}
add_filter( 'embed_oembed_html', 'relife_wrap_video_embed', 20, 2 );

/**
 * Odsek, v ktorom je len odkaz na YouTube alebo Vimeo, sa zmení na video.
 */
function relife_embed_standalone_links( string $content ): string {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$pattern = '#<p>\s*<a [^>]*href="(https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?[^"]*|shorts/[^"?]+|live/[^"?]+)|youtu\.be/[^"]+|vimeo\.com/\d+[^"]*))"[^>]*>[^<]*</a>\s*</p>#i';
	return (string) preg_replace_callback(
		$pattern,
		static function ( array $m ): string {
			global $wp_embed;
			if ( ! $wp_embed instanceof WP_Embed ) {
				return $m[0];
			}
			$embed = $wp_embed->shortcode( array(), html_entity_decode( $m[1] ) );
			return ( is_string( $embed ) && false !== stripos( $embed, '<iframe' ) ) ? $embed : $m[0];
		},
		$content
	);
}
add_filter( 'the_content', 'relife_embed_standalone_links', 11 );
