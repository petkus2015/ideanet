<?php
/**
 * Základné nastavenie témy, štýly, skripty a menu.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Čo téma podporuje.
 */
function ideanet_setup() {
	load_theme_textdomain( 'ideanet', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 160,
			'width'       => 160,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'ideanet-poster', 1080, 1920, true );
	add_image_size( 'ideanet-vizual', 1080, 1350, true );

	register_nav_menus(
		array(
			'primary' => __( 'Hlavné menu (hore)', 'ideanet' ),
			'tabbar'  => __( 'Spodná lišta na mobile — najviac 4 položky', 'ideanet' ),
			'mobile'  => __( 'Rozbalovacie menu na mobile (Viac)', 'ideanet' ),
			'footer'  => __( 'Pätička', 'ideanet' ),
		)
	);
}
add_action( 'after_setup_theme', 'ideanet_setup' );

/**
 * Šírka obsahu pre vložené médiá.
 */
function ideanet_content_width() {
	$GLOBALS['content_width'] = 1180;
}
add_action( 'after_setup_theme', 'ideanet_content_width', 0 );

/**
 * Štýly a skripty.
 */
function ideanet_assets() {
	$ver = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'ideanet-fonts',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap',
		array(),
		null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- verziu určuje Google Fonts.
	);

	wp_enqueue_style( 'ideanet', get_stylesheet_uri(), array( 'ideanet-fonts' ), $ver );

	wp_enqueue_script( 'ideanet', get_theme_file_uri( 'assets/js/app.js' ), array(), $ver, true );
	wp_localize_script( 'ideanet', 'IDEANET', ideanet_js_data() );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ideanet_assets' );

/**
 * Predpojenie na Google Fonts, aby sa písma načítali skôr.
 *
 * @param array  $urls          Doterajšie URL.
 * @param string $relation_type Typ vzťahu.
 * @return array
 */
function ideanet_preconnect( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href' => 'https://fonts.gstatic.com',
			'crossorigin',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'ideanet_preconnect', 10, 2 );

/**
 * Dáta, ktoré potrebuje JavaScript — portfólio a čísla kalkulačky.
 *
 * @return array
 */
function ideanet_js_data() {
	$reels = array();

	foreach ( ideanet_items( 'ideanet_reel', 24 ) as $post ) {
		$poster = ideanet_image( $post, 'ideanet-poster', 'poster' );

		$reels[] = array(
			'id'     => 'reel-' . $post->ID,
			'cat'    => ideanet_meta( $post->ID, 'cat' ),
			'title'  => get_the_title( $post ),
			'desc'   => get_the_excerpt( $post ),
			'dur'    => ideanet_meta( $post->ID, 'dur' ),
			'poster' => $poster ? $poster : '',
			'video'  => ideanet_meta( $post->ID, 'video' ),
			'webm'   => ideanet_meta( $post->ID, 'webm' ),
			'tags'   => array_values( array_filter( array_map( 'trim', preg_split( '/\R/', ideanet_meta( $post->ID, 'tags' ) ) ) ) ),
		);
	}

	$addons = array();
	foreach ( ideanet_pairs( 'calc_addons', 3 ) as $i => $row ) {
		list( $label, $amount, $kind ) = $row;
		$addons[] = array(
			'id'     => 'addon' . $i,
			'label'  => $label,
			'amount' => (float) str_replace( ',', '.', $amount ),
			'kind'   => in_array( $kind, array( 'percento', 'mesacne' ), true ) ? $kind : 'jednorazovo',
		);
	}

	return array(
		'ajax'   => admin_url( 'admin-ajax.php' ),
		'nonce'  => wp_create_nonce( 'ideanet_dopyt' ),
		'reels'  => $reels,
		'texts'  => array(
			'noVideo'   => __( 'Video sem doplníte v administrácii — Reels → upraviť.', 'ideanet' ),
			'sound'     => __( 'Zvuk', 'ideanet' ),
			'expand'    => __( 'Rozbaliť', 'ideanet' ),
			'play'      => __( 'Prehrať ukážku', 'ideanet' ),
			'soundOn'   => __( 'Zapnúť zvuk', 'ideanet' ),
			'soundOff'  => __( 'Vypnúť zvuk', 'ideanet' ),
			'video'     => __( 'Video', 'ideanet' ),
			'errName'   => __( 'Doplňte meno alebo názov značky.', 'ideanet' ),
			'errEmail'  => __( 'Zadajte platný e-mail.', 'ideanet' ),
			'errMsg'    => __( 'Napíšte aspoň pár viet o projekte.', 'ideanet' ),
			'sending'   => __( 'Odosielam…', 'ideanet' ),
			'formErr'   => ideanet_tokens( ideanet_opt( 'form_err' ) ),
			'calcSum'   => __( 'Rozpočet podľa kalkulačky:', 'ideanet' ),
			'calcPkg'   => __( 'Vlastná zostava podľa kalkulačky', 'ideanet' ),
			'total'     => __( 'Spolu (orientačne)', 'ideanet' ),
			'perReel'   => __( 'Vychádza to na %s za jeden Reel.', 'ideanet' ),
			'oneReel'   => __( 'Jeden Reel od natočenia po dodanie.', 'ideanet' ),
			'unit'      => ideanet_opt( 'calc_unit' ),
			'tip'       => ideanet_opt( 'calc_tip' ),
			'travel'    => __( 'Doprava', 'ideanet' ),
			'monthly'   => __( '/ mes.', 'ideanet' ),

			/* Vety kalkulačky. {h}, {f}, {n} a {t} doplní JavaScript. */
			'calcHintDay'      => __( 'Celý deň — som u vás do {h} hodín.', 'ideanet' ),
			'calcHintHalf'     => __( 'Poldeň — som u vás do {h} hodín.', 'ideanet' ),
			'calcHintNone'     => __( 'Natáčanie do {h} — prvý Reel do {f} hodín, každý ďalší pridá hodinu.', 'ideanet' ),
			'calcRowDay'       => __( 'Celý deň do {h} hodín · {n}', 'ideanet' ),
			'calcRowHalf'      => __( 'Poldeň do {h} hodín · {n}', 'ideanet' ),
			'calcRowFirst'     => __( 'Prvý Reel · natáčanie do {h}', 'ideanet' ),
			'calcRowExtraTime' => __( '{n}× Reel navyše · natáčanie +{t}', 'ideanet' ),
			'calcRowExtra'     => __( '{n}× Reel navyše', 'ideanet' ),
		),
		'calc'   => array(
			'first'      => ideanet_num( 'calc_first' ),
			'next'       => ideanet_num( 'calc_next' ),
			'half'       => ideanet_num( 'calc_half' ),
			'halfReels'  => (int) ideanet_num( 'calc_half_reels' ),
			'halfHours'  => (int) ideanet_num( 'calc_half_hours' ),
			'day'        => ideanet_num( 'calc_day' ),
			'dayReels'   => (int) ideanet_num( 'calc_day_reels' ),
			'dayHours'   => (int) ideanet_num( 'calc_day_hours' ),
			'firstHours' => (int) ideanet_num( 'calc_first_hours' ),
			'kmRate'     => ideanet_num( 'calc_km_rate' ),
			'tipFrom'    => (int) ideanet_num( 'calc_tip_from' ),
			'addons'     => $addons,
		),
	);
}

/**
 * Vlastné pole pri položke menu — ikona do spodnej lišty.
 *
 * @param int $item_id ID položky menu.
 */
function ideanet_menu_item_field( $item_id ) {
	$icons = array(
		''           => __( '— automaticky podľa odkazu —', 'ideanet' ),
		'portfolio'  => __( 'Portfólio (prehrávač)', 'ideanet' ),
		'vizualy'    => __( 'Vizuály (obrázok)', 'ideanet' ),
		'sluzby'     => __( 'Služby (zoznam)', 'ideanet' ),
		'cennik'     => __( 'Cenník (cenovka)', 'ideanet' ),
		'kalkulacka' => __( 'Kalkulačka', 'ideanet' ),
		'podmienky'  => __( 'Podmienky (dokument)', 'ideanet' ),
		'kontakt'    => __( 'Kontakt (obálka)', 'ideanet' ),
	);

	$value = get_post_meta( $item_id, '_ideanet_icon', true );
	?>
	<p class="field-ideanet-icon description description-wide">
		<label for="ideanet-icon-<?php echo esc_attr( $item_id ); ?>">
			<?php esc_html_e( 'Ikona v spodnej lište na mobile', 'ideanet' ); ?><br>
			<select id="ideanet-icon-<?php echo esc_attr( $item_id ); ?>" name="ideanet_icon[<?php echo esc_attr( $item_id ); ?>]">
				<?php foreach ( $icons as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'ideanet_menu_item_field' );

/**
 * Uloží ikonu položky menu.
 *
 * @param int $menu_id         ID menu.
 * @param int $menu_item_db_id ID položky.
 */
function ideanet_menu_item_save( $menu_id, $menu_item_db_id ) {
	/* Táto akcia sa spúšťa aj programovo (napríklad pri zakladaní
	   ukážkového menu), vtedy žiadny formulár neexistuje. Preto sa tu
	   nesmie volať check_admin_referer — ten pri chýbajúcom nonce rovno
	   ukončí beh stránky. */
	if ( ! isset( $_POST['ideanet_icon'][ $menu_item_db_id ] ) ) {
		return;
	}

	$nonce = isset( $_POST['update-nav-menu-nonce'] ) ? sanitize_key( wp_unslash( $_POST['update-nav-menu-nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'update-nav_menu' ) ) {
		return;
	}

	$value = sanitize_key( wp_unslash( $_POST['ideanet_icon'][ $menu_item_db_id ] ) );

	if ( $value ) {
		update_post_meta( $menu_item_db_id, '_ideanet_icon', $value );
	} else {
		delete_post_meta( $menu_item_db_id, '_ideanet_icon' );
	}
}
add_action( 'wp_update_nav_menu_item', 'ideanet_menu_item_save', 10, 2 );

/**
 * Odhadne ikonu z cieľa odkazu, keď ju redaktor nevybral.
 *
 * @param WP_Post $item Položka menu.
 * @return string
 */
function ideanet_menu_icon_for( $item ) {
	$icon = get_post_meta( $item->ID, '_ideanet_icon', true );
	if ( $icon ) {
		return $icon;
	}

	$url = strtolower( (string) $item->url );
	foreach ( array( 'portfolio', 'vizualy', 'sluzby', 'cennik', 'kalkulacka', 'podmienky', 'kontakt' ) as $key ) {
		if ( false !== strpos( $url, $key ) ) {
			return $key;
		}
	}

	return 'portfolio';
}
