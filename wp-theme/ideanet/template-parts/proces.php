<?php
/**
 * Proces — štyri kroky.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$kroky = ideanet_items( 'ideanet_krok', 12 );
if ( ! $kroky ) {
	return;
}
?>
<section class="section" id="proces">
	<div class="wrap">
		<?php ideanet_section_head( 'proces' ); ?>

		<ol class="steps" role="list">
			<?php foreach ( $kroky as $i => $krok ) : ?>
				<li class="step reveal">
					<span class="step__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<h3><?php echo esc_html( get_the_title( $krok ) ); ?></h3>
					<p><?php echo esc_html( wp_strip_all_tags( $krok->post_content ) ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
