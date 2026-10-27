<?php
/**
 * Rýchle karty (vzdelávacie materiály) s filtrom a tlačou.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
$mgt_intro = get_page_by_path( 'rychle-materialy' );
mgt_page_head(
	'Pochopte to za minútu',
	$mgt_intro && $mgt_intro->post_excerpt ? $mgt_intro->post_excerpt : 'Krátke karty: príklad, varovné znaky, čo robiť a jedna veta na zapamätanie. Do školy aj na rozhovor so starými rodičmi.',
	'Rýchle materiály'
);
?>
<div class="mgt-wrap">
	<div class="mg mgt-cards-tools mg-no-print">
		<?php echo mgt_filter_bar( 'karty', array( 'Pre koho' => 'mg_skupina' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p class="mgt-actions"><button class="mgt-btn mg-print-btn" type="button" hidden>Vytlačiť karty</button>
		<a class="mgt-link-arrow" href="<?php echo esc_url( MGT_URI . '/assets/pdf/rychle-karty.pdf' ); ?>">Karty v PDF</a></p>
	</div>
	<div class="mg mg-lessons">
		<?php
		while ( have_posts() ) {
			the_post();
			echo mgt_karta_html( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
	<?php
	if ( $mgt_intro && '' !== trim( $mgt_intro->post_content ) ) {
		echo '<div class="mgt-content mgt-after-cards">' . apply_filters( 'the_content', $mgt_intro->post_content ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>
<?php
get_footer();
