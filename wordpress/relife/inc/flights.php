<?php
/**
 * Lacné letenky v téme RELIFE: načítanie zabudovaného bloku, stránka „Lacné letenky“ a odkazy.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Zabudovaný blok (shortcode [lacne_letenky], blok v editore, widget, Nastavenia → Lacné letenky).
// Ak je aktívny aj samostatný plugin (načíta sa pred témou), téma použije jeho kód a doplní len vzhľad RELIFE.
define( 'RELIFE_FLIGHTS_STANDALONE', defined( 'LACNE_LETENKY_VERSION' ) );
require_once RELIFE_DIR . '/inc/letenky/letenky.php';

/**
 * Pri samostatnom plugine: vzhľad RELIFE (farby sekcií a odletov) sa načíta po štýle pluginu.
 */
function relife_flights_skin(): void {
	if ( ! RELIFE_FLIGHTS_STANDALONE ) {
		return;
	}
	$post = get_post();
	$need = is_page_template( 'template-flights.php' )
		|| is_active_widget( false, false, 'lacne_letenky' )
		|| ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'lacne_letenky' ) );
	if ( $need ) {
		wp_enqueue_style( 'relife-letenky-skin', RELIFE_URI . '/inc/letenky/assets/letenky-skin.css', array( 'lacne-letenky' ), RELIFE_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_flights_skin', 30 );

/**
 * Upozornenie v administrácii: samostatný plugin nemá úzky widget, výber sekcií ani plný vzhľad RELIFE.
 */
function relife_flights_standalone_notice(): void {
	if ( ! RELIFE_FLIGHTS_STANDALONE || ! current_user_can( 'activate_plugins' ) || get_user_meta( get_current_user_id(), 'relife_flights_notice_off', true ) ) {
		return;
	}
	$dismiss = wp_nonce_url( add_query_arg( 'relife_flights_notice_off', '1' ), 'relife_flights_notice' );
	echo '<div class="notice notice-warning"><p><strong>RELIFE:</strong> ' . esc_html__( 'Je aktívny samostatný plugin „Lacné letenky“. Téma RELIFE má vlastnú vylepšenú kópiu (výber sekcií, úzky widget do bočného stĺpca, vzhľad RELIFE). Deaktivujte samostatný plugin v Pluginy – nastavenia aj ceny sa zachovajú.', 'relife' ) . ' <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Otvoriť Pluginy', 'relife' ) . '</a> · <a href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Skryť upozornenie', 'relife' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'relife_flights_standalone_notice' );

function relife_flights_notice_dismiss(): void {
	if ( isset( $_GET['relife_flights_notice_off'] ) && check_admin_referer( 'relife_flights_notice' ) ) {
		update_user_meta( get_current_user_id(), 'relife_flights_notice_off', 1 );
	}
}
add_action( 'admin_init', 'relife_flights_notice_dismiss' );

/**
 * Adresa stránky Lacné letenky alebo prázdny reťazec.
 */
function relife_flights_url(): string {
	$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'template-flights.php', 'number' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	return $pages ? (string) get_permalink( $pages[0] ) : '';
}

function relife_create_flights_page(): void {
	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'template-flights.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $existing ) {
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Lacné letenky',
			'post_name'    => 'lacne-letenky',
			'post_content' => "<!-- wp:shortcode -->\n[lacne_letenky]\n<!-- /wp:shortcode -->",
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( (int) $id, '_wp_page_template', 'template-flights.php' );
	}
}
add_action( 'after_switch_theme', 'relife_create_flights_page' );

function relife_maybe_create_flights_page(): void {
	if ( get_option( 'relife_flights_page_done' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	relife_create_flights_page();
	update_option( 'relife_flights_page_done', 1, false );
}
add_action( 'admin_init', 'relife_maybe_create_flights_page' );

/**
 * Štýl a skript bloku sa načítajú len tam, kde je blok/shortcode (to rieši samotný blok); na stránke Lacné letenky nič navyše.
 * Hlavný štýl témy sa vkladá inline, preto blok dostane vlastný súbor len keď je potrebný.
 */

/**
 * Ľavý stĺpec článku (desktop) ako oblasť pre widgety: Vzhľad → Widgety → „Ľavý stĺpec článku“ (napr. widget Lacné letenky).
 */
function relife_register_sidebars(): void {
	register_sidebar(
		array(
			'id'            => 'relife-article-left',
			'name'          => __( 'Ľavý stĺpec článku', 'relife' ),
			'description'   => __( 'Zobrazí sa na desktope pod odporúčanými článkami v ľavom stĺpci článku. Vhodný je widget „Lacné letenky“.', 'relife' ),
			'before_widget' => '<section id="%1$s" class="rl-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<div class="rl-kicker rl-widget__title">',
			'after_title'   => '</div>',
		)
	);
}
add_action( 'widgets_init', 'relife_register_sidebars' );

/**
 * Najnižšia cena zo zobrazených ponúk (len z uloženej kópie, bez sieťovej požiadavky; staršie než 36 hodín sa nezobrazí).
 */
function relife_flights_cheapest(): string {
	$data = get_transient( 'lacne_letenky_data' );
	if ( ! is_array( $data ) ) {
		$data = get_option( 'lacne_letenky_last_good' );
	}
	if ( ! is_array( $data ) || empty( $data['updatedAt'] ) ) {
		return '';
	}
	$ts = strtotime( (string) $data['updatedAt'] );
	if ( ! $ts || time() - $ts > 36 * HOUR_IN_SECONDS ) {
		return '';
	}
	$best = null;
	foreach ( array_merge( (array) ( $data['weekend'] ?? array() ), (array) ( $data['deals'] ?? array() ) ) as $d ) {
		if ( isset( $d['price'] ) && is_numeric( $d['price'] ) && ( null === $best || (float) $d['price'] < $best ) ) {
			$best = (float) $d['price'];
		}
	}
	return null === $best ? '' : sprintf( /* translators: %s price in euro */ __( 'od %s €', 'relife' ), number_format_i18n( $best, 0 ) );
}

/**
 * Štvorcová dlaždica s odkazom na stránku Lacné letenky (kategória Travel).
 */
function relife_flights_tile( string $color ): void {
	$url = relife_flights_url();
	if ( '' === $url ) {
		return;
	}
	$price = relife_flights_cheapest();
	?>
	<a class="rl-flight-tile" href="<?php echo esc_url( $url ); ?>" style="background:<?php echo esc_attr( $color ); ?>">
		<svg class="rl-flight-tile__plane" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z"/></svg>
		<span class="rl-flight-tile__kicker"><?php esc_html_e( 'TRAVEL · LETENKY', 'relife' ); ?></span>
		<span class="rl-flight-tile__body">
			<span class="rl-flight-tile__title"><?php esc_html_e( 'Lacné letenky', 'relife' ); ?></span>
			<span class="rl-flight-tile__line"><?php esc_html_e( 'Najlacnejšie lety z Viedne, Bratislavy a Budapešti kamkoľvek.', 'relife' ); ?></span>
		</span>
		<span class="rl-flight-tile__foot">
			<?php if ( '' !== $price ) : ?><b class="rl-flight-tile__price"><?php echo esc_html( $price ); ?></b><?php else : ?><span></span><?php endif; ?>
			<span class="rl-flight-tile__go" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
		</span>
	</a>
	<?php
}
