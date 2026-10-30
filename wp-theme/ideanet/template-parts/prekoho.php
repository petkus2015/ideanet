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
					<?php
					/* Riadok môže byť len text. Keď značka chýba, nevypisujeme
					   prázdny prvok — zostala by po ňom medzera. */
					$znacka = $item[1] ? $item[0] : '';
					$text   = $item[1] ? $item[1] : $item[0];
					?>
					<li>
						<?php if ( $znacka ) : ?>
							<span aria-hidden="true"><?php echo esc_html( $znacka ); ?></span>
						<?php endif; ?>
						<?php echo esc_html( $text ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
