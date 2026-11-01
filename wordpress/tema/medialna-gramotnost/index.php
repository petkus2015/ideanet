<?php
/**
 * Záložná šablóna (blog, vyhľadávanie).
 *
 * @package MedialnaGramotnostTema
 */

get_header();
mgt_page_head( is_search() ? 'Výsledky hľadania: ' . get_search_query() : ( is_home() ? 'Aktuality' : get_the_archive_title() ) );
echo '<div class="mgt-wrap"><ul class="mgt-testlist">';
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		echo '<li><a href="' . esc_url( get_permalink() ) . '"><span class="mgt-testlist-title">' . esc_html( get_the_title() ) . '</span><span class="mgt-testlist-text">' . esc_html( get_the_excerpt() ) . '</span></a></li>';
	}
} else {
	echo '<li><p>Nič sa nenašlo.</p></li>';
}
echo '</ul>';
the_posts_pagination();
echo '</div>';
get_footer();
