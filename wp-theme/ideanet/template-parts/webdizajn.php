<?php
/**
 * Webdizajn — zvislé ukážky hotových webov.
 *
 * Každá karta je 9:16 video, ktoré sa spustí klikom, a pod ním
 * tlačidlo na živý náhľad webu.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$weby = ideanet_items( 'ideanet_web', 12 );
if ( ! $weby ) {
	return;
}

$cta = ideanet_opt( 'webdizajn_cta' );
?>
<section class="section" id="webdizajn">
	<div class="wrap">
		<?php ideanet_section_head( 'webdizajn' ); ?>

		<div class="webs">
			<?php
			foreach ( $weby as $web ) :
				$nazov  = get_the_title( $web );
				$poster = ideanet_image( $web, 'large', 'poster' );
				$mp4    = ideanet_media_url( ideanet_meta( $web->ID, 'video' ) );
				$webm   = ideanet_media_url( ideanet_meta( $web->ID, 'webm' ) );
				$odkaz  = ideanet_meta( $web->ID, 'url' );
				$kat    = ideanet_meta( $web->ID, 'cat' );
				$veta   = get_the_excerpt( $web );
				?>
				<article class="web reveal">
					<div class="web__media">
						<?php if ( $poster ) : ?>
							<img src="<?php echo esc_url( $poster ); ?>"
								alt="<?php
									/* translators: %s: názov webu. */
									echo esc_attr( sprintf( __( 'Ukážka webu — %s', 'ideanet' ), $nazov ) );
									?>"
								loading="lazy" decoding="async" width="1080" height="1920">
						<?php endif; ?>

						<?php if ( $mp4 || $webm ) : ?>
							<video preload="none" playsinline muted loop
								<?php echo $poster ? 'poster="' . esc_url( $poster ) . '"' : ''; ?>
								aria-label="<?php
									/* translators: %s: názov webu. */
									echo esc_attr( sprintf( __( 'Ukážka webu %s', 'ideanet' ), $nazov ) );
									?>">
								<?php if ( $webm ) : ?>
									<source src="<?php echo esc_url( $webm ); ?>" type="video/webm">
								<?php endif; ?>
								<?php if ( $mp4 ) : ?>
									<source src="<?php echo esc_url( $mp4 ); ?>" type="video/mp4">
								<?php endif; ?>
							</video>
						<?php endif; ?>

						<?php if ( $kat ) : ?>
							<span class="web__cat"><?php echo esc_html( $kat ); ?></span>
						<?php endif; ?>

						<?php if ( $mp4 || $webm ) : ?>
							<button class="web__play" type="button" aria-label="<?php
								/* translators: %s: názov webu. */
								echo esc_attr( sprintf( __( 'Prehrať ukážku webu %s', 'ideanet' ), $nazov ) );
								?>">
								<span>
									<svg class="ico-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5l11 7-11 7z"/></svg>
									<svg class="ico-pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h3.4v14H7zM13.6 5H17v14h-3.4z"/></svg>
								</span>
							</button>
						<?php endif; ?>
					</div>

					<div class="web__body">
						<h3><?php echo esc_html( $nazov ); ?></h3>
						<?php if ( $veta ) : ?>
							<p><?php echo esc_html( $veta ); ?></p>
						<?php endif; ?>
						<?php if ( $odkaz && $cta ) : ?>
							<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( $odkaz ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $cta ); ?>
								<span aria-hidden="true">↗</span>
								<span class="screen-reader-text"><?php esc_html_e( '(otvorí sa v novom okne)', 'ideanet' ); ?></span>
							</a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
