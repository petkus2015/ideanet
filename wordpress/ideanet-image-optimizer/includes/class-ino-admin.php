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
		add_action( 'wp_ajax_ino_scan', array( __CLASS__, 'ajax_scan' ) );
		add_action( 'wp_ajax_ino_delete', array( __CLASS__, 'ajax_delete' ) );
		add_action( 'wp_ajax_ino_sizes_scan', array( __CLASS__, 'ajax_sizes_scan' ) );
		add_action( 'wp_ajax_ino_sizes_delete', array( __CLASS__, 'ajax_sizes_delete' ) );
		add_action( 'wp_ajax_ino_orphans_scan', array( __CLASS__, 'ajax_orphans_scan' ) );
		add_action( 'wp_ajax_ino_orphans_delete', array( __CLASS__, 'ajax_orphans_delete' ) );
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
		wp_enqueue_script( 'ino-admin', INO_URL . 'assets/admin.js', array(), INO_VERSION . '.' . filemtime( INO_DIR . 'assets/admin.js' ), true );
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

	/** Vyhľadá nepoužité obrázky (nič nemaže). */
	public static function ajax_scan() {
		check_ajax_referer( 'ino_run', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Nedostatočné oprávnenie.', 403 );
		}
		@set_time_limit( 120 ); // phpcs:ignore
		$days   = (int) INO_Converter::settings()['cleanup_days'];
		$cursor = isset( $_POST['cursor'] ) ? absint( $_POST['cursor'] ) : 0;
		$start  = microtime( true );
		$items  = array();
		$done   = false;
		$seen   = 0;
		while ( microtime( true ) - $start < 15 ) {
			$ids = INO_Cleaner::image_ids( $cursor, 20, $days );
			if ( ! $ids ) {
				$done = true;
				break;
			}
			foreach ( $ids as $id ) {
				$cursor = $id;
				++$seen;
				if ( ! INO_Cleaner::is_used( $id ) ) {
					$items[] = INO_Cleaner::describe( $id );
				}
				if ( microtime( true ) - $start >= 15 ) {
					break 2;
				}
			}
		}
		wp_send_json_success( array( 'items' => $items, 'cursor' => $cursor, 'done' => $done, 'seen' => $seen ) );
	}

	/** Zmaže vybrané obrázky; každý sa pred zmazaním znova overí. */
	public static function ajax_delete() {
		check_ajax_referer( 'ino_run', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Nedostatočné oprávnenie.', 403 );
		}
		$days    = (int) INO_Converter::settings()['cleanup_days'];
		$ids     = isset( $_POST['ids'] ) ? array_slice( array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ), 0, 25 ) : array();
		$deleted = array();
		$kept    = array();
		foreach ( $ids as $id ) {
			if ( $id && current_user_can( 'delete_post', $id ) && INO_Cleaner::is_deletable( $id, $days ) && wp_delete_attachment( $id, true ) ) {
				$deleted[] = $id;
			} else {
				$kept[] = $id;
			}
		}
		wp_send_json_success( array( 'deleted' => $deleted, 'kept' => $kept ) );
	}

	private static function guard() {
		check_ajax_referer( 'ino_run', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Nedostatočné oprávnenie.', 403 );
		}
		@set_time_limit( 120 ); // phpcs:ignore
	}

	/** Miniatúry v celom uploads (nič nemaže); kurzor = index v zozname priečinkov. */
	public static function ajax_sizes_scan() {
		self::guard();
		$cursor = isset( $_POST['cursor'] ) ? absint( $_POST['cursor'] ) : 0;
		$dirs   = INO_Sizes::all_dirs();
		$main   = INO_Sizes::main_set( 0 === $cursor );
		$u      = wp_get_upload_dir();
		$start  = microtime( true );
		$items  = array();
		$seen   = 0;
		while ( $cursor < count( $dirs ) && microtime( true ) - $start < 15 ) {
			++$seen;
			foreach ( INO_Sizes::thumbs_in_dir( $dirs[ $cursor++ ], $main ) as $base => $thumbs ) {
				$items[] = array(
					'key'   => $base,
					'title' => $base,
					'thumb' => $u['baseurl'] . '/' . $base,
					'count' => count( $thumbs ),
					'bytes' => INO_Sizes::bytes( $thumbs ),
				);
			}
		}
		wp_send_json_success( array( 'items' => $items, 'cursor' => $cursor, 'done' => $cursor >= count( $dirs ), 'seen' => $seen ) );
	}

	public static function ajax_sizes_delete() {
		self::guard();
		$files   = isset( $_POST['files'] ) ? array_slice( array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['files'] ) ), 0, 10 ) : array();
		$deleted = array();
		$kept    = array();
		foreach ( $files as $rel ) {
			if ( INO_Sizes::remove( $rel ) ) {
				$deleted[] = $rel;
			} else {
				$kept[] = $rel;
			}
		}
		wp_send_json_success( array( 'deleted' => $deleted, 'kept' => $kept ) );
	}

	/** Osirelé súbory v uploads; kurzor = index v zozname priečinkov. */
	public static function ajax_orphans_scan() {
		self::guard();
		$cursor = isset( $_POST['cursor'] ) ? absint( $_POST['cursor'] ) : 0;
		$dirs   = INO_Orphans::dirs();
		$known  = INO_Orphans::known_set( 0 === $cursor );
		$u      = wp_get_upload_dir();
		$start  = microtime( true );
		$items  = array();
		$seen   = 0;
		while ( $cursor < count( $dirs ) && microtime( true ) - $start < 15 ) {
			$d = $dirs[ $cursor++ ];
			foreach ( (array) @scandir( $u['basedir'] . '/' . $d ) as $n ) { // phpcs:ignore
				$rel = $d . '/' . $n;
				if ( ! is_file( $u['basedir'] . '/' . $rel ) ) {
					continue;
				}
				++$seen;
				if ( INO_Orphans::is_orphan( $rel, $known ) && ! INO_Cleaner::is_file_used( $rel ) ) {
					$items[] = array(
						'key'   => $rel,
						'title' => $rel,
						'thumb' => $u['baseurl'] . '/' . $rel,
						'bytes' => (int) filesize( $u['basedir'] . '/' . $rel ),
						'date'  => gmdate( 'j. n. Y', (int) filemtime( $u['basedir'] . '/' . $rel ) ),
					);
				}
			}
		}
		wp_send_json_success( array( 'items' => $items, 'cursor' => $cursor, 'done' => $cursor >= count( $dirs ), 'seen' => $seen ) );
	}

	public static function ajax_orphans_delete() {
		self::guard();
		$files   = isset( $_POST['files'] ) ? array_slice( array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['files'] ) ), 0, 25 ) : array();
		$base    = wp_get_upload_dir()['basedir'];
		$deleted = array();
		$kept    = array();
		foreach ( $files as $rel ) {
			if ( INO_Orphans::is_deletable( $rel ) ) {
				wp_delete_file( $base . '/' . $rel );
				$deleted[] = $rel;
			} else {
				$kept[] = $rel;
			}
		}
		wp_send_json_success( array( 'deleted' => $deleted, 'kept' => $kept ) );
	}

	/** Blok „vyhľadať → vybrať → zmazať“. */
	private static function section( $key, $title, $desc, $cols, $btn ) {
		?>
		<hr>
		<h2><?php echo esc_html( $title ); ?></h2>
		<p><?php echo esc_html( $desc ); ?></p>
		<p>
			<button type="button" class="button" id="ino-<?php echo esc_attr( $key ); ?>-scan"><?php echo esc_html( $btn ); ?></button>
			<button type="button" class="button button-link-delete" id="ino-<?php echo esc_attr( $key ); ?>-del" hidden>Zmazať vybrané</button>
			<span id="ino-<?php echo esc_attr( $key ); ?>-st" style="margin-left:10px"></span>
		</p>
		<table class="widefat striped" id="ino-<?php echo esc_attr( $key ); ?>-table" hidden style="max-width:900px">
			<thead><tr><td class="check-column"><input type="checkbox" id="ino-<?php echo esc_attr( $key ); ?>-all" checked></td><th>Náhľad</th>
			<?php foreach ( $cols as $c ) { echo '<th>' . esc_html( $c ) . '</th>'; } ?></tr></thead>
			<tbody></tbody>
		</table>
		<?php
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
			<p>Prevedie JPEG/PNG obrázky použité v článkoch na moderný webový formát, v obsahu upraví odkazy a nahradené staré súbory zmaže.</p>

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
						<td><label><input type="checkbox" name="ino_settings[only_used]" value="1" <?php checked( $s['only_used'] ); ?>> Len obrázky použité v článkoch (vrátane hlavného obrázka); stránky sa nepočítajú</label></td>
					</tr>
					<tr>
						<th scope="row">Čistenie knižnice</th>
						<td>Nemazať obrázky mladšie než <input type="number" min="0" max="3650" name="ino_settings[cleanup_days]" value="<?php echo esc_attr( $s['cleanup_days'] ); ?>" style="width:70px"> dní
							<p class="description">Chráni čerstvo nahrané obrázky, ktoré ešte nie sú vložené do článku.</p></td>
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
			<?php
			self::section( 'unused', 'Nepoužité obrázky', 'Nájde obrázky v knižnici médií, ktoré nie sú použité v žiadnom článku, stránke, hlavnom obrázku, menu, logu ani nastaveniach. Nič sa nezmaže, kým nepotvrdíte.', array( 'Obrázok', 'Nahraný', 'Veľkosť' ), 'Vyhľadať nepoužité obrázky' );
			self::section( 'sizes', 'Miniatúry v celom uploads', 'Prehľadá všetky priečinky pod wp-content/uploads a nájde miniatúry typu meno-300x200.jpg (aj .png, .webp…), ku ktorým existuje väčší hlavný súbor. Odkazy na ne v obsahu sa nahradia hlavným súborom a miniatúry sa zmažú, ostane len hlavný obrázok.', array( 'Hlavný súbor', 'Miniatúr', 'Uvoľní' ), 'Vyhľadať miniatúry' );
			self::section( 'orphans', 'Osirelé súbory v uploads', 'Nájde obrázky v priečinkoch wp-content/uploads/RRRR/MM, ktoré nepatria žiadnej prílohe v knižnici a nikde sa nepoužívajú.', array( 'Súbor', 'Zmenený', 'Veľkosť' ), 'Vyhľadať osirelé súbory' );
			?>
			<p class="description"><strong>Plugin nevidí obrázky natvrdo vložené v súboroch témy ani údaje v tabuľkách iných pluginov.</strong> Zmazané súbory sa nedajú obnoviť, spravte si zálohu.</p>
			<p class="description">Z príkazového riadka: <code>wp ideanet-images optimize [--dry-run] [--id=&lt;ID&gt;]</code>, <code>wp ideanet-images cleanup [--dry-run] [--yes]</code></p>
		</div>
		<?php
	}
}
