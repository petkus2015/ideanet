<?php
/**
 * Stránka sa nenašla.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="top" class="section">
	<div class="wrap entry-wrap">
		<div class="section__head reveal">
			<div>
				<p class="eyebrow"><span class="dot"></span> 404</p>
				<h1><?php esc_html_e( 'Táto stránka tu nie je', 'ideanet' ); ?></h1>
			</div>
		</div>
		<p class="lead"><?php esc_html_e( 'Možno sa presunula alebo je v odkaze preklep. Skúste úvod — odtiaľ sa dostanete všade.', 'ideanet' ); ?></p>
		<p><a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Späť na úvod', 'ideanet' ); ?></a></p>
	</div>
</main>
<?php
get_footer();
