<?php
/**
 * Interaktívne grafy v článkoch: blok „RELIFE graf“ (editor blokov) a shortcode [relife_graf].
 *
 * Dva typy:
 *  - hodnotenie: karta s celkovým skóre a vodorovnými pruhmi (napr. 8,5 / 10), farba podľa výsledku;
 *  - hodnoty: vodorovné pruhy s ľubovoľnými číslami (percentá, eurá…), farby z palety RELIFE.
 * Riadky sa zadávajú po jednom: „Názov | hodnota | popis (nepovinné)“. Klepnutím na riadok s popisom sa popis rozbalí.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Z textu riadkov urobí zoznam: label, raw (zadaná hodnota), num, risk, desc.
 *
 * @return array<int, array{label:string,raw:string,num:float,risk:bool,desc:string,max:float}>
 */
function relife_chart_parse( string $text ): array {
	$out = array();
	foreach ( preg_split( '/\R/', $text ) ?: array() as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$p     = array_map( 'trim', explode( '|', $line, 3 ) );
		$label = $p[0];
		$raw   = $p[1] ?? '';
		$desc  = $p[2] ?? '';
		if ( '' === $label || '' === $raw ) {
			continue;
		}
		$risk = false;
		if ( '!' === substr( $raw, -1 ) ) {
			$risk = true;
			$raw  = trim( substr( $raw, 0, -1 ) );
		}
		$max = 0.0;
		if ( preg_match( '#^\s*(-?\d+(?:[.,]\d+)?)\s*/\s*(\d+(?:[.,]\d+)?)#', $raw, $m ) ) { // „9/10“
			$num = (float) str_replace( ',', '.', $m[1] );
			$max = (float) str_replace( ',', '.', $m[2] );
		} elseif ( preg_match( '#-?\d+(?:[.,]\d+)?#', str_replace( ' ', '', $raw ), $m ) ) {
			$num = (float) str_replace( ',', '.', $m[0] );
		} else {
			continue;
		}
		$out[] = array( 'label' => $label, 'raw' => $raw, 'num' => $num, 'risk' => $risk, 'desc' => $desc, 'max' => $max );
	}
	return $out;
}

/**
 * Farba hodnotenia: dobrá = limetková, stredná = žltá, slabá = ružová; pri riziku platí opačne (vyššie číslo = horšie).
 */
function relife_chart_score_color( float $ratio, bool $risk ): string {
	$good = $risk ? 1 - $ratio : $ratio;
	$mid  = $risk ? 0.25 : 0.55; // pri riziku (7/10) ešte žltá, vyššie už červená
	if ( $good >= 0.75 ) {
		return '#A6FF4D';
	}
	if ( $good >= $mid ) {
		return '#FFE14D';
	}
	return '#FF5A6E';
}

function relife_chart_num( float $n ): string {
	return rtrim( rtrim( number_format( $n, 1, ',', '' ), '0' ), ',' );
}

/**
 * HTML grafu.
 *
 * @param array<string, mixed> $a title, mode (score|values), rows, max, score, note, showTotal
 */
function relife_chart_html( array $a ): string {
	$mode  = 'values' === ( $a['mode'] ?? 'score' ) ? 'values' : 'score';
	$rows  = relife_chart_parse( (string) ( $a['rows'] ?? '' ) );
	$title = trim( (string) ( $a['title'] ?? '' ) );
	$note  = trim( (string) ( $a['note'] ?? '' ) );
	if ( ! $rows ) {
		return '';
	}
	static $uid = 0;
	++$uid;

	$max_attr = (float) ( $a['max'] ?? 0 );
	if ( 'score' === $mode ) {
		$max = $max_attr > 0 ? $max_attr : 10.0;
	} else {
		$max = $max_attr > 0 ? $max_attr : max( array_map( static fn( $r ) => abs( $r['num'] ), $rows ) );
		$max = $max > 0 ? $max : 1.0;
	}

	$show_total = ! isset( $a['showTotal'] ) || (bool) $a['showTotal'];
	$total_html = '';
	if ( 'score' === $mode && $show_total ) {
		$manual = trim( (string) ( $a['score'] ?? '' ) );
		if ( '' !== $manual ) {
			$total = $manual;
		} else {
			$vals = array();
			foreach ( $rows as $r ) {
				if ( ! $r['risk'] ) {
					$vals[] = $r['num'] / ( $r['max'] > 0 ? $r['max'] : $max ) * $max;
				}
			}
			$vals  = $vals ?: array_map( static fn( $r ) => $r['num'], $rows );
			$total = relife_chart_num( round( array_sum( $vals ) / count( $vals ), 1 ) );
		}
		$total_html = '<div class="rl-chart__score">' . esc_html( $total ) . ' <span>/ ' . esc_html( relife_chart_num( $max ) ) . '</span></div>';
	}

	$palette = array( '#3CFFD0', '#FF3D8B', '#FFE14D', '#8F5CFF', '#FF7A2B', '#A6FF4D' );
	$html    = '<figure class="rl-chart rl-chart--' . esc_attr( $mode ) . ' wp-block-relife-chart">';
	if ( '' !== $title || '' !== $total_html ) {
		$html .= '<div class="rl-chart__head">' . ( '' !== $title ? '<div class="rl-chart__title">' . esc_html( $title ) . '</div>' : '' ) . $total_html . '</div>';
	}
	$html .= '<ul class="rl-chart__rows">';
	foreach ( $rows as $i => $r ) {
		$rmax  = $r['max'] > 0 ? $r['max'] : $max;
		$ratio = max( 0.0, min( 1.0, $r['num'] / $rmax ) );
		$color = 'score' === $mode ? relife_chart_score_color( $ratio, $r['risk'] ) : $palette[ $i % count( $palette ) ];
		$val   = ( 'score' === $mode && false === strpos( $r['raw'], '/' ) ) ? $r['raw'] . '/' . relife_chart_num( $rmax ) : $r['raw'];
		$id    = 'rlc' . $uid . '-' . $i;
		$inner = '<span class="rl-chart__top"><span class="rl-chart__label">' . esc_html( $r['label'] ) . '</span><span class="rl-chart__val">' . esc_html( $val ) . '</span></span>'
			. '<span class="rl-chart__track" aria-hidden="true"><span class="rl-chart__bar"></span></span>';
		$html .= '<li class="rl-chart__row' . ( '' !== $r['desc'] ? ' has-desc' : '' ) . '" style="--w:' . esc_attr( (string) round( $ratio * 100, 1 ) ) . '%;--c:' . esc_attr( $color ) . ';--i:' . (int) $i . '">';
		if ( '' !== $r['desc'] ) {
			$html .= '<button type="button" class="rl-chart__btn" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '">' . $inner . '</button><p class="rl-chart__desc" id="' . esc_attr( $id ) . '" hidden>' . esc_html( $r['desc'] ) . '</p>';
		} else {
			$html .= '<div class="rl-chart__btn">' . $inner . '</div>';
		}
		$html .= '</li>';
	}
	$html .= '</ul>';
	if ( '' !== $note ) {
		$html .= '<figcaption class="rl-chart__note">' . esc_html( $note ) . '</figcaption>';
	}
	return $html . '</figure>';
}

/**
 * Shortcode [relife_graf mode="score|values" title="" max="10" score="" note="" total="1"]Názov | 9 | popis …[/relife_graf]
 *
 * @param array<string, string>|string $atts
 */
function relife_chart_shortcode( $atts, ?string $content = null ): string {
	$atts = shortcode_atts( array( 'mode' => 'score', 'title' => '', 'max' => '', 'score' => '', 'note' => '', 'total' => '1', 'rows' => '' ), (array) $atts, 'relife_graf' );
	$text = (string) $content;
	$text = (string) preg_replace( '#</p>\s*<p[^>]*>#i', "\n", $text );
	$text = (string) preg_replace( '#<br\s*/?>#i', "\n", $text );
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
	if ( '' === trim( $text ) ) {
		$text = (string) $atts['rows'];
	}
	return relife_chart_html(
		array(
			'mode'      => $atts['mode'],
			'title'     => $atts['title'],
			'max'       => (float) str_replace( ',', '.', (string) $atts['max'] ),
			'score'     => $atts['score'],
			'note'      => $atts['note'],
			'showTotal' => '0' !== $atts['total'],
			'rows'      => $text,
		)
	);
}
add_shortcode( 'relife_graf', 'relife_chart_shortcode' );

function relife_chart_register_block(): void {
	wp_register_script(
		'relife-chart-editor',
		RELIFE_URI . '/assets/js/chart-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
		RELIFE_VERSION,
		true
	);
	register_block_type(
		'relife/chart',
		array(
			'api_version'     => 3,
			'title'           => 'RELIFE graf',
			'editor_script'   => 'relife-chart-editor',
			'attributes'      => array(
				'mode'      => array( 'type' => 'string', 'default' => 'score' ),
				'title'     => array( 'type' => 'string', 'default' => '' ),
				'rows'      => array( 'type' => 'string', 'default' => "Technologická dominancia | 10\nFinančná kondícia | 10\nKonkurenčná výhoda | 9 | Krátky popis sa rozbalí po klepnutí.\nRizikovosť | 7! | Vyššie číslo znamená vyššie riziko." ),
				'max'       => array( 'type' => 'number', 'default' => 0 ),
				'score'     => array( 'type' => 'string', 'default' => '' ),
				'note'      => array( 'type' => 'string', 'default' => '' ),
				'showTotal' => array( 'type' => 'boolean', 'default' => true ),
			),
			'render_callback' => static fn( $attrs ): string => relife_chart_html( is_array( $attrs ) ? $attrs : array() ),
		)
	);
}
add_action( 'init', 'relife_chart_register_block' );
