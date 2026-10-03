<?php
/**
 * Nastavenia → Mediálna gramotnosť.
 *
 * @package MedialnaGramotnost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nastavenia s predvolenými hodnotami.
 */
function mg_options() {
	$defaults = array(
		'alert_bar'       => 0,
		'alert_text'      => 'Prišli ste o peniaze alebo údaje? <b>Hneď volajte svojej banke</b> a polícii na <b>158</b>.',
		'alert_link_text' => 'Čo robiť krok za krokom →',
		'alert_page'      => 0,
		'a11y_floating'   => 0,
		'load_fonts'      => 1,
		'theme_fonts'     => 0,
		'color_primary'   => '',
		'color_marker'    => '',
		'color_dark'      => '',
	);
	$opt = get_option( 'mg_options', array() );
	return wp_parse_args( is_array( $opt ) ? $opt : array(), $defaults );
}

function mg_sanitize_options( $in ) {
	$in  = is_array( $in ) ? $in : array();
	$out = array(
		'alert_bar'       => empty( $in['alert_bar'] ) ? 0 : 1,
		'alert_text'      => wp_kses( isset( $in['alert_text'] ) ? $in['alert_text'] : '', mg_inline_tags() ),
		'alert_link_text' => sanitize_text_field( isset( $in['alert_link_text'] ) ? $in['alert_link_text'] : '' ),
		'alert_page'      => absint( isset( $in['alert_page'] ) ? $in['alert_page'] : 0 ),
		'a11y_floating'   => empty( $in['a11y_floating'] ) ? 0 : 1,
		'load_fonts'      => empty( $in['load_fonts'] ) ? 0 : 1,
		'theme_fonts'     => empty( $in['theme_fonts'] ) ? 0 : 1,
	);
	foreach ( array( 'color_primary', 'color_marker', 'color_dark' ) as $k ) {
		$out[ $k ] = sanitize_hex_color( isset( $in[ $k ] ) ? $in[ $k ] : '' );
		$out[ $k ] = $out[ $k ] ? $out[ $k ] : '';
	}
	return $out;
}

function mg_admin_menu() {
	add_options_page(
		__( 'Mediálna gramotnosť', 'medialna-gramotnost' ),
		__( 'Mediálna gramotnosť', 'medialna-gramotnost' ),
		'manage_options',
		'medialna-gramotnost',
		'mg_settings_page'
	);
}
add_action( 'admin_menu', 'mg_admin_menu' );

function mg_admin_init() {
	register_setting( 'mg_settings', 'mg_options', array( 'sanitize_callback' => 'mg_sanitize_options' ) );
}
add_action( 'admin_init', 'mg_admin_init' );

/**
 * Políčko formulára.
 */
function mg_field_checkbox( $key, $label, $opt ) {
	printf(
		'<label><input type="checkbox" name="mg_options[%1$s]" value="1" %2$s> %3$s</label>',
		esc_attr( $key ),
		checked( ! empty( $opt[ $key ] ), true, false ),
		esc_html( $label )
	);
}

function mg_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$opt     = mg_options();
	$created = get_option( 'mg_demo_pages', array() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mediálna gramotnosť', 'medialna-gramotnost' ); ?></h1>

		<?php if ( isset( $_GET['mg_imported'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Ukážkové stránky sú vytvorené ako koncepty. Nájdete ich nižšie a v zozname Stránky.', 'medialna-gramotnost' ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Ukážkové stránky', 'medialna-gramotnost' ); ?></h2>
		<p><?php echo mg_has_avada() ? esc_html__( 'Avada Builder je aktívny: stránky sa vytvoria z kontajnerov a stĺpcov Avada, takže ich upravíte priamo v builderi.', 'medialna-gramotnost' ) : esc_html__( 'Avada Builder nie je aktívny: stránky sa vytvoria so záložným rozložením [mg_section]. Po aktivácii Avady môžete import spustiť znova.', 'medialna-gramotnost' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mg_import_demo">
			<?php wp_nonce_field( 'mg_import_demo' ); ?>
			<?php submit_button( __( 'Vytvoriť 11 ukážkových stránok (koncepty)', 'medialna-gramotnost' ), 'primary', 'submit', false ); ?>
		</form>
		<?php if ( $created ) : ?>
			<ul>
			<?php
			foreach ( $created as $slug => $id ) {
				if ( get_post( $id ) ) {
					printf( '<li><a href="%1$s">%2$s</a> · <a href="%3$s">%4$s</a></li>', esc_url( get_edit_post_link( $id ) ), esc_html( get_the_title( $id ) ), esc_url( get_permalink( $id ) ), esc_html__( 'zobraziť', 'medialna-gramotnost' ) );
				}
			}
			?>
			</ul>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'mg_settings' ); ?>
			<h2><?php esc_html_e( 'Celý web', 'medialna-gramotnost' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php esc_html_e( 'Lišta pomoci', 'medialna-gramotnost' ); ?></th><td>
					<?php mg_field_checkbox( 'alert_bar', __( 'Zobraziť tmavú lištu „Prišli ste o peniaze?“ nad hlavičkou na všetkých stránkach', 'medialna-gramotnost' ), $opt ); ?>
					<p><input class="large-text" type="text" name="mg_options[alert_text]" value="<?php echo esc_attr( $opt['alert_text'] ); ?>"></p>
					<p><input class="regular-text" type="text" name="mg_options[alert_link_text]" value="<?php echo esc_attr( $opt['alert_link_text'] ); ?>">
					<?php
					wp_dropdown_pages(
						array(
							'name'              => 'mg_options[alert_page]',
							'selected'          => (int) $opt['alert_page'],
							'show_option_none'  => __( '— stránka pre odkaz —', 'medialna-gramotnost' ),
							'option_none_value' => 0,
							'post_status'       => array( 'publish', 'draft' ),
						)
					);
					?>
					</p>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Väčšie písmo', 'medialna-gramotnost' ); ?></th><td>
					<?php mg_field_checkbox( 'a11y_floating', __( 'Plávajúce tlačidlo A+ v pravom dolnom rohu', 'medialna-gramotnost' ), $opt ); ?>
					<p class="description"><?php esc_html_e( 'Zväčšuje písmo prvkov pluginu v troch stupňoch a zapamätá si voľbu. Tlačidlo môžete vložiť aj ako prvok „MG Väčšie písmo“.', 'medialna-gramotnost' ); ?></p>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Písma', 'medialna-gramotnost' ); ?></th><td>
					<p><?php mg_field_checkbox( 'load_fonts', __( 'Načítať písma Bricolage Grotesque, Atkinson Hyperlegible Next a JetBrains Mono z Google Fonts', 'medialna-gramotnost' ), $opt ); ?></p>
					<p><?php mg_field_checkbox( 'theme_fonts', __( 'Použiť písma témy Avada namiesto vlastných (nadpisy a text)', 'medialna-gramotnost' ), $opt ); ?></p>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Farby', 'medialna-gramotnost' ); ?></th><td>
					<p><label><?php esc_html_e( 'Hlavná (tlačidlá, odkazy)', 'medialna-gramotnost' ); ?> <input type="text" name="mg_options[color_primary]" value="<?php echo esc_attr( $opt['color_primary'] ); ?>" placeholder="#2443D6" size="9"></label></p>
					<p><label><?php esc_html_e( 'Zvýrazňovač (varovné znaky)', 'medialna-gramotnost' ); ?> <input type="text" name="mg_options[color_marker]" value="<?php echo esc_attr( $opt['color_marker'] ); ?>" placeholder="#FFE04A" size="9"></label></p>
					<p><label><?php esc_html_e( 'Tmavá (text, tmavé pásy)', 'medialna-gramotnost' ); ?> <input type="text" name="mg_options[color_dark]" value="<?php echo esc_attr( $opt['color_dark'] ); ?>" placeholder="#121A2E" size="9"></label></p>
				</td></tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Prvky', 'medialna-gramotnost' ); ?></h2>
		<p><?php esc_html_e( 'V Avada Builderi ich nájdete v zozname prvkov pod názvom začínajúcim „MG“. Bez Avady ich vložíte ako shortcody.', 'medialna-gramotnost' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Prvok', 'medialna-gramotnost' ); ?></th><th><?php esc_html_e( 'Shortcode', 'medialna-gramotnost' ); ?></th><th><?php esc_html_e( 'Na čo slúži', 'medialna-gramotnost' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( mg_elements() as $tag => $el ) : ?>
				<?php
				if ( mg_parent_of( $tag ) ) {
					continue;
				}
				$code = '[' . $tag . ']' . ( ! empty( $el['child'] ) ? '[' . $el['child'] . ']…[/' . $el['child'] . '][/' . $tag . ']' : ( ! empty( $el['content'] ) ? '…[/' . $tag . ']' : '' ) );
				?>
				<tr><td><strong><?php echo esc_html( $el['name'] ); ?></strong></td><td><code><?php echo esc_html( $code ); ?></code></td><td><?php echo esc_html( isset( $el['desc'] ) ? $el['desc'] : '' ); ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Ako písať ukážky správ', 'medialna-gramotnost' ); ?></h2>
		<table class="widefat striped" style="max-width:820px">
			<tbody>
				<tr><td><code>==text==</code></td><td><?php esc_html_e( 'Varovný znak – zvýrazní sa a očísluje.', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>---</code></td><td><?php esc_html_e( 'Pod týmto riadkom sú vysvetlivky, jedna na riadok, v poradí zvýraznení.', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>&gt; text</code></td><td><?php esc_html_e( 'Vlastná správa (bublina vpravo).', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>! text</code></td><td><?php esc_html_e( 'Systémové upozornenie (WhatsApp).', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>btn: text</code></td><td><?php esc_html_e( 'Tlačidlo v e-maile alebo na webe.', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>img: popis | #farba</code></td><td><?php esc_html_e( 'Obrázok alebo video vo Facebook príspevku.', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>link: doména | nadpis</code></td><td><?php esc_html_e( 'Náhľad odkazu vo Facebook príspevku.', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>stats: vľavo | vpravo</code></td><td><?php esc_html_e( 'Počty reakcií pod príspevkom.', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>foot: text</code></td><td><?php esc_html_e( 'Pätička e-mailu.', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>title: / input: / alert:</code></td><td><?php esc_html_e( 'Webová stránka: nadpis, vstupné pole, vyskakovacie okno.', 'medialna-gramotnost' ); ?></td></tr>
				<tr><td><code>Volajúci: text</code></td><td><?php esc_html_e( 'Riadok prepisu hovoru (typ Telefonát).', 'medialna-gramotnost' ); ?></td></tr>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Spracovanie tlačidla „Vytvoriť ukážkové stránky“.
 */
function mg_handle_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Nemáte oprávnenie.', 'medialna-gramotnost' ) );
	}
	check_admin_referer( 'mg_import_demo' );
	mg_import_demo_pages();
	wp_safe_redirect( admin_url( 'options-general.php?page=medialna-gramotnost&mg_imported=1' ) );
	exit;
}
add_action( 'admin_post_mg_import_demo', 'mg_handle_import' );
