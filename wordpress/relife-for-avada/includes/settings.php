<?php
/**
 * Stránka nastavení: Nastavenia → RELIFE pre Avadu.
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rlav_settings_menu(): void {
	add_options_page(
		__( 'RELIFE pre Avadu', 'relife-avada' ),
		__( 'RELIFE pre Avadu', 'relife-avada' ),
		'manage_options',
		'relife-avada',
		'rlav_settings_page'
	);
}
add_action( 'admin_menu', 'rlav_settings_menu' );

function rlav_register_settings(): void {
	register_setting(
		'rlav_group',
		'rlav_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'rlav_sanitize_options',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'rlav_register_settings' );

/**
 * @param mixed $input
 * @return array<string, mixed>
 */
function rlav_sanitize_options( mixed $input ): array {
	$input  = is_array( $input ) ? $input : array();
	$accent = isset( $input['accent'] ) ? sanitize_hex_color( (string) $input['accent'] ) : null;
	return array(
		'accent'            => $accent ?: '#3CFFD0',
		'tabbar'            => empty( $input['tabbar'] ) ? 0 : 1,
		'fonts'             => empty( $input['fonts'] ) ? 0 : 1,
		'schema'            => empty( $input['schema'] ) ? 0 : 1,
		'single'            => empty( $input['single'] ) ? 0 : 1,
		'dark_avada'        => empty( $input['dark_avada'] ) ? 0 : 1,
		'newsletter_action' => isset( $input['newsletter_action'] ) ? esc_url_raw( (string) $input['newsletter_action'] ) : '',
		'saved_page'        => isset( $input['saved_page'] ) ? absint( $input['saved_page'] ) : 0,
		'polls_page'        => isset( $input['polls_page'] ) ? absint( $input['polls_page'] ) : 0,
	);
}

function rlav_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$result = null;
	if ( isset( $_POST['rlav_demo_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['rlav_demo_nonce'] ) ), 'rlav_demo' ) ) {
		$result = rlav_import_demo();
	}
	$o = rlav_options();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'RELIFE pre Avadu', 'relife-avada' ); ?></h1>

		<?php if ( is_array( $result ) ) : ?>
			<div class="notice notice-success"><p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: sections 2: formats 3: posts 4: polls 5: pages */
						__( 'Hotovo. Nové: sekcie %1$d, formáty %2$d, články %3$d, ankety %4$d, stránky %5$d.', 'relife-avada' ),
						$result['sections'],
						$result['formats'],
						$result['posts'],
						$result['polls'],
						$result['pages']
					)
				);
				?>
			</p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'rlav_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="rlav_accent"><?php esc_html_e( 'Akcentová farba', 'relife-avada' ); ?></label></th>
					<td><input type="text" id="rlav_accent" name="rlav_options[accent]" value="<?php echo esc_attr( (string) $o['accent'] ); ?>" class="regular-text" placeholder="#3CFFD0"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mobilné spodné menu', 'relife-avada' ); ?></th>
					<td><label><input type="checkbox" name="rlav_options[tabbar]" value="1" <?php checked( 1, (int) $o['tabbar'] ); ?>> <?php esc_html_e( 'Zobraziť spodné menu (Domov, Hľadať, Menu, Uložené, Weekly) na mobile', 'relife-avada' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Doplnky v článku', 'relife-avada' ); ?></th>
					<td><label><input type="checkbox" name="rlav_options[single]" value="1" <?php checked( 1, (int) $o['single'] ); ?>> <?php esc_html_e( 'Pridať odpoveď v skratke, čas čítania, uloženie, zdroje a označenie spolupráce', 'relife-avada' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Štruktúrované dáta', 'relife-avada' ); ?></th>
					<td><label><input type="checkbox" name="rlav_options[schema]" value="1" <?php checked( 1, (int) $o['schema'] ); ?>> <?php esc_html_e( 'Vypísať schema.org Article (vypnite, ak ich už generuje SEO plugin)', 'relife-avada' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Písma', 'relife-avada' ); ?></th>
					<td><label><input type="checkbox" name="rlav_options[fonts]" value="1" <?php checked( 1, (int) $o['fonts'] ); ?>> <?php esc_html_e( 'Načítať Bricolage Grotesque a Inter z Google Fonts (pre GDPR odporúčame nahostovať vlastné)', 'relife-avada' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Tmavý režim Avady', 'relife-avada' ); ?></th>
					<td>
						<label><input type="checkbox" name="rlav_options[dark_avada]" value="1" <?php checked( 1, (int) $o['dark_avada'] ); ?>> <?php esc_html_e( 'Použiť tmavé pozadie na bežných prvkoch Avady', 'relife-avada' ); ?></label>
						<p class="description"><?php esc_html_e( 'Jednoduché prekrytie. Správne nastavenie je v Avada → Options → Colors.', 'relife-avada' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="rlav_news"><?php esc_html_e( 'Adresa formulára newslettra', 'relife-avada' ); ?></label></th>
					<td><input type="url" id="rlav_news" name="rlav_options[newsletter_action]" value="<?php echo esc_attr( (string) $o['newsletter_action'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="rlav_saved"><?php esc_html_e( 'Stránka „Uložené“', 'relife-avada' ); ?></label></th>
					<td><?php wp_dropdown_pages( array( 'name' => 'rlav_options[saved_page]', 'id' => 'rlav_saved', 'selected' => (int) $o['saved_page'], 'show_option_none' => __( '— nevybraná —', 'relife-avada' ), 'option_none_value' => '0' ) ); ?><p class="description"><?php esc_html_e( 'Stránka so shortcodom [relife_saved].', 'relife-avada' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="rlav_polls"><?php esc_html_e( 'Stránka „Staršie ankety“', 'relife-avada' ); ?></label></th>
					<td><?php wp_dropdown_pages( array( 'name' => 'rlav_options[polls_page]', 'id' => 'rlav_polls', 'selected' => (int) $o['polls_page'], 'show_option_none' => __( '— nevybraná —', 'relife-avada' ), 'option_none_value' => '0' ) ); ?><p class="description"><?php esc_html_e( 'Stránka so shortcodom [relife_polls].', 'relife-avada' ); ?></p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr>
		<h2><?php esc_html_e( 'Ukážkový obsah', 'relife-avada' ); ?></h2>
		<p><?php esc_html_e( 'Vytvorí 7 sekcií, 6 formátov, ukážkové články a ankety a stránky „Staršie ankety“ a „Uložené“. Nemení nastavenia webu ani menu. Opakované spustenie nič nezduplikuje.', 'relife-avada' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'rlav_demo', 'rlav_demo_nonce' ); ?>
			<?php submit_button( __( 'Importovať ukážkový obsah', 'relife-avada' ), 'secondary' ); ?>
		</form>

		<hr>
		<h2><?php esc_html_e( 'Shortcody pre Avada Builder', 'relife-avada' ); ?></h2>
		<p><?php esc_html_e( 'V Avada Builderi vložte element „Code Block“ alebo „Shortcode“ a doň napíšte:', 'relife-avada' ); ?></p>
		<p><code>[relife_hero]</code> <code>[relife_quick]</code> <code>[relife_today]</code> <code>[relife_explained]</code> <code>[relife_pillars]</code> <code>[relife_versus]</code> <code>[relife_sections]</code> <code>[relife_formats]</code> <code>[relife_guides]</code> <code>[relife_polls]</code> <code>[relife_saved]</code> <code>[relife_newsletter]</code></p>
	</div>
	<?php
}
