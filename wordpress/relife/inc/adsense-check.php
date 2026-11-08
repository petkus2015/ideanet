<?php
/**
 * Administrácia: Nástroje → Kontrola AdSense (GDPR). Zistí, prečo sa nezobrazuje GDPR lišta z Google AdSense.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Žiadosť o test z administrácie: stránka sa pre prihláseného správcu zobrazí ako pre návštevníka.
 */
function relife_is_cmp_test(): bool {
	static $is = null;
	if ( null !== $is ) {
		return $is;
	}
	$is = false;
	if ( isset( $_GET['rl_cmp_test'] ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$is = (bool) wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['rl_cmp_test'] ) ), 'relife_cmp_test' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}
	return $is;
}

function relife_cmp_test_prepare(): void {
	if ( relife_is_cmp_test() ) {
		show_admin_bar( false );
		nocache_headers();
		define( 'DONOTCACHEPAGE', true );
	}
}
add_action( 'template_redirect', 'relife_cmp_test_prepare', 0 );
add_filter( 'show_admin_bar', static fn( $show ) => relife_is_cmp_test() ? false : $show, 100 );

function relife_cmp_menu(): void {
	add_management_page( __( 'Kontrola AdSense (GDPR)', 'relife' ), __( 'Kontrola AdSense (GDPR)', 'relife' ), 'manage_options', 'relife-cmp', 'relife_cmp_page' );
}
add_action( 'admin_menu', 'relife_cmp_menu' );

function relife_cmp_enqueue( string $hook ): void {
	if ( 'tools_page_relife-cmp' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'relife-cmp-admin', RELIFE_URI . '/assets/js/cmp-admin.js', array(), RELIFE_VERSION, array( 'in_footer' => true ) );
	$pub = relife_adsense_client();
	wp_localize_script(
		'relife-cmp-admin',
		'relifeCmp',
		array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'relife_cmp_ajax' ),
			'testUrl' => add_query_arg( 'rl_cmp_test', wp_create_nonce( 'relife_cmp_test' ), home_url( '/' ) ),
			'script'  => RELIFE_URI . '/assets/js/cmp-check.js?ver=' . RELIFE_VERSION,
			'pub'     => '' !== $pub ? preg_replace( '/^ca-pub-/', '', $pub ) : '',
		)
	);
}
add_action( 'admin_enqueue_scripts', 'relife_cmp_enqueue' );

/**
 * Kontroly zo strany servera: surové HTML (ako ho vidí návštevník bez JavaScriptu), ads.txt, odpoveď Google, nastavenia témy, pluginy.
 *
 * @return array<int, array<string, string>>
 */
function relife_cmp_server_checks( string $pub ): array {
	$res = array();
	$add = static function ( string $id, string $status, string $title, string $detail = '', string $fix = '' ) use ( &$res ): void {
		$res[] = compact( 'id', 'status', 'title', 'detail', 'fix' );
	};

	// Nastavenia témy.
	$client = relife_adsense_client();
	$head   = relife_ads_code( 'head' );
	$unit   = relife_ads_code( 'unit' );
	if ( '' === $client && '' === $head && '' === $unit ) {
		$add( 'srv_theme', 'fail', 'V téme nie je zadané AdSense ID ani kód', 'Vzhľad → Prispôsobiť → RELIFE – Reklamy je prázdne.', 'Zadajte ID vydavateľa ca-pub-' . $pub . ' alebo vložte celý kód AdSense do hlavičky a uložte.' );
	} else {
		$add( 'srv_theme', 'ok', 'Nastavenia témy: AdSense je zadaný', ( '' !== $client ? 'ID: ' . $client . '. ' : '' ) . ( '' !== $head ? 'Kód v hlavičke: áno. ' : '' ) . ( '' !== $unit ? 'Kód reklamy: áno.' : '' ) );
	}
	if ( get_theme_mod( 'relife_ads_skip_admins', false ) ) {
		$add( 'srv_admins', 'info', 'Zapnuté: neukazovať reklamy prihláseným redaktorom', 'Návštevníci to neovplyvní; test z administrácie to obchádza.' );
	}

	// Surové HTML ako návštevník.
	$home = wp_remote_get( home_url( '/' ), array( 'timeout' => 20, 'redirection' => 3, 'headers' => array( 'Cache-Control' => 'no-cache' ), 'user-agent' => 'Mozilla/5.0 (RELIFE AdSense check)' ) );
	if ( is_wp_error( $home ) ) {
		$add( 'srv_raw', 'warn', 'Server sa nevie sám pripojiť na úvodnú stránku', $home->get_error_message(), 'Nie je to chyba lišty; kontrola HTML zo servera sa preskočila.' );
	} else {
		$body = (string) wp_remote_retrieve_body( $home );
		$code = (int) wp_remote_retrieve_response_code( $home );
		$h    = wp_remote_retrieve_headers( $home );
		if ( false === stripos( $body, '</body>' ) || false === strpos( $body, 'rl-' ) ) {
			$add( 'srv_raw', 'warn', 'Odpoveď servera na úvodnú stránku nevyzerá ako stránka webu', 'HTTP ' . $code . ', ' . strlen( $body ) . ' B. Server sa pravdepodobne nevie sám dopytovať (blokovaný loopback, prihlasovacia stena, ochrana proti botom).', 'Nie je to chyba lišty; HTML zo servera sa nedalo overiť, rozhodujú kontroly z prehliadača nižšie.' );
		} elseif ( preg_match( '/adsbygoogle\.js/i', $body ) ) {
			$add( 'srv_raw', 'ok', 'Úvodná stránka (pre návštevníka) obsahuje skript AdSense', 'HTTP ' . $code );
		} else {
			$add( 'srv_raw', 'fail', 'Úvodná stránka pre návštevníka NEOBSAHUJE skript AdSense', 'HTTP ' . $code . '. Skript chýba v HTML, ktoré posiela server.',
				'Ak je v téme AdSense zadaný, HTML je pravdepodobne zo starej cache (Starcache/plugin) alebo ho prepisuje optimalizačný plugin. Vyprázdnite všetky cache a skúste znova.' );
		}
		if ( false !== stripos( $body, '</body>' ) && preg_match_all( '/ca-pub-\d+/', $body, $m ) ) {
			$ids = array_unique( $m[0] );
			if ( ! in_array( 'ca-pub-' . $pub, $ids, true ) ) {
				$add( 'srv_id', 'fail', 'V HTML je iné ID vydavateľa než ca-pub-' . $pub, implode( ', ', $ids ), 'Opravte ID v Customizeri.' );
			}
		}
		$csp = (string) ( $h['content-security-policy'] ?? '' );
		if ( '' !== $csp && ! preg_match( '/fundingchoicesmessages\.google\.com|script-src[^;]*\*/', $csp ) ) {
			$add( 'srv_csp', 'fail', 'Hlavička Content-Security-Policy blokuje Google', 'Nepovoľuje fundingchoicesmessages.google.com.', 'Pridajte pagead2.googlesyndication.com, fundingchoicesmessages.google.com, *.google.com, *.gstatic.com do script-src, frame-src a connect-src.' );
		}
		$cache = (string) ( $h['cache-control'] ?? '' );
		if ( '' !== $cache ) {
			$add( 'srv_cache', 'info', 'Cache-Control úvodnej stránky: ' . $cache );
		}
	}

	// ads.txt.
	$ads = wp_remote_get( home_url( '/ads.txt' ), array( 'timeout' => 15 ) );
	if ( is_wp_error( $ads ) || 200 !== (int) wp_remote_retrieve_response_code( $ads ) ) {
		$add( 'srv_ads', 'warn', 'ads.txt sa nenačítal', is_wp_error( $ads ) ? $ads->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $ads ), 'Pridajte „google.com, pub-' . $pub . ', DIRECT, f08c47fec0942fa0“ (téma ho vytvorí sama pri zadanom ID v Customizeri).' );
	} elseif ( false === strpos( (string) wp_remote_retrieve_body( $ads ), 'pub-' . $pub ) ) {
		$add( 'srv_ads', 'warn', 'ads.txt neobsahuje pub-' . $pub, '', 'Doplňte riadok „google.com, pub-' . $pub . ', DIRECT, f08c47fec0942fa0“.' );
	} else {
		$add( 'srv_ads', 'ok', 'ads.txt obsahuje vaše ID' );
	}

	// Odpoveď Google Funding Choices (z IP servera).
	$fc = wp_remote_get( 'https://fundingchoicesmessages.google.com/i/pub-' . $pub . '?ers=1', array( 'timeout' => 15 ) );
	if ( is_wp_error( $fc ) ) {
		$add( 'srv_fc', 'info', 'Odpoveď Google Funding Choices sa zo servera nepodarilo načítať', $fc->get_error_message() );
	} else {
		$len  = strlen( (string) wp_remote_retrieve_body( $fc ) );
		$code = (int) wp_remote_retrieve_response_code( $fc );
		if ( 200 === $code && $len > 2000 ) {
			$add( 'srv_fc', 'ok', 'Google vracia konfiguráciu správy pre pub-' . $pub, 'HTTP 200, ' . $len . ' B (správa je pravdepodobne publikovaná).' );
		} else {
			$add( 'srv_fc', 'fail', 'Google pre pub-' . $pub . ' nevracia žiadnu správu', 'HTTP ' . $code . ', ' . $len . ' B.',
				'V AdSense: Súkromie a správy → Európske predpisy → vytvorte správu a stlačte „Publikovať“. Stránka relife.sk musí byť v zozname Stránky a Pripravená. Po publikovaní počkajte 1–2 hodiny.' );
		}
	}

	// Pluginy, ktoré sa môžu biť s lištou.
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$hits   = array();
	$needle = 'cookiebot|complianz|cookie-law-info|cookieyes|borlabs|onetrust|iubenda|gdpr|cookie-notice|cookie-banner|wp-rocket|litespeed|autoptimize|w3-total-cache|wp-super-cache|sg-cachepress|sg-optimizer|perfmatters|flying-scripts|async-javascript|wp-fastest-cache|nitropack|breeze|hummingbird|cache-enabler|starcache|td-cloud|tagdiv|kk-star';
	foreach ( get_option( 'active_plugins', array() ) as $p ) {
		if ( preg_match( '/' . $needle . '/i', (string) $p ) ) {
			$hits[] = (string) $p;
		}
	}
	if ( $hits ) {
		$add( 'srv_plugins', 'warn', 'Aktívne pluginy, ktoré môžu zasahovať do lišty alebo skriptov', implode( ', ', $hits ),
			'Cookie pluginy (iná lišta) a pluginy na cache/optimalizáciu JS (odkladanie, minifikácia) môžu AdSense skript zablokovať alebo oddialiť. Vyprázdnite cache a skúste test s ich dočasným vypnutím.' );
	} else {
		$add( 'srv_plugins', 'ok', 'Žiadny známy cookie/cache plugin v aktívnych pluginoch' );
	}
	return $res;
}

function relife_cmp_ajax(): void {
	check_ajax_referer( 'relife_cmp_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( null, 403 );
	}
	$pub = isset( $_POST['pub'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_POST['pub'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( '' === $pub ) {
		$pub = '2577354057353027';
	}
	wp_send_json_success( relife_cmp_server_checks( $pub ) );
}
add_action( 'wp_ajax_relife_cmp', 'relife_cmp_ajax' );

function relife_cmp_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Kontrola AdSense (GDPR lišta)', 'relife' ); ?></h1>
		<p><?php esc_html_e( 'Test otvorí váš web ako návštevníka, skontroluje, či sa načíta skript AdSense a Google správa o súhlase (GDPR), a navrhne opravu. Trvá asi pol minúty. Spustite ho z počítača v EÚ bez VPN a bez blokovača reklám.', 'relife' ); ?></p>
		<p>
			<label for="rl-cmp-pub"><strong>ID vydavateľa</strong></label>
			<input type="text" id="rl-cmp-pub" class="regular-text" value="" placeholder="ca-pub-2577354057353027">
		</p>
		<p><label><input type="checkbox" id="rl-cmp-clear" checked> <?php esc_html_e( 'Pred testom zmazať uložený súhlas v tomto prehliadači (aby sa lišta mohla znova ukázať)', 'relife' ); ?></label></p>
		<p><button type="button" class="button button-primary button-hero" id="rl-cmp-run"><?php esc_html_e( 'Spustiť test', 'relife' ); ?></button> <span id="rl-cmp-status" style="margin-left:12px"></span></p>

		<div id="rl-cmp-verdict" hidden style="margin:20px 0;padding:16px 20px;border-left:5px solid #2271b1;background:#fff;max-width:900px"></div>
		<table class="widefat striped" id="rl-cmp-table" hidden style="max-width:1000px">
			<thead><tr><th style="width:36px"></th><th><?php esc_html_e( 'Kontrola', 'relife' ); ?></th><th><?php esc_html_e( 'Podrobnosti a oprava', 'relife' ); ?></th></tr></thead>
			<tbody></tbody>
		</table>
		<p><button type="button" class="button" id="rl-cmp-copy" hidden><?php esc_html_e( 'Kopírovať výsledok', 'relife' ); ?></button></p>
		<iframe id="rl-cmp-frame" title="test" style="position:absolute;width:1100px;height:800px;left:-5000px;top:0;visibility:hidden" tabindex="-1" aria-hidden="true"></iframe>
	</div>
	<?php
}
