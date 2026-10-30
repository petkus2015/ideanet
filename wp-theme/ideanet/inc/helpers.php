<?php
/**
 * Malé pomocníky, ktoré používajú šablóny.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hodnota z Prispôsobiť, inak predvolená.
 *
 * @param string $key   Kľúč nastavenia bez predpony.
 * @param string $fallback Núdzová hodnota, ak kľúč nie je v predvolených.
 * @return string
 */
function ideanet_opt( $key, $fallback = '' ) {
	$d = ideanet_defaults();
	$default = isset( $d[ $key ] ) ? $d[ $key ] : $fallback;
	$value = get_theme_mod( 'ideanet_' . $key, $default );

	return is_string( $value ) ? $value : $default;
}

/**
 * Rozpíše textové pole na riadky a zahodí prázdne.
 *
 * @param string $key Kľúč nastavenia.
 * @return string[]
 */
function ideanet_lines( $key ) {
	$raw = ideanet_opt( $key );
	$out = array();

	foreach ( preg_split( '/\R/', (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$out[] = $line;
		}
	}

	return $out;
}

/**
 * To isté, ale každý riadok rozdelí na stĺpce podľa zvislej čiary.
 *
 * Takto sa v jednom textovom poli dajú upravovať aj dvoj- a trojstĺpcové
 * zoznamy (nadpis a text, popis a cena), bez pluginu na opakovateľné polia.
 *
 * @param string $key   Kľúč nastavenia.
 * @param int    $count Na koľko stĺpcov doplniť každý riadok.
 * @return array<int,string[]>
 */
function ideanet_pairs( $key, $count = 2 ) {
	$out = array();

	foreach ( ideanet_lines( $key ) as $line ) {
		$cols = array_map( 'trim', explode( '|', $line ) );
		$cols = array_pad( array_slice( $cols, 0, $count ), $count, '' );
		$out[] = $cols;
	}

	return $out;
}

/**
 * Povolené značky v krátkych textoch z Prispôsobiť.
 *
 * @return array
 */
function ideanet_kses() {
	return array(
		'b'      => array(),
		'strong' => array(),
		'i'      => array(),
		'em'     => array(),
		'br'     => array(),
		'span'   => array( 'class' => array() ),
		'small'  => array( 'class' => array() ),
		'a'      => array(
			'href'   => array(),
			'title'  => array(),
			'target' => array(),
			'rel'    => array(),
		),
	);
}

/**
 * Vypíše text z Prispôsobiť aj s povolenými značkami.
 *
 * @param string $key Kľúč nastavenia.
 */
function ideanet_text( $key ) {
	echo wp_kses( ideanet_opt( $key ), ideanet_kses() );
}

/**
 * Doplní do textu zástupné hodnoty: {rok}, {email}, {mesta}, {sadzba}.
 *
 * @param string $text Vstupný text.
 * @return string
 */
function ideanet_tokens( $text ) {
	return strtr(
		(string) $text,
		array(
			'{rok}'     => gmdate( 'Y' ),
			'{email}'   => ideanet_opt( 'contact_email' ),
			'{mesta}'   => ideanet_opt( 'calc_free_cities' ),
			'{sadzba}'  => ideanet_opt( 'calc_km_rate' ),
		)
	);
}

/**
 * Číslo z nastavenia, kde sa môže objaviť čiarka namiesto bodky.
 *
 * @param string $key Kľúč nastavenia.
 * @return float
 */
function ideanet_num( $key ) {
	$raw = str_replace( array( ' ', ' ' ), '', ideanet_opt( $key ) );

	return (float) str_replace( ',', '.', $raw );
}

/**
 * Zoznam ikon, ktoré téma používa vo vlastnej navigácii a pri sieťach.
 *
 * @param string $name Názov ikony.
 * @return string Inline SVG, alebo prázdny reťazec.
 */
function ideanet_icon( $name ) {
	$p = array(
		'portfolio'  => '<rect x="3" y="3" width="18" height="18" rx="5.5"/><path d="M10.2 8.6l5.3 3.4-5.3 3.4z" fill="currentColor" stroke="none"/>',
		'cennik'     => '<path d="M12.6 3H5.6A2.6 2.6 0 003 5.6v7a2.6 2.6 0 00.76 1.84l6.8 6.8a2.6 2.6 0 003.68 0l6.4-6.4a2.6 2.6 0 000-3.68l-6.8-6.8A2.6 2.6 0 0012.6 3z"/><circle cx="7.8" cy="7.8" r="1.3"/>',
		'kalkulacka' => '<rect x="4" y="2.5" width="16" height="19" rx="3.2"/><path d="M8 7h8M8.2 12h1.4M12 12h.01M15.8 12h.01M8.2 16.4h1.4M12 16.4h.01M15.8 16.4h.01"/>',
		'kontakt'    => '<rect x="2.6" y="5" width="18.8" height="14" rx="3.4"/><path d="M3.8 7.6L12 13.4l8.2-5.8"/>',
		'sluzby'     => '<path d="M4 7h16M4 12h16M4 17h10"/>',
		'vizualy'    => '<rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.6" cy="8.6" r="1.8"/><path d="M4 16.5l4.6-4.2 4 3.4 3-2.6L20 16"/>',
		'podmienky'  => '<path d="M7 3.5h7.2L19 8.3V20a1.5 1.5 0 01-1.5 1.5h-10A1.5 1.5 0 016 20V5a1.5 1.5 0 011-1.5z"/><path d="M14 3.6V8.5h4.8M9 13h6M9 16.6h4"/>',
		'viac'       => '<circle cx="5.2" cy="12" r="1.7" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.7" fill="currentColor" stroke="none"/><circle cx="18.8" cy="12" r="1.7" fill="currentColor" stroke="none"/>',
	);

	if ( ! isset( $p[ $name ] ) ) {
		$p[ $name ] = $p['portfolio'];
	}

	return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $p[ $name ] . '</svg>';
}

/**
 * Značky sociálnych sietí aj s cestou ikony.
 *
 * @return array<string,array{label:string,path:string}>
 */
function ideanet_socials() {
	return array(
		'instagram' => array(
			'label' => 'Instagram',
			'path'  => 'M12 2.2c3.2 0 3.6 0 4.86.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.86s0 3.6-.07 4.86c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.86.07s-3.6 0-4.86-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 01-1.38-.9 3.7 3.7 0 01-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.21 15.6 2.2 15.22 2.2 12s0-3.6.07-4.86c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.4 2.21 8.78 2.2 12 2.2zm0 3.1a6.7 6.7 0 100 13.4 6.7 6.7 0 000-13.4zm0 2.35a4.35 4.35 0 110 8.7 4.35 4.35 0 010-8.7zm6.96-.33a1.56 1.56 0 11-3.13 0 1.56 1.56 0 013.13 0z',
		),
		'tiktok'    => array(
			'label' => 'TikTok',
			'path'  => 'M16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07h4.2z',
		),
		'youtube'   => array(
			'label' => 'YouTube',
			'path'  => 'M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 00.5 6.2 31 31 0 000 12a31 31 0 00.5 5.8 3 3 0 002.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 002.1-2.1A31 31 0 0024 12a31 31 0 00-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z',
		),
		'facebook'  => array(
			'label' => 'Facebook',
			'path'  => 'M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07z',
		),
	);
}

/**
 * Vypíše blok s odkazmi na siete. Prázdne odkazy preskočí.
 *
 * @param string $extra Doplnková trieda.
 */
function ideanet_socials_html( $extra = '' ) {
	$links = array();

	foreach ( ideanet_socials() as $key => $meta ) {
		$url = ideanet_opt( 'social_' . $key );
		if ( ! $url ) {
			continue;
		}
		$links[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="%3$s"/></svg></a>',
			esc_url( $url ),
			esc_attr( $meta['label'] ),
			esc_attr( $meta['path'] )
		);
	}

	if ( ! $links ) {
		return;
	}

	printf(
		'<div class="socials%1$s">%2$s</div>',
		$extra ? ' ' . esc_attr( $extra ) : '',
		implode( '', $links ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- zložené z escapovaných častí vyššie.
	);
}

/**
 * Hlavička sekcie — rovnaká na celom webe.
 *
 * @param string $slug      Predpona kľúčov (portfolio, sluzby, …).
 * @param bool   $with_note Či vypísať poznámku vpravo.
 */
function ideanet_section_head( $slug, $with_note = true ) {
	$note = ideanet_opt( $slug . '_note' );
	?>
	<div class="section__head reveal">
		<div>
			<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_opt( $slug . '_eyebrow' ) ); ?></p>
			<h2><?php ideanet_text( $slug . '_title' ); ?></h2>
		</div>
		<?php if ( $with_note && $note ) : ?>
			<p class="section__note"><?php echo wp_kses( $note, ideanet_kses() ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Značka (fotka + meno + firma). Používa sa v hlavičke aj v pätičke.
 *
 * @param string $href Odkaz.
 */
function ideanet_brand( $href = '#top' ) {
	$img = get_theme_mod( 'ideanet_brand_photo' );
	$src = $img ? $img : get_theme_file_uri( 'assets/media/foto-peter.svg' );
	?>
	<a class="brand" href="<?php echo esc_url( $href ); ?>" aria-label="<?php esc_attr_e( 'Domov', 'ideanet' ); ?>">
		<span class="brand__mark">
			<img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( ideanet_opt( 'brand_name' ) ); ?>" width="160" height="160" decoding="async">
		</span>
		<span class="brand__text">
			<span class="brand__name"><?php echo esc_html( ideanet_opt( 'brand_name' ) ); ?></span>
			<span class="brand__co"><?php echo esc_html( ideanet_opt( 'brand_co' ) ); ?><b><?php echo esc_html( ideanet_opt( 'brand_co_bold' ) ); ?></b></span>
		</span>
	</a>
	<?php
}

/**
 * Či sa má sekcia vôbec vypísať.
 *
 * @param string $slug Názov sekcie.
 * @return bool
 */
function ideanet_show( $slug ) {
	return (bool) get_theme_mod( 'ideanet_show_' . $slug, true );
}

/**
 * Položky menu z daného umiestnenia.
 *
 * Keď redaktor menu ešte nezostavil, vráti rozumný základ, aby web
 * nezostal bez navigácie.
 *
 * @param string $location Umiestnenie menu.
 * @param array  $fallback Náhradné položky ako odkaz => názov.
 * @return array<int,array{url:string,label:string,item:?WP_Post}>
 */
function ideanet_menu_items( $location, $fallback = array() ) {
	$out       = array();
	$locations = get_nav_menu_locations();

	if ( isset( $locations[ $location ] ) && $locations[ $location ] ) {
		$menu = wp_get_nav_menu_object( $locations[ $location ] );
		if ( $menu ) {
			foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
				if ( ! $item || (int) $item->menu_item_parent ) {
					continue; // druhú úroveň táto navigácia nemá.
				}
				$out[] = array(
					'url'   => ideanet_menu_url( $item->url ),
					'label' => $item->title,
					'item'  => $item,
				);
			}
		}
	}

	if ( ! $out ) {
		foreach ( $fallback as $url => $label ) {
			$out[] = array(
				'url'   => $url,
				'label' => $label,
				'item'  => null,
			);
		}
	}

	return $out;
}

/**
 * Z odkazu na vlastnú sekciu urobí kotvu.
 *
 * Menu ukladá celú adresu (https://web.sk/#cennik). Na úvodnej stránke
 * z nej spravíme #cennik, aby skrolovanie aj zvýrazňovanie práve čítanej
 * sekcie fungovalo tak ako na statickej verzii. Inde necháme celú adresu,
 * nech odkaz vedie späť na úvod.
 *
 * @param string $url Adresa z menu.
 * @return string
 */
function ideanet_menu_url( $url ) {
	$hash = strpos( $url, '#' );
	if ( false === $hash ) {
		return $url;
	}

	$base     = substr( $url, 0, $hash );
	$fragment = substr( $url, $hash );
	$home     = untrailingslashit( home_url( '/' ) );

	if ( '' === $base || untrailingslashit( $base ) === $home ) {
		return is_front_page() ? $fragment : $home . '/' . $fragment;
	}

	return $url;
}

/**
 * Predvolené sekcie, keď menu ešte nie je zostavené.
 *
 * @return array<string,string>
 */
function ideanet_default_sections() {
	return array(
		'#portfolio'  => __( 'Portfólio', 'ideanet' ),
		'#sluzby'     => __( 'Služby', 'ideanet' ),
		'#cennik'     => __( 'Cenník', 'ideanet' ),
		'#podmienky'  => __( 'Podmienky', 'ideanet' ),
		'#kontakt'    => __( 'Kontakt', 'ideanet' ),
	);
}

/**
 * Zrovná adresu súboru s tým, ako práve beží stránka.
 *
 * Adresa sa do poľa uloží aj s http:// alebo https:// podľa toho, ako
 * bol web nastavený v tej chvíli. Keď sa to neskôr rozíde — a po
 * prechode na https sa to rozchádza často —, prehliadač súbor
 * z nezabezpečenej adresy na zabezpečenej stránke potichu zablokuje
 * a video sa jednoducho neprehrá.
 *
 * @param string $url Uložená adresa.
 * @return string
 */
function ideanet_media_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	$doma = wp_parse_url( home_url(), PHP_URL_HOST );
	$ich  = wp_parse_url( $url, PHP_URL_HOST );

	/* Cudzie adresy necháme tak — o ich nastavení nič nevieme. */
	if ( ! $doma || ! $ich || strtolower( $doma ) !== strtolower( $ich ) ) {
		return $url;
	}

	return set_url_scheme( $url );
}
