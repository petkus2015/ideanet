<?php
/**
 * Plugin Name:       Nadácia Anjelské krídla — bloky pre Avadu
 * Plugin URI:        https://nadaciaanjelskekridla.sk/
 * Description:       Hotové sekcie jednostránky nadácie pre Avada Builder. Po importe sa objavia v Avada Library a v builderi ich vložíte cez Library → Containers.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Nadácia Anjelské krídla
 * License:           GPL-2.0-or-later
 * Text Domain:       nadacia-bloky
 */

defined( 'ABSPATH' ) || exit;

define( 'AK_BLOKY_VERSION', '1.0.0' );
define( 'AK_BLOKY_FILE', __FILE__ );
define( 'AK_BLOKY_DIR', plugin_dir_path( __FILE__ ) );
define( 'AK_BLOKY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Zoznam blokov. Kľúč = názov súboru v priečinku bloky/.
 */
function ak_bloky_zoznam() {
	return array(
		'01-hero'            => array( 'nazov' => 'Nadácia — 01 Hero (text + fotky 4:5)', 'popis' => 'Úvodný panel: vľavo text a tlačidlá, vpravo posuvač fotiek.' ),
		'02-cisla'           => array( 'nazov' => 'Nadácia — 02 Čísla',                   'popis' => 'Pás so štyrmi číslami (rok vzniku, dobrovoľníci, dotácie, 2 %).' ),
		'03-o-nas'           => array( 'nazov' => 'Nadácia — 03 O nás',                   'popis' => 'Fotka, text o nadácii a zoznam, komu pomáha.' ),
		'04-ako-pomahame'    => array( 'nazov' => 'Nadácia — 04 Ako pomáhame',            'popis' => 'Štyri karty so spôsobmi pomoci.' ),
		'05-ziadost-o-pomoc' => array( 'nazov' => 'Nadácia — 05 Žiadosť o pomoc',         'popis' => 'Postup v štyroch krokoch a tlačivá na stiahnutie.' ),
		'06-projekty'        => array( 'nazov' => 'Nadácia — 06 Projekty',                'popis' => 'Tri projekty s fotkou a odkazom.' ),
		'07-clanky'          => array( 'nazov' => 'Nadácia — 07 Články',                  'popis' => 'Náhľad posledných článkov (element Blog).' ),
		'08-galeria'         => array( 'nazov' => 'Nadácia — 08 Galéria',                 'popis' => 'Galéria s lightboxom.' ),
		'09-dve-percenta'    => array( 'nazov' => 'Nadácia — 09 Dve percentá z dane',     'popis' => 'Postup, údaje pre vyhlásenie a tlačivo na stiahnutie.' ),
		'10-podpora'         => array( 'nazov' => 'Nadácia — 10 Podporte nás',            'popis' => 'Tri spôsoby podpory vrátane čísla účtu.' ),
		'11-pribehy'         => array( 'nazov' => 'Nadácia — 11 Príbehy',                 'popis' => 'Ohlasy ľudí, ktorým nadácia pomohla.' ),
		'12-kontakt'         => array( 'nazov' => 'Nadácia — 12 Kontakt',                 'popis' => 'Kontaktné údaje, siete a miesto na formulár.' ),
	);
}

/**
 * Načíta shortcode bloku a doplní adresy k obrázkom a dokumentom.
 */
function ak_bloky_obsah( $kluc ) {
	$subor = AK_BLOKY_DIR . 'bloky/' . $kluc . '.txt';
	if ( ! file_exists( $subor ) ) {
		return '';
	}
	$obsah  = file_get_contents( $subor ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$uploads = wp_get_upload_dir();
	$ucet    = ak_bloky_ucet();

	return strtr(
		$obsah,
		array(
			'{{AK_IMG}}'  => AK_BLOKY_URL . 'assets/img/',
			'{{AK_DOC}}'  => trailingslashit( $uploads['baseurl'] ) . 'nadacia/',
			'{{AK_UCET}}' => '' !== $ucet ? $ucet : '#podpora',
		)
	);
}

/**
 * Odkaz na výpis transparentného účtu (prázdny, kým ho nevyplníte).
 */
function ak_bloky_ucet() {
	return (string) get_option( 'ak_bloky_ucet', '' );
}

/* ─────────────────────────────────────────────
   Štýly blokov
   ───────────────────────────────────────────── */
function ak_bloky_styly() {
	wp_enqueue_style(
		'nadacia-bloky',
		AK_BLOKY_URL . 'assets/css/nadacia-bloky.css',
		array(),
		AK_BLOKY_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'ak_bloky_styly', 20 );
add_action( 'admin_enqueue_scripts', 'ak_bloky_styly_v_builderi' );

function ak_bloky_styly_v_builderi( $hook ) {
	// v editore stránok, aby náhľad v builderi sedel s webom
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		ak_bloky_styly();
	}
}

/* ─────────────────────────────────────────────
   Import do Avada Library
   ───────────────────────────────────────────── */

/** Je Avada Builder (Fusion Builder) aktívny? */
function ak_bloky_ma_avadu() {
	return post_type_exists( 'fusion_element' );
}

/**
 * Vytvorí alebo aktualizuje jeden blok v Avada Library.
 * Vracia ID príspevku alebo WP_Error.
 */
function ak_bloky_importuj_blok( $kluc ) {
	$zoznam = ak_bloky_zoznam();
	if ( ! isset( $zoznam[ $kluc ] ) ) {
		return new WP_Error( 'ak_neznamy_blok', 'Neznámy blok: ' . $kluc );
	}
	$obsah = ak_bloky_obsah( $kluc );
	if ( '' === $obsah ) {
		return new WP_Error( 'ak_prazdny_blok', 'Súbor bloku sa nenašiel: ' . $kluc );
	}

	$existujuci = ak_bloky_najdi_blok( $kluc );
	$data = array(
		'post_title'   => $zoznam[ $kluc ]['nazov'],
		'post_content' => $obsah,
		'post_status'  => 'publish',
		'post_type'    => 'fusion_element',
	);

	if ( $existujuci ) {
		$data['ID'] = $existujuci;
		$id = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}
	if ( is_wp_error( $id ) ) {
		return $id;
	}

	update_post_meta( $id, '_ak_blok', $kluc );
	update_post_meta( $id, '_fusion_element_type', 'sections' ); // v knižnici ide o kontajner/sekciu
	update_post_meta( $id, 'fusion_element_type', 'sections' );  // staršie verzie Avady
	update_post_meta( $id, '_fusion_builder_content', $obsah );

	return $id;
}

/** Nájde už naimportovaný blok podľa kľúča. */
function ak_bloky_najdi_blok( $kluc ) {
	$q = get_posts(
		array(
			'post_type'      => 'fusion_element',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_ak_blok',   // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $kluc,        // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $q ? (int) $q[0] : 0;
}

/* ─────────────────────────────────────────────
   Stránka v administrácii
   ───────────────────────────────────────────── */
function ak_bloky_menu() {
	add_menu_page(
		'Bloky nadácie',
		'Bloky nadácie',
		'manage_options',
		'nadacia-bloky',
		'ak_bloky_stranka',
		'dashicons-heart',
		59
	);
}
add_action( 'admin_menu', 'ak_bloky_menu' );

function ak_bloky_stranka() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Nemáte oprávnenie zobraziť túto stránku.', 'nadacia-bloky' ) );
	}

	$sprava = '';
	$chyba  = '';

	if ( isset( $_POST['ak_ulozit_ucet'] ) && check_admin_referer( 'ak_bloky_ucet' ) ) {
		$novy = isset( $_POST['ak_ucet'] ) ? esc_url_raw( wp_unslash( $_POST['ak_ucet'] ) ) : '';
		update_option( 'ak_bloky_ucet', $novy );
		$sprava = '' !== $novy ? 'Odkaz na transparentný účet je uložený.' : 'Odkaz na transparentný účet sme vymazali.';
	}

	if ( isset( $_POST['ak_import'] ) && check_admin_referer( 'ak_bloky_import' ) ) {
		if ( ! ak_bloky_ma_avadu() ) {
			$chyba = 'Avada Builder nie je aktívny, knižnica sa nedá naplniť. Bloky si zatiaľ môžete skopírovať nižšie.';
		} else {
			$ok = 0;
			$zle = array();
			foreach ( array_keys( ak_bloky_zoznam() ) as $kluc ) {
				$vysledok = ak_bloky_importuj_blok( $kluc );
				if ( is_wp_error( $vysledok ) ) {
					$zle[] = $kluc;
				} else {
					$ok++;
				}
			}
			$sprava = sprintf( 'Do Avada Library sa uložilo %d blokov.', $ok );
			if ( $zle ) {
				$chyba = 'Nepodarilo sa: ' . implode( ', ', $zle );
			}
		}
	}
	?>
	<div class="wrap">
		<h1>Bloky nadácie pre Avada Builder</h1>

		<?php if ( $sprava ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $sprava ); ?></p></div>
		<?php endif; ?>
		<?php if ( $chyba ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $chyba ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! ak_bloky_ma_avadu() ) : ?>
			<div class="notice notice-warning">
				<p>Nenašli sme Avada Builder. Aktivujte tému Avada aj plugin Avada Builder — až potom sa dajú bloky uložiť do knižnice.</p>
			</div>
		<?php endif; ?>

		<h2>1. Odkaz na transparentný účet</h2>
		<p>Adresa výpisu účtu v internet bankingu. Doplní sa do tlačidla
			<em>Transparentný účet — pozrieť pohyby</em> v bloku <strong>10 Podporte nás</strong>.
			Ak pole necháte prázdne, tlačidlo bude odkazovať späť na sekciu podpory.</p>
		<form method="post">
			<?php wp_nonce_field( 'ak_bloky_ucet' ); ?>
			<p>
				<input type="url" name="ak_ucet" value="<?php echo esc_attr( ak_bloky_ucet() ); ?>"
					class="regular-text" style="width:520px;max-width:100%"
					placeholder="https://www.banka.sk/transparentne-ucty/SK37...">
			</p>
			<p><button type="submit" name="ak_ulozit_ucet" value="1" class="button">Uložiť odkaz</button></p>
		</form>
		<p class="description">Odkaz sa vkladá do blokov pri importe. Ak ho zmeníte neskôr, spustite import znova —
			prepíše sa tým položka v knižnici. V stránkach, kde už blok máte vložený, odkaz opravte priamo v builderi.</p>

		<h2>2. Import do knižnice</h2>
		<p>Tlačidlo uloží všetkých dvanásť sekcií do <strong>Avada → Library</strong>. Import môžete spustiť aj opakovane — bloky sa prepíšu, nevzniknú duplikáty.</p>
		<form method="post">
			<?php wp_nonce_field( 'ak_bloky_import' ); ?>
			<p><button type="submit" name="ak_import" value="1" class="button button-primary">Importovať bloky do Avada Library</button></p>
		</form>

		<h2>3. Vloženie do stránky</h2>
		<ol>
			<li>Stránky → Pridať novú, zapnite <strong>Avada Builder</strong>.</li>
			<li>Kliknite na <strong>Library</strong> (ikona knižnice v hornej lište buildera) a v záložke <em>Containers</em> vyberte blok.</li>
			<li>Blok sa vloží ako bežné kontajnery a stĺpce — ďalej ho upravujete klikaním.</li>
			<li>Poradie sekcií podľa návrhu: 01 → 12.</li>
		</ol>
		<p>Ak by sa bloky v knižnici nezobrazili, použite náhradnú cestu: skopírujte shortcode nižšie, v editore stránky prepnite <em>Toggle Builder</em> na klasický editor, vložte a prepnite späť.</p>

		<h2>4. Farby menu a témy</h2>
		<p>Bloky majú farby nastavené v sebe, hlavičku a menu však ovláda téma:</p>
		<ul style="list-style:disc;margin-left:22px">
			<li><strong>Avada → Options → Header</strong>: Header Background Color <code>#28afc3</code>.</li>
			<li><strong>Avada → Options → Menu → Main Menu</strong>: farba písma aj pri prejdení myšou <code>#ffffff</code>, pozadie rozbaľovacieho menu <code>#28afc3</code>.</li>
			<li><strong>Avada → Options → Menu → Mobile Menu</strong>: pozadie <code>#28afc3</code>, text <code>#ffffff</code>.</li>
			<li><strong>Avada → Options → Colors</strong>: Primary <code>#28afc3</code>, Text <code>#2a4750</code>, Headings <code>#0a1f26</code>, Link <code>#14707f</code>.</li>
		</ul>

		<h2>5. Čo doplniť</h2>
		<ul style="list-style:disc;margin-left:22px">
			<li>Fotky sú zatiaľ ilustračné a nesie ich tento plugin. Nahraďte ich vlastnými priamo v builderi (klik na obrázok → Select Image).</li>
			<li>Tlačivá na stiahnutie plugin neobsahuje. Nahrajte do knižnice médií súbory <code>ziadost-o-prispevok.pdf</code>, <code>suhlas-ochrana-osobnych-udajov.pdf</code> a <code>vyhlasenie-2-percenta.pdf</code> a v blokoch 05 a 09 opravte odkazy tlačidiel.</li>
			<li>Kontaktný formulár si vytvorte v <strong>Avada → Forms</strong> a vložte ho do pravého stĺpca bloku 12.</li>
			<li>Odkazy na sociálne siete v bloku 12 vedú zatiaľ na domovské stránky sietí.</li>
			<li>Ohlasy v bloku 11 sú ilustračné — nahraďte ich skutočnými so súhlasom rodín.</li>
		</ul>

		<h2>6. Bloky na skopírovanie</h2>
		<?php foreach ( ak_bloky_zoznam() as $kluc => $blok ) : ?>
			<?php $id = ak_bloky_najdi_blok( $kluc ); ?>
			<h3 style="margin-bottom:4px">
				<?php echo esc_html( $blok['nazov'] ); ?>
				<?php if ( $id ) : ?>
					<span style="font-weight:400;color:#1a7f37">— v knižnici</span>
				<?php endif; ?>
			</h3>
			<p style="margin:0 0 6px;color:#555"><?php echo esc_html( $blok['popis'] ); ?></p>
			<textarea readonly rows="3" style="width:100%;font-family:monospace;font-size:11px" onclick="this.select()"><?php echo esc_textarea( ak_bloky_obsah( $kluc ) ); ?></textarea>
		<?php endforeach; ?>
	</div>
	<?php
}

/* ─────────────────────────────────────────────
   Aktivácia
   ───────────────────────────────────────────── */
function ak_bloky_aktivacia() {
	// Import spúšťame až na požiadanie — pri aktivácii nemusí byť Avada ešte pripravená.
	add_option( 'ak_bloky_verzia', AK_BLOKY_VERSION );
}
register_activation_hook( __FILE__, 'ak_bloky_aktivacia' );

/** Odkaz na nastavenia v zozname pluginov. */
function ak_bloky_odkaz( $odkazy ) {
	$odkaz = '<a href="' . esc_url( admin_url( 'admin.php?page=nadacia-bloky' ) ) . '">Bloky</a>';
	array_unshift( $odkazy, $odkaz );
	return $odkazy;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ak_bloky_odkaz' );
