<?php
/**
 * Chyba 404.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="rl-wrap rl-block rl-center">
	<p class="rl-kicker">404</p>
	<h1 class="rl-pagetitle"><?php esc_html_e( 'Túto stránku sme nenašli.', 'relife' ); ?></h1>
	<p class="rl-muted rl-lead"><?php esc_html_e( 'Skús hľadanie alebo sa vráť na úvod.', 'relife' ); ?></p>
	<div class="rl-searchbox"><?php get_search_form(); ?></div>
	<a class="rl-pill rl-pill--accent" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Späť na domov', 'relife' ); ?></a>
</div>
<?php
get_footer();
