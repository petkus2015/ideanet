<?php
/**
 * Webdizajn — ukážky hotových webov.
 *
 * Karta má rovnaký tvar ako ukážka vizuálu — pomer 4:5 a popis
 * položený na médiu. Klik na médium spustí video, tlačidlo v popise
 * otvorí web naživo.
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
	</div>

	<div class="reels reels--webs" id="webs">
		<button class="reels__arrow reels__arrow--prev" id="prevWeb" aria-label="<?php esc_attr_e( 'Predchádzajúca ukážka webu', 'ideanet' ); ?>" aria-controls="webRail">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>

		<div class="rail rail--webs" id="webRail" tabindex="0" role="listbox" aria-label="<?php esc_attr_e( 'Ukážky hotových webov', 'ideanet' ); ?>">
			<?php
			foreach ( $weby as $i => $web ) :
				$nazov  = get_the_title( $web );
				$poster = ideanet_image( $web, 'large', 'poster' );
				$mp4    = ideanet_media_url( ideanet_meta( $web->ID, 'video' ) );
				$webm   = ideanet_media_url( ideanet_meta( $web->ID, 'webm' ) );
				$odkaz  = ideanet_meta( $web->ID, 'url' );
				$kat    = ideanet_meta( $web->ID, 'cat' );
				$veta   = get_the_excerpt( $web );
				?>
				<article class="web" data-i="<?php echo (int) $i; ?>" role="option" aria-selected="false">
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

					<div class="web__meta">
						<?php if ( $kat ) : ?>
							<span class="web__cat"><?php echo esc_html( $kat ); ?></span>
						<?php endif; ?>
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

		<button class="reels__arrow reels__arrow--next" id="nextWeb" aria-label="<?php esc_attr_e( 'Ďalšia ukážka webu', 'ideanet' ); ?>" aria-controls="webRail">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>

		<div class="rail__dots" id="webDots" role="tablist" aria-label="<?php esc_attr_e( 'Prejsť na ukážku webu', 'ideanet' ); ?>">
			<?php foreach ( $weby as $i => $web ) : ?>
				<button role="tab" data-i="<?php echo (int) $i; ?>"
					aria-label="<?php
						/* translators: 1: poradie, 2: názov webu. */
						echo esc_attr( sprintf( __( 'Ukážka %1$d: %2$s', 'ideanet' ), $i + 1, get_the_title( $web ) ) );
						?>"></button>
			<?php endforeach; ?>
		</div>
	</div>
</section>
