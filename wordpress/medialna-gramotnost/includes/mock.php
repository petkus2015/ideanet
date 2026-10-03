<?php
/**
 * Makety správ: SMS, WhatsApp, e-mail, Facebook, web, telefonát.
 *
 * @package MedialnaGramotnost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Avatar s písmenom.
 */
function mg_avatar( $a, $class = 'mg-m-av' ) {
	$letter = trim( (string) $a['avatar'] );
	if ( '' === $letter ) {
		$plain  = trim( wp_strip_all_tags( preg_replace( '/==/', '', $a['sender'] ) ) );
		$letter = '' === $plain ? '?' : mb_strtoupper( mb_substr( $plain, 0, 1 ) );
	}
	$style = '';
	if ( preg_match( '/^#[0-9a-f]{3,8}$/i', trim( (string) $a['avatar_color'] ) ) ) {
		$style = ' style="background:' . esc_attr( trim( $a['avatar_color'] ) ) . '"';
	}
	return '<div class="' . esc_attr( $class ) . '"' . $style . '>' . esc_html( $letter ) . '</div>';
}

/**
 * Z riadku vytiahne predponu (btn:, img:, …).
 */
function mg_line_kind( $line ) {
	if ( preg_match( '/^(btn|img|link|stats|foot|title|input|alert)\s*:\s*(.*)$/iu', $line, $m ) ) {
		return array( strtolower( $m[1] ), $m[2] );
	}
	if ( 0 === strpos( $line, '> ' ) || '>' === $line ) {
		return array( 'me', substr( $line, 2 ) );
	}
	if ( 0 === strpos( $line, '! ' ) ) {
		return array( 'sys', substr( $line, 2 ) );
	}
	return array( 'text', $line );
}

/**
 * Vytvorí HTML makety. $lines = riadky správy.
 */
function mg_mock( $a, $lines ) {
	$type = $a['type'];
	$html = '';

	switch ( $type ) {
		case 'sms':
		case 'whatsapp':
			$wa    = 'whatsapp' === $type;
			$html .= '<div class="mg-m-phone' . ( $wa ? ' mg-wa' : '' ) . '">';
			$html .= '<div class="mg-m-phone-head">' . mg_avatar( $a, 'mg-m-phone-av' ) . '<div><b>' . mg_inline( $a['sender'] ) . '</b><span>' . mg_inline( $a['subtitle'] ) . '</span></div></div>';
			$html .= '<div class="mg-m-thread">';
			if ( '' !== trim( $a['time'] ) ) {
				$html .= '<div class="mg-m-time">' . esc_html( $a['time'] ) . '</div>';
			}
			foreach ( $lines as $line ) {
				list( $kind, $text ) = mg_line_kind( $line );
				if ( 'sys' === $kind ) {
					$html .= '<div class="mg-m-sys">' . mg_inline( $text ) . '</div>';
				} elseif ( 'me' === $kind ) {
					$html .= '<div class="mg-bubble mg-me">' . mg_inline( $text ) . '</div>';
				} else {
					$html .= '<div class="mg-bubble">' . mg_autolink_style( mg_inline( $text ) ) . '</div>';
				}
			}
			$html .= '</div></div>';
			break;

		case 'email':
			$html .= '<div class="mg-mock"><div class="mg-m-mail-top"><span class="mg-m-dot"></span><span class="mg-m-dot"></span><span class="mg-m-dot"></span><span style="margin-left:6px">' . esc_html__( 'Doručená pošta', 'medialna-gramotnost' ) . '</span></div><div class="mg-m-mail">';
			if ( '' !== trim( $a['subject'] ) ) {
				$html .= '<div class="mg-m-subject">' . mg_inline( $a['subject'] ) . '</div>';
			}
			$html .= '<div class="mg-m-from">' . mg_avatar( $a ) . '<div><b>' . mg_inline( $a['sender'] ) . '</b><span>&lt;' . mg_inline( $a['address'] ) . '&gt;</span></div></div><div class="mg-m-body">';
			$foot  = '';
			foreach ( $lines as $line ) {
				list( $kind, $text ) = mg_line_kind( $line );
				if ( 'btn' === $kind ) {
					$html .= '<span class="mg-m-cta">' . mg_inline( $text ) . '</span>';
				} elseif ( 'foot' === $kind ) {
					$foot .= '<div class="mg-m-foot">' . mg_inline( $text ) . '</div>';
				} else {
					$html .= '<p>' . mg_inline( $text ) . '</p>';
				}
			}
			$html .= '</div>' . $foot . '</div></div>';
			break;

		case 'facebook':
			$html  .= '<div class="mg-mock"><div class="mg-m-fb"><div class="mg-m-fb-head">' . mg_avatar( $a ) . '<div><b>' . mg_inline( $a['sender'] ) . '</b><span>' . mg_inline( $a['subtitle'] ) . '</span></div></div>';
			$stats  = '';
			foreach ( $lines as $line ) {
				list( $kind, $text ) = mg_line_kind( $line );
				if ( 'img' === $kind ) {
					$bits  = array_map( 'trim', explode( '|', $text, 2 ) );
					$bg    = ( isset( $bits[1] ) && preg_match( '/^#[0-9a-f]{3,8}$/i', $bits[1] ) ) ? $bits[1] : '';
					$style = $bg ? 'background:linear-gradient(135deg,#1e293b,' . $bg . ')' : 'background:linear-gradient(135deg,#1e293b,#9f1239)';
					$html .= '<div class="mg-m-fb-img" style="' . esc_attr( $style ) . '"><span>' . mg_inline( $bits[0] ) . '</span></div>';
				} elseif ( 'link' === $kind ) {
					$bits  = array_map( 'trim', explode( '|', $text, 2 ) );
					$html .= '<div class="mg-m-fb-link"><span>' . mg_inline( $bits[0] ) . '</span><b>' . mg_inline( isset( $bits[1] ) ? $bits[1] : '' ) . '</b></div>';
				} elseif ( 'stats' === $kind ) {
					$bits  = array_map( 'trim', explode( '|', $text, 2 ) );
					$stats = '<div class="mg-m-fb-stats"><span>' . mg_inline( $bits[0] ) . '</span><span>' . mg_inline( isset( $bits[1] ) ? $bits[1] : '' ) . '</span></div>';
				} else {
					$html .= '<div class="mg-m-fb-text">' . mg_inline( $text ) . '</div>';
				}
			}
			$html .= $stats . '<div class="mg-m-fb-actions"><span>' . esc_html__( 'Páči sa mi to', 'medialna-gramotnost' ) . '</span><span>' . esc_html__( 'Komentovať', 'medialna-gramotnost' ) . '</span><span>' . esc_html__( 'Zdieľať', 'medialna-gramotnost' ) . '</span></div></div></div>';
			break;

		case 'web':
			$html  .= '<div class="mg-mock"><div class="mg-m-browser-bar"><span class="mg-m-dot"></span><span class="mg-m-dot"></span><div class="mg-m-url">' . mg_inline( $a['url'] ) . '</div></div>';
			$page   = '';
			$popup  = '';
			foreach ( $lines as $line ) {
				list( $kind, $text ) = mg_line_kind( $line );
				if ( 'alert' === $kind ) {
					$popup .= '<p>' . mg_inline( $text ) . '</p>';
				} elseif ( 'title' === $kind ) {
					$page .= '<b class="mg-m-title">' . mg_inline( $text ) . '</b>';
				} elseif ( 'input' === $kind ) {
					$page .= '<div class="mg-m-input">' . mg_inline( $text ) . '</div>';
				} elseif ( 'btn' === $kind ) {
					$page .= '<span class="mg-m-cta">' . mg_inline( $text ) . '</span>';
				} else {
					$page .= '<p>' . mg_inline( $text ) . '</p>';
				}
			}
			if ( $popup ) {
				$html .= '<div class="mg-m-popup">' . $popup . '</div>';
			}
			if ( $page ) {
				$html .= '<div class="mg-m-page">' . $page . '</div>';
			}
			$html .= '</div>';
			break;

		case 'call':
			$html .= '<div class="mg-mock"><div class="mg-m-call"><div class="mg-m-call-head"><b>' . mg_inline( $a['sender'] ) . '</b><span>' . mg_inline( $a['subtitle'] ) . '</span></div>';
			foreach ( $lines as $line ) {
				if ( preg_match( '/^([^:]{1,24}):\s*(.+)$/u', $line, $m ) ) {
					$html .= '<div class="mg-m-line"><i>' . esc_html( $m[1] ) . '</i><span>' . mg_inline( $m[2] ) . '</span></div>';
				} else {
					$html .= '<div class="mg-m-line"><i></i><span>' . mg_inline( $line ) . '</span></div>';
				}
			}
			$html .= '</div></div>';
			break;
	}

	return $html;
}

/**
 * Celá ukážka: lišta s tlačidlom, maketa a zoznam vysvetliviek.
 */
function mg_example_block( $a, $content, $with_button = true ) {
	list( $lines, $notes ) = mg_split_message( $content );
	$open = isset( $a['open'] ) && 'yes' === $a['open'];

	$html = '<div class="mg-example' . ( $open ? ' mg-show' : '' ) . '">';
	if ( $with_button && ( '' !== trim( $a['label'] ) || $notes ) ) {
		$html .= '<div class="mg-example-bar"><span class="mg-example-label">' . esc_html( $a['label'] ) . '</span>';
		if ( $notes ) {
			$html .= '<button class="mg-flags-btn" type="button" aria-pressed="' . ( $open ? 'true' : 'false' ) . '" data-on="' . esc_attr( $a['button_text_on'] ) . '" data-off="' . esc_attr( $a['button_text'] ) . '">' . esc_html( $open ? $a['button_text_on'] : $a['button_text'] ) . '</button>';
		}
		$html .= '</div>';
	}
	$html .= mg_mock( $a, $lines );
	if ( $notes ) {
		$html .= '<ul class="mg-flag-list">';
		foreach ( $notes as $n ) {
			$html .= '<li>' . mg_inline( $n, false ) . '</li>';
		}
		$html .= '</ul>';
	}
	$html .= '</div>';
	return $html;
}
