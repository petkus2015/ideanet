<?php
/**
 * IDEANET — Správa obsahu mimo Avada Builder.
 *
 * Opakovateľné polia (Fusion Builder typ 'multiple') môžu na niektorých
 * inštaláciách Avady zlyhať na úrovni JS vrstvy buildera (tlačidlo
 * „Add Item“ sa nezobrazí) — nezávisle od toho, ako je pole zaregistrované
 * v PHP, keďže samotnú repeater UI vykresľuje až klientský skript
 * Fusion Builderu. Táto admin stránka to celé obchádza: obsah sa ukladá
 * do WP options cez vlastný, jednoduchý formulár (žiadna závislosť na
 * Fusion Builder JS) a v render() každého bloku má prednosť pred
 * hodnotami z buildera aj pred vstavanými defaultmi — pozri
 * ideanet_blocks_resolve_items() v helpers.php.
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Centrálny zoznam spravovaných sekcií: kľúč (zároveň časť názvu WP
 * option a id v URL) => definícia polí a generátor defaultného obsahu.
 * Používa ho aj `ideanet_blocks_resolve_items()` v helpers.php.
 *
 * @return array<string, array<string, mixed>>
 */
function ideanet_blocks_content_sections() {
	$text_fields = function ( $keys ) {
		return $keys;
	};
	unset( $text_fields );

	return array(
		'nav_items'        => array(
			'label'    => __( 'Navigácia — položky menu', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_nav_items',
			'fields'   => array(
				array( 'key' => 'label', 'label' => __( 'Text odkazu', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'link', 'label' => __( 'Cieľ (napr. #sluzby)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
			),
		),
		'hero_slides'      => array(
			'label'    => __( 'Hero — posledné realizácie', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_video_items',
			'fields'   => array(
				array( 'key' => 'category', 'label' => __( 'Kategória', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'title', 'label' => __( 'Názov', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'meta', 'label' => __( 'Dĺžka (napr. 0:18)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'poster', 'label' => __( 'Náhľadový obrázok', 'ideanet-avada-blocks' ), 'type' => 'image' ),
				array( 'key' => 'video', 'label' => __( 'Video (MP4)', 'ideanet-avada-blocks' ), 'type' => 'video' ),
				array( 'key' => 'client', 'label' => __( 'Pre koho (firma)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'tags', 'label' => __( 'Tagy — kto je na videu (oddeľte ;)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'natacal', 'label' => __( 'Natáčal', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
				array( 'key' => 'strihal', 'label' => __( 'Strihal', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
				array( 'key' => 'scenar', 'label' => __( 'Písal scenár', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
			),
		),
		'services'         => array(
			'label'    => __( 'Služby — karty', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_services',
			'fields'   => array(
				array( 'key' => 'title', 'label' => __( 'Názov', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'description', 'label' => __( 'Popis', 'ideanet-avada-blocks' ), 'type' => 'textarea' ),
				array( 'key' => 'points', 'label' => __( 'Zoznam bodov (oddeľte ;)', 'ideanet-avada-blocks' ), 'type' => 'textarea' ),
				array( 'key' => 'link_text', 'label' => __( 'Text odkazu', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'link_url', 'label' => __( 'Cieľ odkazu (napr. #video)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
			),
		),
		'carousel_video'   => array(
			'label'    => __( 'Portfólio — Videografia', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_video_items',
			'fields'   => array(
				array( 'key' => 'category', 'label' => __( 'Kategória', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'title', 'label' => __( 'Názov', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'description', 'label' => __( 'Popis', 'ideanet-avada-blocks' ), 'type' => 'textarea' ),
				array( 'key' => 'meta', 'label' => __( 'Štítok (dĺžka/formát)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'poster', 'label' => __( 'Náhľadový obrázok', 'ideanet-avada-blocks' ), 'type' => 'image' ),
				array( 'key' => 'video', 'label' => __( 'Video (MP4)', 'ideanet-avada-blocks' ), 'type' => 'video' ),
				array( 'key' => 'client', 'label' => __( 'Pre koho (firma)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'tags', 'label' => __( 'Tagy — kto je na videu (oddeľte ;)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'natacal', 'label' => __( 'Natáčal', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
				array( 'key' => 'strihal', 'label' => __( 'Strihal', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
				array( 'key' => 'scenar', 'label' => __( 'Písal scenár', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
			),
		),
		'carousel_grafika' => array(
			'label'    => __( 'Portfólio — Grafický dizajn', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_graphic_items',
			'fields'   => array(
				array( 'key' => 'category', 'label' => __( 'Kategória', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'title', 'label' => __( 'Názov', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'description', 'label' => __( 'Popis', 'ideanet-avada-blocks' ), 'type' => 'textarea' ),
				array( 'key' => 'meta', 'label' => __( 'Štítok (formát)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'poster', 'label' => __( 'Obrázok', 'ideanet-avada-blocks' ), 'type' => 'image' ),
			),
		),
		'carousel_social'  => array(
			'label'    => __( 'Portfólio — Sociálne siete', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_video_items',
			'fields'   => array(
				array( 'key' => 'category', 'label' => __( 'Kategória', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'title', 'label' => __( 'Názov', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'description', 'label' => __( 'Popis', 'ideanet-avada-blocks' ), 'type' => 'textarea' ),
				array( 'key' => 'meta', 'label' => __( 'Štítok', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'poster', 'label' => __( 'Náhľadový obrázok', 'ideanet-avada-blocks' ), 'type' => 'image' ),
				array( 'key' => 'video', 'label' => __( 'Video (MP4)', 'ideanet-avada-blocks' ), 'type' => 'video' ),
				array( 'key' => 'client', 'label' => __( 'Pre koho (firma)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'tags', 'label' => __( 'Tagy — kto je na videu (oddeľte ;)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'natacal', 'label' => __( 'Natáčal', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
				array( 'key' => 'strihal', 'label' => __( 'Strihal', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
				array( 'key' => 'scenar', 'label' => __( 'Písal scenár', 'ideanet-avada-blocks' ), 'type' => 'checkbox' ),
			),
		),
		'process_steps'    => array(
			'label'    => __( 'Proces — kroky', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_steps',
			'fields'   => array(
				array( 'key' => 'number', 'label' => __( 'Číslo (napr. 01)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'title', 'label' => __( 'Názov kroku', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'description', 'label' => __( 'Popis', 'ideanet-avada-blocks' ), 'type' => 'textarea' ),
			),
		),
		'pricing_speeds'   => array(
			'label'    => __( 'Ferové ceny — rýchlosti dodania', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_speeds',
			'fields'   => array(
				array( 'key' => 'label', 'label' => __( 'Názov úrovne', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'days_text', 'label' => __( 'Dodanie za (napr. 5–7 pracovných dní)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'surcharge', 'label' => __( 'Príplatok v % (0 pre štandard)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
			),
		),
		'training_formats' => array(
			'label'    => __( 'Školenia — formáty a ceny', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_formats',
			'fields'   => array(
				array( 'key' => 'name', 'label' => __( 'Názov formátu', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'price', 'label' => __( 'Cena (napr. od 490 €)', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'description', 'label' => __( 'Popis', 'ideanet-avada-blocks' ), 'type' => 'textarea' ),
			),
		),
		'testimonials'     => array(
			'label'    => __( 'Referencie — citáty', 'ideanet-avada-blocks' ),
			'defaults' => 'ideanet_blocks_default_testimonials',
			'fields'   => array(
				array( 'key' => 'quote', 'label' => __( 'Citát', 'ideanet-avada-blocks' ), 'type' => 'textarea' ),
				array( 'key' => 'name', 'label' => __( 'Meno', 'ideanet-avada-blocks' ), 'type' => 'text' ),
				array( 'key' => 'role', 'label' => __( 'Pozícia / firma', 'ideanet-avada-blocks' ), 'type' => 'text' ),
			),
		),
	);
}

/**
 * Názov WP option, do ktorej sa ukladá obsah danej sekcie.
 *
 * @param string $section_key Kľúč sekcie (viď ideanet_blocks_content_sections()).
 * @return string
 */
function ideanet_blocks_content_option_name( $section_key ) {
	return 'ideanet_blocks_content_' . $section_key;
}

function ideanet_blocks_content_add_menu() {
	add_menu_page(
		__( 'IDEANET obsah', 'ideanet-avada-blocks' ),
		__( 'IDEANET obsah', 'ideanet-avada-blocks' ),
		'manage_options',
		'ideanet-blocks-content',
		'ideanet_blocks_render_content_page',
		'dashicons-layout',
		58
	);
}
add_action( 'admin_menu', 'ideanet_blocks_content_add_menu' );

function ideanet_blocks_content_enqueue( $hook ) {
	if ( 'toplevel_page_ideanet-blocks-content' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style(
		'ideanet-blocks-admin-content',
		IDEANET_BLOCKS_URL . 'assets/css/admin-content.css',
		array(),
		IDEANET_BLOCKS_VERSION
	);
	wp_enqueue_script(
		'ideanet-blocks-admin-content',
		IDEANET_BLOCKS_URL . 'assets/js/admin-content.js',
		array(),
		IDEANET_BLOCKS_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'ideanet_blocks_content_enqueue' );

/**
 * Sanitizuje a uloží jednu sekciu z $_POST, ak prišla s platným nonce.
 *
 * @param string $section_key Kľúč sekcie.
 * @param array  $fields      Definícia polí danej sekcie.
 */
function ideanet_blocks_content_maybe_save( $section_key, $fields ) {
	$nonce_name = 'ideanet_blocks_content_nonce_' . $section_key;
	if ( empty( $_POST[ $nonce_name ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) ), 'ideanet_blocks_save_' . $section_key ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$post_key = 'ideanet_rows_' . $section_key;
	$raw_rows = isset( $_POST[ $post_key ] ) ? wp_unslash( $_POST[ $post_key ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$clean    = array();

	if ( is_array( $raw_rows ) ) {
		foreach ( $raw_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean_row  = array();
			$has_value  = false;
			foreach ( $fields as $field ) {
				$key   = $field['key'];
				$value = $row[ $key ] ?? '';
				switch ( $field['type'] ) {
					case 'textarea':
						$value = sanitize_textarea_field( $value );
						break;
					case 'checkbox':
						$value = ! empty( $value ) ? 'yes' : 'no';
						break;
					case 'image':
					case 'video':
						$value = esc_url_raw( $value );
						break;
					default:
						$value = sanitize_text_field( $value );
				}
				if ( 'checkbox' !== $field['type'] && '' !== trim( (string) $value ) ) {
					$has_value = true;
				}
				$clean_row[ $key ] = $value;
			}
			if ( $has_value ) {
				$clean[] = $clean_row;
			}
		}
	}

	update_option( ideanet_blocks_content_option_name( $section_key ), $clean );
	add_settings_error(
		'ideanet_blocks_content',
		'ideanet_blocks_content_saved_' . $section_key,
		__( 'Uložené: ', 'ideanet-avada-blocks' ) . $section_key,
		'success'
	);
}

function ideanet_blocks_render_content_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$sections = ideanet_blocks_content_sections();
	foreach ( $sections as $key => $section ) {
		ideanet_blocks_content_maybe_save( $key, $section['fields'] );
	}
	settings_errors( 'ideanet_blocks_content' );
	?>
	<div class="wrap ideanet-admin-content">
		<h1><?php esc_html_e( 'IDEANET — obsah blokov', 'ideanet-avada-blocks' ); ?></h1>
		<p>
			<?php esc_html_e( 'Táto stránka spravuje obsah IDEANET blokov nezávisle od Avada Builderu — funguje aj vtedy, keď sa v builderi nedá použiť tlačidlo „Add Item“ (repeater pole). Len čo v niektorej sekcii uložíte aspoň jednu položku, zobrazí sa na stránke namiesto obsahu z buildera aj namiesto vstavaného ukážkového obsahu.', 'ideanet-avada-blocks' ); ?>
		</p>
		<nav class="ideanet-admin-content__nav">
			<?php foreach ( $sections as $key => $section ) : ?>
				<a href="#ideanet-section-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $section['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php foreach ( $sections as $key => $section ) : ?>
			<?php
			$rows = get_option( ideanet_blocks_content_option_name( $key ), array() );
			if ( ! is_array( $rows ) || ! $rows ) {
				$rows = array();
			}
			?>
			<section class="ideanet-admin-content__section" id="ideanet-section-<?php echo esc_attr( $key ); ?>">
				<h2><?php echo esc_html( $section['label'] ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'ideanet_blocks_save_' . $key, 'ideanet_blocks_content_nonce_' . $key ); ?>
					<div class="ideanet-rows" data-section="<?php echo esc_attr( $key ); ?>">
						<?php foreach ( $rows as $i => $row ) : ?>
							<?php ideanet_blocks_render_content_row( $key, $i, $row, $section['fields'] ); ?>
						<?php endforeach; ?>
					</div>
					<p>
						<button type="button" class="button ideanet-add-row" data-section="<?php echo esc_attr( $key ); ?>">
							<?php esc_html_e( '+ Pridať položku', 'ideanet-avada-blocks' ); ?>
						</button>
					</p>
					<?php submit_button( __( 'Uložiť', 'ideanet-avada-blocks' ), 'primary', 'submit_' . $key ); ?>
				</form>
				<template id="ideanet-tmpl-<?php echo esc_attr( $key ); ?>">
					<?php ideanet_blocks_render_content_row( $key, '__INDEX__', array(), $section['fields'] ); ?>
				</template>
			</section>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Vykreslí jeden riadok (jednu položku) repeatera pre danú sekciu —
 * používa sa aj pre uložené dáta aj ako <template> pre nové riadky v JS.
 *
 * @param string     $section_key Kľúč sekcie.
 * @param int|string $i           Index riadku (alebo '__INDEX__' pre šablónu).
 * @param array      $row         Hodnoty riadku.
 * @param array      $fields      Definícia polí.
 */
function ideanet_blocks_render_content_row( $section_key, $i, $row, $fields ) {
	$name_base = 'ideanet_rows_' . $section_key . '[' . $i . ']';
	?>
	<div class="ideanet-row" data-row>
		<button type="button" class="ideanet-row__remove" aria-label="<?php esc_attr_e( 'Odstrániť položku', 'ideanet-avada-blocks' ); ?>">&times;</button>
		<?php foreach ( $fields as $field ) : ?>
			<?php
			$key   = $field['key'];
			$value = $row[ $key ] ?? '';
			$id    = 'ideanet-' . $section_key . '-' . $i . '-' . $key;
			?>
			<p class="ideanet-field ideanet-field--<?php echo esc_attr( $field['type'] ); ?>">
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
				<?php if ( 'textarea' === $field['type'] ) : ?>
					<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name_base . '[' . $key . ']' ); ?>" rows="2" class="widefat"><?php echo esc_textarea( $value ); ?></textarea>
				<?php elseif ( 'checkbox' === $field['type'] ) : ?>
					<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name_base . '[' . $key . ']' ); ?>" value="1" <?php checked( 'yes', $value ); ?>>
				<?php elseif ( 'image' === $field['type'] || 'video' === $field['type'] ) : ?>
					<span class="ideanet-media-field">
						<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name_base . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="widefat ideanet-media-url" readonly>
						<button type="button" class="button ideanet-media-pick" data-type="<?php echo esc_attr( $field['type'] ); ?>"><?php esc_html_e( 'Vybrať', 'ideanet-avada-blocks' ); ?></button>
						<button type="button" class="button ideanet-media-clear"><?php esc_html_e( 'Zmazať', 'ideanet-avada-blocks' ); ?></button>
						<?php if ( 'image' === $field['type'] ) : ?>
							<span class="ideanet-media-preview"<?php echo $value ? '' : ' hidden'; ?>>
								<img src="<?php echo esc_url( $value ); ?>" alt="">
							</span>
						<?php endif; ?>
					</span>
				<?php else : ?>
					<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name_base . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="widefat">
				<?php endif; ?>
			</p>
		<?php endforeach; ?>
	</div>
	<?php
}
