<?php
/**
 * Tabuľky v článkoch: čistý markup (hlavička, bez vložených farieb) a obal pre posúvanie na mobile.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Z vloženého štýlu odstráni farby, pozadie, šírky a rámy (napr. zo starého builderu), ostatné (text-align) nechá.
 */
function relife_clean_inline_style( string $style ): string {
	$keep = array();
	foreach ( explode( ';', $style ) as $decl ) {
		$decl = trim( $decl );
		if ( '' === $decl ) {
			continue;
		}
		$prop = strtolower( trim( (string) strtok( $decl, ':' ) ) );
		if ( in_array( $prop, array( 'text-align', 'vertical-align' ), true ) ) {
			$keep[] = $decl;
		}
	}
	return implode( ';', $keep );
}

/**
 * @param array<int, string> $m
 */
function relife_clean_table( array $m ): string {
	$html = $m[0];

	// Vložené štýly a staré atribúty vzhľadu.
	$html = (string) preg_replace_callback(
		'/\sstyle=("|\')(.*?)\1/is',
		static function ( array $s ): string {
			$clean = relife_clean_inline_style( $s[2] );
			return '' === $clean ? '' : ' style="' . esc_attr( $clean ) . '"';
		},
		$html
	);
	$html = (string) preg_replace( '/\s(?:width|height|bgcolor|border|cellpadding|cellspacing|valign)=("|\')[^"\']*\1/i', '', $html );

	// Ak chýba <thead>, prvý riadok s <th> sa stane hlavičkou.
	if ( false === stripos( $html, '<thead' ) ) {
		$html = (string) preg_replace_callback(
			'/(<table\b[^>]*>)(\s*(?:<caption\b.*?<\/caption>)?\s*(?:<tbody\b[^>]*>)?\s*)(<tr\b[^>]*>(?:(?!<\/tr>).)*<\/tr>)/is',
			static function ( array $r ): string {
				if ( ! preg_match( '/<th\b/i', $r[3] ) ) {
					return $r[0];
				}
				// Ak je v riadku len <th>, ide o hlavičku; ak sú <th> aj <td> (hlavičky riadkov), nechaj.
				if ( preg_match( '/<td\b/i', $r[3] ) ) {
					return $r[0];
				}
				return $r[1] . $r[2] . '<thead>' . $r[3] . '</thead>';
			},
			$html,
			1
		);
	}
	return $html;
}

/**
 * Každá tabuľka dostane obal `.rl-table` (posúvanie do strany na mobile), ak ho ešte nemá cez blok tabuľky.
 */
function relife_wrap_tables( string $content ): string {
	if ( false === stripos( $content, '<table' ) ) {
		return $content;
	}
	$aria = esc_attr__( 'Tabuľka', 'relife' );
	return (string) preg_replace_callback(
		'/<figure\b[^>]*class="([^"]*wp-block-table[^"]*)"[^>]*>(.*?)<\/figure>|<table\b.*?<\/table>/is',
		static function ( array $m ) use ( $aria ): string {
			if ( isset( $m[2] ) && '' !== $m[2] ) {
				// Blok tabuľky: zachovať štýl bloku (is-style-…), titulok pod tabuľkou.
				$classes = trim( str_replace( 'wp-block-table', '', $m[1] ) );
				$inner   = (string) $m[2];
				$caption = '';
				if ( preg_match( '/<figcaption\b.*?<\/figcaption>/is', $inner, $c ) ) {
					$caption = '<p class="rl-table__cap">' . wp_strip_all_tags( $c[0] ) . '</p>';
					$inner   = str_replace( $c[0], '', $inner );
				}
				$inner = (string) preg_replace_callback( '/<table\b.*?<\/table>/is', 'relife_clean_table', $inner );
				return '<div class="rl-table ' . esc_attr( $classes ) . '" tabindex="0" role="region" aria-label="' . $aria . '">' . $inner . '</div>' . $caption;
			}
			return '<div class="rl-table" tabindex="0" role="region" aria-label="' . $aria . '">' . relife_clean_table( $m ) . '</div>';
		},
		$content
	);
}
add_filter( 'the_content', 'relife_wrap_tables', 13 );

function relife_table_block_styles(): void {
	register_block_style( 'core/table', array( 'name' => 'rl-versus', 'label' => __( 'Porovnanie (Versus)', 'relife' ) ) );
}
add_action( 'init', 'relife_table_block_styles' );
