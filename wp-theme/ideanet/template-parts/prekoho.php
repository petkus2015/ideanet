<?php
/**
 * Pre koho je to určené.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$items = ideanet_pairs( 'prekoho_items', 2 );
?>
<section class="section section--alt" id="prekoho">
	<div class="wrap">
		<?php ideanet_section_head( 'prekoho' ); ?>

		<?php if ( $items ) : ?>
			<ul class="segments reveal" role="list">
				<?php foreach ( $items as $item ) : ?>
					<li><span aria-hidden="true"><?php echo esc_html( $item[0] ); ?></span> <?php echo esc_html( $item[1] ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
