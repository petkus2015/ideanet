<?php
/**
 * Príklady podľa kanála alebo skupiny.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
mgt_page_head( single_term_title( '', false ), term_description(), 'Atlas podvodov' );
?>
<div class="mgt-wrap"><div class="mgt-cases">
	<?php
	while ( have_posts() ) {
		the_post();
		if ( 'mg_priklad' === get_post_type() ) {
			echo mgt_priklad_item( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'mg_karta' === get_post_type() ) {
			echo '<div class="mg mg-lessons">' . mgt_karta_html( get_post() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<p><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></p>';
		}
	}
	?>
</div></div>
<?php
get_footer();
