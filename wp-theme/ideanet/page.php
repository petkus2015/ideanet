<?php
/**
 * Samostatná stránka.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="top" class="section">
	<div class="wrap entry-wrap">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'entry' ); ?>>
				<div class="section__head reveal">
					<div>
						<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
						<h1><?php the_title(); ?></h1>
					</div>
				</div>
				<div class="entry__body reveal"><?php the_content(); ?></div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
