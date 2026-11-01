<?php
/**
 * Stránka nenájdená.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
mgt_page_head( 'Túto stránku sme nenašli', 'Možno bola presunutá. Ak ste sem prišli z odkazu v SMS alebo e-maile, buďte opatrní: overte si, kto vám ho poslal.' );
echo '<div class="mgt-wrap"><p><a class="mgt-btn" href="' . esc_url( home_url( '/' ) ) . '">Na domovskú stránku</a></p></div>';
get_footer();
