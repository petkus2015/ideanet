<?php
/**
 * Nastavenia → Meškanie vlakov: stav sťahovania, nastavenia a ručné spustenie.
 */

defined( 'ABSPATH' ) || exit;

class MV_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_mv_run', array( __CLASS__, 'run' ) );
		add_action( 'admin_post_mv_upload_gtfs', array( __CLASS__, 'upload' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MV_FILE ), array( __CLASS__, 'links' ) );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=meskanie-vlakov' ) ) . '">Nastavenia</a>' );
		return $links;
	}

	public static function menu() {
		add_options_page( 'Meškanie vlakov', 'Meškanie vlakov', 'manage_options', 'meskanie-vlakov', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting(
			'mv_settings',
			'mv_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	public static function sanitize( $in ) {
		$in  = (array) $in;
		$url = esc_url_raw( trim( $in['gtfs_url'] ?? '' ) );
		return array(
			'demo'           => empty( $in['demo'] ) ? 0 : 1,
			'google_fonts'   => empty( $in['google_fonts'] ) ? 0 : 1,
			'quick_stations' => sanitize_text_field( $in['quick_stations'] ?? '' ),
			'gtfs_url'       => $url ? $url : MV_Fetcher::GTFS_URL,
		);
	}

	/** Ručné spustenie sťahovania z administrácie. */
	public static function run() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nemáte oprávnenie.' );
		}
		check_admin_referer( 'mv_run' );
		$what = sanitize_key( $_POST['what'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- overené vyššie
		$msg  = '';
		if ( 'trains' === $what ) {
			try {
				$msg = 'Stiahnuté vlaky: ' . MV_Fetcher::fetch_trains() . '.';
			} catch ( Exception $e ) {
				$msg = 'Chyba: ' . $e->getMessage();
			}
		} elseif ( 'timetable' === $what ) {
			MV_Fetcher::build_timetable();
			$st  = (array) get_option( 'mv_status', array() );
			$msg = empty( $st['tt_error'] ) ? 'Cestovný poriadok zostavený: ' . (int) ( $st['tt_count'] ?? 0 ) . ' spojov.' : 'Chyba: ' . $st['tt_error'];
		}
		set_transient( 'mv_notice', $msg, 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=meskanie-vlakov' ) );
		exit;
	}

	/** Ručné nahratie GTFS (keď ho server nevie stiahnuť sám). */
	public static function upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nemáte oprávnenie.' );
		}
		check_admin_referer( 'mv_upload_gtfs' );
		$file = $_FILES['gtfs'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- súbor sa overuje nižšie
		$msg  = '';
		if ( ! $file || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			$err = $file ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
			$msg = in_array( $err, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true )
				? 'Súbor je väčší, ako hosting dovoľuje nahrať (' . size_format( wp_max_upload_size() ) . '). Požiadajte hosting o zvýšenie limitu upload_max_filesize.'
				: 'Súbor sa nepodarilo nahrať (kód chyby ' . $err . ').';
		} elseif ( ! is_uploaded_file( $file['tmp_name'] ) || ! MV_Fetcher::is_zip( $file['tmp_name'] ) ) {
			$msg = 'Nahratý súbor nie je ZIP. Vyberte súbor gtfs.zip.';
		} else {
			wp_mkdir_p( MV_Fetcher::dir() );
			if ( ! move_uploaded_file( $file['tmp_name'], MV_Fetcher::gtfs_copy() ) ) {
				$msg = 'Súbor sa nepodarilo uložiť do ' . MV_Fetcher::dir() . '.';
			} else {
				MV_Fetcher::build_timetable( true );
				$st  = (array) get_option( 'mv_status', array() );
				$msg = empty( $st['tt_error'] ) ? 'Súbor nahratý, cestovný poriadok zostavený: ' . (int) ( $st['tt_count'] ?? 0 ) . ' spojov na dnes.' : 'Chyba: ' . $st['tt_error'];
			}
		}
		set_transient( 'mv_notice', $msg, 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=meskanie-vlakov' ) );
		exit;
	}

	private static function when( $ts ) {
		return $ts ? esc_html( wp_date( 'j. n. Y H:i', (int) $ts ) ) : '—';
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = mv_settings();
		$st     = (array) get_option( 'mv_status', array() );
		$next   = wp_next_scheduled( 'mv_fetch_trains' );
		$notice = get_transient( 'mv_notice' );
		delete_transient( 'mv_notice' );
		?>
		<div class="wrap">
			<h1>Meškanie vlakov</h1>
			<?php if ( $notice ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>

			<h2>Ako vložiť na stránku</h2>
			<p>Na ľubovoľnú stránku vložte shortcode (v editore blok <em>Shortcode</em>):</p>
			<p><code>[meskanie_vlakov]</code></p>
			<p>Voliteľne: <code>[meskanie_vlakov sirka="plna"]</code> roztiahne tabuľu na celú šírku okna,
				<code>[meskanie_vlakov stanice="Žilina, Vrútky, Martin"]</code> zmení tlačidlá častých staníc.</p>

			<h2>Stav sťahovania</h2>
			<table class="widefat striped" style="max-width:760px">
				<tbody>
					<tr>
						<th>Poloha a meškanie vlakov</th>
						<td>
							Naposledy: <?php echo self::when( $st['trains_ok'] ?? 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php if ( ! empty( $st['trains_count'] ) ) : ?>
								(<?php echo (int) $st['trains_count']; ?> vlakov)
							<?php endif; ?>
							<?php if ( isset( $st['trains_match'] ) ) : ?>
								<br>Spárované s cestovným poriadkom: <?php echo (int) $st['trains_match']; ?> z <?php echo (int) ( $st['trains_count'] ?? 0 ); ?> vlakov
							<?php endif; ?>
							<br>Ďalšie stiahnutie: <?php echo self::when( $next ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php if ( ! empty( $st['trains_error'] ) ) : ?>
								<br><strong style="color:#b32d2e">Posledná chyba (<?php echo self::when( $st['trains_error_at'] ?? 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>): <?php echo esc_html( $st['trains_error'] ); ?></strong>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Cestovný poriadok (GTFS)</th>
						<td>
							Naposledy: <?php echo self::when( $st['tt_ok'] ?? 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php if ( ! empty( $st['tt_count'] ) ) : ?>
								(<?php echo (int) $st['tt_count']; ?> spojov na dnes)
							<?php endif; ?>
							<?php if ( ! empty( $st['tt_error'] ) ) : ?>
								<br><strong style="color:#b32d2e">Posledná chyba (<?php echo self::when( $st['tt_error_at'] ?? 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>): <?php echo esc_html( $st['tt_error'] ); ?></strong>
							<?php endif; ?>
							<?php if ( ! empty( $st['tt_note'] ) ) : ?>
								<br><span style="color:#996800"><?php echo esc_html( $st['tt_note'] ); ?></span>
							<?php endif; ?>
							<?php if ( MV_Fetcher::is_zip( MV_Fetcher::gtfs_copy() ) ) : ?>
								<br>Uložená kópia GTFS: <?php echo esc_html( size_format( filesize( MV_Fetcher::gtfs_copy() ) ) ); ?> z <?php echo self::when( filemtime( MV_Fetcher::gtfs_copy() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Súbory s dátami</th>
						<td><code><?php echo esc_html( MV_Fetcher::dir() ); ?></code></td>
					</tr>
				</tbody>
			</table>
			<?php if ( ! empty( $st['trains_sample'] ) ) : ?>
				<details style="max-width:760px;margin:12px 0">
					<summary>Ukážka údajov zo ŽSR (pri nahlasovaní chyby skopírujte)</summary>
					<textarea readonly rows="12" style="width:100%;font-family:monospace;font-size:12px"><?php echo esc_textarea( wp_json_encode( $st['trains_sample'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); ?></textarea>
				</details>
			<?php endif; ?>
			<p>
				<?php foreach ( array( 'trains' => 'Stiahnuť polohu vlakov teraz', 'timetable' => 'Zostaviť cestovný poriadok teraz' ) as $what => $label ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px">
						<?php wp_nonce_field( 'mv_run' ); ?>
						<input type="hidden" name="action" value="mv_run">
						<input type="hidden" name="what" value="<?php echo esc_attr( $what ); ?>">
						<?php submit_button( $label, 'secondary', 'mv_run_' . $what, false ); ?>
					</form>
				<?php endforeach; ?>
			</p>
			<h3>Nahrať cestovný poriadok ručne</h3>
			<p style="max-width:760px">Ak server nevie súbor stiahnuť sám (chyba vyššie), stiahnite ho v prehliadači
				z <a href="<?php echo esc_url( $s['gtfs_url'] ); ?>"><?php echo esc_html( $s['gtfs_url'] ); ?></a>
				a nahrajte ho sem. Plugin si ho uloží a každý deň z neho zostaví cestovný poriadok, kým sa nepodarí stiahnuť novší.
				Najväčšia veľkosť nahrávaného súboru na tomto hostingu: <strong><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></strong>.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'mv_upload_gtfs' ); ?>
				<input type="hidden" name="action" value="mv_upload_gtfs">
				<input type="file" name="gtfs" accept=".zip,application/zip" required>
				<?php submit_button( 'Nahrať a zostaviť', 'secondary', 'mv_upload', false ); ?>
			</form>

			<p class="description" style="max-width:760px">
				Sťahovanie beží cez WP-Cron, ktorý WordPress spúšťa pri návštevách webu. Ak má web málo návštev,
				nastavte na hostingu systémový cron, ktorý každé 2 minúty otvorí <code><?php echo esc_html( site_url( 'wp-cron.php' ) ); ?></code>.
			</p>

			<h2>Nastavenia</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'mv_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Ukážkový režim</th>
						<td><label><input type="checkbox" name="mv_settings[demo]" value="1" <?php checked( $s['demo'] ); ?>>
							Zobraziť vymyslené dáta namiesto skutočných (na vyskúšanie vzhľadu).</label></td>
					</tr>
					<tr>
						<th scope="row"><label for="mv-quick">Časté stanice</label></th>
						<td><input id="mv-quick" class="large-text" type="text" name="mv_settings[quick_stations]" value="<?php echo esc_attr( $s['quick_stations'] ); ?>">
							<p class="description">Tlačidlá pod poľom „Z ktorej stanice cestujete?“, oddelené čiarkou.</p></td>
					</tr>
					<tr>
						<th scope="row">Písma</th>
						<td><label><input type="checkbox" name="mv_settings[google_fonts]" value="1" <?php checked( $s['google_fonts'] ); ?>>
							Načítať písma z Google Fonts.</label>
							<p class="description">Návštevníkovi sa pritom odošle IP adresa do Googlu (GDPR). Bez nich sa použijú systémové písma.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="mv-gtfs">Adresa cestovného poriadku (GTFS)</label></th>
						<td><input id="mv-gtfs" class="large-text code" type="url" name="mv_settings[gtfs_url]" value="<?php echo esc_attr( $s['gtfs_url'] ); ?>">
							<p class="description">Otvorené dáta ŽSR (CC0). Meniť len ak ŽSR zmení adresu.</p></td>
					</tr>
				</table>
				<?php submit_button( 'Uložiť nastavenia', 'primary', 'mv_save' ); ?>
			</form>

			<p class="description" style="max-width:760px">
				Zdroj polohy vlakov: mapa.zsr.sk (nedokumentované, neoficiálne). Pred verejným nasadením si overte podmienky používania dát ŽSR.
			</p>
		</div>
		<?php
	}
}

MV_Admin::init();
