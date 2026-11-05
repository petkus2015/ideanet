<?php
/**
 * Riešenie chýb 404 (Search Console): presmerovanie starých adries, sitemap a protokol 404.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cesta požiadavky bez domény, lomiek a parametrov, malými písmenami.
 */
function relife_request_path(): string {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '/' !== $base && 0 === strpos( $path, $base ) ) {
		$path = substr( $path, strlen( $base ) - 1 );
	}
	return strtolower( trim( rawurldecode( $path ), '/' ) );
}

/**
 * Ručné presmerovania z Customizeru: riadok „/stara-adresa/ => /nova-adresa/“.
 *
 * @return array<string, string>
 */
function relife_manual_redirects(): array {
	$map = array();
	foreach ( preg_split( '/\R/', (string) get_theme_mod( 'relife_redirects', '' ) ) ?: array() as $line ) {
		if ( false === strpos( $line, '=>' ) ) {
			continue;
		}
		list( $from, $to ) = array_map( 'trim', explode( '=>', $line, 2 ) );
		$from = strtolower( trim( (string) wp_parse_url( $from, PHP_URL_PATH ), '/' ) );
		if ( '' !== $from && '' !== $to ) {
			$map[ $from ] = $to;
		}
	}
	return $map;
}

function relife_handle_404_redirects(): void {
	if ( ! is_404() ) {
		return;
	}
	$path = relife_request_path();
	if ( '' === $path ) {
		return;
	}

	// 1. Ručné presmerovania.
	$manual = relife_manual_redirects();
	if ( isset( $manual[ $path ] ) ) {
		$to = $manual[ $path ];
		wp_redirect( 0 === strpos( $to, 'http' ) ? esc_url_raw( $to ) : home_url( '/' . ltrim( $to, '/' ) ), 301 ); // phpcs:ignore WordPress.Security.SafeRedirect
		exit;
	}

	// 2. sitemap.xml a podobné adresy → sitemap WordPressu.
	if ( in_array( $path, array( 'sitemap.xml', 'sitemap_index.xml', 'sitemap-index.xml', 'sitemap' ), true ) && function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled() ) {
		wp_safe_redirect( home_url( '/wp-sitemap.xml' ), 301 );
		exit;
	}

	// 3. Stará štruktúra adries: nájdi článok alebo stránku podľa posledného úseku (slug).
	$segments = array_values( array_filter( explode( '/', $path ), static fn( string $s ): bool => '' !== $s ) );
	while ( $segments && in_array( end( $segments ), array( 'amp', 'feed', 'embed', 'comment-page-1' ), true ) ) {
		array_pop( $segments );
	}
	if ( $segments && preg_match( '/^(page|comment-page-)\d*$/', (string) ( $segments[ count( $segments ) - 2 ] ?? '' ) ) ) {
		$segments = array_slice( $segments, 0, -2 );
	}
	$slug = $segments ? sanitize_title( (string) preg_replace( '/\.(html?|php|aspx?)$/', '', (string) end( $segments ) ) ) : '';
	if ( '' !== $slug && ! ctype_digit( $slug ) ) {
		$found = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( $found ) {
			$url = get_permalink( (int) $found[0] );
			if ( $url && trailingslashit( $url ) !== trailingslashit( home_url( '/' . $path ) ) ) {
				wp_safe_redirect( $url, 301 );
				exit;
			}
		}
		// Stará adresa kategórie alebo značky.
		if ( count( $segments ) >= 2 && in_array( $segments[0], array( 'category', 'kategoria', 'tag', 'tema' ), true ) ) {
			$tax  = in_array( $segments[0], array( 'tag', 'tema' ), true ) ? 'post_tag' : 'category';
			$term = get_term_by( 'slug', $slug, $tax );
			if ( $term instanceof WP_Term ) {
				$link = get_term_link( $term );
				if ( is_string( $link ) ) {
					wp_safe_redirect( $link, 301 );
					exit;
				}
			}
		}
	}

	relife_log_404( $path );
}
add_action( 'template_redirect', 'relife_handle_404_redirects', 1 );

/**
 * Protokol adries s chybou 404 (max. 200), aby bolo vidieť, čo treba presmerovať.
 */
function relife_log_404( string $path ): void {
	if ( preg_match( '/\.(js|css|map|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|txt|xml|json)$/', $path ) && 'ads.txt' !== $path ) {
		return;
	}
	if ( false !== strpos( $path, 'wp-' ) || false !== strpos( $path, '.php' ) ) {
		return;
	}
	$log = get_option( 'relife_404_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	$ref = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( isset( $log[ $path ] ) ) {
		++$log[ $path ]['n'];
		$log[ $path ]['t'] = time();
		if ( '' !== $ref ) {
			$log[ $path ]['r'] = $ref;
		}
	} elseif ( count( $log ) < 200 ) {
		$log[ $path ] = array( 'n' => 1, 't' => time(), 'r' => $ref );
	} else {
		return;
	}
	update_option( 'relife_404_log', $log, false );
}

function relife_404_admin_menu(): void {
	add_management_page( __( '404 adresy', 'relife' ), __( 'RELIFE 404 adresy', 'relife' ), 'manage_options', 'relife-404', 'relife_404_admin_page' );
}
add_action( 'admin_menu', 'relife_404_admin_menu' );

function relife_404_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['relife_404_clear'] ) && check_admin_referer( 'relife_404_clear' ) ) {
		delete_option( 'relife_404_log' );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Protokol vymazaný.', 'relife' ) . '</p></div>';
	}
	$log = get_option( 'relife_404_log', array() );
	$log = is_array( $log ) ? $log : array();
	uasort( $log, static fn( array $a, array $b ): int => $b['n'] <=> $a['n'] );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'RELIFE 404 adresy', 'relife' ); ?></h1>
		<p><?php esc_html_e( 'Adresy, na ktoré návštevníci alebo roboty prišli a nič tam nie je. Presmerovanie pridáte vo Vzhľad → Prispôsobiť → RELIFE → Presmerovania (riadok: /stara-adresa/ => /nova-adresa/).', 'relife' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Adresa', 'relife' ); ?></th><th><?php esc_html_e( 'Počet', 'relife' ); ?></th><th><?php esc_html_e( 'Naposledy', 'relife' ); ?></th><th><?php esc_html_e( 'Odkiaľ', 'relife' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $log ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'Zatiaľ žiadne.', 'relife' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $log as $p => $row ) : ?>
				<tr>
					<td>/<?php echo esc_html( (string) $p ); ?>/</td>
					<td><?php echo (int) $row['n']; ?></td>
					<td><?php echo esc_html( wp_date( 'j. n. Y H:i', (int) $row['t'] ) ); ?></td>
					<td><?php echo esc_html( (string) ( $row['r'] ?? '' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" style="margin-top:16px">
			<?php wp_nonce_field( 'relife_404_clear' ); ?>
			<button class="button" name="relife_404_clear" value="1"><?php esc_html_e( 'Vymazať protokol', 'relife' ); ?></button>
		</form>
	</div>
	<?php
}

function relife_redirects_customize( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_setting( 'relife_redirects', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp_customize->add_control( 'relife_redirects', array(
		'label'       => __( 'Presmerovania (301)', 'relife' ),
		'description' => __( 'Jedno na riadok: /stara-adresa/ => /nova-adresa/ (alebo celá adresa). Funguje len pre adresy, ktoré neexistujú. Staré adresy článkov sa dohľadajú podľa názvu (slug) automaticky.', 'relife' ),
		'section'     => 'relife_options',
		'type'        => 'textarea',
	) );
}
add_action( 'customize_register', 'relife_redirects_customize' );
