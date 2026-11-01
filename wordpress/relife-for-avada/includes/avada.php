<?php
/**
 * Integrácia s Avada Builderom (Fusion Builder): elementy RELIFE.
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upozornenie, ak nie je aktívna téma Avada (plugin funguje aj s inou témou, ale je určený pre Avadu).
 */
function rlav_avada_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$theme = wp_get_theme();
	$names = array( strtolower( (string) $theme->get( 'Name' ) ), strtolower( (string) $theme->get_template() ) );
	if ( in_array( 'avada', $names, true ) ) {
		return;
	}
	echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'RELIFE pre Avadu: téma Avada nie je aktívna. Shortcody a spodné menu fungujú aj tak, elementy Avada Builderu sa ale zobrazia až s Avadou.', 'relife-avada' ) . '</p></div>';
}
add_action( 'admin_notices', 'rlav_avada_notice' );

/**
 * Elementy v Avada Builderi (len ak je Fusion Builder aktívny).
 */
function rlav_register_fusion_elements(): void {
	if ( ! function_exists( 'fusion_builder_map' ) ) {
		return;
	}

	$count = static fn( string $default ): array => array(
		array(
			'type'        => 'textfield',
			'heading'     => esc_attr__( 'Počet článkov', 'relife-avada' ),
			'param_name'  => 'count',
			'value'       => $default,
			'description' => esc_attr__( 'Koľko článkov zobraziť.', 'relife-avada' ),
		),
	);

	$elements = array(
		array( 'RELIFE – Hero + anketa', 'relife_hero', array() ),
		array( 'RELIFE – Anketa a nálada', 'relife_sidecard', array() ),
		array( 'RELIFE – Quick Take', 'relife_quick', $count( '4' ) ),
		array( 'RELIFE – Dnes (podľa nálady)', 'relife_today', $count( '4' ) ),
		array( 'RELIFE – Vysvetlené jednoducho', 'relife_explained', $count( '2' ) ),
		array( 'RELIFE – Piliere', 'relife_pillars', array() ),
		array( 'RELIFE – Versus', 'relife_versus', array() ),
		array( 'RELIFE – Sekcie (dlaždice)', 'relife_sections', array() ),
		array( 'RELIFE – Formáty', 'relife_formats', array() ),
		array( 'RELIFE – Ultimate Guides', 'relife_guides', $count( '4' ) ),
		array( 'RELIFE – Staršie ankety', 'relife_polls', array() ),
		array( 'RELIFE – Uložené články', 'relife_saved', array() ),
		array( 'RELIFE – Newsletter', 'relife_newsletter', array() ),
	);

	foreach ( $elements as $el ) {
		fusion_builder_map(
			array(
				'name'            => esc_attr( $el[0] ),
				'shortcode'       => $el[1],
				'icon'            => 'fusiona-code',
				'preview'         => '',
				'preview_id'      => '',
				'allow_generator' => true,
				'params'          => $el[2],
			)
		);
	}
}
add_action( 'fusion_builder_before_init', 'rlav_register_fusion_elements' );
