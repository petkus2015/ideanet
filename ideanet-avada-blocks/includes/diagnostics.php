<?php
/**
 * Diagnostická stránka (Nástroje → IDEANET bloky).
 *
 * Namiesto ďalšieho kola odhadov „funguje to / nefunguje to" táto
 * stránka na skutočnom serveri priamo overí, čo presne Avada/Fusion
 * Builder vidí — a vypľuje kopírovateľný textový report. Nezávisí
 * na žiadnej neistej internej Avada funkcii, len na štandardných
 * WordPress/PHP introspekčných nástrojoch (shortcode_exists,
 * did_action, defined, function_exists), takže je spoľahlivá
 * naprieč verziami.
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'ideanet_blocks_register_diagnostics_page' );

function ideanet_blocks_register_diagnostics_page() {
	add_management_page(
		__( 'IDEANET bloky — diagnostika', 'ideanet-avada-blocks' ),
		__( 'IDEANET bloky', 'ideanet-avada-blocks' ),
		'manage_options',
		'ideanet-blocks-diagnostics',
		'ideanet_blocks_render_diagnostics_page'
	);
}

/**
 * Skúsi vykresliť shortcode s minimálnymi/testovacími atribútmi a zachytí
 * výsledok aj prípadnú PHP chybu, bez toho aby zhodila celú stránku.
 *
 * @param string $tag  Shortcode tag.
 * @param array  $atts Testovacie atribúty.
 * @return array{ok:bool, len:int, message:string}
 */
function ideanet_blocks_selftest_render( $tag, $atts = array() ) {
	if ( ! shortcode_exists( $tag ) ) {
		return array(
			'ok'      => false,
			'len'     => 0,
			'message' => __( 'shortcode nie je zaregistrovaný (add_shortcode preň nikdy nebehol)', 'ideanet-avada-blocks' ),
		);
	}
	$atts_str = '';
	foreach ( $atts as $k => $v ) {
		$atts_str .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	try {
		$html = do_shortcode( '[' . $tag . $atts_str . ']' );
		$len  = strlen( (string) $html );
		if ( $len < 10 ) {
			return array(
				'ok'      => false,
				'len'     => $len,
				'message' => __( 'vrátil prázdny/takmer prázdny výstup', 'ideanet-avada-blocks' ),
			);
		}
		return array( 'ok' => true, 'len' => $len, 'message' => __( 'v poriadku', 'ideanet-avada-blocks' ) );
	} catch ( \Throwable $e ) {
		return array(
			'ok'      => false,
			'len'     => 0,
			'message' => $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')',
		);
	}
}

function ideanet_blocks_render_diagnostics_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$theme         = wp_get_theme();
	$theme_name    = $theme->get( 'Name' );
	$theme_version = $theme->get( 'Version' );
	$is_avada      = ( false !== stripos( (string) $theme_name, 'avada' ) )
		|| ( false !== stripos( (string) $theme->get( 'Template' ), 'avada' ) );
	$avada_const   = defined( 'AVADA_VERSION' ) ? AVADA_VERSION : null;

	$fb_function_exists = function_exists( 'fusion_builder_map' );
	$fb_class_exists     = class_exists( 'FusionBuilder' );
	$fb_version_const    = defined( 'FUSION_BUILDER_VERSION' ) ? FUSION_BUILDER_VERSION : null;
	$fb_hook_fired       = did_action( 'fusion_builder_before_init' );

	$map    = ideanet_blocks_element_map();
	$rows   = array();
	foreach ( $map as $file => $tag ) {
		$rows[ $tag ] = shortcode_exists( $tag );
	}

	$selftests = array(
		'ideanet_hero'         => ideanet_blocks_selftest_render( 'ideanet_hero', array( 'heading' => 'Test' ) ),
		'ideanet_services'     => ideanet_blocks_selftest_render( 'ideanet_services', array( 'heading' => 'Test' ) ),
		'ideanet_carousel'     => ideanet_blocks_selftest_render( 'ideanet_carousel', array( 'title' => 'Test' ) ),
		'ideanet_process'      => ideanet_blocks_selftest_render( 'ideanet_process', array( 'heading' => 'Test' ) ),
		'ideanet_pricing'      => ideanet_blocks_selftest_render( 'ideanet_pricing', array( 'heading' => 'Test' ) ),
		'ideanet_training'     => ideanet_blocks_selftest_render( 'ideanet_training', array( 'heading' => 'Test' ) ),
		'ideanet_testimonials' => ideanet_blocks_selftest_render( 'ideanet_testimonials', array() ),
		'ideanet_contact'      => ideanet_blocks_selftest_render( 'ideanet_contact', array( 'heading' => 'Test' ) ),
	);

	$css_path = IDEANET_BLOCKS_DIR . 'assets/css/blocks.css';
	$js_path  = IDEANET_BLOCKS_DIR . 'assets/js/blocks.js';

	$ok  = '✅';
	$bad = '❌';
	$unk = '❔';

	// ── poskladanie kopírovateľného textového reportu ──
	$lines   = array();
	$lines[] = 'IDEANET Avada Blocks — diagnostický report';
	$lines[] = 'Vygenerované: ' . current_time( 'Y-m-d H:i:s' );
	$lines[] = '';
	$lines[] = '--- Prostredie ---';
	$lines[] = 'WordPress: ' . get_bloginfo( 'version' );
	$lines[] = 'PHP: ' . PHP_VERSION;
	$lines[] = 'Aktívna téma: ' . $theme_name . ' ' . $theme_version . ( $is_avada ? ' (rozpoznané ako Avada)' : ' (NIE JE rozpoznaná ako Avada!)' );
	$lines[] = 'AVADA_VERSION konštanta: ' . ( $avada_const ? $avada_const : 'nedefinovaná' );
	$lines[] = '';
	$lines[] = '--- Fusion Builder / Avada Builder ---';
	$lines[] = 'function fusion_builder_map() existuje: ' . ( $fb_function_exists ? 'áno' : 'NIE' );
	$lines[] = 'class FusionBuilder existuje: ' . ( $fb_class_exists ? 'áno' : 'NIE' );
	$lines[] = 'FUSION_BUILDER_VERSION konštanta: ' . ( $fb_version_const ? $fb_version_const : 'nedefinovaná' );
	$lines[] = 'hook fusion_builder_before_init odpálený ' . $fb_hook_fired . 'x na tomto requeste';
	$lines[] = '';
	$lines[] = '--- Náš plugin ---';
	$lines[] = 'Verzia: ' . IDEANET_BLOCKS_VERSION;
	$lines[] = 'blocks.css existuje na disku: ' . ( file_exists( $css_path ) ? 'áno (' . size_format( filesize( $css_path ) ) . ')' : 'NIE' );
	$lines[] = 'blocks.js existuje na disku: ' . ( file_exists( $js_path ) ? 'áno (' . size_format( filesize( $js_path ) ) . ')' : 'NIE' );
	$lines[] = '';
	$lines[] = '--- Registrácia shortcodov (add_shortcode) ---';
	foreach ( $rows as $tag => $registered ) {
		$lines[] = $tag . ': ' . ( $registered ? 'zaregistrovaný' : 'CHÝBA' );
	}
	$lines[] = '';
	$lines[] = '--- Samo-test vykreslenia (do_shortcode na testovacích dátach) ---';
	foreach ( $selftests as $tag => $r ) {
		$lines[] = $tag . ': ' . ( $r['ok'] ? 'OK, ' . $r['len'] . ' znakov' : 'ZLYHALO — ' . $r['message'] );
	}
	$report_text = implode( "\n", $lines );

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'IDEANET bloky — diagnostika', 'ideanet-avada-blocks' ); ?></h1>
		<p>
			<?php esc_html_e( 'Táto stránka overuje priamo na tomto serveri, či sa bloky správne zaregistrovali a vykresľujú. Ak niečo nefunguje na stránke s blokmi, skopírujte report nižšie a pošlite ho na podporu/vývojárovi — ušetrí to kolo hádania.', 'ideanet-avada-blocks' ); ?>
		</p>

		<?php if ( ! $is_avada ) : ?>
			<div class="notice notice-error"><p>
				<strong><?php esc_html_e( 'Aktívna téma nie je rozpoznaná ako Avada.', 'ideanet-avada-blocks' ); ?></strong>
				<?php esc_html_e( 'Bloky sú postavené na Fusion Builderi, ktorý je súčasťou témy Avada — bez nej sa v builderi neobjavia.', 'ideanet-avada-blocks' ); ?>
			</p></div>
		<?php endif; ?>

		<?php if ( ! $fb_function_exists ) : ?>
			<div class="notice notice-error"><p>
				<strong><?php esc_html_e( 'fusion_builder_map() nie je dostupná.', 'ideanet-avada-blocks' ); ?></strong>
				<?php esc_html_e( 'Fusion Builder sa buď ešte nenačítal, alebo táto verzia Avady používa inú registračnú funkciu. Toto je najpravdepodobnejší dôvod, prečo sa bloky v builderi neponúkajú.', 'ideanet-avada-blocks' ); ?>
			</p></div>
		<?php elseif ( 0 === $fb_hook_fired ) : ?>
			<div class="notice notice-warning"><p>
				<?php esc_html_e( 'Hook fusion_builder_before_init sa na tejto stránke (admin) zatiaľ neodpálil. Nemusí to byť problém — niektoré verzie ho spúšťajú len na konkrétnych obrazovkách buildera. Dôležitejšie je, či nižšie v zozname „Registrácia shortcodov" svietia zelené fajky.', 'ideanet-avada-blocks' ); ?>
			</p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Prostredie', 'ideanet-avada-blocks' ); ?></h2>
		<table class="widefat striped" style="max-width:720px">
			<tbody>
				<tr><td><?php esc_html_e( 'WordPress', 'ideanet-avada-blocks' ); ?></td><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
				<tr><td>PHP</td><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
				<tr>
					<td><?php esc_html_e( 'Aktívna téma', 'ideanet-avada-blocks' ); ?></td>
					<td><?php echo esc_html( $theme_name . ' ' . $theme_version ); ?> <?php echo $is_avada ? $ok : $bad; ?></td>
				</tr>
				<tr><td>AVADA_VERSION</td><td><?php echo esc_html( $avada_const ?: '—' ); ?></td></tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Fusion Builder / Avada Builder', 'ideanet-avada-blocks' ); ?></h2>
		<table class="widefat striped" style="max-width:720px">
			<tbody>
				<tr><td>fusion_builder_map()</td><td><?php echo $fb_function_exists ? $ok : $bad; ?></td></tr>
				<tr><td>class FusionBuilder</td><td><?php echo $fb_class_exists ? $ok : $bad; ?></td></tr>
				<tr><td>FUSION_BUILDER_VERSION</td><td><?php echo esc_html( $fb_version_const ?: '—' ); ?></td></tr>
				<tr><td>fusion_builder_before_init odpálený</td><td><?php echo (int) $fb_hook_fired; ?>×</td></tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Náš plugin', 'ideanet-avada-blocks' ); ?></h2>
		<table class="widefat striped" style="max-width:720px">
			<tbody>
				<tr><td><?php esc_html_e( 'Verzia', 'ideanet-avada-blocks' ); ?></td><td><?php echo esc_html( IDEANET_BLOCKS_VERSION ); ?></td></tr>
				<tr><td>blocks.css</td><td><?php echo file_exists( $css_path ) ? $ok . ' ' . esc_html( size_format( filesize( $css_path ) ) ) : $bad; ?></td></tr>
				<tr><td>blocks.js</td><td><?php echo file_exists( $js_path ) ? $ok . ' ' . esc_html( size_format( filesize( $js_path ) ) ) : $bad; ?></td></tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Registrácia shortcodov', 'ideanet-avada-blocks' ); ?></h2>
		<table class="widefat striped" style="max-width:720px">
			<thead><tr><th>Shortcode</th><th>add_shortcode()</th></tr></thead>
			<tbody>
				<?php foreach ( $rows as $tag => $registered ) : ?>
					<tr><td><code><?php echo esc_html( $tag ); ?></code></td><td><?php echo $registered ? $ok : $bad; ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Samo-test vykreslenia', 'ideanet-avada-blocks' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Každý shortcode sa tu naozaj spustí s testovacími dátami (do_shortcode) priamo na vašom serveri.', 'ideanet-avada-blocks' ); ?></p>
		<table class="widefat striped" style="max-width:720px">
			<thead><tr><th>Shortcode</th><th>Výsledok</th></tr></thead>
			<tbody>
				<?php foreach ( $selftests as $tag => $r ) : ?>
					<tr>
						<td><code><?php echo esc_html( $tag ); ?></code></td>
						<td>
							<?php echo $r['ok'] ? $ok . ' ' . esc_html( $r['len'] ) . ' znakov' : $bad . ' ' . esc_html( $r['message'] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Kopírovateľný report', 'ideanet-avada-blocks' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Označte všetko a skopírujte (Ctrl+A, Ctrl+C) — pošlite tento text, keď niečo nefunguje.', 'ideanet-avada-blocks' ); ?></p>
		<textarea readonly rows="20" style="max-width:720px;width:100%;font-family:monospace;font-size:12.5px" onclick="this.select()"><?php echo esc_textarea( $report_text ); ?></textarea>
	</div>
	<?php
}
