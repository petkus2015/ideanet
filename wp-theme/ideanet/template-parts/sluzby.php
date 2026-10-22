<?php
/**
 * Služby — tri cesty spolupráce a zoznam „čo je vždy v cene".
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$lanes = ideanet_items( 'ideanet_sluzba', 6 );
$vcene = ideanet_lines( 'vcene_items' );
?>
<section class="section" id="sluzby">
	<div class="wrap">
		<?php ideanet_section_head( 'sluzby' ); ?>

		<?php if ( $lanes ) : ?>
			<div class="lanes">
				<?php foreach ( $lanes as $lane ) : ?>
					<?php
					$best   = ideanet_meta( $lane->ID, 'best' );
					$target = ideanet_meta( $lane->ID, 'target' );
					$items  = array_filter( array_map( 'trim', preg_split( '/\R/', ideanet_meta( $lane->ID, 'features' ) ) ) );
					?>
					<a class="lane<?php echo $best ? ' lane--best' : ''; ?> reveal" href="#cennik" data-tab="<?php echo esc_attr( $target ? $target : 'firmy' ); ?>">
						<?php if ( ideanet_meta( $lane->ID, 'tag' ) ) : ?>
							<span class="lane__tag"><?php echo esc_html( ideanet_meta( $lane->ID, 'tag' ) ); ?></span>
						<?php endif; ?>
						<h3><?php echo esc_html( get_the_title( $lane ) ); ?></h3>
						<p><?php echo esc_html( wp_strip_all_tags( $lane->post_content ) ); ?></p>
						<?php if ( $items ) : ?>
							<ul>
								<?php foreach ( $items as $item ) : ?>
									<li><?php echo esc_html( $item ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( ideanet_meta( $lane->ID, 'price' ) ) : ?>
							<span class="lane__price"><?php echo wp_kses( ideanet_meta( $lane->ID, 'price' ), ideanet_kses() ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $vcene ) : ?>
			<div class="incl reveal">
				<div class="incl__head">
					<h3><?php echo esc_html( ideanet_opt( 'vcene_title' ) ); ?></h3>
					<p><?php echo esc_html( ideanet_opt( 'vcene_note' ) ); ?></p>
				</div>
				<ul role="list">
					<?php foreach ( $vcene as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>
