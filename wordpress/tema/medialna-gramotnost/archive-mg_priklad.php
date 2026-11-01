<?php
/**
 * Atlas podvodov: všetky príklady s filtrom podľa kanála a skupiny.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
$mgt_intro = get_page_by_path( 'atlas-podvodov' );
mgt_page_head(
	$mgt_intro ? $mgt_intro->post_title : 'Atlas podvodov',
	$mgt_intro ? $mgt_intro->post_excerpt : 'Najčastejšie podvody, s ktorými sa ľudia na Slovensku stretávajú. Pri každom je ukážka, varovné znaky a čo robiť.',
	'Atlas podvodov'
);
?>
<div class="mgt-wrap">
	<p class="mgt-note">Ukážky sú vytvorené na výučbu. Napodobňujú skutočné podvody, o ktorých informovali SK-CERT, NBS, Polícia SR a Europol. Mená, čísla a adresy sú zmenené.</p>
	<?php echo mgt_filter_bar( 'atlas', array( 'Kanál' => 'mg_kanal', 'Pre koho' => 'mg_skupina' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="mgt-cases">
		<?php
		while ( have_posts() ) {
			the_post();
			echo mgt_priklad_item( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
</div>
<?php
get_footer();
