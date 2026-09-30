<?php
/**
 * Zdieľané pomocné funkcie pre všetky IDEANET bloky.
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalizuje hodnotu repeater parametra (typ 'multiple') na pole riadkov.
 * Fusion Builder ju zvyčajne doručí do render() už ako pole, ale pre istotu
 * zvládne aj JSON reťazec alebo prázdnu hodnotu.
 *
 * @param mixed $raw Surová hodnota z $args.
 * @return array<int, array<string, mixed>>
 */
function ideanet_blocks_multiple( $raw ) {
	if ( is_array( $raw ) ) {
		return $raw;
	}
	if ( is_string( $raw ) && '' !== trim( $raw ) ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
	}
	return array();
}

/**
 * Rozdelí textarea so zoznamom (jedna položka na riadok) na pole.
 * Používa sa pre zoznamy odrážok (ib-ticks), funkcie balíkov a pod.
 *
 * @param string $text Text s riadkami.
 * @return array<int, string>
 */
function ideanet_blocks_lines( $text ) {
	if ( '' === trim( (string) $text ) ) {
		return array();
	}
	$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
	$lines = array_map( 'trim', $lines );
	return array_values(
		array_filter(
			$lines,
			function ( $l ) {
				return '' !== $l;
			}
		)
	);
}

/**
 * Vráti pole preddefinovaných SVG ikon (rovnaký vizuál ako pôvodný web).
 *
 * @return array<string, string>
 */
function ideanet_blocks_icon_set() {
	return array(
		'video'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="13" height="12" rx="2.5"/><path d="M15 10.5l6-3.5v10l-6-3.5z"/></svg>',
		'design'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="9" cy="9.5" r="1.4"/><circle cx="15" cy="9.5" r="1.4"/><path d="M8.5 15c1.8 1.3 5.2 1.3 7 0"/></svg>',
		'megaphone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l5-6 4 4 3-4 6 7"/><path d="M3 21h18"/><circle cx="7.5" cy="5.5" r="2.5"/></svg>',
		'graduate'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-5 10 5-10 5-10-5z"/><path d="M6 11v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/><path d="M22 9v7"/></svg>',
		'calendar'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
		'star'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.7 5.9 6.3.7-4.7 4.4 1.3 6.3L12 17.6 6.4 20.3l1.3-6.3L3 9.6l6.3-.7z"/></svg>',
	);
}

/**
 * Vráti pole možností ikon pre 'select' parameter builderu (kľúč => popisok).
 *
 * @return array<string, string>
 */
function ideanet_blocks_icon_choices() {
	return array(
		'video'     => __( 'Kamera / video', 'ideanet-avada-blocks' ),
		'design'    => __( 'Dizajn / paleta', 'ideanet-avada-blocks' ),
		'megaphone' => __( 'Megafón / komunikácia', 'ideanet-avada-blocks' ),
		'graduate'  => __( 'Školenie / absolvent', 'ideanet-avada-blocks' ),
		'calendar'  => __( 'Kalendár', 'ideanet-avada-blocks' ),
		'star'      => __( 'Hviezda', 'ideanet-avada-blocks' ),
		'custom'    => __( 'Vlastné SVG', 'ideanet-avada-blocks' ),
	);
}

/**
 * Vykreslí SVG ikonu podľa kľúča; pri 'custom' použije poskytnutý raw SVG.
 *
 * @param string $key        Kľúč ikony alebo 'custom'.
 * @param string $custom_svg Vlastný SVG markup (len pri 'custom').
 * @return string Bezpečný SVG markup.
 */
function ideanet_blocks_render_icon( $key, $custom_svg = '' ) {
	if ( 'custom' === $key && '' !== trim( $custom_svg ) ) {
		return wp_kses(
			$custom_svg,
			array(
				'svg'      => array(
					'viewbox'         => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'xmlns'           => true,
				),
				'path'     => array(
					'd'    => true,
					'fill' => true,
				),
				'circle'   => array(
					'cx' => true,
					'cy' => true,
					'r'  => true,
				),
				'rect'     => array(
					'x'      => true,
					'y'      => true,
					'width'  => true,
					'height' => true,
					'rx'     => true,
				),
			)
		);
	}
	$icons = ideanet_blocks_icon_set();
	return $icons[ $key ] ?? $icons['video'];
}

/**
 * Vykreslí spoločný nadpisový blok sekcie (kicker + h2 s dôrazom + podnadpis).
 *
 * @param string $kicker    Malý štítok nad nadpisom.
 * @param string $heading   Prvá časť nadpisu.
 * @param string $emphasis  Zvýraznená (kurzívna) časť nadpisu.
 * @param string $sub       Podnadpis / popisný text.
 * @param string $extra_cls Doplnkové CSS triedy pre <header>.
 * @return string HTML.
 */
function ideanet_blocks_head( $kicker, $heading, $emphasis, $sub, $extra_cls = '' ) {
	if ( '' === trim( $heading ) && '' === trim( $kicker ) && '' === trim( $sub ) ) {
		return '';
	}
	ob_start();
	?>
	<header class="ib-head ib-reveal <?php echo esc_attr( $extra_cls ); ?>">
		<?php if ( '' !== trim( $kicker ) ) : ?>
			<p class="ib-kicker"><?php echo esc_html( $kicker ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== trim( $heading ) || '' !== trim( $emphasis ) ) : ?>
			<h2>
				<?php echo esc_html( $heading ); ?>
				<?php if ( '' !== trim( $emphasis ) ) : ?>
					<em><?php echo esc_html( $emphasis ); ?></em>
				<?php endif; ?>
			</h2>
		<?php endif; ?>
		<?php if ( '' !== trim( $sub ) ) : ?>
			<p class="ib-sub"><?php echo wp_kses_post( $sub ); ?></p>
		<?php endif; ?>
	</header>
	<?php
	return ob_get_clean();
}

/**
 * Vykreslí jednu kartu karuselu (aj hero karusel používa tú istú šablónu).
 *
 * @param array $item {
 *     @type string $mode     'video' alebo 'image'.
 *     @type string $category Štítok kategórie.
 *     @type string $title    Názov.
 *     @type string $desc     Popis (skrytý v hero variante cez CSS).
 *     @type string $meta     Štítok vpravo hore (dĺžka / formát).
 *     @type string $poster   URL náhľadového obrázka.
 *     @type string $video    URL video súboru (len mode = video).
 * }
 * @return string HTML.
 */
function ideanet_blocks_render_tile( $item ) {
	$mode  = 'image' === ( $item['mode'] ?? 'video' ) ? 'image' : 'video';
	$title = $item['title'] ?? '';
	$desc  = $item['desc'] ?? '';
	$cat   = $item['category'] ?? '';
	$meta  = $item['meta'] ?? '';
	$poster = $item['poster'] ?? '';
	$video  = $item['video'] ?? '';

	ob_start();
	?>
	<article class="ib-slide"
		data-title="<?php echo esc_attr( $title ); ?>"
		data-desc="<?php echo esc_attr( $desc ); ?>"
		data-poster="<?php echo esc_url( $poster ); ?>"
		<?php if ( 'video' === $mode && $video ) : ?>
			data-video="<?php echo esc_url( $video ); ?>"
		<?php endif; ?>
		role="option" aria-label="<?php echo esc_attr( $title ); ?>">
		<div class="ib-tile" data-mode="<?php echo esc_attr( $mode ); ?>">
			<?php if ( $poster ) : ?>
				<img src="<?php echo esc_url( $poster ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async">
			<?php endif; ?>
			<?php if ( 'video' === $mode && $video ) : ?>
				<video preload="none" playsinline muted loop poster="<?php echo esc_url( $poster ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
					<source src="<?php echo esc_url( $video ); ?>" type="video/mp4">
				</video>
				<span class="ib-tile__bar"></span>
			<?php endif; ?>
			<span class="ib-tile__shade"></span>
			<?php if ( $cat ) : ?><span class="ib-tile__cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
			<?php if ( $meta ) : ?><span class="ib-tile__meta"><?php echo esc_html( $meta ); ?></span><?php endif; ?>
			<button class="ib-tile__play" data-act="<?php echo 'video' === $mode ? 'play' : 'open'; ?>"
				aria-label="<?php echo esc_attr( ( 'video' === $mode ? __( 'Prehrať ukážku', 'ideanet-avada-blocks' ) : __( 'Zobraziť', 'ideanet-avada-blocks' ) ) . ' ' . $title ); ?>"></button>
		</div>
		<?php if ( $title || $desc ) : ?>
			<div class="ib-slide__meta">
				<?php if ( $title ) : ?><h4><?php echo esc_html( $title ); ?></h4><?php endif; ?>
				<?php if ( $desc ) : ?><p><?php echo esc_html( $desc ); ?></p><?php endif; ?>
			</div>
		<?php endif; ?>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Zabezpečí, aby boli skripty a štýly bloku vložené na stránku len raz.
 */
function ideanet_blocks_uid( $prefix = 'ib' ) {
	static $count = 0;
	++$count;
	return sanitize_html_class( $prefix ) . '-' . $count;
}

function ideanet_blocks_enqueue_assets() {
	if ( wp_style_is( 'ideanet-blocks', 'enqueued' ) ) {
		return;
	}
	wp_enqueue_style(
		'ideanet-blocks-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'ideanet-blocks',
		IDEANET_BLOCKS_URL . 'assets/css/blocks.css',
		array(),
		IDEANET_BLOCKS_VERSION
	);
	wp_enqueue_script(
		'ideanet-blocks',
		IDEANET_BLOCKS_URL . 'assets/js/blocks.js',
		array(),
		IDEANET_BLOCKS_VERSION,
		true
	);
}
