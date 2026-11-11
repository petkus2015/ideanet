<?php
/**
 * Template Name: Lacné letenky
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="rl-flights-hero">
	<div class="rl-wrap">
		<p class="rl-kicker"><?php esc_html_e( 'TRAVEL', 'relife' ); ?></p>
		<h1 class="rl-pagetitle"><?php the_title(); ?></h1>
		<p class="rl-muted rl-lead"><?php esc_html_e( 'Najlacnejšie letenky z Viedne, Bratislavy a Budapešti kamkoľvek. Ceny sa obnovujú trikrát denne, vždy z momondo, Ryanair a Wizz Air.', 'relife' ); ?></p>
		<ul class="rl-flights-legend" aria-label="<?php esc_attr_e( 'Odlety', 'relife' ); ?>">
			<li style="--c:#FF3D8B"><b>VIE</b> <?php esc_html_e( 'Viedeň', 'relife' ); ?></li>
			<li style="--c:#A6FF4D"><b>BTS</b> <?php esc_html_e( 'Bratislava', 'relife' ); ?></li>
			<li style="--c:#8F5CFF"><b>BUD</b> <?php esc_html_e( 'Budapešť', 'relife' ); ?></li>
		</ul>
	</div>
</section>
<section class="rl-wrap rl-block rl-flights">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</section>
<?php
get_footer();
