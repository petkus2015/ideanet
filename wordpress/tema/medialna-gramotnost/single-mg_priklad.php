<?php
/**
 * Jeden príklad podvodu.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="mgt-wrap mgt-single-case">
		<p class="mgt-crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Domov</a> <span aria-hidden="true">/</span> <a href="<?php echo esc_url( get_post_type_archive_link( 'mg_priklad' ) ); ?>">Atlas podvodov</a></p>
		<div class="mgt-cases"><?php echo mgt_priklad_item( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php
		$mgt_tests = get_posts( array( 'post_type' => 'mg_test', 'numberposts' => 1, 'meta_key' => '_mg_test_type', 'meta_value' => 'trenazer' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $mgt_tests ) :
			?>
			<div class="mgt-cta">
				<div><h2 class="mgt-h2">Spoznáte to aj v teste?</h2><p>Vyskúšajte si podobné správy a uvidíte, čo ste prehliadli.</p></div>
				<a class="mgt-btn" href="<?php echo esc_url( get_permalink( $mgt_tests[0] ) ); ?>">Spustiť test</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
