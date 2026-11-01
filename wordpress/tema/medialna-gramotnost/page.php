<?php
/**
 * Bežná stránka (obsah z blokového editora).
 *
 * @package MedialnaGramotnostTema
 */

get_header();
while ( have_posts() ) :
	the_post();
	mgt_page_head( get_the_title(), has_excerpt() ? get_the_excerpt() : '', get_the_title() );
	echo '<div class="mgt-wrap mgt-content">';
	the_content();
	echo '</div>';
endwhile;
get_footer();
