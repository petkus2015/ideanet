<?php
/**
 * Typy obsahu a kategórie.
 *
 * Príklady podvodov (mg_priklad), Testy (mg_test), Rýchle karty (mg_karta), Pojmy (mg_pojem).
 * Kategórie: Kanál (mg_kanal) a Pre koho (mg_skupina).
 *
 * @package MedialnaGramotnostTema
 */

defined( 'ABSPATH' ) || exit;

function mgt_register_types() {
	$common = array(
		'public'       => true,
		'show_in_rest' => true,
		'menu_position'=> 21,
		'has_archive'  => true,
	);

	register_post_type(
		'mg_priklad',
		array_merge(
			$common,
			array(
				'labels'      => mgt_labels( 'Príklady podvodov', 'Príklad podvodu', 'Pridať príklad' ),
				'menu_icon'   => 'dashicons-warning',
				'supports'    => array( 'title', 'editor', 'excerpt', 'page-attributes', 'revisions' ),
				'rewrite'     => array( 'slug' => 'podvody' ),
				'description' => 'Ukážky podvodných správ s varovnými znakmi.',
			)
		)
	);

	register_post_type(
		'mg_test',
		array_merge(
			$common,
			array(
				'labels'    => mgt_labels( 'Testy', 'Test', 'Pridať test' ),
				'menu_icon' => 'dashicons-forms',
				'supports'  => array( 'title', 'excerpt', 'page-attributes', 'revisions' ),
				'rewrite'   => array( 'slug' => 'testy' ),
			)
		)
	);

	register_post_type(
		'mg_karta',
		array_merge(
			$common,
			array(
				'labels'    => mgt_labels( 'Rýchle karty', 'Rýchla karta', 'Pridať kartu' ),
				'menu_icon' => 'dashicons-index-card',
				'supports'  => array( 'title', 'page-attributes', 'revisions' ),
				'rewrite'   => array( 'slug' => 'materialy' ),
			)
		)
	);

	register_post_type(
		'mg_pojem',
		array_merge(
			$common,
			array(
				'labels'    => mgt_labels( 'Slovník', 'Pojem', 'Pridať pojem' ),
				'menu_icon' => 'dashicons-book-alt',
				'supports'  => array( 'title', 'editor', 'revisions' ),
				'rewrite'   => array( 'slug' => 'slovnik' ),
			)
		)
	);

	register_taxonomy(
		'mg_kanal',
		array( 'mg_priklad' ),
		array(
			'labels'            => array(
				'name'          => 'Kanály',
				'singular_name' => 'Kanál',
				'add_new_item'  => 'Pridať kanál',
				'menu_name'     => 'Kanály',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'kanal' ),
		)
	);

	register_taxonomy(
		'mg_skupina',
		array( 'mg_priklad', 'mg_karta', 'mg_test' ),
		array(
			'labels'            => array(
				'name'          => 'Pre koho',
				'singular_name' => 'Skupina',
				'add_new_item'  => 'Pridať skupinu',
				'menu_name'     => 'Pre koho',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'pre' ),
		)
	);
}
add_action( 'init', 'mgt_register_types' );

function mgt_labels( $plural, $single, $add ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $single,
		'add_new'            => $add,
		'add_new_item'       => $add,
		'edit_item'          => 'Upraviť: ' . mb_strtolower( $single ),
		'new_item'           => $single,
		'view_item'          => 'Zobraziť',
		'search_items'       => 'Hľadať',
		'not_found'          => 'Nič sa nenašlo.',
		'not_found_in_trash' => 'Kôš je prázdny.',
		'all_items'          => 'Všetky',
		'menu_name'          => $plural,
	);
}

/**
 * Archívy: všetky položky na jednej stránke, zoradené podľa poradia.
 */
function mgt_archive_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( array( 'mg_priklad', 'mg_karta', 'mg_test' ) ) || $q->is_tax( array( 'mg_kanal', 'mg_skupina' ) ) ) {
		$q->set( 'posts_per_page', 100 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'ASC' ) );
	}
	if ( $q->is_post_type_archive( 'mg_pojem' ) ) {
		$q->set( 'posts_per_page', 500 );
		$q->set( 'orderby', 'title' );
		$q->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'mgt_archive_query' );
