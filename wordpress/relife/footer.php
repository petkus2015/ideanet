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
				<div class="rl-footer__mark"><?php echo esc_html( get_bloginfo( 'name' ) ?: 'RELIFE' ); ?></div>
				<div class="rl-brand__tag"><?php echo esc_html( strtoupper( get_bloginfo( 'description' ) ?: 'Culture. Future. Better Living.' ) ); ?></div>
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
			</nav>
		</div>
		<div class="rl-footer__legal">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<span><?php esc_html_e( 'Ochrana súkromia · Cookies · Označenie spolupráce a affiliate odkazov', 'relife' ); ?></span>
		</div>
	</div>
</footer>

<nav class="rl-tabbar" aria-label="<?php esc_attr_e( 'Spodné menu', 'relife' ); ?>">
	<a class="rl-tab js-tab" data-match="home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
		<?php esc_html_e( 'Domov', 'relife' ); ?>
	</a>
	<a class="rl-tab js-tab" data-match="sections" href="<?php echo esc_url( home_url( '/#sekcie' ) ); ?>">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
		<?php esc_html_e( 'Sekcie', 'relife' ); ?>
	</a>
	<button type="button" class="rl-tab rl-tab--search js-search-toggle" aria-label="<?php esc_attr_e( 'Hľadať', 'relife' ); ?>">
		<span class="rl-tab__fab"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg></span>
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

<?php wp_footer(); ?>
</body>
</html>
