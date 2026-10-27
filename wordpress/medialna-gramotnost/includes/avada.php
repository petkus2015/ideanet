<?php
/**
 * Napojenie na Avada Builder (Fusion Builder).
 *
 * Každý prvok z mg_elements() sa zaregistruje cez fusion_builder_map().
 * Rodičovské prvky (napr. „MG Karty s odkazom“) majú potomkov, ktoré sa
 * pridávajú tlačidlom „Pridať položku“ priamo v builderi.
 *
 * @package MedialnaGramotnost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Preloží pole z definície prvku na parameter Avada Buildera.
 */
function mg_avada_param( $p ) {
	list( $name, $type, $label ) = $p;
	$default = isset( $p[3] ) ? $p[3] : '';
	$options = isset( $p[4] ) ? $p[4] : null;
	$desc    = isset( $p[5] ) ? $p[5] : '';

	$out = array(
		'heading'     => $label,
		'description' => $desc,
		'param_name'  => $name,
	);

	switch ( $type ) {
		case 'textarea':
			$out['type']  = 'textarea';
			$out['value'] = $default;
			break;
		case 'select':
			$out['type']    = 'select';
			$out['value']   = $options;
			$out['default'] = $default;
			break;
		case 'yesno':
			$out['type']    = 'radio_button_set';
			$out['value']   = array(
				'yes' => esc_attr__( 'Áno', 'medialna-gramotnost' ),
				'no'  => esc_attr__( 'Nie', 'medialna-gramotnost' ),
			);
			$out['default'] = $default;
			break;
		case 'checkboxes':
			$out['type']    = 'checkbox_button_set';
			$out['value']   = $options;
			$out['default'] = $default;
			break;
		case 'link':
			$out['type']  = 'link_selector';
			$out['value'] = $default;
			break;
		case 'number':
			$out['type']    = 'range';
			$out['min']     = isset( $options['min'] ) ? $options['min'] : 0;
			$out['max']     = isset( $options['max'] ) ? $options['max'] : 10;
			$out['step']    = 1;
			$out['value']   = $default;
			$out['default'] = $default;
			break;
		default:
			$out['type']  = 'textfield';
			$out['value'] = $default;
	}
	return $out;
}

/**
 * Spoločné polia „CSS trieda“ a „CSS ID“, ako majú prvky Avada.
 */
function mg_avada_common_params() {
	return array(
		array(
			'type'        => 'textfield',
			'heading'     => esc_attr__( 'CSS trieda', 'medialna-gramotnost' ),
			'description' => esc_attr__( 'Vlastná trieda pre úpravy vzhľadu.', 'medialna-gramotnost' ),
			'param_name'  => 'class',
			'value'       => '',
			'group'       => esc_attr__( 'Rozšírené', 'medialna-gramotnost' ),
		),
		array(
			'type'        => 'textfield',
			'heading'     => esc_attr__( 'CSS ID (kotva)', 'medialna-gramotnost' ),
			'description' => esc_attr__( 'Napr. sms-balik – potom sa dá odkazovať na stranka/#sms-balik.', 'medialna-gramotnost' ),
			'param_name'  => 'id',
			'value'       => '',
			'group'       => esc_attr__( 'Rozšírené', 'medialna-gramotnost' ),
		),
	);
}

/**
 * Vytvorí pole pre fusion_builder_map() z definície prvku.
 */
function mg_avada_map_args( $tag, $el ) {
	$params = array();
	$parent = mg_parent_of( $tag );

	// Rodič: obsahom sú potomkovia (Avada zobrazí zoznam položiek).
	if ( ! empty( $el['child'] ) ) {
		$els      = mg_elements();
		$child    = $els[ $el['child'] ];
		$params[] = array(
			'type'        => 'tinymce',
			'heading'     => esc_attr__( 'Položky', 'medialna-gramotnost' ),
			'description' => sprintf( esc_attr__( 'Pridajte, upravte alebo preusporiadajte položky „%s“.', 'medialna-gramotnost' ), $child['name'] ),
			'param_name'  => 'element_content',
			'value'       => '[' . $el['child'] . '][/' . $el['child'] . ']',
		);
	}

	// Obsah prvku (text medzi značkami).
	if ( ! empty( $el['content'] ) ) {
		$c        = $el['content'];
		$params[] = array(
			'type'        => 'tinymce' === $c[0] ? 'tinymce' : 'textarea',
			'heading'     => $c[1],
			'description' => isset( $c[3] ) ? $c[3] : '',
			'param_name'  => 'element_content',
			'value'       => isset( $c[2] ) ? $c[2] : '',
		);
	}

	foreach ( $el['params'] as $p ) {
		$params[] = mg_avada_param( $p );
	}

	if ( ! $parent ) {
		$params = array_merge( $params, mg_avada_common_params() );
	}

	$args = array(
		'name'      => $el['name'],
		'shortcode' => $tag,
		'icon'      => isset( $el['icon'] ) ? $el['icon'] : 'fusiona-tag',
		'params'    => $params,
	);

	if ( ! empty( $el['desc'] ) ) {
		$args['description'] = $el['desc'];
	}
	if ( ! empty( $el['child'] ) ) {
		$args['multi']         = 'multi_element_parent';
		$args['element_child'] = $el['child'];
		$args['child_ui']      = true;
		$args['sortable']      = true;
	}
	if ( $parent ) {
		$args['hide_from_builder'] = true;
		$args['allow_generator']   = true;
		$args['multi']             = 'multi_element_child';
		$args['parent']            = $parent;
	}

	/**
	 * Úprava mapovania pre konkrétny prvok.
	 */
	return apply_filters( 'mg_avada_map_args', $args, $tag, $el );
}

/**
 * Registrácia všetkých prvkov v Avada Builderi.
 */
function mg_avada_register() {
	if ( ! function_exists( 'fusion_builder_map' ) ) {
		return;
	}
	foreach ( mg_elements() as $tag => $el ) {
		fusion_builder_map( mg_avada_map_args( $tag, $el ) );
	}
}
add_action( 'fusion_builder_before_init', 'mg_avada_register' );

/**
 * Je Avada Builder aktívny?
 */
function mg_has_avada() {
	return defined( 'FUSION_BUILDER_VERSION' ) || class_exists( 'FusionBuilder' ) || function_exists( 'fusion_builder_map' );
}

/**
 * V živom editore Avada načítame skript a štýly aj do náhľadu.
 */
function mg_avada_live_assets() {
	mg_enqueue_assets();
}
add_action( 'fusion_builder_enqueue_live_scripts', 'mg_avada_live_assets' );
