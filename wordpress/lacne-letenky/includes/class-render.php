<?php
/**
 * Výstup bloku – spoločný pre shortcode, blok v editore, widget aj tagDiv Composer.
 */

defined( 'ABSPATH' ) || exit;

class Lacne_Letenky_Render {

	public static function init() {
		wp_register_style( 'lacne-letenky', LACNE_LETENKY_URL . 'assets/lacne-letenky.css', array(), LACNE_LETENKY_VERSION );
		wp_register_script( 'lacne-letenky', LACNE_LETENKY_URL . 'assets/lacne-letenky.js', array(), LACNE_LETENKY_VERSION, true );

		add_shortcode( 'lacne_letenky', array( __CLASS__, 'shortcode' ) );

		wp_register_script(
			'lacne-letenky-editor',
			LACNE_LETENKY_URL . 'blocks/offers/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			LACNE_LETENKY_VERSION,
			true
		);
		register_block_type(
			LACNE_LETENKY_DIR . 'blocks/offers',
			array(
				'editor_script'   => 'lacne-letenky-editor',
				'render_callback' => function ( $attrs ) {
					return self::html( is_array( $attrs ) ? $attrs : array() );
				},
			)
		);
	}

	/** [lacne_letenky limit="8" title="Najlacnejšie letenky kamkoľvek"] */
	public static function shortcode( $atts ) {
		return self::html( shortcode_atts( array( 'limit' => '', 'title' => '' ), $atts, 'lacne_letenky' ) );
	}

	public static function html( $args ) {
		wp_enqueue_style( 'lacne-letenky' );
		wp_enqueue_script( 'lacne-letenky' );

		$limit = isset( $args['limit'] ) && (int) $args['limit'] > 0 ? (int) $args['limit'] : (int) Lacne_Letenky_Settings::get( 'limit' );
		$title = isset( $args['title'] ) && '' !== $args['title'] ? $args['title'] : Lacne_Letenky_Settings::get( 'title' );
		$font  = Lacne_Letenky_Settings::get( 'font' );
		$data  = Lacne_Letenky_Data::get();

		$attrs = array(
			'data-lacne-letenky' => '',
			'data-src'           => Lacne_Letenky_Data::rest_url(),
			'data-limit'         => max( 1, min( 24, $limit ) ),
		);
		if ( $title ) {
			$attrs['data-title'] = $title;
		}
		if ( 'theme' === $font ) {
			$attrs['data-font']  = 'inherit';
			$attrs['data-fonts'] = 'false';
		}

		$html = '<div class="lacne-letenky-wrap">';
		$html .= '<div';
		foreach ( $attrs as $k => $v ) {
			$html .= ' ' . $k . ( '' === $v ? '' : '="' . esc_attr( $v ) . '"' );
		}
		$html .= '>';
		if ( is_array( $data ) ) {
			// JSON_HEX_* zabráni tomu, aby text v dátach ukončil <script>.
			$html .= '<script type="application/json" data-ll-initial>' .
				wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) .
				'</script>';
		}
		$html .= '<noscript>' . esc_html__( 'Na zobrazenie lacných leteniek zapnite JavaScript.', 'lacne-letenky' ) . '</noscript>';
		$html .= '</div></div>';
		return $html;
	}
}
