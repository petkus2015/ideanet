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
	register_taxonomy(
		'relife_place_city',
		'relife_place',
		array(
			'labels'            => array( 'name' => __( 'Mestá', 'relife' ), 'singular_name' => __( 'Mesto', 'relife' ) ),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
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

/* ---------- Mestá: každé mesto má vlastnú stránku, vlastný zoznam z Google Máp a vlastné podniky ---------- */

/**
 * @return array<int, WP_Term>
 */
function relife_places_cities(): array {
	$t = get_terms( array( 'taxonomy' => 'relife_place_city', 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC' ) );
	return is_array( $t ) ? array_values( array_filter( $t, static fn( $x ): bool => $x instanceof WP_Term ) ) : array();
}

function relife_places_valid_maps_url( string $url ): bool {
	return (bool) preg_match( '#^https://(maps\.app\.goo\.gl|www\.google\.[a-z.]+/maps|maps\.google\.[a-z.]+|goo\.gl/maps)#i', trim( $url ) );
}

/**
 * Stránka mesta (šablóna Kam na jedlo); vytvorí sa, ak ešte neexistuje.
 */
function relife_places_ensure_page( WP_Term $t ): int {
	$pid = (int) get_term_meta( $t->term_id, 'rl_page_id', true );
	if ( $pid && 'page' === get_post_type( $pid ) && 'trash' !== get_post_status( $pid ) ) {
		return $pid;
	}
	$found = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_rl_places_city', 'meta_value' => $t->slug ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $found ) {
		update_term_meta( $t->term_id, 'rl_page_id', (int) $found[0] );
		return (int) $found[0];
	}
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Kam na jedlo – ' . $t->name, 'post_name' => 'kam-na-jedlo-' . $t->slug, 'post_content' => '' ) );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( (int) $id, '_wp_page_template', 'template-places.php' );
	update_post_meta( (int) $id, '_rl_places_city', $t->slug );
	update_term_meta( $t->term_id, 'rl_page_id', (int) $id );
	return (int) $id;
}

/**
 * Vytvorí mesto (term + stránka). Vráti term alebo WP_Error.
 *
 * @return WP_Term|WP_Error
 */
function relife_places_add_city( string $name, string $maps_url = '' ) {
	$name = sanitize_text_field( $name );
	if ( '' === $name ) {
		return new WP_Error( 'relife_city', __( 'Zadajte názov mesta.', 'relife' ) );
	}
	$exists = get_term_by( 'name', $name, 'relife_place_city' );
	if ( $exists instanceof WP_Term ) {
		return new WP_Error( 'relife_city', __( 'Mesto s týmto názvom už existuje.', 'relife' ) );
	}
	$res = wp_insert_term( $name, 'relife_place_city' );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$t = get_term( (int) $res['term_id'], 'relife_place_city' );
	if ( ! $t instanceof WP_Term ) {
		return new WP_Error( 'relife_city', __( 'Mesto sa nepodarilo vytvoriť.', 'relife' ) );
	}
	if ( '' !== $maps_url ) {
		update_term_meta( $t->term_id, 'rl_maps_url', esc_url_raw( $maps_url ) );
	}
	relife_places_ensure_page( $t );
	return $t;
}

/**
 * Jednorazová migrácia: doterajšie podniky a stránka patria Bratislave.
 */
function relife_places_migrate(): void {
	if ( get_option( 'relife_places_cities_v1' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$t = get_term_by( 'slug', 'bratislava', 'relife_place_city' );
	if ( ! $t instanceof WP_Term ) {
		$res = wp_insert_term( 'Bratislava', 'relife_place_city', array( 'slug' => 'bratislava' ) );
		$t   = is_wp_error( $res ) ? null : get_term( (int) $res['term_id'], 'relife_place_city' );
	}
	if ( ! $t instanceof WP_Term ) {
		return;
	}
	if ( '' === (string) get_term_meta( $t->term_id, 'rl_maps_url', true ) ) {
		update_term_meta( $t->term_id, 'rl_maps_url', (string) get_theme_mod( 'relife_places_maps_url', 'https://maps.app.goo.gl/JgE1WtDw7LtKG1dD8?g_st=i' ) );
	}
	$page = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_wp_page_template', 'meta_value' => 'template-places.php' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $page ) {
		update_post_meta( (int) $page[0], '_rl_places_city', $t->slug );
		update_term_meta( $t->term_id, 'rl_page_id', (int) $page[0] );
	} else {
		relife_places_ensure_page( $t );
	}
	$orphans = get_posts( array( 'post_type' => 'relife_place', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'relife_place_city', 'operator' => 'NOT EXISTS' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $orphans as $oid ) {
		wp_set_object_terms( (int) $oid, array( (int) $t->term_id ), 'relife_place_city' );
	}
	update_option( 'relife_places_cities_v1', 1, false );
}
add_action( 'admin_init', 'relife_places_migrate' );

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
function relife_places_data( string $city = '' ): array {
	$args = array(
			'post_type'      => 'relife_place',
			'post_status'    => 'publish',
			'posts_per_page' => 300,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
	);
	if ( '' !== $city ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'relife_place_city', 'field' => 'slug', 'terms' => $city ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$q   = new WP_Query( $args );
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


/**
 * ID podniku s rovnakým názvom (v danom meste; bez mesta všade), alebo 0.
 */
function relife_places_find( string $name, string $city = '' ): int {
	$args = array( 'post_type' => 'relife_place', 'post_status' => 'any', 'title' => $name, 'posts_per_page' => 1, 'fields' => 'ids' );
	if ( '' !== $city ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'relife_place_city', 'field' => 'slug', 'terms' => $city ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$f = get_posts( $args );
	return $f ? (int) $f[0] : 0;
}

/**
 * Vytvorí podnik z riadku importu; vráti false, ak už existuje (rovnaký názov).
 *
 * @param array<string,string> $r
 */
function relife_places_create( array $r, string $default = '', string $city = '' ): bool {
	$name = sanitize_text_field( $r['name'] );
	if ( '' === $name ) {
		return false;
	}
	if ( relife_places_find( $name, $city ) ) {
		return false;
	}
	$id = wp_insert_post( array( 'post_type' => 'relife_place', 'post_status' => 'publish', 'post_title' => $name, 'post_excerpt' => sanitize_textarea_field( $r['desc'] ?? '' ) ) );
	if ( ! $id || is_wp_error( $id ) ) {
		return false;
	}
	$rating = '' === ( $r['rating'] ?? '' ) ? '' : (string) max( 0, min( 5, (float) str_replace( ',', '.', $r['rating'] ) ) );
	foreach ( array( 'rating' => $rating, 'address' => sanitize_text_field( $r['address'] ?? '' ), 'url' => esc_url_raw( $r['url'] ?? '' ) ) as $k => $v ) {
		if ( '' !== $v ) {
			update_post_meta( (int) $id, '_rl_place_' . $k, $v );
		}
	}
	if ( '' !== $city ) {
		wp_set_object_terms( (int) $id, $city, 'relife_place_city' );
	}
	$cat_raw = '' !== ( $r['cat'] ?? '' ) ? $r['cat'] : $default;
	$cats    = array_filter( array_map( 'trim', explode( ',', $cat_raw ) ) );
	if ( $cats ) {
		wp_set_object_terms( (int) $id, $cats, 'relife_place_cat' );
	}
	$img = $r['img'] ?? '';
	if ( '' !== $img && filter_var( $img, FILTER_VALIDATE_URL ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$att = media_sideload_image( $img, (int) $id, $name, 'id' );
		if ( is_int( $att ) ) {
			set_post_thumbnail( (int) $id, $att );
		}
	}
	return true;
}

/**
 * Jedným klikom načíta zoznam „Bratislava kam na jedlo“ (32 podnikov z Google Máp) priložený k téme.
 */
function relife_places_seed_handle(): void {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'Nemáte oprávnenie.', 'relife' ), 403 );
	}
	check_admin_referer( 'relife_places_seed' );
	relife_places_migrate();
	$file = RELIFE_DIR . '/inc/data/places-bratislava.json';
	$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$added   = 0;
	$skipped = 0;
	foreach ( is_array( $data ) ? $data : array() as $row ) {
		relife_places_create( array( 'name' => (string) $row['name'], 'cat' => (string) $row['cat'], 'rating' => '', 'desc' => '', 'address' => (string) $row['address'], 'url' => (string) $row['url'], 'img' => '' ), '', 'bratislava' ) ? ++$added : ++$skipped;
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'relife-places-cities', 'imported' => $added, 'skipped' => $skipped ), admin_url( 'edit.php?post_type=relife_place' ) ) );
	exit;
}
add_action( 'admin_post_relife_places_seed', 'relife_places_seed_handle' );


/* ---------- Aktualizácia zo zdieľaného zoznamu Google Máp (na vyžiadanie) ---------- */

/**
 * Načíta zdieľaný zoznam z Google Máp (adresa z Customizeru) a vráti jeho miesta.
 *
 * @return array<int, array{name:string,address:string,url:string}>|WP_Error
 */
function relife_places_fetch_gmaps_list( string $share_url ) {
	$share_url = trim( $share_url );
	if ( '' === $share_url || ! preg_match( '#^https://(maps\.app\.goo\.gl|www\.google\.[a-z.]+/maps|maps\.google\.[a-z.]+|goo\.gl/maps)#i', $share_url ) ) {
		return new WP_Error( 'relife_places_url', __( 'Odkaz na zoznam v Google Mapách nie je zadaný alebo nie je platný (Vzhľad → Prispôsobiť → RELIFE – Úvodná stránka).', 'relife' ) );
	}
	$args = array(
		'timeout'    => 25,
		'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
		'headers'    => array( 'Accept-Language' => 'sk,en;q=0.8', 'Cookie' => 'CONSENT=YES+cb; SOCS=CAI' ),
	);
	$res = wp_remote_get( $share_url, $args );
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'relife_places_http', __( 'Zoznam sa nepodarilo načítať z Google Máp: ', 'relife' ) . $res->get_error_message() );
	}
	$html = (string) wp_remote_retrieve_body( $res );
	$list = '';
	if ( preg_match( '#<link href="(/maps/preview/entitylist/getlist[^"]+)"#', $html, $m ) ) {
		$list = 'https://www.google.com' . html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
	} else {
		// Záloha: ID zoznamu z konečnej adresy po presmerovaní.
		$final = '';
		if ( isset( $res['http_response'] ) && is_object( $res['http_response'] ) && method_exists( $res['http_response'], 'get_response_object' ) ) {
			$obj   = $res['http_response']->get_response_object();
			$final = isset( $obj->url ) ? (string) $obj->url : '';
		}
		if ( preg_match( '#placelists/list/([A-Za-z0-9_-]+)#', $final . ' ' . $html, $m ) ) {
			$list = 'https://www.google.com/maps/preview/entitylist/getlist?authuser=0&hl=sk&pb=' . rawurlencode( '!1m1!1s' . $m[1] . '!2e2!3e2!4i500' );
		}
	}
	if ( '' === $list ) {
		return new WP_Error( 'relife_places_parse', __( 'V odpovedi Google Máp sa nenašiel zoznam. Skontrolujte, či je zoznam zdieľaný („Každý s odkazom“).', 'relife' ) );
	}
	$res2 = wp_remote_get( $list, $args );
	if ( is_wp_error( $res2 ) ) {
		return new WP_Error( 'relife_places_http', __( 'Zoznam sa nepodarilo načítať z Google Máp: ', 'relife' ) . $res2->get_error_message() );
	}
	$body = (string) wp_remote_retrieve_body( $res2 );
	$json = json_decode( (string) preg_replace( '/^\)\]\}\'\s*/', '', $body ), true );
	if ( ! is_array( $json ) || ! isset( $json[0][8] ) || ! is_array( $json[0][8] ) ) {
		return new WP_Error( 'relife_places_parse', __( 'Odpoveď Google Máp má iný tvar, než očakávame. Zoznam sa nedá načítať automaticky; použite import (CSV z Google Takeout).', 'relife' ) );
	}
	$out = array();
	foreach ( $json[0][8] as $it ) {
		$name = isset( $it[2] ) ? trim( (string) $it[2] ) : '';
		if ( '' === $name ) {
			continue;
		}
		$addr   = isset( $it[1][4] ) ? trim( (string) $it[1][4] ) : '';
		$out[]  = array(
			'name'    => $name,
			'address' => $addr,
			'url'     => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $name . ' ' . ( '' !== $addr ? $addr : 'Bratislava' ) ),
		);
	}
	return $out;
}

/**
 * Zosúladí podniky mesta so zoznamom z Google Máp: nové pridá, pri existujúcich doplní adresu a odkaz, ak chýbajú.
 *
 * @return array{added:int,updated:int,total:int}|WP_Error
 */
function relife_places_sync( WP_Term $city, bool $remove_missing = false ) {
	$items = relife_places_fetch_gmaps_list( (string) get_term_meta( $city->term_id, 'rl_maps_url', true ) );
	if ( is_wp_error( $items ) ) {
		return $items;
	}
	$added   = 0;
	$updated = 0;
	$seen    = array();
	foreach ( $items as $it ) {
		$seen[] = mb_strtolower( $it['name'] );
		$dup    = relife_places_find( $it['name'], $city->slug );
		if ( $dup ) {
			$changed = false;
			foreach ( array( 'address' => $it['address'], 'url' => $it['url'] ) as $k => $v ) {
				if ( '' !== $v && '' === (string) get_post_meta( $dup, '_rl_place_' . $k, true ) ) {
					update_post_meta( $dup, '_rl_place_' . $k, 'url' === $k ? esc_url_raw( $v ) : sanitize_text_field( $v ) );
					$changed = true;
				}
			}
			$updated += $changed ? 1 : 0;
			continue;
		}
		if ( relife_places_create( array( 'name' => $it['name'], 'cat' => 'Ostatné', 'rating' => '', 'desc' => '', 'address' => $it['address'], 'url' => $it['url'], 'img' => '' ), '', $city->slug ) ) {
			$new = relife_places_find( $it['name'], $city->slug );
			if ( $new ) {
				update_post_meta( $new, '_rl_place_source', 'gmaps' );
			}
			++$added;
		}
	}
	if ( $remove_missing ) {
		$mine = get_posts( array( 'post_type' => 'relife_place', 'post_status' => 'publish', 'posts_per_page' => 500, 'meta_key' => '_rl_place_source', 'meta_value' => 'gmaps', 'tax_query' => array( array( 'taxonomy' => 'relife_place_city', 'field' => 'slug', 'terms' => $city->slug ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( $mine as $p ) {
			if ( ! in_array( mb_strtolower( $p->post_title ), $seen, true ) ) {
				wp_trash_post( $p->ID );
			}
		}
	}
	update_term_meta( $city->term_id, 'rl_last_sync', time() );
	return array( 'added' => $added, 'updated' => $updated, 'total' => count( $items ) );
}

/* ---------- Admin: jedno miesto pre mestá, zoznamy z Máp a import (Podniky → Mestá a zoznamy) ---------- */

function relife_places_hub_url( array $args = array() ): string {
	return add_query_arg( $args, admin_url( 'edit.php?post_type=relife_place&page=relife-places-cities' ) );
}

function relife_places_hub_menu(): void {
	add_submenu_page( 'edit.php?post_type=relife_place', __( 'Mestá a zoznamy', 'relife' ), __( 'Mestá a zoznamy', 'relife' ), 'edit_pages', 'relife-places-cities', 'relife_places_hub_page' );
}
add_action( 'admin_menu', 'relife_places_hub_menu' );

/**
 * Uloží mesto (nové alebo existujúce) a voliteľne hneď aktualizuje zoznam z Máp.
 */
function relife_places_city_save_handle(): void {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'Nemáte oprávnenie.', 'relife' ), 403 );
	}
	check_admin_referer( 'relife_places_city' );
	$term_id = isset( $_POST['term_id'] ) ? (int) $_POST['term_id'] : 0;
	$name    = isset( $_POST['city_name'] ) ? sanitize_text_field( wp_unslash( $_POST['city_name'] ) ) : '';
	$url     = isset( $_POST['maps_url'] ) ? trim( esc_url_raw( wp_unslash( $_POST['maps_url'] ) ) ) : '';
	$args    = array();
	if ( '' !== $url && ! relife_places_valid_maps_url( $url ) ) {
		wp_safe_redirect( relife_places_hub_url( array( 'sync_err' => rawurlencode( __( 'Odkaz musí byť zdieľaný odkaz na zoznam z Google Máp (napr. https://maps.app.goo.gl/…).', 'relife' ) ) ) ) );
		exit;
	}
	if ( $term_id ) {
		$t = get_term( $term_id, 'relife_place_city' );
		if ( $t instanceof WP_Term ) {
			if ( '' !== $name && $name !== $t->name ) {
				wp_update_term( $t->term_id, 'relife_place_city', array( 'name' => $name ) );
			}
			update_term_meta( $t->term_id, 'rl_maps_url', $url );
			relife_places_ensure_page( $t );
		}
	} else {
		$t = relife_places_add_city( $name, $url );
		if ( is_wp_error( $t ) ) {
			wp_safe_redirect( relife_places_hub_url( array( 'sync_err' => rawurlencode( $t->get_error_message() ) ) ) );
			exit;
		}
		$args['city_added'] = rawurlencode( $t->name );
	}
	if ( isset( $t ) && $t instanceof WP_Term && ! empty( $_POST['do_sync'] ) ) {
		$res = relife_places_sync( $t, ! empty( $_POST['remove_missing'] ) );
		if ( is_wp_error( $res ) ) {
			$args['sync_err'] = rawurlencode( $res->get_error_message() );
		} else {
			$args += array( 'sync_city' => rawurlencode( $t->name ), 'sync_added' => $res['added'], 'sync_updated' => $res['updated'], 'sync_total' => $res['total'] );
		}
	} elseif ( ! isset( $args['city_added'] ) ) {
		$args['saved'] = 1;
	}
	wp_safe_redirect( relife_places_hub_url( $args ) );
	exit;
}
add_action( 'admin_post_relife_places_city', 'relife_places_city_save_handle' );

/**
 * Hlášky o výsledku (zdieľa ich obrazovka Mestá a zoznamy aj stránka importu).
 */
function relife_places_notices(): void {
	// phpcs:disable WordPress.Security.NonceVerification
	if ( isset( $_GET['sync_err'] ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['sync_err'] ) ) ) ) . '</p></div>';
	}
	if ( isset( $_GET['sync_total'] ) ) {
		$city = isset( $_GET['sync_city'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['sync_city'] ) ) ) : '';
		/* translators: 1: city, 2: total, 3: added, 4: updated */
		echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( '%1$s – zoznam z Google Máp: %2$d miest. Pridaných nových: %3$d, doplnených: %4$d.', 'relife' ), $city, (int) $_GET['sync_total'], (int) ( $_GET['sync_added'] ?? 0 ), (int) ( $_GET['sync_updated'] ?? 0 ) ) ) . '</p></div>';
	}
	if ( isset( $_GET['city_added'] ) ) {
		/* translators: %s: city */
		echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Mesto %s bolo pridané a bola preň vytvorená stránka.', 'relife' ), rawurldecode( sanitize_text_field( wp_unslash( $_GET['city_added'] ) ) ) ) ) . '</p></div>';
	}
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Uložené.', 'relife' ) . '</p></div>';
	}
	if ( isset( $_GET['imported'] ) ) {
		/* translators: 1: imported, 2: skipped */
		echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Pridaných podnikov: %1$d, preskočených (už existujú): %2$d.', 'relife' ), (int) $_GET['imported'], (int) ( $_GET['skipped'] ?? 0 ) ) ) . '</p></div>';
	}
	// phpcs:enable
}

function relife_places_hub_page(): void {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	relife_places_migrate();
	$cities = relife_places_cities();
	?>
	<style>
		.rlc-card{background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:16px 20px;margin:16px 0;max-width:900px}
		.rlc-card h2{margin:0 0 4px;font-size:18px;display:flex;gap:10px;align-items:baseline;flex-wrap:wrap}
		.rlc-card h2 small{font-weight:400;color:#646970;font-size:13px}
		.rlc-links{margin:0 0 12px;display:flex;gap:14px;flex-wrap:wrap}
		.rlc-row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:10px}
		.rlc-row label{display:block;font-weight:600;margin-bottom:4px}
		.rlc-row .grow{flex:1 1 360px}
		.rlc-row input[type=text],.rlc-row input[type=url]{width:100%}
		.rlc-card details{margin-top:12px;border-top:1px solid #dcdcde;padding-top:10px}
		.rlc-card summary{cursor:pointer;font-weight:600}
	</style>
	<div class="wrap">
		<h1><?php esc_html_e( 'Kam na jedlo – mestá a zoznamy', 'relife' ); ?></h1>
		<p><?php esc_html_e( 'Každé mesto má vlastnú stránku a vlastný zoznam podnikov. Vložte odkaz na zdieľaný zoznam z Google Máp a kliknite na „Uložiť a aktualizovať z Máp“. Keď v Mapách niečo pridáte, stačí kliknúť znova – pribudnú len nové miesta, hodnotenia, popisy, fotky a kategórie sa nemenia.', 'relife' ); ?></p>
		<?php relife_places_notices(); ?>

		<?php foreach ( $cities as $c ) : ?>
			<?php
			$pid   = relife_places_ensure_page( $c );
			$count = (int) $c->count;
			$last  = (int) get_term_meta( $c->term_id, 'rl_last_sync', true );
			$url   = (string) get_term_meta( $c->term_id, 'rl_maps_url', true );
			?>
			<div class="rlc-card">
				<h2><?php echo esc_html( $c->name ); ?> <small><?php echo esc_html( sprintf( /* translators: %d: number of places */ _n( '%d podnik', '%d podnikov', $count, 'relife' ), $count ) ); ?><?php if ( $last ) : ?> · <?php echo esc_html( sprintf( /* translators: %s: date */ __( 'naposledy z Máp: %s', 'relife' ), wp_date( 'j. n. Y H:i', $last ) ) ); ?><?php endif; ?></small></h2>
				<p class="rlc-links">
					<?php if ( $pid ) : ?>
						<a href="<?php echo esc_url( (string) get_permalink( $pid ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Zobraziť stránku ↗', 'relife' ); ?></a>
						<a href="<?php echo esc_url( (string) get_edit_post_link( $pid, 'raw' ) ); ?>"><?php esc_html_e( 'Upraviť stránku', 'relife' ); ?></a>
					<?php endif; ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=relife_place&rl_city=' . rawurlencode( $c->slug ) ) ); ?>"><?php esc_html_e( 'Podniky tohto mesta', 'relife' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=relife_place' ) ); ?>"><?php esc_html_e( 'Pridať podnik ručne', 'relife' ); ?></a>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="relife_places_city">
					<input type="hidden" name="term_id" value="<?php echo (int) $c->term_id; ?>">
					<?php wp_nonce_field( 'relife_places_city' ); ?>
					<div class="rlc-row">
						<div><label><?php esc_html_e( 'Názov mesta', 'relife' ); ?></label><input type="text" name="city_name" value="<?php echo esc_attr( $c->name ); ?>" class="regular-text"></div>
						<div class="grow"><label><?php esc_html_e( 'Odkaz na zdieľaný zoznam z Google Máp', 'relife' ); ?></label><input type="url" name="maps_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://maps.app.goo.gl/…"></div>
					</div>
					<p><label><input type="checkbox" name="remove_missing" value="1"> <?php esc_html_e( 'Presunúť do koša aj podniky, ktoré som zo zoznamu v Mapách odstránil (len tie pridané aktualizáciou)', 'relife' ); ?></label></p>
					<p>
						<button type="submit" name="do_sync" value="1" class="button button-primary"><?php esc_html_e( 'Uložiť a aktualizovať z Máp', 'relife' ); ?></button>
						<button type="submit" class="button"><?php esc_html_e( 'Len uložiť', 'relife' ); ?></button>
					</p>
				</form>
				<details>
					<summary><?php esc_html_e( 'Vložiť podniky ručne (text alebo CSV z Google Takeout)', 'relife' ); ?></summary>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="relife_places_import">
						<input type="hidden" name="city" value="<?php echo esc_attr( $c->slug ); ?>">
						<input type="hidden" name="from" value="hub">
						<?php wp_nonce_field( 'relife_places_import' ); ?>
						<p class="description"><code>Názov | Kategória | Hodnotenie | Popis | Adresa | Odkaz do Máp | URL fotky</code> – <?php esc_html_e( 'od druhého poľa sú všetky nepovinné.', 'relife' ); ?></p>
						<textarea name="data" rows="6" class="large-text code" placeholder="Pizzeria Mamma | Pizza, Talianska | 4,6 | Chrumkavé cesto. | Obchodná 12"></textarea>
						<p><label><?php esc_html_e( 'Predvolená kategória, ak chýba:', 'relife' ); ?> <input type="text" name="default_cat" class="regular-text"></label> <button type="submit" class="button"><?php esc_html_e( 'Importovať do mesta', 'relife' ); ?> <?php echo esc_html( $c->name ); ?></button></p>
					</form>
				</details>
			</div>
		<?php endforeach; ?>

		<div class="rlc-card" style="border-style:dashed">
			<h2><?php esc_html_e( 'Pridať ďalšie mesto', 'relife' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Vytvorí sa nová stránka „Kam na jedlo – <mesto>“ (pridajte ju do menu vo Vzhľad → Menu) a samostatný zoznam podnikov.', 'relife' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="relife_places_city">
				<input type="hidden" name="term_id" value="0">
				<input type="hidden" name="do_sync" value="1">
				<?php wp_nonce_field( 'relife_places_city' ); ?>
				<div class="rlc-row">
					<div><label><?php esc_html_e( 'Názov mesta', 'relife' ); ?></label><input type="text" name="city_name" class="regular-text" placeholder="Žilina" required></div>
					<div class="grow"><label><?php esc_html_e( 'Odkaz na zdieľaný zoznam z Google Máp (nepovinné)', 'relife' ); ?></label><input type="url" name="maps_url" placeholder="https://maps.app.goo.gl/…"></div>
				</div>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Pridať mesto a načítať zoznam', 'relife' ); ?></button></p>
			</form>
		</div>
	</div>
	<?php
}

/**
 * Zoznam podnikov: filter podľa mesta a odkaz na obrazovku Mestá a zoznamy.
 */
function relife_places_list_filter( string $post_type ): void {
	if ( 'relife_place' !== $post_type ) {
		return;
	}
	$sel = isset( $_GET['rl_city'] ) ? sanitize_title( wp_unslash( $_GET['rl_city'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<select name="rl_city"><option value="">' . esc_html__( 'Všetky mestá', 'relife' ) . '</option>';
	foreach ( relife_places_cities() as $c ) {
		echo '<option value="' . esc_attr( $c->slug ) . '"' . selected( $sel, $c->slug, false ) . '>' . esc_html( $c->name ) . '</option>';
	}
	echo '</select> <a class="button" href="' . esc_url( relife_places_hub_url() ) . '">' . esc_html__( 'Mestá a zoznamy z Máp', 'relife' ) . '</a>';
}
add_action( 'restrict_manage_posts', 'relife_places_list_filter' );

function relife_places_list_query( WP_Query $q ): void {
	if ( ! is_admin() || ! $q->is_main_query() || 'relife_place' !== $q->get( 'post_type' ) || empty( $_GET['rl_city'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$q->set( 'tax_query', array( array( 'taxonomy' => 'relife_place_city', 'field' => 'slug', 'terms' => sanitize_title( wp_unslash( $_GET['rl_city'] ) ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.DB.SlowDBQuery
}
add_action( 'pre_get_posts', 'relife_places_list_query' );

/* ---------- Import (Nástroje → Import podnikov) ---------- */

function relife_places_import_menu(): void {
	add_management_page( __( 'Import podnikov', 'relife' ), __( 'Import podnikov (jedlo)', 'relife' ), 'edit_pages', 'relife-places-import', 'relife_places_import_page' );
}
add_action( 'admin_menu', 'relife_places_import_menu' );

function relife_places_import_page(): void {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import podnikov (Kam na jedlo)', 'relife' ); ?></h1>
		<?php relife_places_notices(); ?>
		<div class="notice notice-info inline" style="padding:12px 16px">
			<p><strong><?php esc_html_e( 'Zoznamy z Google Máp a mestá sa spravujú na jednom mieste:', 'relife' ); ?></strong> <a href="<?php echo esc_url( relife_places_hub_url() ); ?>"><?php esc_html_e( 'Podniky → Mestá a zoznamy', 'relife' ); ?></a></p>
		</div>
		<div class="notice notice-info inline" style="padding:12px 16px">
			<p><strong><?php esc_html_e( 'Zoznam „Bratislava kam na jedlo“ (Peter), 32 podnikov z Google Máp', 'relife' ); ?></strong><br>
			<?php esc_html_e( 'Pridá názvy, adresy, odkazy do Máp a navrhnuté kategórie (podľa názvu, skontrolujte ich). Hodnotenia, popisy a fotky doplníte pri jednotlivých podnikoch.', 'relife' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="relife_places_seed">
				<?php wp_nonce_field( 'relife_places_seed' ); ?>
				<?php submit_button( __( 'Načítať zoznam do webu', 'relife' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<p><?php esc_html_e( 'Alebo vložte vlastný zoznam podnikov. Podporované sú dva tvary:', 'relife' ); ?></p>
		<ol>
			<li><strong><?php esc_html_e( 'Riadky s oddeľovačom |', 'relife' ); ?></strong> – <code>Názov | Kategória | Hodnotenie | Popis | Adresa | Odkaz do Máp | URL fotky</code> <?php esc_html_e( '(od druhého poľa sú všetky nepovinné; viac kategórií oddeľte čiarkou)', 'relife' ); ?></li>
			<li><strong><?php esc_html_e( 'CSV z Google Takeout', 'relife' ); ?></strong> (<?php esc_html_e( 'Uložené miesta → súbor zoznamu s hlavičkou Title, Note, URL, Tags, Comment', 'relife' ); ?>).</li>
		</ol>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="relife_places_import">
			<?php wp_nonce_field( 'relife_places_import' ); ?>
			<p><label><?php esc_html_e( 'Mesto:', 'relife' ); ?> <select name="city"><?php foreach ( relife_places_cities() as $c ) : ?><option value="<?php echo esc_attr( $c->slug ); ?>"><?php echo esc_html( $c->name ); ?></option><?php endforeach; ?></select></label></p>
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
	$city    = isset( $_POST['city'] ) ? sanitize_title( wp_unslash( $_POST['city'] ) ) : '';
	$added   = 0;
	$skipped = 0;
	foreach ( relife_places_parse_import( $data ) as $r ) {
		if ( '' === trim( $r['name'] ) ) {
			continue;
		}
		relife_places_create( $r, $default, $city ) ? ++$added : ++$skipped;
	}
	if ( isset( $_POST['from'] ) && 'hub' === $_POST['from'] ) {
		wp_safe_redirect( relife_places_hub_url( array( 'imported' => $added, 'skipped' => $skipped ) ) );
	} else {
		wp_safe_redirect( add_query_arg( array( 'page' => 'relife-places-import', 'imported' => $added, 'skipped' => $skipped ), admin_url( 'tools.php' ) ) );
	}
	exit;
}
add_action( 'admin_post_relife_places_import', 'relife_places_import_handle' );
