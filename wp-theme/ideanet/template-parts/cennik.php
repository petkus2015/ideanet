<?php
/**
 * Férové ceny — orientačná cena si človek poskladá v kalkulačke.
 *
 * Balíky sa na úvodnej stránke nevypisujú; zostávajú v administrácii,
 * ťahá si ich z nich výber vo formulári aj ponuka na jednu stranu.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="section section--alt" id="cennik">
	<div class="wrap">
		<?php ideanet_section_head( 'cennik' ); ?>
		<?php get_template_part( 'template-parts/kalkulacka' ); ?>
	</div>
</section>
