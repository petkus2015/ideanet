<?php
/**
 * Vlastné typy obsahu — Reels, vizuály, balíky, referencie, kroky,
 * podmienky, služby a prijaté dopyty.
 *
 * Všetko je postavené len na jadre WordPressu, aby téma nepotrebovala
 * žiadny plugin na vlastné polia.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Popis všetkých typov obsahu aj s ich vlastnými poľami.
 *
 * @return array
 */
function ideanet_post_types() {
	return array(

		'ideanet_reel' => array(
			'labels'   => array( 'Reel', 'Reels (portfólio)' ),
			'menu'     => 'dashicons-video-alt3',
			'supports' => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
			'help'     => 'Náhľadový obrázok = plagát videa (9:16). Ak video nevyplníte, karta zostane na plagáte.',
			'fields'   => array(
				'poster' => array( 'Plagát — náhradný obrázok', 'media', 'Použije sa len vtedy, keď položka nemá náhľadový obrázok.', 'image' ),
				'cat'   => array( 'Kategória', 'text', 'Štítok vľavo hore na karte — napríklad Gastro.' ),
				'dur'   => array( 'Dĺžka', 'text', 'Nechajte prázdne — dĺžka sa prečíta z videa. Vyplňte len vtedy, keď chcete ukázať niečo iné, alebo keď video ešte nemáte.' ),
				'video' => array( 'Video (MP4)', 'media', 'Nahrajte alebo vyberte súbor z knižnice médií. Vertikál 9:16.', 'video' ),
				'webm'  => array( 'Video (WebM) — nepovinné', 'media', 'Druhý formát pre staršie prehliadače.', 'video' ),
				'tags'  => array( 'Štítky', 'textarea', 'Jeden na riadok. Zobrazia sa pod popisom.' ),
			),
		),

		'ideanet_vizual' => array(
			'labels'   => array( 'Vizuál', 'Vizuály a identita' ),
			'menu'     => 'dashicons-art',
			'supports' => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
			'help'     => 'Náhľadový obrázok v pomere 4:5. Výpis (excerpt) je popis pod nadpisom.',
			'fields'   => array(
				'image' => array( 'Obrázok — náhradný', 'media', 'Použije sa len vtedy, keď položka nemá náhľadový obrázok.', 'image' ),
				'cat' => array( 'Kategória', 'text', 'Napríklad Identita, Vizuály, Event.' ),
			),
		),

		'ideanet_fotoset' => array(
			'labels'   => array( 'Fotosada', 'Fotky — sady klientov' ),
			'menu'     => 'dashicons-format-gallery',
			'supports' => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
			'help'     => 'Jedna sada = jeden klient, pre ktorého ste fotili. Nadpis je názov klienta, výpis je veta pod ním. Náhľadový obrázok je obrázok na karte; keď ho nevyplníte, použije sa prvá fotka zo sady.',
			'fields'   => array(
				'cat'   => array( 'Kategória', 'text', 'Štítok na karte — napríklad Gastro alebo Event.' ),
				'fotky' => array( 'Fotky v sade', 'gallery', 'Vyberte naraz aj desať fotiek. Poradie zmeníte potiahnutím, krížikom fotku zo sady vyhodíte.' ),
			),
		),

		'ideanet_web' => array(
			'labels'   => array( 'Ukážka webu', 'Webdizajn — ukážky' ),
			'menu'     => 'dashicons-desktop',
			'supports' => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
			'help'     => 'Zvislé ukážky hotových webov na úvodnej stránke. Video je 9:16 — prejdenie webu na mobile. Náhľadový obrázok je prvý obrázok videa. Kým tu nič nie je, sekcia Webdizajn sa nezobrazí.',
			'fields'   => array(
				'poster' => array( 'Prvý obrázok videa', 'media', 'Použije sa len vtedy, keď položka nemá náhľadový obrázok. Zvislý, 9:16.', 'image' ),
				'cat'    => array( 'Kategória', 'text', 'Štítok vľavo hore na karte — napríklad Gastro.' ),
				'video'  => array( 'Video (MP4)', 'media', 'Zvislé 9:16, bez zvuku. Najlepšie krátke prejdenie webu zhora nadol.', 'video' ),
				'webm'   => array( 'Video (WebM) — nepovinné', 'media', 'Druhý formát pre staršie prehliadače.', 'video' ),
				'url'    => array( 'Odkaz na živý web', 'text', 'Bez neho sa tlačidlo pod ukážkou nezobrazí.' ),
			),
		),

		'ideanet_redizajn' => array(
			'labels'   => array( 'Ukážka klienta', 'Redizajn — ukážky klientov' ),
			'menu'     => 'dashicons-admin-site-alt3',
			'supports' => array( 'title', 'editor', 'page-attributes' ),
			'help'     => 'Hotové weby na podstránke s ponukou redizajnu. Nadpis je názov klienta alebo odboru, obsah je jedna veta o výsledku. Kým tu nič nie je, sekcia s ukážkami sa na podstránke nezobrazí.',
			'fields'   => array(
				'web' => array( 'Snímka webu', 'media', 'Snímka obrazovky na šírku, ideálne v pomere 16:11. Ak položka nemá náhľadový obrázok, použije sa táto.', 'image' ),
				'url' => array( 'Odkaz na web', 'text', 'Nepovinné. Ak vyplníte, názov ukážky bude odkaz.' ),
			),
		),

		'ideanet_referencia' => array(
			'labels'   => array( 'Referencia', 'Referencie' ),
			'menu'     => 'dashicons-format-quote',
			'supports' => array( 'title', 'editor', 'page-attributes' ),
			'help'     => 'Do obsahu napíšte samotný citát. Nadpis sa nezobrazuje, slúži len na orientáciu v zozname.',
			'fields'   => array(
				'autor' => array( 'Kto to povedal', 'text', 'Napríklad Marek H.' ),
				'rola'  => array( 'Rola', 'text', 'Napríklad majiteľ kaviarne.' ),
			),
		),

		'ideanet_sluzba' => array(
			'labels'   => array( 'Služba', 'Služby (cesty spolupráce)' ),
			'menu'     => 'dashicons-screenoptions',
			'supports' => array( 'title', 'editor', 'page-attributes' ),
			'help'     => 'Karty v sekcii Služby. Obsah je odsek pod nadpisom. Ceny sem nepatria — tie sú v cenníku.',
			'fields'   => array(
				'tag'      => array( 'Štítok', 'text', 'Napríklad Pre firmy.' ),
				'features' => array( 'Odrážky', 'textarea', 'Jedna na riadok.' ),
				'note'     => array( 'Riadok pod odrážkami', 'text', 'Krátka veta na spodku karty — napríklad Polovičný úväzok alebo dohodou. Ceny sem nepíšte, tie patria do cenníka. Tučné písmo cez &lt;b&gt;.' ),
				'target'   => array( 'Kam karta vedie', 'select', 'Cenník pre karty s balíkmi, kontakt tam, kde sa rozsah dohaduje.', array(
					'cennik'  => 'Cenník',
					'kontakt' => 'Kontakt',
				) ),
				'best'     => array( 'Zvýrazniť kartu', 'checkbox', 'Vyzdvihne kartu farebným rámom.' ),
			),
		),

		'ideanet_balik' => array(
			'labels'   => array( 'Balík', 'Balíky a ceny' ),
			'menu'     => 'dashicons-tag',
			'supports' => array( 'title', 'page-attributes' ),
			'help'     => 'Jeden balík = jedna cena v cenníku. Poradie nastavíte cez Poradie v boxe Vlastnosti stránky.',
			'fields'   => array(
				'group'    => array( 'Záložka cenníka', 'select', '', array(
					'firmy'    => 'Pre firmy',
					'agentury' => 'Pre agentúry',
					'eventy'   => 'Eventy',
				) ),
				'layout'   => array( 'Podoba', 'select', 'Karta = veľká dlaždica s odrážkami. Riadok = úzky riadok s cenou vpravo.', array(
					'plan' => 'Karta (mesačné balíky, eventy)',
					'tier' => 'Riadok (jednorazové, white-label)',
				) ),
				'block'    => array( 'Zaradiť do bloku', 'select', 'Riadky sa zoskupujú pod vlastné medzinadpisy.', array(
					''            => 'Hlavný zoznam',
					'jednorazovo' => 'Jednorazová produkcia',
					'podcast'     => 'Podcast',
					'vizualy'     => 'Vizuály a identita',
					'grafika'     => 'Grafika na event',
				) ),
				'price'    => array( 'Cena', 'text', 'Napríklad 390 € alebo 70 € / h.' ),
				'unit'     => array( 'Za čo', 'text', 'Napríklad / mesiac. Len pri karte.' ),
				'desc'     => array( 'Krátky popis', 'text', 'Veta pod cenou (karta) alebo pod názvom (riadok).' ),
				'features' => array( 'Čo je v balíku', 'textarea', 'Jedna položka na riadok. Len pri karte.' ),
				'badge'    => array( 'Stužka', 'text', 'Napríklad Najčastejšia voľba. Nechajte prázdne, ak nemá byť.' ),
				'best'     => array( 'Zvýrazniť', 'checkbox', '' ),
				'star'     => array( 'Hviezdička v názve', 'text', 'Napríklad najžiadanejšie. Len pri riadku.' ),
				'cta'      => array( 'Text tlačidla', 'text', 'Prázdne = Mám záujem / Dopyt. Nechajte „—" ak tlačidlo nemá byť.' ),
			),
		),

		'ideanet_krok' => array(
			'labels'   => array( 'Krok', 'Proces (kroky)' ),
			'menu'     => 'dashicons-list-view',
			'supports' => array( 'title', 'editor', 'page-attributes' ),
			'help'     => 'Číslo kroku sa doplní automaticky podľa poradia.',
			'fields'   => array(),
		),

		'ideanet_podmienka' => array(
			'labels'   => array( 'Podmienka', 'Podmienky' ),
			'menu'     => 'dashicons-shield-alt',
			'supports' => array( 'title', 'editor', 'page-attributes' ),
			'help'     => 'Nadpis a jedna až dve vety.',
			'fields'   => array(),
		),

		'ideanet_dopyt' => array(
			'labels'   => array( 'Dopyt', 'Prijaté dopyty' ),
			'menu'     => 'dashicons-email-alt',
			'supports' => array( 'title', 'editor' ),
			'help'     => 'Záznamy z kontaktného formulára. Ukladajú sa aj vtedy, keď e-mail z hostingu neodíde.',
			'public'   => false,
			'create'   => false,
			'fields'   => array(
				'od_mena'  => array( 'Meno', 'text', '' ),
				'od_email' => array( 'E-mail', 'text', '' ),
				'od_typ'   => array( 'O čo má záujem', 'text', '' ),
				'od_ip'    => array( 'IP adresa', 'text', '' ),
			),
		),
	);
}

/**
 * Zaregistruje typy obsahu.
 */
function ideanet_register_post_types() {
	$first = true;

	foreach ( ideanet_post_types() as $slug => $def ) {
		list( $single, $plural ) = $def['labels'];
		$public = ! isset( $def['public'] ) || $def['public'];

		$args = array(
			'labels'          => array(
				'name'               => $plural,
				'singular_name'      => $single,
				'add_new'            => __( 'Pridať nový', 'ideanet' ),
				/* translators: %s: názov položky v jednotnom čísle. */
				'add_new_item'       => sprintf( __( 'Pridať: %s', 'ideanet' ), $single ),
				/* translators: %s: názov položky v jednotnom čísle. */
				'edit_item'          => sprintf( __( 'Upraviť: %s', 'ideanet' ), $single ),
				'search_items'       => $plural,
				'not_found'          => __( 'Zatiaľ tu nič nie je.', 'ideanet' ),
				'not_found_in_trash' => __( 'V koši nič nie je.', 'ideanet' ),
				'menu_name'          => $plural,
			),
			'public'          => $public,
			'publicly_queryable' => false,
			'show_ui'         => true,
			'show_in_menu'    => $first ? 'ideanet' : 'ideanet',
			'show_in_rest'    => true,
			'has_archive'     => false,
			'exclude_from_search' => true,
			'rewrite'         => false,
			'menu_icon'       => $def['menu'],
			'supports'        => $def['supports'],
			'capability_type' => 'post',
		);

		if ( isset( $def['create'] ) && false === $def['create'] ) {
			$args['capabilities'] = array( 'create_posts' => 'do_not_allow' );
			$args['map_meta_cap'] = true;
		}

		register_post_type( $slug, $args );

		foreach ( $def['fields'] as $key => $field ) {
			register_post_meta(
				$slug,
				'_ideanet_' . $key,
				array(
					'single'        => true,
					'type'          => 'string',
					'show_in_rest'  => false,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}

		$first = false;
	}
}
add_action( 'init', 'ideanet_register_post_types' );

/**
 * Spoločná položka v menu administrácie, aby osem typov obsahu
 * nerozhádzalo celý ľavý stĺpec.
 */
function ideanet_admin_menu() {
	add_menu_page(
		__( 'IDEANET — obsah', 'ideanet' ),
		'IDEANET',
		'edit_posts',
		'ideanet',
		'ideanet_admin_page',
		'dashicons-format-video',
		3
	);

	add_submenu_page( 'ideanet', __( 'Kde čo upraviť', 'ideanet' ), __( 'Kde čo upraviť', 'ideanet' ), 'edit_posts', 'ideanet', 'ideanet_admin_page' );

	/* Dve veci sa hľadajú najčastejšie, nech sú na dosah z ľavého stĺpca. */
	add_submenu_page( 'ideanet', __( 'Kontakt', 'ideanet' ), __( 'Kontakt a siete', 'ideanet' ), 'customize', ideanet_customize_link( 'kontakt' ) );
	add_submenu_page( 'ideanet', __( 'Formulár', 'ideanet' ), __( 'Formulár', 'ideanet' ), 'customize', ideanet_customize_link( 'formular' ) );
}
add_action( 'admin_menu', 'ideanet_admin_menu', 9 );

/**
 * Priamy odkaz do Prispôsobiť, rovno na konkrétnu sekciu.
 *
 * @param string $slug Názov sekcie bez predpony.
 * @return string
 */
function ideanet_customize_link( $slug ) {
	return add_query_arg(
		array( 'autofocus' => array( 'section' => 'ideanet_' . $slug ) ),
		admin_url( 'customize.php' )
	);
}

/**
 * Rozcestník v administrácii.
 *
 * Panel v Prispôsobiť má veľa sekcií a hľadať v ňom, kde sa mení
 * e-mail alebo formulár, je otrava. Tu je na každú priamy odkaz.
 */
function ideanet_admin_page() {
	$texty = array();
	foreach ( ideanet_customizer_map() as $slug => $section ) {
		$texty[] = array( $section['title'], ideanet_customize_link( $slug ) );
	}

	$obsah = array();
	foreach ( ideanet_post_types() as $slug => $def ) {
		$obsah[] = array(
			$def['labels'][1],
			admin_url( 'edit.php?post_type=' . $slug ),
			isset( $def['help'] ) ? $def['help'] : '',
		);
	}

	/* translators: %s: číslo verzie témy. */
	$verzia = sprintf( __( 'Verzia témy %s — zistiť, či je novšia', 'ideanet' ), wp_get_theme( get_template() )->get( 'Version' ) );

	$ostatne = array(
		array( $verzia, ideanet_check_now_link() ),
		array( __( 'Menu — hore, spodná lišta na mobile, pätička', 'ideanet' ), admin_url( 'nav-menus.php' ) ),
		array( __( 'Obrázky a videá v knižnici médií', 'ideanet' ), admin_url( 'upload.php' ) ),
		array( __( 'Stránky', 'ideanet' ), admin_url( 'edit.php?post_type=page' ) ),
	);

	/* Priamy odkaz na podstránku s ponukou redizajnu — inak sa hľadá
	   medzi stránkami a nie je jasné, ktorá to je. */
	$redizajn = function_exists( 'ideanet_redizajn_stranka' ) ? ideanet_redizajn_stranka() : null;
	if ( $redizajn ) {
		$ostatne[] = array(
			__( 'Podstránka s ponukou redizajnu webov', 'ideanet' ),
			get_edit_post_link( $redizajn->ID, '' ),
		);
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'IDEANET — kde čo upraviť', 'ideanet' ); ?></h1>
		<p><?php esc_html_e( 'Celý web sa dá upraviť bez zásahu do kódu. Každý odkaz vedie rovno na miesto, kde sa to mení.', 'ideanet' ); ?></p>

		<div style="display:flex;flex-wrap:wrap;gap:1.5rem;align-items:flex-start;max-width:1200px">

			<div style="flex:1 1 380px">
				<h2><?php esc_html_e( 'Texty, ceny a kontakt', 'ideanet' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Všetko v Prispôsobiť — zmeny vidíte hneď v náhľade vpravo.', 'ideanet' ); ?></p>
				<table class="widefat striped">
					<tbody>
					<?php foreach ( $texty as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row[0] ); ?></td>
							<td style="width:1%;white-space:nowrap">
								<a class="button" href="<?php echo esc_url( $row[1] ); ?>"><?php esc_html_e( 'Upraviť', 'ideanet' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div style="flex:1 1 380px">
				<h2><?php esc_html_e( 'Obsah', 'ideanet' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Položky, ktoré sa dajú pridávať a mazať.', 'ideanet' ); ?></p>
				<table class="widefat striped">
					<tbody>
					<?php foreach ( $obsah as $row ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $row[0] ); ?></strong>
								<?php if ( $row[2] ) : ?>
									<br><span class="description"><?php echo esc_html( $row[2] ); ?></span>
								<?php endif; ?>
							</td>
							<td style="width:1%;white-space:nowrap">
								<a class="button" href="<?php echo esc_url( $row[1] ); ?>"><?php esc_html_e( 'Otvoriť', 'ideanet' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Ostatné', 'ideanet' ); ?></h2>
				<table class="widefat striped">
					<tbody>
					<?php foreach ( $ostatne as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row[0] ); ?></td>
							<td style="width:1%;white-space:nowrap">
								<a class="button" href="<?php echo esc_url( $row[1] ); ?>"><?php esc_html_e( 'Otvoriť', 'ideanet' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Vlastné polia pri úprave položky.
 */
function ideanet_meta_boxes() {
	foreach ( ideanet_post_types() as $slug => $def ) {
		if ( ! $def['fields'] && empty( $def['help'] ) ) {
			continue;
		}

		add_meta_box(
			'ideanet-fields',
			__( 'Nastavenia zobrazenia', 'ideanet' ),
			'ideanet_meta_box_html',
			$slug,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'ideanet_meta_boxes' );

/**
 * Obsah meta boxu.
 *
 * @param WP_Post $post Upravovaná položka.
 */
function ideanet_meta_box_html( $post ) {
	$types = ideanet_post_types();
	$def   = isset( $types[ $post->post_type ] ) ? $types[ $post->post_type ] : null;
	if ( ! $def ) {
		return;
	}

	wp_nonce_field( 'ideanet_save_meta', 'ideanet_meta_nonce' );

	if ( ! empty( $def['help'] ) ) {
		echo '<p class="description" style="margin:.2rem 0 1rem">' . esc_html( $def['help'] ) . '</p>';
	}

	echo '<table class="form-table" role="presentation"><tbody>';

	foreach ( $def['fields'] as $key => $field ) {
		$label = $field[0];
		$type  = $field[1];
		$hint  = isset( $field[2] ) ? $field[2] : '';
		$extra = isset( $field[3] ) ? $field[3] : null;
		$name  = '_ideanet_' . $key;
		$value = (string) get_post_meta( $post->ID, $name, true );
		$id    = 'ideanet-' . $key;

		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';

		switch ( $type ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="5" class="large-text code">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( (array) $extra as $val => $text ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $val ),
						selected( $value, $val, false ),
						esc_html( $text )
					);
				}
				echo '</select>';
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( $value, '1', false ),
					esc_html( $hint )
				);
				$hint = '';
				break;

			case 'gallery':
				$ids = ideanet_gallery_ids( $value );
				printf(
					'<input type="hidden" id="%1$s" name="%2$s" value="%3$s" class="ideanet-gallery-value">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( implode( ',', $ids ) )
				);
				echo '<div class="ideanet-gallery" data-target="' . esc_attr( $id ) . '">';
				foreach ( $ids as $att ) {
					ideanet_gallery_thumb( $att );
				}
				echo '</div>';
				printf(
					'<p><button type="button" class="button ideanet-gallery-pick" data-target="%1$s">%2$s</button> <span class="description ideanet-gallery-count">%3$s</span></p>',
					esc_attr( $id ),
					esc_html__( 'Pridať fotky z knižnice', 'ideanet' ),
					esc_html( sprintf( /* translators: %d: počet fotiek. */ _n( '%d fotka v sade', '%d fotiek v sade', count( $ids ), 'ideanet' ), count( $ids ) ) )
				);
				break;

			case 'media':
				printf(
					'<input type="url" id="%1$s" name="%2$s" value="%3$s" class="large-text" style="max-width:32rem"> <button type="button" class="button ideanet-pick" data-target="%1$s" data-type="%4$s">%5$s</button>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $extra ? $extra : 'image' ),
					esc_html__( 'Vybrať z knižnice', 'ideanet' )
				);
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="large-text" style="max-width:32rem">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
		}

		if ( $hint ) {
			echo '<p class="description">' . wp_kses( $hint, array( 'b' => array(), 'code' => array() ) ) . '</p>';
		}

		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * Uloží vlastné polia.
 *
 * @param int $post_id ID položky.
 */
function ideanet_save_meta( $post_id ) {
	if ( ! isset( $_POST['ideanet_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ideanet_meta_nonce'] ) ), 'ideanet_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$types = ideanet_post_types();
	$type  = get_post_type( $post_id );
	if ( ! isset( $types[ $type ] ) ) {
		return;
	}

	foreach ( $types[ $type ]['fields'] as $key => $field ) {
		$name = '_ideanet_' . $key;

		if ( 'checkbox' === $field[1] ) {
			$value = isset( $_POST[ $name ] ) ? '1' : '';
		} elseif ( ! isset( $_POST[ $name ] ) ) {
			continue;
		} elseif ( 'textarea' === $field[1] ) {
			$value = sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) );
		} elseif ( 'gallery' === $field[1] ) {
			$value = implode( ',', ideanet_gallery_ids( wp_unslash( $_POST[ $name ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ideanet_gallery_ids prepustí len kladné čísla.
		} elseif ( 'media' === $field[1] ) {
			$value = esc_url_raw( wp_unslash( $_POST[ $name ] ) );
		} else {
			$value = wp_kses( wp_unslash( $_POST[ $name ] ), ideanet_kses() );
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, $name );
		} else {
			update_post_meta( $post_id, $name, $value );
		}
	}
}
add_action( 'save_post', 'ideanet_save_meta' );

/**
 * Skript na výber súboru z knižnice médií pri poliach typu „media".
 *
 * @param string $hook Aktuálna stránka administrácie.
 */
function ideanet_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	if ( ! array_key_exists( (string) get_post_type(), ideanet_post_types() ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_add_inline_style(
		'wp-admin',
		'.ideanet-gallery{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 10px}
		.ideanet-gallery:empty{display:none}
		.ideanet-gallery__item{position:relative;width:88px;height:88px;border-radius:6px;overflow:hidden;border:1px solid #c3c4c7;background:#f0f0f1;cursor:move}
		.ideanet-gallery__item img{width:100%;height:100%;object-fit:cover;display:block}
		.ideanet-gallery__x{position:absolute;top:2px;right:2px;width:22px;height:22px;line-height:1;border:0;border-radius:50%;background:rgba(0,0,0,.65);color:#fff;cursor:pointer;font-size:16px}
		.ideanet-gallery__item.ui-sortable-helper{opacity:.85}'
	);
	wp_add_inline_script(
		'media-views',
		"jQuery(function($){
			$(document).on('click','.ideanet-pick',function(e){
				e.preventDefault();
				var b=$(this), target=$('#'+b.data('target')), type=b.data('type')||'image';
				var frame=wp.media({title:'" . esc_js( __( 'Vyberte súbor', 'ideanet' ) ) . "',library:{type:type},multiple:false});
				frame.on('select',function(){ target.val(frame.state().get('selection').first().toJSON().url); });
				frame.open();
			});

			/* Sada fotiek: ID sa držia v skrytom poli, poradie miniatúr je poradie v sade. */
			function zapis(wrap){
				var ids=[];
				wrap.find('.ideanet-gallery__item').each(function(){ ids.push($(this).data('id')); });
				$('#'+wrap.data('target')).val(ids.join(','));
				wrap.closest('td').find('.ideanet-gallery-count').text(
					ids.length===1 ? '1 " . esc_js( __( 'fotka v sade', 'ideanet' ) ) . "' : ids.length+' " . esc_js( __( 'fotiek v sade', 'ideanet' ) ) . "'
				);
			}

			$('.ideanet-gallery').each(function(){
				$(this).sortable({ items:'.ideanet-gallery__item', tolerance:'pointer', update:function(){ zapis($(this)); } });
			});

			$(document).on('click','.ideanet-gallery__x',function(e){
				e.preventDefault();
				var wrap=$(this).closest('.ideanet-gallery');
				$(this).closest('.ideanet-gallery__item').remove();
				zapis(wrap);
			});

			$(document).on('click','.ideanet-gallery-pick',function(e){
				e.preventDefault();
				var cil=$(this).data('target');
				var wrap=$('.ideanet-gallery').filter(function(){ return $(this).data('target')===cil; });
				var frame=wp.media({title:'" . esc_js( __( 'Vyberte fotky do sady', 'ideanet' ) ) . "',library:{type:'image'},multiple:'add',button:{text:'" . esc_js( __( 'Pridať do sady', 'ideanet' ) ) . "'}});
				frame.on('select',function(){
					frame.state().get('selection').each(function(att){
						var a=att.toJSON();
						if (wrap.find('.ideanet-gallery__item[data-id=\"'+a.id+'\"]').length) { return; }
						var src=(a.sizes&&a.sizes.thumbnail)?a.sizes.thumbnail.url:a.url;
						wrap.append('<span class=\"ideanet-gallery__item\" data-id=\"'+a.id+'\"><img src=\"'+src+'\" alt=\"\"><button type=\"button\" class=\"ideanet-gallery__x\">&times;</button></span>');
					});
					if (!wrap.data('ui-sortable')) { wrap.sortable({ items:'.ideanet-gallery__item', tolerance:'pointer', update:function(){ zapis($(this)); } }); }
					zapis(wrap);
				});
				frame.open();
			});
		});"
	);
}
add_action( 'admin_enqueue_scripts', 'ideanet_admin_assets' );

/**
 * Stĺpce v zozname balíkov, nech je cena vidieť na prvý pohľad.
 *
 * @param array $cols Pôvodné stĺpce.
 * @return array
 */
function ideanet_balik_columns( $cols ) {
	$new = array();
	foreach ( $cols as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['ideanet_group'] = __( 'Záložka', 'ideanet' );
			$new['ideanet_price'] = __( 'Cena', 'ideanet' );
		}
	}
	return $new;
}
add_filter( 'manage_ideanet_balik_posts_columns', 'ideanet_balik_columns' );

/**
 * Obsah vlastných stĺpcov.
 *
 * @param string $col     Názov stĺpca.
 * @param int    $post_id ID položky.
 */
function ideanet_balik_column( $col, $post_id ) {
	if ( 'ideanet_price' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_ideanet_price', true ) );
	}
	if ( 'ideanet_group' === $col ) {
		$map = array(
			'firmy'    => __( 'Pre firmy', 'ideanet' ),
			'agentury' => __( 'Pre agentúry', 'ideanet' ),
			'eventy'   => __( 'Eventy', 'ideanet' ),
		);
		$g = (string) get_post_meta( $post_id, '_ideanet_group', true );
		echo esc_html( isset( $map[ $g ] ) ? $map[ $g ] : $g );
	}
}
add_action( 'manage_ideanet_balik_posts_custom_column', 'ideanet_balik_column', 10, 2 );

/**
 * Položky týchto typov sa v zozname radia podľa poradia, nie podľa dátumu.
 *
 * @param WP_Query $q Dotaz.
 */
function ideanet_admin_order( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$type = $q->get( 'post_type' );
	if ( is_string( $type ) && isset( ideanet_post_types()[ $type ] ) && 'ideanet_dopyt' !== $type && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', 'menu_order title' );
		$q->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'ideanet_admin_order' );

/**
 * Načíta položky jedného typu v správnom poradí.
 *
 * @param string $type  Typ obsahu.
 * @param int    $limit Koľko najviac.
 * @param array  $meta  Prípadný filter podľa vlastného poľa.
 * @return WP_Post[]
 */
function ideanet_items( $type, $limit = 50, $meta = array() ) {
	$args = array(
		'post_type'      => $type,
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'ASC',
		),
		'no_found_rows'  => true,
	);

	if ( $meta ) {
		$args['meta_query'] = array( $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- malé množstvo položiek, bez taxonómie to inak nejde.
	}

	return get_posts( $args );
}

/**
 * Hodnota vlastného poľa položky.
 *
 * @param int    $id  ID položky.
 * @param string $key Kľúč bez predpony.
 * @return string
 */
function ideanet_meta( $id, $key ) {
	return (string) get_post_meta( $id, '_ideanet_' . $key, true );
}

/**
 * Obrázok položky — najprv náhľadový, potom náhradné pole.
 *
 * @param WP_Post $post Položka.
 * @param string  $size Veľkosť obrázka.
 * @param string  $key  Kľúč náhradného poľa.
 * @return string URL, alebo prázdny reťazec.
 */
function ideanet_image( $post, $size, $key ) {
	$url = get_the_post_thumbnail_url( $post, $size );
	if ( $url ) {
		return $url;
	}

	return ideanet_media_url( ideanet_meta( $post->ID, $key ) );
}

/**
 * Zoznam ID fotiek zo sady, len kladné čísla a bez duplicít.
 *
 * @param string $raw Uložená hodnota, čísla oddelené čiarkou.
 * @return int[]
 */
function ideanet_gallery_ids( $raw ) {
	$out = array();

	foreach ( explode( ',', (string) $raw ) as $part ) {
		$id = (int) trim( $part );
		if ( $id > 0 && ! in_array( $id, $out, true ) ) {
			$out[] = $id;
		}
	}

	return $out;
}

/**
 * Jedna miniatúra v poli so sadou fotiek.
 *
 * @param int $id ID prílohy.
 */
function ideanet_gallery_thumb( $id ) {
	$src = wp_get_attachment_image_url( $id, 'thumbnail' );
	if ( ! $src ) {
		return;
	}

	printf(
		'<span class="ideanet-gallery__item" data-id="%1$d"><img src="%2$s" alt=""><button type="button" class="ideanet-gallery__x" aria-label="%3$s">&times;</button></span>',
		(int) $id,
		esc_url( $src ),
		esc_attr__( 'Vyhodiť zo sady', 'ideanet' )
	);
}

/**
 * Počet fotiek aj so správne skloneným slovom.
 *
 * Slovenčina skloňuje podľa počtu — 1 fotka, 2 až 4 fotky, 5 a viac
 * fotiek. V CSS sa to spraviť nedá, preto to rieši PHP.
 *
 * @param int $n Počet fotiek.
 * @return string Napríklad „10 fotiek“.
 */
function ideanet_pocet_fotiek( $n ) {
	$n = (int) $n;

	if ( 1 === $n ) {
		$slovo = __( 'fotka', 'ideanet' );
	} elseif ( $n >= 2 && $n <= 4 ) {
		$slovo = __( 'fotky', 'ideanet' );
	} else {
		$slovo = __( 'fotiek', 'ideanet' );
	}

	return $n . ' ' . $slovo;
}

/**
 * Fotky jednej sady pripravené na výpis.
 *
 * @param int $post_id ID sady.
 * @param int $limit   Najviac koľko.
 * @return array<int,array{full:string,thumb:string,alt:string,w:int,h:int}>
 */
function ideanet_gallery_photos( $post_id, $limit = 40 ) {
	$out = array();

	foreach ( array_slice( ideanet_gallery_ids( ideanet_meta( $post_id, 'fotky' ) ), 0, $limit ) as $id ) {
		$full = wp_get_attachment_image_src( $id, 'large' );
		if ( ! $full ) {
			continue;
		}
		$thumb = wp_get_attachment_image_url( $id, 'medium' );
		$out[] = array(
			'full'  => $full[0],
			'thumb' => $thumb ? $thumb : $full[0],
			'alt'   => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'w'     => (int) $full[1],
			'h'     => (int) $full[2],
		);
	}

	return $out;
}

/**
 * Skratky v hornej lište, keď je človek prihlásený a pozerá sa na web.
 *
 * Texty sa dajú upraviť ceruzkou priamo v náhľade, ale videá, vizuály
 * a ceny sú položky obsahu — tie ceruzku nemajú. Odtiaľto sa k nim
 * dostane na jedno kliknutie.
 *
 * @param WP_Admin_Bar $bar Horná lišta.
 */
function ideanet_admin_bar( $bar ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$bar->add_node(
		array(
			'id'    => 'ideanet',
			'title' => 'IDEANET',
			'href'  => admin_url( 'admin.php?page=ideanet' ),
		)
	);

	$polozky = array(
		'texty'   => array( __( 'Upraviť texty a ceny', 'ideanet' ), admin_url( 'customize.php' ) ),
		'reels'   => array( __( 'Reels (portfólio)', 'ideanet' ), admin_url( 'edit.php?post_type=ideanet_reel' ) ),
		'vizualy' => array( __( 'Vizuály a identita', 'ideanet' ), admin_url( 'edit.php?post_type=ideanet_vizual' ) ),
		'baliky'  => array( __( 'Balíky a ceny', 'ideanet' ), admin_url( 'edit.php?post_type=ideanet_balik' ) ),
		'menu'    => array( __( 'Menu', 'ideanet' ), admin_url( 'nav-menus.php' ) ),
		'dopyty'  => array( __( 'Prijaté dopyty', 'ideanet' ), admin_url( 'edit.php?post_type=ideanet_dopyt' ) ),
	);

	foreach ( $polozky as $id => $polozka ) {
		$bar->add_node(
			array(
				'parent' => 'ideanet',
				'id'     => 'ideanet-' . $id,
				'title'  => $polozka[0],
				'href'   => $polozka[1],
			)
		);
	}
}
add_action( 'admin_bar_menu', 'ideanet_admin_bar', 80 );
