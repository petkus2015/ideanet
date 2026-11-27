<?php
/**
 * Fotky — dlaždice so sadami od klientov.
 *
 * Jedna dlaždica je jedna zákazka, pre ktorú sa aj fotilo. Klik otvorí
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

		<div class="tiles">
			<?php
			foreach ( $sady as $sada ) :
				$fotky = ideanet_gallery_photos( $sada->ID );
				if ( ! $fotky ) {
					continue;
				}

				$nazov  = get_the_title( $sada );
				$obal   = get_the_post_thumbnail_url( $sada, 'large' );
				$obal   = $obal ? $obal : $fotky[0]['full'];
				$kat    = ideanet_meta( $sada->ID, 'cat' );
				$veta   = get_the_excerpt( $sada );
				$pocet  = count( $fotky );
				?>
				<button type="button" class="tile reveal"
					data-fotky="<?php echo esc_attr( wp_json_encode( $fotky ) ); ?>"
					data-nazov="<?php echo esc_attr( $nazov ); ?>"
					aria-label="<?php
						/* translators: 1: názov sady, 2: počet fotiek. */
						echo esc_attr( sprintf( __( 'Otvoriť sadu %1$s, %2$d fotiek', 'ideanet' ), $nazov, $pocet ) );
						?>">
					<span class="tile__media">
						<img src="<?php echo esc_url( $obal ); ?>"
							alt="<?php
								/* translators: %s: názov sady. */
								echo esc_attr( sprintf( __( 'Fotky pre %s', 'ideanet' ), $nazov ) );
								?>"
							loading="lazy" decoding="async">
						<span class="tile__count"><?php echo (int) $pocet; ?></span>
					</span>
					<span class="tile__body">
						<?php if ( $kat ) : ?>
							<span class="tile__cat"><?php echo esc_html( $kat ); ?></span>
						<?php endif; ?>
						<span class="tile__name"><?php echo esc_html( $nazov ); ?></span>
						<?php if ( $veta ) : ?>
							<span class="tile__note"><?php echo esc_html( $veta ); ?></span>
						<?php endif; ?>
					</span>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
</section>
