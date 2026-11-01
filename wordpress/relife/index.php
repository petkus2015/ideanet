<?php
/**
 * Záložná šablóna (zoznam článkov).
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="rl-wrap rl-block">
	<?php if ( is_home() && ! is_front_page() ) : ?>
		<h1 class="rl-pagetitle"><?php single_post_title(); ?></h1>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="rl-grid rl-grid--4">
			<?php
			while ( have_posts() ) {
				the_post();
				relife_card( (int) get_the_ID() );
			}
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => '←',
				'next_text' => '→',
			)
		);
		?>
	<?php else : ?>
		<p class="rl-muted"><?php esc_html_e( 'Nič sa nenašlo.', 'relife' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
