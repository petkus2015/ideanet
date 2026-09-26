<?php
/**
 * Blok pre tagDiv Composer. Tento súbor načíta téma Newspaper až vtedy, keď blok potrebuje,
 * takže trieda td_block už existuje.
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'td_block' ) && ! class_exists( 'td_block_lacne_letenky' ) ) {

	class td_block_lacne_letenky extends td_block {

		public function render( $atts, $content = null ) {
			parent::render( $atts );
			$atts = (array) $atts;

			$classes = method_exists( $this, 'get_block_classes' ) ? $this->get_block_classes() : 'td_block_lacne_letenky';
			$html_at = method_exists( $this, 'get_block_html_atts' ) ? $this->get_block_html_atts() : '';

			$buffy  = '<div class="' . esc_attr( $classes ) . '" ' . $html_at . '>';
			$buffy .= method_exists( $this, 'get_block_css' ) ? $this->get_block_css() : '';

			// V živom editore Composera sa skripty bloku nespúšťajú – ukážeme zástupný štítok.
			$in_editor = class_exists( 'td_util' ) && (
				( method_exists( 'td_util', 'tdc_is_live_editor_iframe' ) && td_util::tdc_is_live_editor_iframe() ) ||
				( method_exists( 'td_util', 'tdc_is_live_editor_ajax' ) && td_util::tdc_is_live_editor_ajax() )
			);
			if ( $in_editor ) {
				$buffy .= '<div style="padding:28px;border:1px dashed #c3cad6;border-radius:16px;text-align:center;font:15px/1.5 sans-serif;color:#475569">'
					. '<strong style="display:block;font-size:17px;color:#0f172a">✈ Lacné letenky</strong>'
					. 'Bangkok, Dubaj, Abu Dhabí a ponuky kamkoľvek z Viedne a Bratislavy. Ceny sa zobrazia na zverejnenej stránke.</div>';
			} else {
				$buffy .= Lacne_Letenky_Render::html(
					array(
						'title' => isset( $atts['ll_title'] ) ? sanitize_text_field( $atts['ll_title'] ) : '',
						'limit' => isset( $atts['ll_limit'] ) ? (int) $atts['ll_limit'] : 0,
					)
				);
			}
			$buffy .= '</div>';
			return $buffy;
		}
	}
}
