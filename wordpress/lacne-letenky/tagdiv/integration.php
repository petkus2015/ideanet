<?php
/**
 * Prvok „Lacné letenky“ v tagDiv Composer (téma Newspaper od tagDiv).
 *
 * tagDiv registruje vlastné bloky cez td_api_block::add na háku td_global_after.
 * Ak téma Newspaper nie je aktívna alebo sa jej API zmenilo, nič sa neregistruje
 * a blok sa dá stále vložiť shortcodom [lacne_letenky] (napr. v prvku „Column text“).
 */

defined( 'ABSPATH' ) || exit;

class Lacne_Letenky_TagDiv {

	public static function init() {
		add_action( 'td_global_after', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		if ( ! class_exists( 'td_api_block' ) || ! method_exists( 'td_api_block', 'add' ) ) {
			return;
		}
		td_api_block::add(
			'td_block_lacne_letenky',
			array(
				'map_in_visual_composer' => true,
				'map_in_td_composer'     => true,
				'name'                   => 'Lacné letenky',
				'base'                   => 'td_block_lacne_letenky',
				'class'                  => 'td_block_lacne_letenky',
				'controls'               => 'full',
				'category'               => 'Blocks',
				'icon'                   => 'icon-pagebuilder-td_block_1',
				'file'                   => LACNE_LETENKY_DIR . 'tagdiv/td_block_lacne_letenky.php',
				'params'                 => array(
					array(
						'param_name'  => 'll_title',
						'type'        => 'textfield',
						'value'       => '',
						'heading'     => 'Nadpis bloku',
						'description' => 'Prázdne = bez nadpisu, blok začne rovno ponukami',
						'holder'      => 'div',
						'class'       => 'tdc-textfield-extrabig',
					),
					array(
						'param_name'  => 'll_limit',
						'type'        => 'textfield',
						'value'       => '',
						'heading'     => 'Počet kariet',
						'description' => 'Prázdne = podľa Nastavenia → Lacné letenky',
						'holder'      => 'div',
						'class'       => 'tdc-textfield-small',
					),
				),
			)
		);
	}
}
