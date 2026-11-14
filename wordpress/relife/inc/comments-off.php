<?php
/**
 * Komentáre sú v téme úplne vypnuté (články, stránky, médiá, vlastné typy obsahu).
 * Vrátiť ich zapne filter: add_filter( 'relife_disable_comments', '__return_false' );
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function relife_comments_disabled(): bool {
	return (bool) apply_filters( 'relife_disable_comments', true );
}

/** Podpora komentárov a spätných odkazov sa odoberie všetkým typom obsahu. */
function relife_comments_remove_support(): void {
	if ( ! relife_comments_disabled() ) {
		return;
	}
	foreach ( get_post_types() as $type ) {
		if ( post_type_supports( $type, 'comments' ) ) {
			remove_post_type_support( $type, 'comments' );
		}
		if ( post_type_supports( $type, 'trackbacks' ) ) {
			remove_post_type_support( $type, 'trackbacks' );
		}
	}
}
add_action( 'init', 'relife_comments_remove_support', 100 );

function relife_comments_closed( bool $open ): bool {
	return relife_comments_disabled() ? false : $open;
}
add_filter( 'comments_open', 'relife_comments_closed', 20 );
add_filter( 'pings_open', 'relife_comments_closed', 20 );

/** Existujúce komentáre sa na webe nezobrazujú. */
function relife_comments_hide_existing( array $comments ): array {
	return relife_comments_disabled() ? array() : $comments;
}
add_filter( 'comments_array', 'relife_comments_hide_existing', 20 );

function relife_comments_count( $count ) {
	return relife_comments_disabled() ? 0 : $count;
}
add_filter( 'get_comments_number', 'relife_comments_count', 20 );

/** Administrácia: bez menu Komentáre, bez odkazu v admin lište, bez prístupu na stránku komentárov a bez widgetu na nástenke. */
function relife_comments_admin(): void {
	if ( ! relife_comments_disabled() ) {
		return;
	}
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
}
add_action( 'admin_menu', 'relife_comments_admin', 999 );

function relife_comments_admin_redirect(): void {
	global $pagenow;
	if ( relife_comments_disabled() && in_array( $pagenow, array( 'edit-comments.php', 'options-discussion.php' ), true ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'relife_comments_admin_redirect' );

function relife_comments_dashboard(): void {
	if ( relife_comments_disabled() ) {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}
}
add_action( 'wp_dashboard_setup', 'relife_comments_dashboard' );

function relife_comments_admin_bar( WP_Admin_Bar $bar ): void {
	if ( relife_comments_disabled() ) {
		$bar->remove_node( 'comments' );
	}
}
add_action( 'admin_bar_menu', 'relife_comments_admin_bar', 999 );

/** REST: bez koncového bodu komentárov; XML-RPC bez pingbackov; bez hlavičky X-Pingback a bez kanálov komentárov. */
function relife_comments_rest( array $endpoints ): array {
	if ( relife_comments_disabled() ) {
		unset( $endpoints['/wp/v2/comments'], $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'relife_comments_rest' );

function relife_comments_xmlrpc( array $methods ): array {
	if ( relife_comments_disabled() ) {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
	}
	return $methods;
}
add_filter( 'xmlrpc_methods', 'relife_comments_xmlrpc' );

function relife_comments_headers( array $headers ): array {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'relife_comments_headers' );

function relife_comments_feed_links(): void {
	if ( relife_comments_disabled() ) {
		remove_action( 'wp_head', 'feed_links', 2 );
		add_action(
			'wp_head',
			static function (): void {
				echo '<link rel="alternate" type="application/rss+xml" title="' . esc_attr( get_bloginfo( 'name' ) ) . '" href="' . esc_url( get_feed_link() ) . '">' . "\n";
			},
			2
		);
	}
}
add_action( 'init', 'relife_comments_feed_links' );

/** Kanál komentárov presmeruje na hlavný kanál. */
function relife_comments_feed_redirect(): void {
	if ( relife_comments_disabled() ) {
		wp_safe_redirect( get_feed_link(), 301 );
		exit;
	}
}
add_action( 'do_feed_rss2_comments', 'relife_comments_feed_redirect', 1 );
add_action( 'do_feed_atom_comments', 'relife_comments_feed_redirect', 1 );
