<?php
/**
 * Článok.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
while ( have_posts() ) :
	the_post();
	mgt_page_head( get_the_title(), '', 'Aktuality' );
	echo '<div class="mgt-wrap mgt-content"><p class="mgt-muted">' . esc_html( get_the_date() ) . '</p>';
	the_content();
	echo '</div>';
endwhile;
get_footer();
