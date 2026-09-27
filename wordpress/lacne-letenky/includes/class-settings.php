<?php
/**
 * Nastavenia → Lacné letenky: zdroj dát, cache, počet kariet, písmo a stav posledného načítania.
 */

defined( 'ABSPATH' ) || exit;

class Lacne_Letenky_Settings {

	const OPTION = 'lacne_letenky_options';

	public static function defaults() {
		return array(
			'src'           => LACNE_LETENKY_DEFAULT_SRC,
			'cache_minutes' => 15,
			'limit'         => 8,
			'title'         => '',
			'font'          => 'theme', // theme = písmo témy Newspaper, figtree = písmo bloku
			'live'          => 1,       // tlačidlo spustí živé hľadanie na momondo a Ryanair
			'live_minutes'  => 10,      // výsledok živého hľadania platí X minút
		);
	}

	public static function get( $key ) {
		$opts = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		return isset( $opts[ $key ] ) ? $opts[ $key ] : null;
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_lacne_letenky_refresh', array( __CLASS__, 'refresh' ) );
	}

	public static function menu() {
		add_options_page( 'Lacné letenky', 'Lacné letenky', 'manage_options', 'lacne-letenky', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting(
			'lacne_letenky',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize( $in ) {
		$d   = self::defaults();
		$out = array(
			'src'           => isset( $in['src'] ) && wp_http_validate_url( $in['src'] ) ? esc_url_raw( trim( $in['src'] ) ) : $d['src'],
			'cache_minutes' => isset( $in['cache_minutes'] ) ? max( 5, min( 240, (int) $in['cache_minutes'] ) ) : $d['cache_minutes'],
			'limit'         => isset( $in['limit'] ) ? max( 1, min( 24, (int) $in['limit'] ) ) : $d['limit'],
			'title'         => isset( $in['title'] ) ? sanitize_text_field( $in['title'] ) : '',
			'font'          => isset( $in['font'] ) && 'figtree' === $in['font'] ? 'figtree' : 'theme',
			'live'          => empty( $in['live'] ) ? 0 : 1,
			'live_minutes'  => isset( $in['live_minutes'] ) ? max( 5, min( 120, (int) $in['live_minutes'] ) ) : $d['live_minutes'],
		);
		Lacne_Letenky_Data::flush();
		return $out;
	}

	public static function refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nemáte oprávnenie.', 'lacne-letenky' ) );
		}
		check_admin_referer( 'lacne_letenky_refresh' );
		Lacne_Letenky_Data::flush();
		Lacne_Letenky_Data::get( true );
		wp_safe_redirect( admin_url( 'options-general.php?page=lacne-letenky&refreshed=1' ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o      = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		$status = get_option( Lacne_Letenky_Data::STATUS_KEY );
		$data   = get_option( Lacne_Letenky_Data::BACKUP_KEY );
		?>
		<div class="wrap">
			<h1>Lacné letenky VIE · BTS</h1>

			<?php if ( isset( $_GET['refreshed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-info is-dismissible"><p>Ceny boli znova načítané zo zdroja.</p></div>
			<?php endif; ?>

			<h2>Ako pridať blok na stránku</h2>
			<ul style="list-style:disc;padding-left:20px">
				<li><strong>tagDiv Composer (téma Newspaper):</strong> v zozname prvkov nájdite <em>Lacné letenky</em>. Ak ho tam nevidíte, pridajte prvok <em>Column text</em> a vložte doň shortcode nižšie.</li>
				<li><strong>Editor blokov (Gutenberg):</strong> pridajte blok <em>Lacné letenky</em>.</li>
				<li><strong>Shortcode kdekoľvek:</strong> <code>[lacne_letenky]</code>, prípadne <code>[lacne_letenky limit="12" title="Kam lacno z Viedne"]</code></li>
				<li><strong>Bočný panel:</strong> Vzhľad → Widgety → <em>Lacné letenky</em>.</li>
			</ul>

			<?php if ( is_array( $status ) && ! $status['ok'] ) : ?>
				<div class="notice notice-error"><p><strong>Ceny sa nepodarilo načítať zo zdroja</strong> (<?php echo esc_html( $status['message'] ); ?>).
				<?php if ( false !== strpos( $status['message'], '404' ) ) : ?>
					Súbor s cenami na tejto adrese neexistuje. Ak používate predvolenú adresu, ceny sa na GitHube objavia až po zlúčení zmien do predvolenej vetvy repozitára – dovtedy môžete nižšie zadať adresu súboru z inej vetvy.
				<?php endif; ?></p></div>
			<?php endif; ?>

			<h2>Stav cien</h2>
			<table class="widefat striped" style="max-width:760px">
				<tbody>
					<tr><th style="width:220px">Posledné hľadanie letov</th><td><?php echo is_array( $data ) && ! empty( $data['updatedAt'] ) ? esc_html( wp_date( 'j. n. Y H:i', strtotime( $data['updatedAt'] ) ) ) : '—'; ?></td></tr>
					<tr><th>Počet ponúk</th><td><?php echo is_array( $data ) && isset( $data['deals'] ) ? (int) count( $data['deals'] ) : '—'; ?></td></tr>
					<tr><th>Posledné živé hľadanie</th><td>
						<?php
						$ls = get_option( 'lacne_letenky_live_status' );
						echo is_array( $ls ) ? esc_html( wp_date( 'j. n. Y H:i', $ls['time'] ) . ' – ' . ( $ls['ok'] ? 'OK, ' : 'chyba: ' ) . $ls['message'] ) : '—';
						?>
					</td></tr>
					<tr><th>Posledné načítanie zdroja</th><td>
						<?php
						if ( is_array( $status ) ) {
							echo esc_html( wp_date( 'j. n. Y H:i', $status['time'] ) . ' – ' . ( $status['ok'] ? 'OK, ' : 'chyba: ' ) . $status['message'] );
						} else {
							echo '—';
						}
						?>
					</td></tr>
				</tbody>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
				<input type="hidden" name="action" value="lacne_letenky_refresh">
				<?php wp_nonce_field( 'lacne_letenky_refresh' ); ?>
				<?php submit_button( 'Načítať ceny teraz', 'secondary', 'submit', false ); ?>
			</form>

			<h2>Nastavenia</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'lacne_letenky' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ll-src">Zdroj dát (deals.json)</label></th>
						<td><input id="ll-src" type="url" class="large-text code" name="<?php echo esc_attr( self::OPTION ); ?>[src]" value="<?php echo esc_attr( $o['src'] ); ?>">
						<p class="description">Súbor, ktorý 3× denne aktualizuje GitHub Actions (momondo, Ryanair, Wizz Air).</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="ll-cache">Obnoviť zo zdroja každých</label></th>
						<td><input id="ll-cache" type="number" min="5" max="240" name="<?php echo esc_attr( self::OPTION ); ?>[cache_minutes]" value="<?php echo esc_attr( $o['cache_minutes'] ); ?>"> minút</td>
					</tr>
					<tr>
						<th scope="row"><label for="ll-limit">Počet kariet</label></th>
						<td><input id="ll-limit" type="number" min="1" max="24" name="<?php echo esc_attr( self::OPTION ); ?>[limit]" value="<?php echo esc_attr( $o['limit'] ); ?>">
						<p class="description">Okrem veľkej karty Bangkoku. Dubaj a Abu Dhabí sú vždy medzi nimi.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="ll-title">Nadpis</label></th>
						<td><input id="ll-title" type="text" class="regular-text" placeholder="bez nadpisu" name="<?php echo esc_attr( self::OPTION ); ?>[title]" value="<?php echo esc_attr( $o['title'] ); ?>">
						<p class="description">Voliteľné. Prázdne = blok začne rovno ponukami.</p></td>
					</tr>
					<tr>
						<th scope="row">Živé hľadanie</th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[live]" value="1" <?php checked( ! empty( $o['live'] ) ); ?>> tlačidlo „Vyhľadaj aktuálne lacné letenky“ hľadá hneď na momondo a Ryanair</label>
							<p><label>Výsledok platí <input type="number" min="5" max="120" class="small-text" name="<?php echo esc_attr( self::OPTION ); ?>[live_minutes]" value="<?php echo esc_attr( $o['live_minutes'] ); ?>"> minút</label></p>
							<p class="description">Kliknutia v tomto čase dostanú výsledok spred chvíle, aby návštevníci nezahltili zdroje. Ceny Wizz Air sa berú z aktualizácie na GitHube.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Písmo</th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[font]" value="theme" <?php checked( $o['font'], 'theme' ); ?>> podľa témy webu (odporúčané pre Newspaper)</label><br>
							<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[font]" value="figtree" <?php checked( $o['font'], 'figtree' ); ?>> Figtree (Google Fonts)</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
