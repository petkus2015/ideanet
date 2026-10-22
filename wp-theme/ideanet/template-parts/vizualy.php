<?php
/**
 * Vizuály a identita.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$shots = ideanet_items( 'ideanet_vizual', 24 );
if ( ! $shots ) {
	return;
}
?>
<section class="section section--shots" id="vizualy">
	<div class="wrap">
		<?php ideanet_section_head( 'vizualy' ); ?>
	</div>

	<div class="reels reels--shots" id="shots">
		<button class="reels__arrow reels__arrow--prev" id="prevVis" aria-label="<?php esc_attr_e( 'Predchádzajúci vizuál', 'ideanet' ); ?>" aria-controls="visRail">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>

		<div class="rail rail--shots" id="visRail" tabindex="0" role="listbox" aria-label="<?php esc_attr_e( 'Ukážky vizuálov a identít', 'ideanet' ); ?>">
			<?php foreach ( $shots as $i => $shot ) : ?>
				<?php $img = ideanet_image( $shot, 'ideanet-vizual', 'image' ); ?>
				<article class="shot" data-i="<?php echo (int) $i; ?>" role="option" aria-selected="false" aria-label="<?php echo esc_attr( get_the_title( $shot ) ); ?>">
					<?php if ( $img ) : ?>
						<img src="<?php echo esc_url( $img ); ?>"
							 alt="<?php
								/* translators: %s: názov vizuálu. */
								echo esc_attr( sprintf( __( 'Ukážka vizuálu — %s', 'ideanet' ), get_the_title( $shot ) ) );
								?>"
							 loading="lazy" decoding="async" width="1080" height="1350">
					<?php endif; ?>
					<div class="shot__meta">
						<?php if ( ideanet_meta( $shot->ID, 'cat' ) ) : ?>
							<span class="shot__cat"><?php echo esc_html( ideanet_meta( $shot->ID, 'cat' ) ); ?></span>
						<?php endif; ?>
						<h3><?php echo esc_html( get_the_title( $shot ) ); ?></h3>
						<p><?php echo esc_html( get_the_excerpt( $shot ) ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<button class="reels__arrow reels__arrow--next" id="nextVis" aria-label="<?php esc_attr_e( 'Ďalší vizuál', 'ideanet' ); ?>" aria-controls="visRail">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>

		<div class="rail__dots" id="visDots" role="tablist" aria-label="<?php esc_attr_e( 'Prejsť na vizuál', 'ideanet' ); ?>">
			<?php foreach ( $shots as $i => $shot ) : ?>
				<button role="tab" data-i="<?php echo (int) $i; ?>"
					aria-label="<?php
						/* translators: 1: poradie, 2: názov vizuálu. */
						echo esc_attr( sprintf( __( 'Vizuál %1$d: %2$s', 'ideanet' ), $i + 1, get_the_title( $shot ) ) );
						?>"></button>
			<?php endforeach; ?>
		</div>
	</div>
</section>
