<?php
/**
 * Úvodná stránka — poskladaná zo sekcií.
 *
 * Poradie sekcií je dané tu; či sa niektorá vypíše, sa prepína
 * v Prispôsobiť → IDEANET → Nadpisy sekcií.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="top">
	<?php
	get_template_part( 'template-parts/hero' );

	foreach ( array( 'portfolio', 'vizualy', 'sluzby', 'prekoho', 'proces', 'referencie', 'cennik', 'kalkulacka', 'podmienky', 'kontakt' ) as $section ) {
		if ( ideanet_show( $section ) ) {
			get_template_part( 'template-parts/' . $section );
		}
	}
	?>
</main>

<?php
get_footer();
