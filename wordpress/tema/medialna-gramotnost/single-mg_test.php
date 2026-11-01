<?php
/**
 * Jeden test (trenažér alebo kvíz).
 *
 * @package MedialnaGramotnostTema
 */

get_header();
while ( have_posts() ) :
	the_post();
	mgt_page_head( get_the_title(), mgt_meta( get_the_ID(), 'intro' ), 'Testy' );
	?>
	<div class="mgt-wrap mgt-test">
		<?php echo mgt_render_test_post( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
endwhile;
get_footer();
