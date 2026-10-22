<?php
/**
 * Podmienky spolupráce.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$facts = ideanet_items( 'ideanet_podmienka', 20 );
$links = array(
	array( ideanet_opt( 'ponuka_firmy_url' ), ideanet_opt( 'ponuka_firmy_label' ) ),
	array( ideanet_opt( 'ponuka_agentury_url' ), ideanet_opt( 'ponuka_agentury_label' ) ),
);
$links = array_values( array_filter( $links, function ( $l ) {
	return '' !== $l[0];
} ) );
?>
<section class="section" id="podmienky">
	<div class="wrap">
		<?php ideanet_section_head( 'podmienky' ); ?>

		<?php if ( $facts ) : ?>
			<div class="facts reveal">
				<?php foreach ( $facts as $fact ) : ?>
					<div class="fact">
						<h3><?php echo esc_html( get_the_title( $fact ) ); ?></h3>
						<p><?php echo esc_html( wp_strip_all_tags( $fact->post_content ) ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $links ) : ?>
			<div class="grab reveal">
				<p><?php ideanet_text( 'podmienky_grab' ); ?></p>
				<div class="grab__links">
					<?php foreach ( $links as $link ) : ?>
						<a class="btn btn--ghost" href="<?php echo esc_url( $link[0] ); ?>"><?php echo esc_html( $link[1] ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
