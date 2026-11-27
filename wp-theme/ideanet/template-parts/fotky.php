<?php
/**
 * Fotky — pás sád od klientov.
 *
 * Jedna karta je jedna zákazka, pre ktorú sa aj fotilo. Klik otvorí
 * celú sadu v prehliadači fotiek. Sady sa pridávajú v IDEANET →
 * Fotky — sady klientov.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$sady = ideanet_items( 'ideanet_fotoset', 12 );
if ( ! $sady ) {
	return;
}
?>
<section class="section section--alt" id="fotky">
	<div class="wrap">
		<?php ideanet_section_head( 'fotky' ); ?>
	</div>

	<div class="reels reels--sets" id="sets">
		<button class="reels__arrow reels__arrow--prev" id="prevSet" aria-label="<?php esc_attr_e( 'Predchádzajúca sada', 'ideanet' ); ?>" aria-controls="setRail">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>

		<div class="rail rail--sets" id="setRail" tabindex="0" role="listbox" aria-label="<?php esc_attr_e( 'Fotosady od klientov', 'ideanet' ); ?>">
			<?php
			foreach ( $sady as $i => $sada ) :
				$fotky = ideanet_gallery_photos( $sada->ID );
				if ( ! $fotky ) {
					continue;
				}

				$nazov = get_the_title( $sada );
				$obal  = get_the_post_thumbnail_url( $sada, 'large' );
				$obal  = $obal ? $obal : $fotky[0]['full'];
				$kat   = ideanet_meta( $sada->ID, 'cat' );
				$veta  = get_the_excerpt( $sada );
				$pocet = ideanet_pocet_fotiek( count( $fotky ) );
				?>
				<button type="button" class="set" data-i="<?php echo (int) $i; ?>" role="option" aria-selected="false"
					data-fotky="<?php echo esc_attr( wp_json_encode( $fotky ) ); ?>"
					data-nazov="<?php echo esc_attr( $nazov ); ?>"
					aria-label="<?php
						/* translators: 1: názov sady, 2: počet fotiek aj so slovom, napríklad „10 fotiek“. */
						echo esc_attr( sprintf( __( 'Otvoriť sadu %1$s, %2$s', 'ideanet' ), $nazov, $pocet ) );
						?>">
					<img src="<?php echo esc_url( $obal ); ?>"
						alt="<?php
							/* translators: %s: názov sady. */
							echo esc_attr( sprintf( __( 'Fotky pre %s', 'ideanet' ), $nazov ) );
							?>"
						loading="lazy" decoding="async" width="1080" height="1350">
					<span class="set__count" aria-hidden="true"><?php echo esc_html( $pocet ); ?></span>
					<span class="set__meta">
						<?php if ( $kat ) : ?>
							<span class="set__cat"><?php echo esc_html( $kat ); ?></span>
						<?php endif; ?>
						<span class="set__name"><?php echo esc_html( $nazov ); ?></span>
						<?php if ( $veta ) : ?>
							<span class="set__note"><?php echo esc_html( $veta ); ?></span>
						<?php endif; ?>
					</span>
				</button>
			<?php endforeach; ?>
		</div>

		<button class="reels__arrow reels__arrow--next" id="nextSet" aria-label="<?php esc_attr_e( 'Ďalšia sada', 'ideanet' ); ?>" aria-controls="setRail">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>

		<div class="rail__dots" id="setDots" role="tablist" aria-label="<?php esc_attr_e( 'Prejsť na sadu', 'ideanet' ); ?>">
			<?php foreach ( $sady as $i => $sada ) : ?>
				<button role="tab" data-i="<?php echo (int) $i; ?>"
					aria-label="<?php
						/* translators: 1: poradie, 2: názov sady. */
						echo esc_attr( sprintf( __( 'Sada %1$d: %2$s', 'ideanet' ), $i + 1, get_the_title( $sada ) ) );
						?>"></button>
			<?php endforeach; ?>
		</div>
	</div>
</section>
