<?php
/**
 * Záložná šablóna — výpis príspevkov.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="top" class="section">
	<div class="wrap entry-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="section__head reveal">
				<div>
					<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
					<h1><?php echo esc_html( is_search() ? __( 'Výsledky hľadania', 'ideanet' ) : __( 'Zo zápisníka', 'ideanet' ) ); ?></h1>
				</div>
			</div>

			<div class="entries">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'entry-card reveal' ); ?>>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="entry-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					</article>
					<?php
				endwhile;
				?>
			</div>

			<div class="entry-nav"><?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?></div>
		<?php else : ?>
			<div class="section__head reveal">
				<div>
					<h1><?php esc_html_e( 'Nič tu nie je', 'ideanet' ); ?></h1>
				</div>
			</div>
			<p class="lead"><?php esc_html_e( 'Skúste hľadať inak alebo sa vráťte na úvod.', 'ideanet' ); ?></p>
			<p><a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Späť na úvod', 'ideanet' ); ?></a></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
