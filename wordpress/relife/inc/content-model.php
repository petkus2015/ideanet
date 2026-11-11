<?php
/**
 * Dátový model: formáty článkov, ankety, doplnkové polia a farby sekcií.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sedem sekcií RELIFE: slug => [názov, popis, farba, podtémy].
 *
 * @return array<string, array{name:string,desc:string,color:string,topics:string}>
 */
function relife_sections(): array {
	$sections = array(
		'trendy' => array( 'name' => 'TRENDY', 'desc' => 'Čo práve hýbe svetom mladých.', 'color' => '#FF3D8B', 'topics' => 'Internet • Popkultúra • Filmy a seriály • Virálne témy • Ľudia • Móda • Hudba' ),
		'tech'   => array( 'name' => 'TECH', 'desc' => 'Technológie bez zbytočného komplikovania.', 'color' => '#3CFFD0', 'topics' => 'AI • Gadgety • Aplikácie • Digitálny život • Budúcnosť' ),
		'health' => array( 'name' => 'HEALTH', 'desc' => 'Telo, myseľ a dlhší kvalitný život.', 'color' => '#A6FF4D', 'topics' => 'Fitness • Výživa • Spánok • Psychika • Longevity' ),
		'money'  => array( 'name' => 'MONEY', 'desc' => 'Ako zarobiť, investovať a mať viac možností.', 'color' => '#FFE14D', 'topics' => 'Osobné financie • Investovanie • Kariéra • Podnikanie • AI nástroje' ),
		'travel' => array( 'name' => 'TRAVEL', 'desc' => 'Miesta, ktoré stoja za to.', 'color' => '#FF7A2B', 'topics' => 'Destinácie • Low-cost cestovanie • Mestá • Zážitky • Sprievodcovia' ),
		'style'  => array( 'name' => 'STYLE', 'desc' => 'Ako žijeme, tvoríme a vyjadrujeme sa.', 'color' => '#8F5CFF', 'topics' => 'Dizajn • Móda • Bývanie • Grooming • Kultúra • Kreativita' ),
		'life'   => array( 'name' => 'LIFE', 'desc' => 'Praktický sprievodca moderným životom.', 'color' => '#F5F5F5', 'topics' => 'Vzťahy • Osobný rozvoj • Produktivita • Psychológia • Každodenné rozhodnutia' ),
	);

	/** @var array<string, array{name:string,desc:string,color:string,topics:string}> $sections */
	$sections = apply_filters( 'relife_sections', $sections );
	return $sections;
}

/**
 * Šesť obsahových formátov.
 *
 * @return array<string, array{name:string,title:string,desc:string}>
 */
function relife_formats(): array {
	return array(
		'quick-take'     => array( 'name' => 'QUICK TAKE', 'title' => 'Hlavné za minútu', 'desc' => 'Krátke a rýchle vysvetlenie aktuálnej témy. Ideálne na mobil a sociálne siete.' ),
		'explained'      => array( 'name' => 'EXPLAINED', 'title' => 'Zložité jednoducho', 'desc' => 'ETF, AI agenti, kortizol či digitálne súkromie, vysvetlené pre ľudí, nie pre expertov.' ),
		'versus'         => array( 'name' => 'VERSUS', 'title' => 'Pomôže rozhodnúť', 'desc' => 'Porovnania aplikácií, produktov, služieb, destinácií či finančných možností.' ),
		'how-to'         => array( 'name' => 'HOW TO', 'title' => 'Krok za krokom', 'desc' => 'Konkrétne návody s praktickým výsledkom.' ),
		'trend-report'   => array( 'name' => 'TREND REPORT', 'title' => 'Hype alebo zmysel?', 'desc' => 'Čo rastie, prečo sa o tom hovorí a či to má reálny význam.' ),
		'ultimate-guide' => array( 'name' => 'ULTIMATE GUIDE', 'title' => 'Všetko na jednom mieste', 'desc' => 'Komplexné tematické stránky, ktoré pravidelne aktualizujeme.' ),
	);
}

/**
 * Taxonómia „Formát" a typ obsahu „Anketa".
 */
function relife_register_content_model(): void {
	register_taxonomy(
		'relife_format',
		array( 'post' ),
		array(
			'labels'            => array(
				'name'          => __( 'Formáty', 'relife' ),
				'singular_name' => __( 'Formát', 'relife' ),
				'menu_name'     => __( 'Formáty', 'relife' ),
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'format' ),
		)
	);

	register_post_type(
		'relife_poll',
		array(
			'labels'       => array(
				'name'          => __( 'Ankety', 'relife' ),
				'singular_name' => __( 'Anketa', 'relife' ),
				'add_new_item'  => __( 'Pridať anketu', 'relife' ),
				'edit_item'     => __( 'Upraviť anketu', 'relife' ),
			),
			'public'       => true,
			'has_archive'  => false,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-chart-bar',
			'supports'     => array( 'title' ),
			'rewrite'      => array( 'slug' => 'anketa' ),
		)
	);

	$post_meta = array(
		'relife_answer'     => 'sanitize_textarea_field',
		'relife_sources'    => 'sanitize_textarea_field',
		'relife_disclosure' => 'sanitize_text_field',
	);
	foreach ( $post_meta as $key => $callback ) {
		register_post_meta(
			'post',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $callback,
				'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
			)
		);
	}

	register_term_meta(
		'category',
		'relife_color',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => static fn( $value ): string => (string) sanitize_hex_color( (string) $value ),
			'auth_callback'     => static fn(): bool => current_user_can( 'manage_categories' ),
		)
	);
}
add_action( 'init', 'relife_register_content_model' );

/* ------------------------------------------------------------------------- *
 * Metaboxy pre článok
 * ------------------------------------------------------------------------- */

function relife_add_metaboxes(): void {
	add_meta_box( 'relife_article', __( 'RELIFE – odpoveď, zdroje, spolupráca', 'relife' ), 'relife_render_article_metabox', 'post', 'normal', 'high' );
	add_meta_box( 'relife_poll_options', __( 'Možnosti ankety', 'relife' ), 'relife_render_poll_metabox', 'relife_poll', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'relife_add_metaboxes' );

function relife_render_article_metabox( WP_Post $post ): void {
	wp_nonce_field( 'relife_article_save', 'relife_article_nonce' );
	$answer     = (string) get_post_meta( $post->ID, 'relife_answer', true );
	$sources    = (string) get_post_meta( $post->ID, 'relife_sources', true );
	$disclosure = (string) get_post_meta( $post->ID, 'relife_disclosure', true );
	?>
	<p>
		<label for="relife_answer"><strong><?php esc_html_e( 'Odpoveď v skratke', 'relife' ); ?></strong></label><br>
		<textarea id="relife_answer" name="relife_answer" rows="3" class="widefat"><?php echo esc_textarea( $answer ); ?></textarea>
		<span class="description"><?php esc_html_e( '1–2 vety s priamou odpoveďou. Zobrazí sa v zvýraznenom bloku na začiatku článku (dobré pre AI vyhľadávanie).', 'relife' ); ?></span>
	</p>
	<p>
		<label for="relife_sources"><strong><?php esc_html_e( 'Zdroje', 'relife' ); ?></strong></label><br>
		<textarea id="relife_sources" name="relife_sources" rows="3" class="widefat"><?php echo esc_textarea( $sources ); ?></textarea>
		<span class="description"><?php esc_html_e( 'Jeden zdroj na riadok.', 'relife' ); ?></span>
	</p>
	<p>
		<label for="relife_disclosure"><strong><?php esc_html_e( 'Označenie spolupráce / affiliate', 'relife' ); ?></strong></label><br>
		<input type="text" id="relife_disclosure" name="relife_disclosure" class="widefat" value="<?php echo esc_attr( $disclosure ); ?>">
	</p>
	<?php
}

function relife_save_article_meta( int $post_id ): void {
	if ( ! isset( $_POST['relife_article_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['relife_article_nonce'] ) ), 'relife_article_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'relife_answer', 'relife_sources' ) as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST[ $key ] ) ) : '';
		update_post_meta( $post_id, $key, $value );
	}
	$disclosure = isset( $_POST['relife_disclosure'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['relife_disclosure'] ) ) : '';
	update_post_meta( $post_id, 'relife_disclosure', $disclosure );
}
add_action( 'save_post_post', 'relife_save_article_meta' );

/* ------------------------------------------------------------------------- *
 * Anketa: možnosti vo formáte „názov | počet hlasov"
 * ------------------------------------------------------------------------- */

function relife_render_poll_metabox( WP_Post $post ): void {
	wp_nonce_field( 'relife_poll_save', 'relife_poll_nonce' );
	$raw    = (string) get_post_meta( $post->ID, 'relife_poll_options', true );
	$status = (string) get_post_meta( $post->ID, 'relife_poll_status', true );
	$status = 'closed' === $status ? 'closed' : 'active';
	$cat    = (string) get_post_meta( $post->ID, 'relife_poll_section', true );
	?>
	<p>
		<label for="relife_poll_options"><strong><?php esc_html_e( 'Možnosti (každá na nový riadok: názov | základný počet hlasov; hlasy čitateľov sa pripočítajú)', 'relife' ); ?></strong></label><br>
		<textarea id="relife_poll_options" name="relife_poll_options" rows="6" class="widefat" placeholder="AI nástroje | 38&#10;Fitness a zdravie | 24"><?php echo esc_textarea( $raw ); ?></textarea>
		<span class="description"><?php esc_html_e( 'Čitateľ hlasuje len vo svojom prehliadači. Počty hlasov tu zadáte sami (napr. z externého nástroja).', 'relife' ); ?></span>
	</p>
	<p>
		<label for="relife_poll_status"><strong><?php esc_html_e( 'Stav', 'relife' ); ?></strong></label>
		<select id="relife_poll_status" name="relife_poll_status">
			<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Aktívna (zobrazí sa na úvodnej stránke)', 'relife' ); ?></option>
			<option value="closed" <?php selected( $status, 'closed' ); ?>><?php esc_html_e( 'Uzavretá (zobrazí sa v starších ankétach)', 'relife' ); ?></option>
		</select>
	</p>
	<p>
		<label for="relife_poll_section"><strong><?php esc_html_e( 'Sekcia (slug kategórie, voliteľné)', 'relife' ); ?></strong></label>
		<input type="text" id="relife_poll_section" name="relife_poll_section" value="<?php echo esc_attr( $cat ); ?>" class="regular-text">
	</p>
	<?php
}

function relife_save_poll_meta( int $post_id ): void {
	if ( ! isset( $_POST['relife_poll_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['relife_poll_nonce'] ) ), 'relife_poll_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$options = isset( $_POST['relife_poll_options'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['relife_poll_options'] ) ) : '';
	update_post_meta( $post_id, 'relife_poll_options', $options );

	$status = isset( $_POST['relife_poll_status'] ) ? sanitize_key( wp_unslash( (string) $_POST['relife_poll_status'] ) ) : 'active';
	update_post_meta( $post_id, 'relife_poll_status', 'closed' === $status ? 'closed' : 'active' );

	$section = isset( $_POST['relife_poll_section'] ) ? sanitize_key( wp_unslash( (string) $_POST['relife_poll_section'] ) ) : '';
	update_post_meta( $post_id, 'relife_poll_section', $section );
}
add_action( 'save_post_relife_poll', 'relife_save_poll_meta' );

/**
 * Spracuje možnosti ankety do poľa.
 *
 * @return array<int, array{label:string,votes:int,pct:int}>
 */
function relife_poll_options( int $poll_id ): array {
	$raw   = (string) get_post_meta( $poll_id, 'relife_poll_options', true );
	$lines = preg_split( '/\r\n|\r|\n/', $raw ) ?: array();
	$out   = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts   = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[]   = array(
			'label' => $parts[0],
			'votes' => isset( $parts[1] ) ? max( 0, (int) $parts[1] ) : 0,
			'pct'   => 0,
		);
	}
	// K základu zadanému v administrácii sa pripočítajú skutočné hlasy čitateľov (pozri inc/polls.php).
	$live = get_post_meta( $poll_id, 'relife_poll_votes', true );
	if ( is_array( $live ) ) {
		foreach ( $out as $i => $opt ) {
			$out[ $i ]['votes'] += isset( $live[ $i ] ) ? max( 0, (int) $live[ $i ] ) : 0;
		}
	}
	$total = array_sum( array_column( $out, 'votes' ) );
	foreach ( $out as $i => $opt ) {
		$out[ $i ]['pct'] = $total > 0 ? (int) round( $opt['votes'] / $total * 100 ) : 0;
	}
	return $out;
}

/* ------------------------------------------------------------------------- *
 * Farba sekcie – pole pri úprave kategórie
 * ------------------------------------------------------------------------- */

function relife_category_color_field( WP_Term $term ): void {
	$color = (string) get_term_meta( $term->term_id, 'relife_color', true );
	wp_nonce_field( 'relife_term_color', 'relife_term_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="relife_color"><?php esc_html_e( 'Farba sekcie (RELIFE)', 'relife' ); ?></label></th>
		<td>
			<input type="text" id="relife_color" name="relife_color" value="<?php echo esc_attr( $color ); ?>" placeholder="#3CFFD0">
			<p class="description"><?php esc_html_e( 'Hex farba, napr. #FF3D8B. Ak je prázdna, použije sa predvolená podľa slugu.', 'relife' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'category_edit_form_fields', 'relife_category_color_field' );

function relife_save_category_color( int $term_id ): void {
	if ( ! isset( $_POST['relife_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['relife_term_nonce'] ) ), 'relife_term_color' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$color = isset( $_POST['relife_color'] ) ? (string) sanitize_hex_color( wp_unslash( (string) $_POST['relife_color'] ) ) : '';
	update_term_meta( $term_id, 'relife_color', $color );
}
add_action( 'edited_category', 'relife_save_category_color' );

/* ------------------------------------------------------------------------- *
 * REST: pole „relife" pri článkoch (karty na stránke Uložené)
 * ------------------------------------------------------------------------- */

function relife_register_rest_fields(): void {
	register_rest_field(
		'post',
		'relife',
		array(
			'get_callback' => static function ( array $post ): array {
				$id      = (int) $post['id'];
				$section = relife_primary_section( $id );
				return array(
					'section'   => $section ? $section->name : '',
					'color'     => $section ? relife_section_color( $section ) : '#3CFFD0',
					'format'    => relife_format_label( $id ),
					'readTime'  => relife_reading_time( $id ),
					'thumbnail' => (string) get_the_post_thumbnail_url( $id, 'relife-card' ),
				);
			},
			'schema'       => array(
				'description' => 'RELIFE card data',
				'type'        => 'object',
			),
		)
	);
}
add_action( 'rest_api_init', 'relife_register_rest_fields' );
