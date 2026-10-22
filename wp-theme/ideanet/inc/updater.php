<?php
/**
 * Aktualizácie témy priamo z WordPressu.
 *
 * Téma nie je v katalógu na wordpress.org, takže ju WordPress sám
 * nekontroluje. Hlavička „Update URI" v style.css mu povie, že sa má
 * pýtať nás — od WordPressu 6.1 na to existuje oficiálny háčik
 * update_themes_{doména}. Nové vydanie sa potom objaví vo Vzhľad → Témy
 * a nainštaluje sa jedným kliknutím, rovnako ako hociktorá iná téma.
 *
 * Obsah ani nastavenia sa pri aktualizácii nestratia — sú v databáze,
 * nie v súboroch témy.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Repozitár, z ktorého sa berú vydania.
 *
 * Dá sa prepísať konštantou IDEANET_UPDATE_REPO vo wp-config.php.
 *
 * @return string Napríklad petkus2015/ideanet.
 */
function ideanet_update_repo() {
	$repo = defined( 'IDEANET_UPDATE_REPO' ) ? IDEANET_UPDATE_REPO : 'petkus2015/ideanet';

	return (string) apply_filters( 'ideanet_update_repo', $repo );
}

/**
 * Adresa, ktorá je v hlavičke témy. Podľa nej sa pozná, že vydanie
 * patrí práve tejto téme.
 *
 * @return string
 */
function ideanet_update_uri() {
	return 'https://github.com/' . ideanet_update_repo();
}

/**
 * Názov súboru s témou, ktorý je priložený k vydaniu.
 *
 * @return string
 */
function ideanet_update_asset() {
	return (string) apply_filters( 'ideanet_update_asset', 'ideanet-theme.zip' );
}

/**
 * Údaje o poslednom vydaní. Výsledok sa odkladá, aby sa pri každom
 * načítaní administrácie nechodilo na sieť.
 *
 * @param bool $force Vynútiť nové zistenie.
 * @return array|false Pole s kľúčmi version, package, url, notes; inak false.
 */
function ideanet_latest_release( $force = false ) {
	$key = 'ideanet_update';

	if ( ! $force ) {
		$cached = get_site_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		if ( 'ziadne' === $cached ) {
			return false;
		}
	}

	$url = apply_filters(
		'ideanet_update_api_url',
		'https://api.github.com/repos/' . ideanet_update_repo() . '/releases/latest'
	);

	$headers = array(
		'Accept'     => 'application/vnd.github+json',
		'User-Agent' => 'IDEANET-theme/' . wp_get_theme( 'ideanet' )->get( 'Version' ),
	);

	/* Súkromný repozitár potrebuje prístupový token — verejný nie. */
	if ( defined( 'IDEANET_UPDATE_TOKEN' ) && IDEANET_UPDATE_TOKEN ) {
		$headers['Authorization'] = 'Bearer ' . IDEANET_UPDATE_TOKEN;
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 10,
			'headers' => $headers,
		)
	);

	$code = wp_remote_retrieve_response_code( $response );
	$body = is_wp_error( $response ) ? '' : wp_remote_retrieve_body( $response );
	$data = $body ? json_decode( $body, true ) : null;

	if ( is_wp_error( $response ) || 200 !== (int) $code || ! is_array( $data ) ) {
		/* Keď sa to nepodarí, skúsime to znova o hodinu — nie pri každom
		   načítaní stránky. */
		set_site_transient( $key, 'ziadne', HOUR_IN_SECONDS );
		return false;
	}

	$version = ideanet_clean_version( isset( $data['tag_name'] ) ? $data['tag_name'] : '' );
	if ( ! $version ) {
		set_site_transient( $key, 'ziadne', HOUR_IN_SECONDS );
		return false;
	}

	/* Balík musí byť hotová téma priložená k vydaniu. Zdrojový archív,
	   ktorý GitHub vyrába sám, obsahuje celý repozitár aj so statickou
	   verziou webu — ako téma by sa nedal nainštalovať. */
	$package = '';
	foreach ( (array) ( isset( $data['assets'] ) ? $data['assets'] : array() ) as $asset ) {
		if ( isset( $asset['name'] ) && ideanet_update_asset() === $asset['name'] ) {
			$package = isset( $asset['browser_download_url'] ) ? $asset['browser_download_url'] : '';
			break;
		}
	}

	if ( ! $package ) {
		set_site_transient( $key, 'ziadne', HOUR_IN_SECONDS );
		return false;
	}

	$release = array(
		'version' => $version,
		'package' => $package,
		'url'     => isset( $data['html_url'] ) ? $data['html_url'] : ideanet_update_uri(),
		'notes'   => isset( $data['body'] ) ? (string) $data['body'] : '',
	);

	set_site_transient( $key, $release, 12 * HOUR_IN_SECONDS );

	return $release;
}

/**
 * Zo značky vydania spraví číslo verzie: theme-v1.2.0 → 1.2.0.
 *
 * @param string $tag Značka vydania.
 * @return string Prázdny reťazec, keď to nie je číslo verzie.
 */
function ideanet_clean_version( $tag ) {
	$tag = preg_replace( '/^(theme-)?v/i', '', trim( (string) $tag ) );

	return preg_match( '/^\d+(\.\d+)*$/', $tag ) ? $tag : '';
}

/**
 * Odpoveď pre WordPress, keď sa pýta na aktualizácie tém z github.com.
 *
 * Na tej doméne môžu mať vydania aj iné témy, preto sa najprv overí,
 * že ide naozaj o túto.
 *
 * @param array|false $update     Doterajšia odpoveď.
 * @param array       $theme_data Hlavičky témy.
 * @param string      $stylesheet Priečinok témy.
 * @return array|false
 */
function ideanet_check_update( $update, $theme_data, $stylesheet ) {
	if ( empty( $theme_data['UpdateURI'] ) ) {
		return $update;
	}
	if ( untrailingslashit( $theme_data['UpdateURI'] ) !== untrailingslashit( ideanet_update_uri() ) ) {
		return $update;
	}

	$force   = ! empty( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- len obnovenie zistenia, nič nemení.
	$release = ideanet_latest_release( $force );

	if ( ! $release ) {
		return $update;
	}

	$current = isset( $theme_data['Version'] ) ? $theme_data['Version'] : '0';
	if ( ! version_compare( $release['version'], $current, '>' ) ) {
		return $update;
	}

	return array(
		'id'           => $theme_data['UpdateURI'],
		'theme'        => $stylesheet,
		'version'      => $release['version'],
		'url'          => $release['url'],
		'package'      => $release['package'],
		'requires'     => isset( $theme_data['RequiresWP'] ) ? $theme_data['RequiresWP'] : '',
		'requires_php' => isset( $theme_data['RequiresPHP'] ) ? $theme_data['RequiresPHP'] : '',
	);
}
add_filter( 'update_themes_github.com', 'ideanet_check_update', 10, 3 );

/**
 * Po nainštalovaní zabudneme, čo sme vedeli o vydaniach — inak by
 * WordPress ešte pol dňa ukazoval aktualizáciu, ktorá je už hotová.
 *
 * @param WP_Upgrader $upgrader Inštalátor.
 * @param array       $data     Čo sa inštalovalo.
 */
function ideanet_after_upgrade( $upgrader, $data ) {
	if ( ! isset( $data['type'] ) || 'theme' !== $data['type'] ) {
		return;
	}

	$temy = isset( $data['themes'] ) ? (array) $data['themes'] : array();
	if ( $temy && ! in_array( get_stylesheet(), $temy, true ) && ! in_array( get_template(), $temy, true ) ) {
		return;
	}

	delete_site_transient( 'ideanet_update' );
	delete_site_transient( 'update_themes' );
}
add_action( 'upgrader_process_complete', 'ideanet_after_upgrade', 10, 2 );

/**
 * Archív musí skončiť v priečinku „ideanet", nie v priečinku
 * pomenovanom podľa vydania — inak by WordPress nainštaloval druhú tému
 * vedľa a nastavenia by zostali pri tej starej.
 *
 * @param string      $source   Priečinok s rozbaleným archívom.
 * @param string      $remote   Pôvodný priečinok.
 * @param WP_Upgrader $upgrader Inštalátor.
 * @param array       $args     Doplnkové údaje.
 * @return string|WP_Error
 */
function ideanet_fix_folder( $source, $remote, $upgrader, $args = array() ) {
	global $wp_filesystem;

	if ( empty( $args['theme'] ) || 'ideanet' !== $args['theme'] ) {
		return $source;
	}

	$spravny = trailingslashit( dirname( untrailingslashit( $source ) ) ) . 'ideanet/';
	if ( untrailingslashit( $source ) === untrailingslashit( $spravny ) ) {
		return $source;
	}

	if ( $wp_filesystem && $wp_filesystem->move( $source, $spravny, true ) ) {
		return $spravny;
	}

	return $source;
}
add_filter( 'upgrader_source_selection', 'ideanet_fix_folder', 10, 4 );

/**
 * Po aktualizácii témy môže byť potrebné niečo dorovnať. Zatiaľ si len
 * poznačíme verziu — je to miesto, kam prídu prípadné budúce úpravy
 * uložených údajov.
 */
function ideanet_maybe_upgrade() {
	$teraz = wp_get_theme( get_template() )->get( 'Version' );
	$predtym = get_option( 'ideanet_version' );

	if ( $predtym === $teraz ) {
		return;
	}

	update_option( 'ideanet_version', $teraz );

	/**
	 * Spustí sa raz po každej zmene verzie témy.
	 *
	 * @param string       $teraz   Nová verzia.
	 * @param string|false $predtym Predchádzajúca verzia, alebo false pri prvom spustení.
	 */
	do_action( 'ideanet_updated', $teraz, $predtym );
}
add_action( 'init', 'ideanet_maybe_upgrade', 100 );

/**
 * Odkaz „Zistiť aktualizácie" v rozcestníku.
 *
 * @return string
 */
function ideanet_check_now_link() {
	return wp_nonce_url(
		add_query_arg( 'ideanet-check', '1', admin_url( 'admin.php?page=ideanet' ) ),
		'ideanet-check'
	);
}

/**
 * Obslúži ten odkaz.
 */
function ideanet_handle_check() {
	if ( empty( $_GET['ideanet-check'] ) ) {
		return;
	}
	if ( ! current_user_can( 'update_themes' ) ) {
		return;
	}
	check_admin_referer( 'ideanet-check' );

	delete_site_transient( 'ideanet_update' );
	delete_site_transient( 'update_themes' );
	wp_update_themes();

	wp_safe_redirect( admin_url( 'themes.php' ) );
	exit;
}
add_action( 'admin_init', 'ideanet_handle_check' );
