<?php
/**
 * Štandardná stránka.
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
	<?php
	while ( have_posts() ) {
		the_post();
		?>
		<article <?php post_class( 'rl-prose' ); ?>>
			<h1 class="rl-pagetitle"><?php the_title(); ?></h1>
			<div class="rl-content"><?php the_content(); ?></div>
		</article>
		<?php
	}
	?>
</div>
<?php
get_footer();
