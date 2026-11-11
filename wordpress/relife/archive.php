<?php
/**
 * Archív sekcie (kategórie), formátu, autora a pod.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$term  = get_queried_object();
$color = $term instanceof WP_Term && 'category' === $term->taxonomy ? relife_section_color( $term ) : 'var(--rl-accent)';
?>
<header class="rl-archivehead" style="--rl-section:<?php echo esc_attr( $color ); ?>">
	<div class="rl-wrap">
		<span class="rl-archivehead__bar"></span>
		<h1 class="rl-pagetitle"><?php the_archive_title(); ?></h1>
		<?php the_archive_description( '<p class="rl-muted rl-lead">', '</p>' ); ?>
	</div>
</header>

<div class="rl-wrap rl-block">
	<?php if ( have_posts() ) : ?>
		<div class="rl-grid rl-grid--4">
			<?php
			while ( have_posts() ) {
				the_post();
				relife_card( (int) get_the_ID() );
			}
			?>
		</div>
		<?php relife_pagination(); ?>
	<?php else : ?>
		<p class="rl-muted"><?php esc_html_e( 'V tejto sekcii zatiaľ nič nemáme.', 'relife' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
