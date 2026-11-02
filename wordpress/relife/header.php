<?php
/**
 * Hlavička: logo, navigácia, uložené, vyhľadávanie.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="dark">
	<meta name="theme-color" content="#131313">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text rl-skip" href="#content"><?php esc_html_e( 'Preskočiť na obsah', 'relife' ); ?></a>

<header class="rl-header">
	<div class="rl-wrap rl-header__row">
		<?php if ( has_custom_logo() ) : ?>
			<?php /* the_custom_logo() vypíše vlastný odkaz, preto tu nesmie byť ďalší <a> (vnorené odkazy rozbijú rozloženie). */ ?>
			<div class="rl-brand rl-brand--logo"><?php the_custom_logo(); ?></div>
		<?php else : ?>
			<a class="rl-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<span class="rl-brand__mark"><?php echo esc_html( get_bloginfo( 'name' ) ?: 'RELIFE' ); ?></span>
				<span class="rl-brand__tag"><?php echo esc_html( strtoupper( get_bloginfo( 'description' ) ?: 'Culture. Future. Better Living.' ) ); ?></span>
			</a>
		<?php endif; ?>

		<nav class="rl-nav" aria-label="<?php esc_attr_e( 'Hlavná navigácia', 'relife' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'rl-nav__list',
					'fallback_cb'    => 'relife_fallback_menu',
					'depth'          => 1,
				)
			);
			?>
		</nav>

		<div class="rl-header__tools">
			<a class="rl-pill rl-pill--outline rl-hide-sm" href="<?php echo esc_url( relife_page_url_by_template( 'template-saved.php' ) ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-6-4-6 4z"/></svg>
				<?php esc_html_e( 'Uložené', 'relife' ); ?>
				<span class="rl-count js-saved-count">0</span>
			</a>
			<button type="button" class="rl-iconbtn js-search-toggle" aria-expanded="false" aria-controls="rl-search" aria-label="<?php esc_attr_e( 'Hľadať', 'relife' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
			</button>
		</div>
	</div>
	<div id="rl-search" class="rl-search" hidden>
		<div class="rl-wrap"><?php get_search_form(); ?></div>
	</div>
</header>

<main id="content" class="rl-main">
