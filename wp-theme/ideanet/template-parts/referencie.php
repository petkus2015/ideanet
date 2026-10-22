<?php
/**
 * Referencie klientov.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$quotes = ideanet_items( 'ideanet_referencia', 12 );
if ( ! $quotes ) {
	return;
}
?>
<section class="section section--alt" id="referencie">
	<div class="wrap">
		<?php ideanet_section_head( 'referencie' ); ?>

		<div class="quotes">
			<?php foreach ( $quotes as $quote ) : ?>
				<figure class="quote reveal">
					<blockquote><?php echo esc_html( wp_strip_all_tags( $quote->post_content ) ); ?></blockquote>
					<figcaption>
						<b><?php echo esc_html( ideanet_meta( $quote->ID, 'autor' ) ); ?></b>
						<span><?php echo esc_html( ideanet_meta( $quote->ID, 'rola' ) ); ?></span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
