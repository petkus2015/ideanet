<?php
/**
 * Jeden pojem.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
while ( have_posts() ) :
	the_post();
	mgt_page_head( get_the_title(), '', 'Slovník' );
	echo '<div class="mgt-wrap mgt-content">';
	the_content();
	echo '<p><a class="mgt-link-arrow" href="' . esc_url( get_post_type_archive_link( 'mg_pojem' ) ) . '">Celý slovník</a></p></div>';
endwhile;
get_footer();
