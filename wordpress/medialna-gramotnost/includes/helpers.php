<?php
/**
 * Pomocné funkcie: atribúty, bezpečné vypisovanie, zoznamy, odkazy.
 *
 * @package MedialnaGramotnost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Doplní predvolené hodnoty atribútov podľa definície prvku.
 */
function mg_atts( $tag, $atts ) {
	$els      = mg_elements();
	$defaults = array(
		'class' => '',
		'id'    => '',
	);
	if ( isset( $els[ $tag ]['params'] ) ) {
		foreach ( $els[ $tag ]['params'] as $p ) {
			$defaults[ $p[0] ] = isset( $p[3] ) ? $p[3] : '';
		}
	}
	$atts = shortcode_atts( $defaults, is_array( $atts ) ? $atts : array(), $tag );
	foreach ( $atts as $k => $v ) {
		// Avada Builder a editor môžu uložiť úvodzovky a znaky ako entity.
		$atts[ $k ] = is_string( $v ) ? html_entity_decode( $v, ENT_QUOTES, 'UTF-8' ) : $v;
	}
	return $atts;
}

/**
 * Povolené HTML značky v krátkych textoch.
 */
function mg_inline_tags() {
	return array(
		'b'      => array(),
		'strong' => array(),
		'em'     => array(),
		'i'      => array(),
		's'      => array(),
		'small'  => array(),
		'br'     => array(),
		'span'   => array( 'class' => true ),
		'a'      => array( 'href' => true, 'target' => true, 'rel' => true ),
	);
}

/**
 * Bezpečný krátky text: povolí základné značky a ==zvýraznenie== zmení na varovný znak.
 */
function mg_inline( $text, $flags = true ) {
	$text = wp_kses( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ), mg_inline_tags() );
	if ( $flags ) {
		$text = preg_replace( '/==(.+?)==/u', '<span class="mg-flag">$1</span>', $text );
	} else {
		$text = preg_replace( '/==(.+?)==/u', '$1', $text );
	}
	return $text;
}

/**
 * Text s riadkami → odseky oddelené <br>.
 */
function mg_text( $text ) {
	return nl2br( mg_inline( trim( (string) $text ), false ) );
}

/**
 * Rozdelí text na položky podľa bodkočiarky (alebo nového riadku).
 */
function mg_split( $text, $sep = ';' ) {
	$text  = html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' );
	$parts = preg_split( '/' . preg_quote( $sep, '/' ) . '|\r?\n/u', $text );
	return array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
}

/**
 * Rozdelí „a|b; c|d“ na dvojice.
 */
function mg_pairs( $text ) {
	$out = array();
	foreach ( mg_split( $text ) as $item ) {
		$bits  = array_map( 'trim', explode( '|', $item, 2 ) );
		$out[] = array( $bits[0], isset( $bits[1] ) ? $bits[1] : '' );
	}
	return $out;
}

/**
 * Riadky obsahu (zachová prázdne oddeľovače ---).
 */
function mg_lines( $content ) {
	$content = html_entity_decode( (string) $content, ENT_QUOTES, 'UTF-8' );
	// Editor môže pridať <br> alebo <p>; zmeníme ich na nové riadky.
	$content = preg_replace( '#<br\s*/?>|</p>\s*<p[^>]*>#i', "\n", $content );
	$content = preg_replace( '#</?p[^>]*>#i', '', $content );
	$lines   = preg_split( '/\r?\n/u', $content );
	return array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
}

/**
 * Rozdelí obsah ukážky na riadky správy a vysvetlivky (oddeľovač ---).
 */
function mg_split_message( $content ) {
	$msg   = array();
	$notes = array();
	$into  = 'msg';
	foreach ( mg_lines( $content ) as $line ) {
		// WordPress (wptexturize) môže z --- urobiť pomlčku „—“; uznáme všetky varianty.
		if ( preg_match( '/^[\-\x{2013}\x{2014}]+$/u', $line ) ) {
			$into = 'notes';
			continue;
		}
		if ( 'msg' === $into ) {
			$msg[] = $line;
		} else {
			$notes[] = $line;
		}
	}
	return array( $msg, $notes );
}

/**
 * Atribúty odkazu; externé odkazy sa otvoria v novom okne.
 */
function mg_href( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	$attr = ' href="' . esc_url( $url ) . '"';
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host ) {
		$attr .= ' target="_blank" rel="noopener"';
	}
	return $attr;
}

/**
 * Obalí prvok spoločným kontajnerom s triedou .mg (štýly platia len vnútri).
 */
function mg_wrap( $tag, $atts, $inner, $class = '', $data = array() ) {
	$classes = trim( 'mg mg-el-' . str_replace( '_', '-', substr( $tag, 3 ) ) . ' ' . $class . ' ' . $atts['class'] );
	$attr    = ' class="' . esc_attr( $classes ) . '"';
	if ( ! empty( $atts['id'] ) ) {
		$attr .= ' id="' . esc_attr( $atts['id'] ) . '"';
	}
	foreach ( $data as $k => $v ) {
		$attr .= ' data-' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	return '<div' . $attr . '>' . $inner . '</div>';
}

/**
 * Kontext rodiča pre potomkov (napr. rozloženie krokov).
 */
function mg_ctx_push( $tag, $atts ) {
	$GLOBALS['mg_ctx'][ $tag ][] = $atts;
}
function mg_ctx_pop( $tag ) {
	array_pop( $GLOBALS['mg_ctx'][ $tag ] );
}
function mg_ctx( $tag, $key, $default = '' ) {
	if ( empty( $GLOBALS['mg_ctx'][ $tag ] ) ) {
		return $default;
	}
	$top = end( $GLOBALS['mg_ctx'][ $tag ] );
	return isset( $top[ $key ] ) ? $top[ $key ] : $default;
}

/**
 * Obsah potomkov bez prázdnych odsekov, ktoré pridáva wpautop.
 */
function mg_children( $content ) {
	$content = preg_replace( '#^\s*</p>|<p>\s*$#', '', (string) $content );
	$out     = do_shortcode( shortcode_unautop( $content ) );
	return preg_replace( '#<p>\s*</p>|<br\s*/?>\s*(?=<)#', '', $out );
}

/**
 * Ikony kariet (inline SVG).
 */
function mg_icon( $name ) {
	$paths = array(
		'message' => '<path d="M4 5h16v11H8l-4 4z"/><path d="M12 8v3M12 13.5v.5"/>',
		'doc'     => '<path d="M4 4h12l4 4v12H4z"/><path d="M8 10h8M8 14h8M8 18h5"/>',
		'search'  => '<circle cx="11" cy="11" r="7"/><path d="M16 16l5 5"/>',
		'alert'   => '<path d="M12 3l9 16H3z"/><path d="M12 10v4M12 16.5v.5"/>',
		'phone'   => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M10 18h4"/>',
		'shield'  => '<path d="M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
		'users'   => '<circle cx="9" cy="7" r="3"/><circle cx="17" cy="9" r="2.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M14 20c0-2.5 1.3-4.5 3-4.5s3 2 3 4.5"/>',
		'monitor' => '<path d="M3 5h18v11H3z"/><path d="M8 20h8M12 16v4"/>',
		'book'    => '<path d="M4 4h7a3 3 0 0 1 3 3v13a2 2 0 0 0-2-2H4z"/><path d="M20 4h-4a3 3 0 0 0-2 1"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' . $paths[ $name ] . '</svg>';
}

/**
 * Označí v texte bublín veci, ktoré vyzerajú ako webová adresa (podčiarknutie ako v mobile).
 */
function mg_autolink_style( $html ) {
	$parts = preg_split( '/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $i => $part ) {
		if ( '' === $part || '<' === $part[0] ) {
			continue;
		}
		$parts[ $i ] = preg_replace( '#\b((?:https?://)?[a-z0-9-]+(?:\.[a-z0-9-]+)*\.(?:[a-z]{2,})(?:/[^\s<]*)?)#iu', '<span class="mg-lnk">$1</span>', $part );
	}
	return implode( '', $parts );
}
