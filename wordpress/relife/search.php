<?php
/**
 * Výsledky vyhľadávania.
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
	<h1 class="rl-pagetitle">
		<?php
		/* translators: %s search query */
		printf( esc_html__( 'Výsledky pre: %s', 'relife' ), '<span class="rl-accent">' . esc_html( get_search_query() ) . '</span>' );
		?>
	</h1>
	<div class="rl-searchbox"><?php get_search_form(); ?></div>
	<?php if ( have_posts() ) : ?>
		<div class="rl-grid rl-grid--4">
			<?php
			while ( have_posts() ) {
				the_post();
				if ( 'post' === get_post_type() ) {
					relife_card( (int) get_the_ID() );
				}
			}
			?>
		</div>
		<?php relife_pagination(); ?>
	<?php else : ?>
		<p class="rl-muted"><?php esc_html_e( 'Skús iné kľúčové slovo.', 'relife' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
