<?php
/**
 * Podmienky spolupráce.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$facts = ideanet_items( 'ideanet_podmienka', 20 );
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
	</div>
</section>
