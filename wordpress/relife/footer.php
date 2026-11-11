<?php
/**
 * Pätička, newsletter a spodné menu na mobile.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action = (string) get_theme_mod( 'relife_newsletter_action', '' );
?>
</main>

<footer class="rl-footer">
	<div class="rl-wrap">
		<div class="rl-footer__grid">
			<div>
				<?php
				$footer_logo = (int) get_theme_mod( 'relife_footer_logo', 0 );
				$footer_img  = $footer_logo > 0 ? wp_get_attachment_image( $footer_logo, 'full', false, array( 'class' => 'rl-footer__logo-img', 'alt' => get_bloginfo( 'name' ), 'loading' => 'lazy' ) ) : '';
				?>
				<?php if ( '' !== $footer_img ) : ?>
					<a class="rl-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo $footer_img; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php else : ?>
					<div class="rl-footer__mark"><?php echo esc_html( get_bloginfo( 'name' ) ?: 'RELIFE' ); ?></div>
					<div class="rl-brand__tag"><?php echo esc_html( strtoupper( get_bloginfo( 'description' ) ?: 'Culture. Future. Better Living.' ) ); ?></div>
				<?php endif; ?>
				<p class="rl-footer__text"><?php echo esc_html( (string) get_theme_mod( 'relife_tagline_footer', 'Digitálny magazín pre generáciu, ktorá chce vedieť viac, žiť lepšie a byť o krok vpred.' ) ); ?></p>
			</div>

			<div id="newsletter" class="rl-newsletter">
				<h2 class="rl-newsletter__title"><?php esc_html_e( 'RELIFE Weekly', 'relife' ); ?></h2>
				<p><?php esc_html_e( 'Týždenný výber najdôležitejších tém a praktických odporúčaní. Raz týždenne, bez spamu.', 'relife' ); ?></p>
				<form class="rl-newsletter__form" method="post" action="<?php echo esc_url( '' !== $action ? $action : '#newsletter' ); ?>">
					<label class="screen-reader-text" for="rl-email"><?php esc_html_e( 'E-mail', 'relife' ); ?></label>
					<input id="rl-email" type="email" name="email" required placeholder="<?php esc_attr_e( 'Tvoja e-mailová adresa', 'relife' ); ?>" autocomplete="email">
					<button type="submit"><?php esc_html_e( 'Odoberať', 'relife' ); ?></button>
				</form>
			</div>

			<nav class="rl-footer__nav" aria-label="<?php esc_attr_e( 'Pätička', 'relife' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'rl-footer__list',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
				<?php $adv_url = relife_adv_url(); ?>
				<?php $fl_url = function_exists( 'relife_flights_url' ) ? relife_flights_url() : ''; ?>
				<?php if ( '' !== $adv_url || '' !== $fl_url ) : ?>
					<ul class="rl-footer__list rl-footer__list--extra">
						<?php if ( '' !== $fl_url ) : ?><li><a href="<?php echo esc_url( $fl_url ); ?>"><?php esc_html_e( 'Lacné letenky', 'relife' ); ?></a></li><?php endif; ?>
						<?php if ( '' !== $adv_url ) : ?><li><a href="<?php echo esc_url( $adv_url ); ?>"><?php esc_html_e( 'Inzerujte u nás', 'relife' ); ?></a></li><?php endif; ?>
					</ul>
				<?php endif; ?>
			</nav>
		</div>
		<div class="rl-footer__legal">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<span><?php esc_html_e( 'Ochrana súkromia · Cookies · Označenie spolupráce a affiliate odkazov', 'relife' ); ?><?php if ( function_exists( 'relife_google_tag_enabled' ) && relife_google_tag_enabled() && get_theme_mod( 'relife_cookie_banner', true ) && get_theme_mod( 'relife_google_tag_consent', true ) ) : ?> · <button type="button" class="rl-linkbtn js-cookie-settings"><?php esc_html_e( 'Nastavenia cookies', 'relife' ); ?></button><?php endif; ?></span>
		</div>
	</div>
</footer>

<nav class="rl-tabbar" aria-label="<?php esc_attr_e( 'Spodné menu', 'relife' ); ?>">
	<a class="rl-tab js-tab" data-match="home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
		<?php esc_html_e( 'Domov', 'relife' ); ?>
	</a>
	<?php $tab_fl = function_exists( 'relife_flights_url' ) ? relife_flights_url() : ''; ?>
	<?php if ( '' !== $tab_fl ) : ?>
		<a class="rl-tab js-tab" data-match="flights" href="<?php echo esc_url( $tab_fl ); ?>">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z"/></svg>
			<?php esc_html_e( 'Letenky', 'relife' ); ?>
		</a>
	<?php else : ?>
		<button type="button" class="rl-tab js-search-toggle" aria-expanded="false" aria-controls="rl-search">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
			<?php esc_html_e( 'Hľadať', 'relife' ); ?>
		</button>
	<?php endif; ?>
	<button type="button" class="rl-tab rl-tab--menu js-menu-toggle" aria-expanded="false" aria-controls="rl-menu" aria-label="<?php esc_attr_e( 'Otvoriť menu', 'relife' ); ?>" data-open-label="<?php esc_attr_e( 'Zavrieť menu', 'relife' ); ?>" data-closed-label="<?php esc_attr_e( 'Otvoriť menu', 'relife' ); ?>">
		<span class="rl-tab__fab">
			<svg class="ico-menu" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			<svg class="ico-close" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</span>
	</button>
	<a class="rl-tab js-tab" data-match="saved" href="<?php echo esc_url( relife_page_url_by_template( 'template-saved.php' ) ); ?>">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-6-4-6 4z"/></svg>
		<?php esc_html_e( 'Uložené', 'relife' ); ?>
	</a>
	<a class="rl-tab js-tab" data-match="weekly" href="#newsletter">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 8l9 6 9-6"/></svg>
		Weekly
	</a>
</nav>


<?php
$menu_sections = relife_sections();
$menu_formats  = relife_formats();
$menu_guides   = relife_posts_by_format( 'ultimate-guide', 4 );
$pillar_slugs  = array( 'tech', 'health', 'money' );
$other_slugs   = array( 'trendy', 'travel', 'style', 'life' );
?>
<div id="rl-menu" class="rl-menu" role="dialog" aria-modal="false" aria-label="<?php esc_attr_e( 'Sekcie', 'relife' ); ?>" hidden>
	<div class="rl-menu__inner">
		<div class="rl-kicker">RELIFE</div>
		<h2 class="rl-menu__title"><?php esc_html_e( 'Sekcie', 'relife' ); ?></h2>
		<?php get_search_form(); ?>

		<div class="rl-menu__head"><h3><?php esc_html_e( 'Hlavné piliere', 'relife' ); ?></h3><span class="rl-muted"><?php esc_html_e( '3 sekcie', 'relife' ); ?></span></div>
		<?php foreach ( $pillar_slugs as $slug ) : ?>
			<?php
			if ( ! isset( $menu_sections[ $slug ] ) ) {
				continue;
			}
			$sec  = $menu_sections[ $slug ];
			$term = relife_section_term( $slug );
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

		<div class="rl-menu__head"><h3><?php esc_html_e( 'Ďalšie sekcie', 'relife' ); ?></h3><span class="rl-muted"><?php esc_html_e( '4 sekcie', 'relife' ); ?></span></div>
		<div class="rl-mgrid">
			<?php foreach ( $other_slugs as $slug ) : ?>
				<?php
				if ( ! isset( $menu_sections[ $slug ] ) ) {
					continue;
				}
				$sec  = $menu_sections[ $slug ];
				$term = relife_section_term( $slug );
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

		<div class="rl-menu__head"><h3><?php esc_html_e( 'Podľa formátu', 'relife' ); ?></h3></div>
		<div class="rl-mformats">
			<?php foreach ( $menu_formats as $slug => $f ) : ?>
				<a href="<?php echo esc_url( (string) get_term_link( $slug, 'relife_format' ) ); ?>"><?php echo esc_html( $f['name'] ); ?></a>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $menu_guides ) ) : ?>
			<div class="rl-menu__head"><h3>Ultimate Guides</h3></div>
			<div class="rl-mguides">
				<?php foreach ( $menu_guides as $gid ) : ?>
					<?php $gs = relife_primary_section( (int) $gid ); ?>
					<a href="<?php echo esc_url( (string) get_permalink( (int) $gid ) ); ?>">
						<span class="rl-dot" style="background:<?php echo esc_attr( relife_section_color( $gs ) ); ?>"></span>
						<span class="rl-mguides__t"><?php echo esc_html( get_the_title( (int) $gid ) ); ?></span>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $fl_url ) : ?>
			<a class="rl-madv rl-madv--flights" href="<?php echo esc_url( $fl_url ); ?>">
				<span class="rl-madv__t"><b><?php esc_html_e( 'Lacné letenky', 'relife' ); ?></b><small><?php esc_html_e( 'Najlacnejšie lety z Viedne a Bratislavy', 'relife' ); ?></small></span>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
			</a>
		<?php endif; ?>

		<?php if ( '' !== $adv_url ) : ?>
			<a class="rl-madv" href="<?php echo esc_url( $adv_url ); ?>">
				<span class="rl-madv__t"><b><?php esc_html_e( 'Inzerujte u nás', 'relife' ); ?></b><small><?php esc_html_e( 'Overené články, optimalizované pre AEO', 'relife' ); ?></small></span>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
			</a>
		<?php endif; ?>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
