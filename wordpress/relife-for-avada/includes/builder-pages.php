<?php
/**
 * Hotové stránky v Avada Builderi: Domov, Ankety, Uložené.
 *
 * Obsah používa kontajnery Avada Builderu (fusion_builder_container → row → column → Text Block)
 * a v ňom shortcody RELIFE. Stránky sa dajú v Builderi ďalej upravovať.
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Jeden stĺpec s Text Blockom, v ktorom je shortcode.
 */
function rlav_fb_column( string $shortcode, string $type = '1_1', bool $first = true, bool $last = true ): string {
	return '[fusion_builder_column type="' . $type . '" layout="' . $type . '" first="' . ( $first ? 'true' : 'false' ) . '" last="' . ( $last ? 'true' : 'false' ) . '" spacing="" center_content="no" link="" target="_self" min_height="" hide_on_mobile="small-visibility,medium-visibility,large-visibility" background_type="single" hover_type="none"]'
		. '[fusion_text]' . $shortcode . '[/fusion_text]'
		. '[/fusion_builder_column]';
}

/**
 * Kontajner (sekcia) s jedným riadkom.
 */
function rlav_fb_container( string $columns, string $bg = '#131313', string $pad_top = '0', string $pad_bottom = '0' ): string {
	return '[fusion_builder_container type="flex" hundred_percent="no" hundred_percent_height="no" equal_height_columns="no" hide_on_mobile="small-visibility,medium-visibility,large-visibility" background_color="' . $bg . '" padding_top="' . $pad_top . '" padding_bottom="' . $pad_bottom . '" border_style="solid"]'
		. '[fusion_builder_row]' . $columns . '[/fusion_builder_row]'
		. '[/fusion_builder_container]';
}

/**
 * Obsah domovskej stránky.
 */
function rlav_home_builder_content(): string {
	$sections = array( 'hero', 'quick', 'today', 'explained', 'pillars', 'versus', 'sections', 'formats', 'guides', 'newsletter' );
	$out      = '';
	foreach ( $sections as $i => $name ) {
		$top  = 0 === $i ? '20' : '0';
		$out .= rlav_fb_container( rlav_fb_column( '[relife_' . $name . ']' ), '#131313', $top, '0' );
	}
	return $out;
}

/**
 * Obsah stránky Ankety: aktívna anketa vedľa starších ankiet.
 */
function rlav_polls_builder_content(): string {
	$cols = rlav_fb_column( '[relife_sidecard]', '1_3', true, false ) . rlav_fb_column( '[relife_polls]', '2_3', false, true );
	return rlav_fb_container( $cols, '#131313', '20', '20' );
}

/**
 * Obsah stránky Uložené.
 */
function rlav_saved_builder_content(): string {
	return rlav_fb_container( rlav_fb_column( '[relife_saved]' ), '#131313', '20', '20' );
}

/**
 * Šablóna stránky na plnú šírku, ak ju aktívna téma (Avada) ponúka.
 */
function rlav_full_width_template(): string {
	$templates = wp_get_theme()->get_page_templates();
	return isset( $templates['100-width.php'] ) ? '100-width.php' : '';
}

/**
 * Vytvorí stránku s obsahom pre Avada Builder (ak ešte neexistuje).
 *
 * @return int ID stránky (existujúcej alebo novej), 0 pri chybe.
 */
function rlav_create_builder_page( string $title, string $slug, string $content, bool &$is_new ): int {
	$is_new   = false;
	$existing = get_page_by_path( $slug );
	if ( $existing instanceof WP_Post ) {
		return $existing->ID;
	}
	$args = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => $content,
	);
	$tpl = rlav_full_width_template();
	if ( '' !== $tpl ) {
		$args['page_template'] = $tpl;
	}
	$id = wp_insert_post( $args, true );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	// Stránka sa otvorí v Avada Builderi.
	update_post_meta( $id, 'fusion_builder_status', 'active' );
	// Skryť titulok stránky (Avada si ho zobrazuje sama); kľúče sa líšia podľa verzie Avady.
	update_post_meta( $id, 'pyre_page_title', 'no' );
	update_post_meta( $id, '_fusion', array( 'page_title_bar' => 'hide' ) );
	$is_new = true;
	return (int) $id;
}

/**
 * Vytvorí stránky Domov, Ankety a Uložené. Voliteľne nastaví Domov ako úvodnú stránku.
 *
 * @return int Počet nových stránok.
 */
function rlav_create_builder_pages( bool $set_front ): int {
	$new     = 0;
	$options = rlav_options();
	$defs    = array(
		array( 'Domov', 'domov', rlav_home_builder_content(), 'home' ),
		array( 'Ankety', 'ankety', rlav_polls_builder_content(), 'polls' ),
		array( 'Uložené', 'ulozene', rlav_saved_builder_content(), 'saved' ),
	);
	$ids = array();
	foreach ( $defs as $d ) {
		$is_new = false;
		$id     = rlav_create_builder_page( $d[0], $d[1], $d[2], $is_new );
		if ( $id > 0 ) {
			$ids[ $d[3] ] = $id;
			if ( $is_new ) {
				++$new;
			}
			if ( 'home' !== $d[3] && empty( $options[ $d[3] . '_page' ] ) ) {
				$options[ $d[3] . '_page' ] = $id;
			}
		}
	}
	update_option( 'rlav_options', $options );
	if ( $set_front && ! empty( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}
	return $new;
}
