<?php
/**
 * Stránka „Inzerujte u nás“: interaktívny graf, ponuka a kontaktný formulár.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dáta grafu z Customizeru: riadok „mesiac|hodnota“.
 *
 * @return array<int, array{label:string,value:int}>
 */
function relife_adv_default_data(): string {
	return "aug 2026|640\nsep 2026|990\nokt 2026|1500";
}

function relife_adv_default_intro(): string {
	return 'Publikujeme overené a zaujímavé články, optimalizované pre odpovede vyhľadávačov a generatívnej AI. Vaša značka sa tak objaví tam, kde ľudia hľadajú odpovede, a naša viditeľnosť rastie z mesiaca na mesiac.';
}

/**
 * Hodnota nastavenia stránky (pole pri úprave stránky), inak predvolená.
 */
function relife_adv_meta( string $key, string $default = '' ): string {
	$id = (int) get_queried_object_id();
	if ( $id ) {
		$v = get_post_meta( $id, '_rl_adv_' . $key, true );
		if ( is_string( $v ) && '' !== trim( $v ) ) {
			return $v;
		}
	}
	return $default;
}

function relife_adv_data(): array {
	$out = array();
	foreach ( preg_split( '/\R/', relife_adv_meta( 'data', relife_adv_default_data() ) ) ?: array() as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( 2 === count( $parts ) && '' !== $parts[0] && is_numeric( $parts[1] ) ) {
			$out[] = array( 'label' => $parts[0], 'value' => (int) $parts[1] );
		}
	}
	return (array) apply_filters( 'relife_adv_data', $out );
}

function relife_adv_enqueue(): void {
	if ( is_page_template( 'template-advertise.php' ) ) {
		wp_enqueue_script( 'relife-advertise', RELIFE_URI . '/assets/js/advertise.js', array(), RELIFE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'relife_adv_enqueue' );

/**
 * Spracovanie formulára (bez pluginu): kontrola, ochrana pred spamom, e-mail redakcii.
 */
function relife_adv_submit(): void {
	$back = wp_get_referer() ?: home_url( '/' );
	if ( ! isset( $_POST['relife_adv_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['relife_adv_nonce'] ) ), 'relife_adv' ) || ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( add_query_arg( 'adv', 'err', $back ) . '#kontakt' );
		exit;
	}
	$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$company = sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) );
	$topic   = sanitize_text_field( wp_unslash( $_POST['topic'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$key = 'relife_adv_' . md5( $ip );
	if ( '' === $name || ! is_email( $email ) || '' === $message || get_transient( $key ) ) {
		wp_safe_redirect( add_query_arg( 'adv', 'err', $back ) . '#kontakt' );
		exit;
	}
	set_transient( $key, 1, MINUTE_IN_SECONDS );

	$pid = absint( $_POST['page_id'] ?? 0 );
	$to  = $pid ? sanitize_email( (string) get_post_meta( $pid, '_rl_adv_email', true ) ) : '';
	$to = is_email( $to ) ? $to : (string) get_option( 'admin_email' );
	$ok = wp_mail(
		$to,
		sprintf( '[RELIFE inzercia] %s – %s', $topic ?: 'Dopyt', $name ),
		sprintf( "Meno: %s\nFirma: %s\nE-mail: %s\nZáujem: %s\n\n%s", $name, $company, $email, $topic, $message ),
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);
	wp_safe_redirect( add_query_arg( 'adv', $ok ? 'ok' : 'err', $back ) . '#kontakt' );
	exit;
}
add_action( 'admin_post_nopriv_relife_adv', 'relife_adv_submit' );
add_action( 'admin_post_relife_adv', 'relife_adv_submit' );

/**
 * Pole pri úprave stránky (Stránky → Inzerujte u nás): úvod, údaje grafu, e-mail.
 */
function relife_adv_metabox(): void {
	add_meta_box(
		'relife_adv',
		__( 'Inzerujte u nás: úvod, graf a kontakt', 'relife' ),
		static function ( WP_Post $post ): void {
			wp_nonce_field( 'relife_adv_meta', 'relife_adv_meta_nonce' );
			$intro = (string) get_post_meta( $post->ID, '_rl_adv_intro', true );
			$data  = (string) get_post_meta( $post->ID, '_rl_adv_data', true );
			$email = (string) get_post_meta( $post->ID, '_rl_adv_email', true );
			?>
			<p><em><?php esc_html_e( 'Platí pre stránku so šablónou „Inzerujte u nás“. Texty sekcií „Prečo publikovať u nás“ a „Ako sa môžeme spojiť“ upravujete priamo v editore stránky vyššie.', 'relife' ); ?></em></p>
			<p><label for="rl_adv_intro"><strong><?php esc_html_e( 'Úvodný text pod nadpisom', 'relife' ); ?></strong></label>
			<textarea id="rl_adv_intro" name="rl_adv_intro" rows="3" class="widefat" placeholder="<?php echo esc_attr( relife_adv_default_intro() ); ?>"><?php echo esc_textarea( $intro ); ?></textarea></p>
			<p><label for="rl_adv_data"><strong><?php esc_html_e( 'Údaje grafu (jeden mesiac na riadok: mesiac|hodnota)', 'relife' ); ?></strong></label>
			<textarea id="rl_adv_data" name="rl_adv_data" rows="6" class="widefat" placeholder="<?php echo esc_attr( relife_adv_default_data() ); ?>"><?php echo esc_textarea( $data ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Zobrazenia vo funkciách generatívnej AI (Google Search Console), napr. nov 2026|2100. Prázdne = predvolené údaje.', 'relife' ); ?></span></p>
			<p><label for="rl_adv_email"><strong><?php esc_html_e( 'E-mail pre dopyty', 'relife' ); ?></strong></label>
			<input type="email" id="rl_adv_email" name="rl_adv_email" value="<?php echo esc_attr( $email ); ?>" class="widefat" placeholder="<?php echo esc_attr( (string) get_option( 'admin_email' ) ); ?>">
			<span class="description"><?php esc_html_e( 'Kam sa pošle formulár. Prázdne = e-mail správcu webu. Na stránke sa nezobrazuje.', 'relife' ); ?></span></p>
			<?php
		},
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'relife_adv_metabox' );

function relife_adv_meta_save( int $post_id ): void {
	if ( ! isset( $_POST['relife_adv_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['relife_adv_meta_nonce'] ) ), 'relife_adv_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = array(
		'intro' => 'sanitize_textarea_field',
		'data'  => 'sanitize_textarea_field',
		'email' => 'sanitize_email',
	);
	foreach ( $fields as $k => $cb ) {
		$val = isset( $_POST[ 'rl_adv_' . $k ] ) ? (string) call_user_func( $cb, wp_unslash( $_POST[ 'rl_adv_' . $k ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === trim( $val ) ) {
			delete_post_meta( $post_id, '_rl_adv_' . $k );
		} else {
			update_post_meta( $post_id, '_rl_adv_' . $k, $val );
		}
	}
}
add_action( 'save_post_page', 'relife_adv_meta_save' );

/**
 * Predvolený obsah stránky (bloky), ktorý upravujete v editore stránky.
 */
function relife_adv_default_content(): string {
	$card = static function ( string $title, string $text, string $n = '' ): string {
		$num = '' !== $n ? "<!-- wp:paragraph {\"className\":\"rl-adv-why__n\"} -->\n<p class=\"rl-adv-why__n\">{$n}</p>\n<!-- /wp:paragraph -->\n\n" : '';
		return "<!-- wp:group {\"className\":\"rl-adv-offer\"} -->\n<div class=\"wp-block-group rl-adv-offer\">{$num}<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">{$title}</h3>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n\n";
	};
	$h2 = static fn( string $t ): string => "<!-- wp:heading {\"className\":\"rl-sechead__title\"} -->\n<h2 class=\"wp-block-heading rl-sechead__title\">{$t}</h2>\n<!-- /wp:heading -->\n\n";

	$why = $card( 'Overené', 'Tvrdenia podkladáme zdrojmi a uvádzame ich pod článkom. Čitateľ vie, odkiaľ informácia pochádza, a vaša značka stojí vedľa dôveryhodného obsahu.', '01' )
		. $card( 'Zaujímavé', 'Píšeme o témach, ktoré ľudia naozaj hľadajú: technológie, zdravie, peniaze, cestovanie a štýl. Prehľadne, bez zbytočnej vaty.', '02' )
		. $card( 'Optimalizované pre AEO', 'Každý článok začína odpoveďou v skratke, má jasnú štruktúru a štruktúrované dáta. Vďaka tomu ho Google a generatívne AI ľahko citujú a zobrazujú.', '03' )
		. $card( 'Transparentné', 'Spoluprácu vždy jasne označíme. Čitatelia nám veria, a preto veria aj partnerom, ktorých odporúčame.', '04' );
	$offers = $card( 'Sponzorovaný článok', 'Redakčne spracovaný obsah v štýle RELIFE, jasne označený ako spolupráca.' )
		. $card( 'Bannery a reklamné plochy', 'Reklama v texte článkov a na úvodnej stránke, ktorá nenarúša čítanie.' )
		. $card( 'Newsletter Weekly', 'Zmienka alebo odporúčanie vo výbere týždňa pre naše čitateľky a čitateľov.' );

	return $h2( 'Prečo publikovať u nás' )
		. "<!-- wp:paragraph {\"className\":\"rl-muted rl-lead\"} -->\n<p class=\"rl-muted rl-lead\">Nepíšeme do šuflíka. Každý článok je overený, zaujímavý a pripravený tak, aby ho našli ľudia aj odpoveďové systémy.</p>\n<!-- /wp:paragraph -->\n\n"
		. "<!-- wp:group {\"className\":\"rl-grid rl-grid--4 rl-adv-why\"} -->\n<div class=\"wp-block-group rl-grid rl-grid--4 rl-adv-why\">" . $why . "</div>\n<!-- /wp:group -->\n\n"
		. $h2( 'Ako sa môžeme spojiť' )
		. "<!-- wp:group {\"className\":\"rl-grid rl-grid--3 rl-adv-offers\"} -->\n<div class=\"wp-block-group rl-grid rl-grid--3 rl-adv-offers\">" . $offers . "</div>\n<!-- /wp:group -->";
}


/**
 * Stránku „Inzerujte u nás“ vytvorí raz (pri aktivácii alebo po aktualizácii).
 */
function relife_create_adv_page(): void {
	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'template-advertise.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $existing ) {
		return;
	}
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Inzerujte u nás', 'post_name' => 'inzercia', 'post_content' => relife_adv_default_content() ) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( (int) $id, '_wp_page_template', 'template-advertise.php' );
	}
}
add_action( 'after_switch_theme', 'relife_create_adv_page' );

function relife_maybe_create_adv_page(): void {
	if ( get_option( 'relife_adv_page_done_2' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	relife_create_adv_page();
	// Stránka z verzie 1.17.0 má prázdny obsah: doplň texty, aby sa dali upravovať v editore.
	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-advertise.php' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $pages && '' === trim( $pages[0]->post_content ) ) {
		wp_update_post( array( 'ID' => $pages[0]->ID, 'post_content' => relife_adv_default_content() ) );
	}
	update_option( 'relife_adv_page_done_2', 1, false );
}
add_action( 'admin_init', 'relife_maybe_create_adv_page' );

/**
 * Adresa stránky „Inzerujte u nás“ alebo prázdny reťazec, ak ešte neexistuje.
 */
function relife_adv_url(): string {
	$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'template-advertise.php', 'number' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	return $pages ? (string) get_permalink( $pages[0] ) : '';
}
