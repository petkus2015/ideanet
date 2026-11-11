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
