<?php
/**
 * Jedna rýchla karta.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="mgt-wrap mgt-single-card">
		<p class="mgt-crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Domov</a> <span aria-hidden="true">/</span> <a href="<?php echo esc_url( get_post_type_archive_link( 'mg_karta' ) ); ?>">Rýchle materiály</a></p>
		<div class="mg mg-lessons mg-one"><?php echo mgt_karta_html( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</div>
	<?php
endwhile;
get_footer();
