<?php
defined( 'ABSPATH' ) || exit;

/**
 * Administrácia: Médiá → Optimalizácia obrázkov (nastavenia + spúšťanie po dávkach cez AJAX).
 */
class INO_Admin {

	const PAGE = 'ino-optimizer';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_ino_run', array( __CLASS__, 'ajax_run' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( INO_DIR . 'ideanet-image-optimizer.php' ), array( __CLASS__, 'links' ) );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'upload.php?page=' . self::PAGE ) ) . '">Optimalizovať</a>' );
		return $links;
	}

	public static function menu() {
		add_media_page( 'Optimalizácia obrázkov', 'Optimalizácia obrázkov', 'manage_options', self::PAGE, array( __CLASS__, 'render' ) );
	}

	public static function register() {
		register_setting( 'ino_group', 'ino_settings', array( 'sanitize_callback' => array( 'INO_Converter', 'sanitize_settings' ) ) );
	}

	public static function assets( $hook ) {
		if ( 'media_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_script( 'ino-admin', INO_URL . 'assets/admin.js', array(), INO_VERSION, true );
		wp_localize_script( 'ino-admin', 'INO', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'ino_run' ),
		) );
	}

	/** Spracuje pár obrázkov (max ~20 s) a vráti kurzor na pokračovanie. */
	public static function ajax_run() {
		check_ajax_referer( 'ino_run', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Nedostatočné oprávnenie.', 403 );
		}
		@set_time_limit( 120 ); // phpcs:ignore
		wp_raise_memory_limit( 'image' );

		$cursor = isset( $_POST['cursor'] ) ? absint( $_POST['cursor'] ) : 0;
		$start  = microtime( true );
		$items  = array();
		$done   = false;

		while ( microtime( true ) - $start < 20 ) {
			$ids = INO_Converter::candidate_ids( $cursor, 5 );
			if ( ! $ids ) {
				$done = true;
				break;
			}
			foreach ( $ids as $id ) {
				$cursor = $id;
				$r      = INO_Converter::optimize( $id );
				$items[] = array(
					'id'      => $id,
					'title'   => get_the_title( $id ) ?: basename( (string) get_attached_file( $id ) ),
					'status'  => $r['status'],
					'message' => $r['message'],
					'before'  => $r['before'] ?? 0,
					'after'   => $r['after'] ?? 0,
				);
				if ( microtime( true ) - $start >= 20 ) {
					break;
				}
			}
		}
		wp_send_json_success( array( 'items' => $items, 'cursor' => $cursor, 'done' => $done ) );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = INO_Converter::settings();
		$mime   = INO_Converter::target_mime( $s['format'] );
		$webp   = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
		$avif   = wp_image_editor_supports( array( 'mime_type' => 'image/avif' ) );
		$totals = INO_Converter::totals();
		?>
		<div class="wrap">
			<h1>Optimalizácia obrázkov</h1>
			<p>Prevedie JPEG/PNG obrázky použité v článkoch a stránkach na moderný webový formát, v obsahu upraví odkazy a nahradené staré súbory zmaže.</p>

			<?php if ( ! $mime ) : ?>
				<div class="notice notice-error"><p>Váš server nepodporuje WebP ani AVIF. Požiadajte hosting o PHP s GD alebo Imagick s podporou WebP.</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'ino_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Formát</th>
						<td>
							<select name="ino_settings[format]">
								<option value="webp" <?php selected( $s['format'], 'webp' ); ?> <?php disabled( ! $webp ); ?>>WebP<?php echo $webp ? '' : ' (nepodporované)'; ?></option>
								<option value="avif" <?php selected( $s['format'], 'avif' ); ?> <?php disabled( ! $avif ); ?>>AVIF<?php echo $avif ? '' : ' (nepodporované)'; ?></option>
							</select>
							<p class="description">WebP funguje všade. AVIF je menší, ale server ho musí vedieť vytvoriť (inak sa použije WebP).</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Kvalita (30–100)</th>
						<td><input type="number" min="30" max="100" name="ino_settings[quality]" value="<?php echo esc_attr( $s['quality'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row">Maximálna šírka (px)</th>
						<td><input type="number" min="0" max="10000" name="ino_settings[max_width]" value="<?php echo esc_attr( $s['max_width'] ); ?>">
							<p class="description">Väčšie obrázky sa zmenšia. 0 = nezmenšovať.</p></td>
					</tr>
					<tr>
						<th scope="row">Rozsah</th>
						<td><label><input type="checkbox" name="ino_settings[only_used]" value="1" <?php checked( $s['only_used'] ); ?>> Len obrázky použité v príspevkoch a stránkach (vrátane hlavného obrázka)</label></td>
					</tr>
					<tr>
						<th scope="row">Staré súbory</th>
						<td><label><input type="checkbox" name="ino_settings[delete_originals]" value="1" <?php checked( $s['delete_originals'] ); ?>> Po úspešnom nahradení natrvalo zmazať pôvodné súbory (aj všetky ich veľkosti)</label>
							<p class="description"><strong>Zmazanie sa nedá vrátiť.</strong> Urobte si najprv zálohu webu.</p></td>
					</tr>
				</table>
				<?php submit_button( 'Uložiť nastavenia' ); ?>
			</form>

			<hr>
			<h2>Spustenie</h2>
			<p>
				Čaká na spracovanie: <strong id="ino-pending"><?php echo (int) INO_Converter::count_candidates(); ?></strong> JPEG/PNG príloh.
				Už optimalizovaných: <strong><?php echo (int) $totals['count']; ?></strong>, ušetrené <strong><?php echo esc_html( size_format( $totals['saved'] ) ); ?></strong>.
			</p>
			<p>
				<button type="button" class="button button-primary" id="ino-start" <?php disabled( ! $mime ); ?>>Optimalizovať obrázky</button>
				<button type="button" class="button" id="ino-stop" hidden>Zastaviť</button>
				<span id="ino-status" style="margin-left:10px"></span>
			</p>
			<table class="widefat striped" id="ino-log" hidden style="max-width:900px">
				<thead><tr><th>Obrázok</th><th>Výsledok</th><th>Veľkosť</th></tr></thead>
				<tbody></tbody>
			</table>
			<p class="description">Z príkazového riadka: <code>wp ideanet-images optimize [--dry-run] [--id=&lt;ID&gt;]</code></p>
		</div>
		<?php
	}
}
