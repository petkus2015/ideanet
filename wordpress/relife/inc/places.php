<?php
/**
 * Kam na jedlo: typ obsahu „Podnik“ (hodnotenie, fotka, krátky popis, kategórie, adresa, odkaz do Máp),
 * stránka so zoznamom s filtrovaním a import zoznamu (Nástroje → Import podnikov).
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function relife_places_register(): void {
	register_post_type(
		'relife_place',
		array(
			'labels'       => array(
				'name'          => __( 'Podniky (jedlo)', 'relife' ),
				'singular_name' => __( 'Podnik', 'relife' ),
				'add_new_item'  => __( 'Pridať podnik', 'relife' ),
				'edit_item'     => __( 'Upraviť podnik', 'relife' ),
				'all_items'     => __( 'Všetky podniky', 'relife' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-carrot',
			'menu_position' => 26,
			'supports'     => array( 'title', 'excerpt', 'thumbnail' ),
			'taxonomies'   => array( 'relife_place_cat' ),
		)
	);
	register_taxonomy(
		'relife_place_cat',
		'relife_place',
		array(
			'labels'            => array( 'name' => __( 'Kategórie podnikov', 'relife' ), 'singular_name' => __( 'Kategória', 'relife' ) ),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => false,
		)
	);
}
add_action( 'init', 'relife_places_register' );

function relife_places_metabox(): void {
	add_meta_box(
		'relife_place_meta',
		__( 'Údaje o podniku', 'relife' ),
		static function ( WP_Post $post ): void {
			wp_nonce_field( 'relife_place_save', 'relife_place_nonce' );
			$v = static fn( string $k ): string => (string) get_post_meta( $post->ID, '_rl_place_' . $k, true );
			?>
			<p><label><strong><?php esc_html_e( 'Hodnotenie (0–5)', 'relife' ); ?></strong><br><input type="number" name="rl_place_rating" min="0" max="5" step="0.1" value="<?php echo esc_attr( $v( 'rating' ) ); ?>" class="small-text"></label></p>
			<p><label><strong><?php esc_html_e( 'Cenová hladina', 'relife' ); ?></strong><br>
				<select name="rl_place_price">
					<option value=""><?php esc_html_e( '— neuvedené —', 'relife' ); ?></option>
					<?php foreach ( array( 1 => '€', 2 => '€€', 3 => '€€€', 4 => '€€€€' ) as $k => $lbl ) : ?>
						<option value="<?php echo (int) $k; ?>" <?php selected( $v( 'price' ), (string) $k ); ?>><?php echo esc_html( $lbl ); ?></option>
					<?php endforeach; ?>
				</select></label></p>
			<p><label><strong><?php esc_html_e( 'Adresa / štvrť', 'relife' ); ?></strong><br><input type="text" name="rl_place_address" value="<?php echo esc_attr( $v( 'address' ) ); ?>" class="widefat"></label></p>
			<p><label><strong><?php esc_html_e( 'Odkaz do Google Máp', 'relife' ); ?></strong><br><input type="url" name="rl_place_url" value="<?php echo esc_attr( $v( 'url' ) ); ?>" class="widefat" placeholder="https://maps.app.goo.gl/…"></label></p>
			<p class="description"><?php esc_html_e( 'Fotka: nastavte „Obrázok k príspevku“ vpravo. Krátky popis: pole „Úryvok“ nižšie. Kategórie vpravo.', 'relife' ); ?></p>
			<?php
		},
		'relife_place',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'relife_places_metabox' );

function relife_places_save( int $post_id ): void {
	if ( ! isset( $_POST['relife_place_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['relife_place_nonce'] ) ), 'relife_place_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$map = array(
		'rating'  => static fn( $x ): string => '' === trim( (string) $x ) ? '' : (string) max( 0, min( 5, (float) str_replace( ',', '.', (string) $x ) ) ),
		'price'   => static fn( $x ): string => in_array( (string) $x, array( '1', '2', '3', '4' ), true ) ? (string) $x : '',
		'address' => 'sanitize_text_field',
		'url'     => 'esc_url_raw',
	);
	foreach ( $map as $k => $cb ) {
		$val = isset( $_POST[ 'rl_place_' . $k ] ) ? (string) call_user_func( $cb, wp_unslash( $_POST[ 'rl_place_' . $k ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === $val ) {
			delete_post_meta( $post_id, '_rl_place_' . $k );
		} else {
			update_post_meta( $post_id, '_rl_place_' . $k, $val );
		}
	}
}
add_action( 'save_post_relife_place', 'relife_places_save' );

/**
 * Stĺpce v zozname podnikov v administrácii.
 *
 * @param array<string,string> $cols
 * @return array<string,string>
 */
function relife_places_columns( array $cols ): array {
	$cols['rl_rating'] = __( 'Hodnotenie', 'relife' );
	$cols['rl_thumb']  = __( 'Fotka', 'relife' );
	return $cols;
}
add_filter( 'manage_relife_place_posts_columns', 'relife_places_columns' );

function relife_places_column_content( string $col, int $post_id ): void {
	if ( 'rl_rating' === $col ) {
		$r = (string) get_post_meta( $post_id, '_rl_place_rating', true );
		echo '' !== $r ? esc_html( number_format_i18n( (float) $r, 1 ) . ' ★' ) : '—';
	}
	if ( 'rl_thumb' === $col ) {
		echo has_post_thumbnail( $post_id ) ? wp_kses_post( get_the_post_thumbnail( $post_id, array( 48, 48 ) ) ) : '—';
	}
}
add_action( 'manage_relife_place_posts_custom_column', 'relife_places_column_content', 10, 2 );

/**
 * Stránku „Kam na jedlo v BA“ vytvorí téma raz (pri aktivácii alebo po aktualizácii).
 */
function relife_create_places_page(): void {
	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'template-places.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $existing ) {
		return;
	}
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Kam na jedlo v BA', 'post_name' => 'kam-na-jedlo-v-ba', 'post_content' => '' ) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( (int) $id, '_wp_page_template', 'template-places.php' );
	}
}
add_action( 'after_switch_theme', 'relife_create_places_page' );

function relife_maybe_create_places_page(): void {
	if ( get_option( 'relife_places_page_done' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	relife_create_places_page();
	update_option( 'relife_places_page_done', 1, false );
}
add_action( 'admin_init', 'relife_maybe_create_places_page' );

function relife_places_enqueue(): void {
	if ( is_page_template( 'template-places.php' ) ) {
		wp_enqueue_script( 'relife-places', RELIFE_URI . '/assets/js/places.js', array(), RELIFE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_places_enqueue' );

/**
 * Údaje podnikov pre šablónu.
 *
 * @return array<int, array<string, mixed>>
 */
function relife_places_data(): array {
	$q = new WP_Query(
		array(
			'post_type'      => 'relife_place',
			'post_status'    => 'publish',
			'posts_per_page' => 300,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	$out = array();
	foreach ( $q->posts as $p ) {
		$terms = get_the_terms( $p, 'relife_place_cat' );
		$terms = is_array( $terms ) ? $terms : array();
		$out[] = array(
			'id'      => $p->ID,
			'name'    => get_the_title( $p ),
			'desc'    => has_excerpt( $p ) ? wp_strip_all_tags( get_the_excerpt( $p ) ) : '',
			'rating'  => (string) get_post_meta( $p->ID, '_rl_place_rating', true ),
			'price'   => (int) get_post_meta( $p->ID, '_rl_place_price', true ),
			'address' => (string) get_post_meta( $p->ID, '_rl_place_address', true ),
			'url'     => (string) get_post_meta( $p->ID, '_rl_place_url', true ),
			'cats'    => $terms,
			'thumb'   => has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 'relife-card', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 760px) 100vw, 330px' ) ) : '',
		);
	}
	return $out;
}

/* ---------- Import (Nástroje → Import podnikov) ---------- */

function relife_places_import_menu(): void {
	add_management_page( __( 'Import podnikov', 'relife' ), __( 'Import podnikov (jedlo)', 'relife' ), 'edit_pages', 'relife-places-import', 'relife_places_import_page' );
}
add_action( 'admin_menu', 'relife_places_import_menu' );

function relife_places_import_page(): void {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$done = isset( $_GET['imported'] ) ? (int) $_GET['imported'] : -1; // phpcs:ignore WordPress.Security.NonceVerification
	$skip = isset( $_GET['skipped'] ) ? (int) $_GET['skipped'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import podnikov (Kam na jedlo)', 'relife' ); ?></h1>
		<?php if ( $done >= 0 ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( sprintf( /* translators: 1: imported, 2: skipped */ __( 'Pridaných podnikov: %1$d, preskočených (už existujú): %2$d.', 'relife' ), $done, $skip ) ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Vložte zoznam podnikov. Podporované sú dva tvary:', 'relife' ); ?></p>
		<ol>
			<li><strong><?php esc_html_e( 'Riadky s oddeľovačom |', 'relife' ); ?></strong> – <code>Názov | Kategória | Hodnotenie | Popis | Adresa | Odkaz do Máp | URL fotky</code> <?php esc_html_e( '(od druhého poľa sú všetky nepovinné; viac kategórií oddeľte čiarkou)', 'relife' ); ?></li>
			<li><strong><?php esc_html_e( 'CSV z Google Takeout', 'relife' ); ?></strong> (<?php esc_html_e( 'Uložené miesta → súbor zoznamu s hlavičkou Title, Note, URL, Tags, Comment', 'relife' ); ?>).</li>
		</ol>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="relife_places_import">
			<?php wp_nonce_field( 'relife_places_import' ); ?>
			<textarea name="data" rows="14" class="large-text code" placeholder="Pizzeria Mamma | Pizza, Talianska | 4,6 | Chrumkavé cesto a domáce cestoviny. | Obchodná 12 | https://maps.app.goo.gl/… | https://…/foto.jpg"></textarea>
			<p><label><?php esc_html_e( 'Predvolená kategória, ak chýba:', 'relife' ); ?> <input type="text" name="default_cat" value="" class="regular-text"></label></p>
			<?php submit_button( __( 'Importovať', 'relife' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * @return array<int, array<string,string>>
 */
function relife_places_parse_import( string $text ): array {
	$text = trim( $text );
	$rows = array();
	$first = strtolower( (string) strtok( $text, "\n" ) );
	if ( false !== strpos( $first, 'title' ) && false !== strpos( $first, 'url' ) && false === strpos( $first, '|' ) ) {
		$lines = preg_split( '/\R/', $text ) ?: array();
		$head  = array_map( static fn( $h ) => strtolower( trim( (string) $h ) ), str_getcsv( (string) array_shift( $lines ) ) );
		foreach ( $lines as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$cols = str_getcsv( $line );
			$get  = static function ( array $keys ) use ( $head, $cols ): string {
				foreach ( $keys as $k ) {
					$i = array_search( $k, $head, true );
					if ( false !== $i && isset( $cols[ $i ] ) && '' !== trim( (string) $cols[ $i ] ) ) {
						return trim( (string) $cols[ $i ] );
					}
				}
				return '';
			};
			$rows[] = array( 'name' => $get( array( 'title', 'name' ) ), 'cat' => $get( array( 'tags' ) ), 'rating' => '', 'desc' => $get( array( 'note', 'comment' ) ), 'address' => '', 'url' => $get( array( 'url' ) ), 'img' => '' );
		}
		return $rows;
	}
	foreach ( preg_split( '/\R/', $text ) ?: array() as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}
		$p      = array_pad( array_map( 'trim', explode( '|', $line ) ), 7, '' );
		$rows[] = array( 'name' => $p[0], 'cat' => $p[1], 'rating' => $p[2], 'desc' => $p[3], 'address' => $p[4], 'url' => $p[5], 'img' => $p[6] );
	}
	return $rows;
}

function relife_places_import_handle(): void {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'Nemáte oprávnenie.', 'relife' ), 403 );
	}
	check_admin_referer( 'relife_places_import' );
	$data    = isset( $_POST['data'] ) ? (string) wp_unslash( $_POST['data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$default = isset( $_POST['default_cat'] ) ? sanitize_text_field( wp_unslash( $_POST['default_cat'] ) ) : '';
	$added   = 0;
	$skipped = 0;
	foreach ( relife_places_parse_import( $data ) as $r ) {
		$name = sanitize_text_field( $r['name'] );
		if ( '' === $name ) {
			continue;
		}
		$dup = get_posts( array( 'post_type' => 'relife_place', 'post_status' => 'any', 'title' => $name, 'posts_per_page' => 1, 'fields' => 'ids' ) );
		if ( $dup ) {
			++$skipped;
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'relife_place',
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_excerpt' => sanitize_textarea_field( $r['desc'] ),
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$rating = '' === $r['rating'] ? '' : (string) max( 0, min( 5, (float) str_replace( ',', '.', $r['rating'] ) ) );
		foreach ( array( 'rating' => $rating, 'address' => sanitize_text_field( $r['address'] ), 'url' => esc_url_raw( $r['url'] ) ) as $k => $v ) {
			if ( '' !== $v ) {
				update_post_meta( (int) $id, '_rl_place_' . $k, $v );
			}
		}
		$cats = array_filter( array_map( 'trim', explode( ',', '' !== $r['cat'] ? $r['cat'] : $default ) ) );
		if ( $cats ) {
			wp_set_object_terms( (int) $id, $cats, 'relife_place_cat' );
		}
		if ( '' !== $r['img'] && filter_var( $r['img'], FILTER_VALIDATE_URL ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$att = media_sideload_image( $r['img'], (int) $id, $name, 'id' );
			if ( is_int( $att ) ) {
				set_post_thumbnail( (int) $id, $att );
			}
		}
		++$added;
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'relife-places-import', 'imported' => $added, 'skipped' => $skipped ), admin_url( 'tools.php' ) ) );
	exit;
}
add_action( 'admin_post_relife_places_import', 'relife_places_import_handle' );
