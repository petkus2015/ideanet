<?php
/**
 * Výstup komponentov (karta anketa/nálada, spodné menu, vyhľadávanie).
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Karta s anketou a výberom nálady.
 */
function rlav_sidecard_html(): string {
	ob_start();
	$poll = rlav_active_poll();
	?>
<aside class="rl-sidecard js-sidecard" aria-label="<?php esc_attr_e( 'Anketa a nálada', 'relife-avada' ); ?>">
	<div class="rl-tabs" role="tablist">
		<button type="button" role="tab" class="rl-tabs__btn is-active js-tab-poll" aria-selected="true"><?php esc_html_e( 'Anketa', 'relife-avada' ); ?></button>
		<button type="button" role="tab" class="rl-tabs__btn js-tab-mood" aria-selected="false"><?php esc_html_e( 'Nálada', 'relife-avada' ); ?></button>
	</div>

	<div class="rl-panel js-panel-poll">
		<?php if ( $poll instanceof WP_Post ) : ?>
			<div class="rl-poll js-poll" data-poll="<?php echo (int) $poll->ID; ?>">
				<div class="rl-poll__head">
					<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 20V10M12 20V4M19 20v-7"/></svg>
					<div>
						<div class="rl-poll__kicker"><?php esc_html_e( 'RELIFE HLASUJE', 'relife-avada' ); ?></div>
					</div>
				</div>
				<h2 class="rl-poll__q"><?php echo esc_html( get_the_title( $poll ) ); ?></h2>
				<div class="rl-poll__opts">
					<?php foreach ( rlav_poll_options( $poll->ID ) as $i => $opt ) : ?>
						<button type="button" class="rl-opt js-opt" data-index="<?php echo (int) $i; ?>" data-pct="<?php echo (int) $opt['pct']; ?>" aria-pressed="false">
							<span class="rl-opt__row">
								<span class="rl-opt__radio" aria-hidden="true"><i></i></span>
								<span class="rl-opt__label"><?php echo esc_html( $opt['label'] ); ?></span>
								<span class="rl-opt__pct js-pct"></span>
							</span>
							<span class="rl-opt__bar"><span class="js-bar"></span></span>
						</button>
					<?php endforeach; ?>
				</div>
				<p class="rl-poll__note js-poll-note"><?php esc_html_e( 'Výsledky sa zobrazia po tvojom hlasovaní.', 'relife-avada' ); ?></p>
				<a class="rl-outline-link" href="<?php echo esc_url( rlav_page_url( 'polls' ) ); ?>">
					<?php esc_html_e( 'Pozrieť staršie ankety', 'relife-avada' ); ?> <span aria-hidden="true">→</span>
				</a>
			</div>
		<?php else : ?>
			<p class="rl-muted"><?php esc_html_e( 'Zatiaľ nie je aktívna žiadna anketa.', 'relife-avada' ); ?></p>
			<a class="rl-outline-link" href="<?php echo esc_url( rlav_page_url( 'polls' ) ); ?>"><?php esc_html_e( 'Pozrieť staršie ankety', 'relife-avada' ); ?> <span aria-hidden="true">→</span></a>
		<?php endif; ?>
	</div>

	<div class="rl-panel js-panel-mood" hidden>
		<div class="rl-poll__kicker"><?php esc_html_e( 'TVOJA NÁLADA', 'relife-avada' ); ?></div>
		<h2 class="rl-poll__q"><?php esc_html_e( 'Akú máš dnes náladu?', 'relife-avada' ); ?></h2>
		<p class="rl-muted"><?php esc_html_e( 'Vyber si a upravíme ti výber článkov a kategórií.', 'relife-avada' ); ?></p>
		<div class="rl-moods">
			<?php foreach ( rlav_moods() as $mood ) : ?>
				<button type="button" class="rl-mood js-mood" data-mood="<?php echo esc_attr( $mood['id'] ); ?>" aria-pressed="false">
					<span><?php echo esc_html( $mood['label'] ); ?></span>
					<span class="rl-mood__hint"><?php echo esc_html( strtoupper( implode( ' · ', $mood['sections'] ) ) ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
		<p class="rl-poll__note"><?php esc_html_e( 'Výber si pamätá len tvoj prehliadač.', 'relife-avada' ); ?></p>
	</div>
</aside>
	<?php
	return (string) ob_get_clean();
}

/**
 * Spodné menu (mobil), rozbaľovacie menu Sekcie a panel hľadania.
 */
function rlav_mobile_nav_output(): void {
	if ( empty( rlav_option( 'tabbar' ) ) || is_admin() || is_feed() ) {
		return;
	}
	?>
	<div class="rl-scope rl-scope--bare" id="rl-nav-root">
	<nav class="rl-tabbar" aria-label="<?php esc_attr_e( 'Spodné menu', 'relife-avada' ); ?>">
	<a class="rl-tab js-tab" data-match="home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
		<?php esc_html_e( 'Domov', 'relife-avada' ); ?>
	</a>
	<button type="button" class="rl-tab js-search-toggle" aria-expanded="false" aria-controls="rl-search">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
		<?php esc_html_e( 'Hľadať', 'relife-avada' ); ?>
	</button>
	<button type="button" class="rl-tab rl-tab--menu js-menu-toggle" aria-expanded="false" aria-controls="rl-menu" aria-label="<?php esc_attr_e( 'Otvoriť menu', 'relife-avada' ); ?>" data-open-label="<?php esc_attr_e( 'Zavrieť menu', 'relife-avada' ); ?>" data-closed-label="<?php esc_attr_e( 'Otvoriť menu', 'relife-avada' ); ?>">
		<span class="rl-tab__fab">
			<svg class="ico-menu" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			<svg class="ico-close" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</span>
	</button>
	<a class="rl-tab js-tab" data-match="saved" href="<?php echo esc_url( rlav_page_url( 'saved' ) ); ?>">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-6-4-6 4z"/></svg>
		<?php esc_html_e( 'Uložené', 'relife-avada' ); ?>
	</a>
	<a class="rl-tab js-tab" data-match="weekly" href="#newsletter">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 8l9 6 9-6"/></svg>
		Weekly
	</a>
</nav>

	<div id="rl-search" class="rl-search" hidden>
		<?php echo rlav_search_form_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>

	<?php
$menu_sections = rlav_sections();
$menu_formats  = rlav_formats();
$menu_guides   = rlav_posts_by_format( 'ultimate-guide', 4 );
$pillar_slugs  = array( 'tech', 'health', 'money' );
$other_slugs   = array( 'trendy', 'travel', 'style', 'life' );
?>
<div id="rl-menu" class="rl-menu" role="dialog" aria-modal="false" aria-label="<?php esc_attr_e( 'Sekcie', 'relife-avada' ); ?>" hidden>
	<div class="rl-menu__inner">
		<div class="rl-kicker">RELIFE</div>
		<h2 class="rl-menu__title"><?php esc_html_e( 'Sekcie', 'relife-avada' ); ?></h2>
		<?php echo rlav_search_form_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="rl-menu__head"><h3><?php esc_html_e( 'Hlavné piliere', 'relife-avada' ); ?></h3><span class="rl-muted"><?php esc_html_e( '3 sekcie', 'relife-avada' ); ?></span></div>
		<?php foreach ( $pillar_slugs as $slug ) : ?>
			<?php
			if ( ! isset( $menu_sections[ $slug ] ) ) {
				continue;
			}
			$sec  = $menu_sections[ $slug ];
			$term = get_category_by_slug( $slug );
			?>
			<a class="rl-mpillar" style="background:<?php echo esc_attr( $sec['color'] ); ?>" href="<?php echo esc_url( $term instanceof WP_Term ? (string) get_category_link( $term ) : home_url( '/' ) ); ?>">
				<span class="rl-mpillar__initial" aria-hidden="true"><?php echo esc_html( mb_substr( $sec['name'], 0, 1 ) ); ?></span>
				<span class="rl-mpillar__top">
					<span class="rl-mpillar__name"><?php echo esc_html( $sec['name'] ); ?></span>
					<span class="rl-mpillar__arrow" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
				</span>
				<span class="rl-mpillar__desc"><?php echo esc_html( $sec['desc'] ); ?></span>
				<span class="rl-mpillar__chips">
					<?php foreach ( array_filter( array_map( 'trim', explode( '•', $sec['topics'] ) ) ) as $topic ) : ?>
						<span><?php echo esc_html( $topic ); ?></span>
					<?php endforeach; ?>
				</span>
			</a>
		<?php endforeach; ?>

		<div class="rl-menu__head"><h3><?php esc_html_e( 'Ďalšie sekcie', 'relife-avada' ); ?></h3><span class="rl-muted"><?php esc_html_e( '4 sekcie', 'relife-avada' ); ?></span></div>
		<div class="rl-mgrid">
			<?php foreach ( $other_slugs as $slug ) : ?>
				<?php
				if ( ! isset( $menu_sections[ $slug ] ) ) {
					continue;
				}
				$sec  = $menu_sections[ $slug ];
				$term = get_category_by_slug( $slug );
				$tops = array_slice( array_filter( array_map( 'trim', explode( '•', $sec['topics'] ) ) ), 0, 2 );
				?>
				<a class="rl-mtile" style="background:<?php echo esc_attr( $sec['color'] ); ?>" href="<?php echo esc_url( $term instanceof WP_Term ? (string) get_category_link( $term ) : home_url( '/' ) ); ?>">
					<span class="rl-mtile__name"><?php echo esc_html( $sec['name'] ); ?></span>
					<span>
						<span class="rl-mtile__desc"><?php echo esc_html( $sec['desc'] ); ?></span>
						<span class="rl-mtile__tops"><?php echo esc_html( mb_strtoupper( implode( ' • ', $tops ) ) ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="rl-menu__head"><h3><?php esc_html_e( 'Podľa formátu', 'relife-avada' ); ?></h3></div>
		<div class="rl-mformats">
			<?php foreach ( $menu_formats as $slug => $f ) : ?>
				<a href="<?php echo esc_url( rlav_format_url( $slug ) ); ?>"><?php echo esc_html( $f['name'] ); ?></a>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $menu_guides ) ) : ?>
			<div class="rl-menu__head"><h3>Ultimate Guides</h3></div>
			<div class="rl-mguides">
				<?php foreach ( $menu_guides as $gid ) : ?>
					<?php $gs = rlav_primary_section( (int) $gid ); ?>
					<a href="<?php echo esc_url( (string) get_permalink( (int) $gid ) ); ?>">
						<span class="rl-dot" style="background:<?php echo esc_attr( rlav_section_color( $gs ) ); ?>"></span>
						<span class="rl-mguides__t"><?php echo esc_html( get_the_title( (int) $gid ) ); ?></span>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'rlav_mobile_nav_output', 5 );

/**
 * Spodné menu potrebuje miesto na konci stránky.
 */
function rlav_tabbar_spacing_css(): void {
	if ( empty( rlav_option( 'tabbar' ) ) ) {
		return;
	}
	echo '<style id="rlav-tabbar-space">@media (max-width:760px){body.rlav-has-tabbar{padding-bottom:calc(96px + env(safe-area-inset-bottom))}}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_head', 'rlav_tabbar_spacing_css', 99 );

/**
 * Formulár vyhľadávania.
 */
function rlav_search_form_html(): string {
	$uid = wp_unique_id( 'rl-s-' );
	ob_start();
	?>
	<form role="search" method="get" class="rl-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Hľadať', 'relife-avada' ); ?></label>
		<input type="search" id="<?php echo esc_attr( $uid ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Hľadaj tému, napr. ETF alebo spánok', 'relife-avada' ); ?>">
		<button type="submit"><?php esc_html_e( 'Hľadať', 'relife-avada' ); ?></button>
	</form>
	<?php
	return (string) ob_get_clean();
}

/**
 * Adresa archívu formátu (alebo domov, ak formát ešte neexistuje).
 */
function rlav_format_url( string $slug ): string {
	$link = get_term_link( $slug, 'relife_format' );
	return is_wp_error( $link ) ? home_url( '/' ) : (string) $link;
}
