<?php
/**
 * Úvodná obrazovka.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$chips   = array_slice( ideanet_lines( 'hero_chips' ), 0, 6 );
$marquee = array_filter( array_map( 'trim', explode( ',', ideanet_opt( 'hero_marquee' ) ) ) );
?>
<section class="hero">
	<div class="wrap hero__inner">
		<div class="hero__copy">
			<p class="eyebrow reveal"><span class="dot"></span> <?php echo esc_html( ideanet_opt( 'hero_eyebrow' ) ); ?></p>
			<h1 class="reveal">
				<span class="l1"><?php echo esc_html( ideanet_opt( 'hero_title' ) ); ?></span><br>
				<span class="l2"><?php echo esc_html( ideanet_opt( 'hero_title_2' ) ); ?> </span><span class="grad"><?php echo esc_html( ideanet_opt( 'hero_title_hl' ) ); ?></span>.
			</h1>
			<p class="lead reveal"><?php ideanet_text( 'hero_lead' ); ?></p>

			<?php $flow = ideanet_lines( 'hero_flow' ); ?>
			<?php if ( $flow ) : ?>
				<ul class="hero__flow reveal" role="list">
					<?php foreach ( $flow as $i => $step ) : ?>
						<li><span><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span> <?php echo esc_html( $step ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="hero__cta reveal">
				<a class="btn btn--primary" href="<?php echo esc_url( ideanet_opt( 'hero_cta1_url' ) ); ?>"><?php echo esc_html( ideanet_opt( 'hero_cta1' ) ); ?></a>
				<a class="btn btn--ghost" href="<?php echo esc_url( ideanet_opt( 'hero_cta2_url' ) ); ?>"><?php echo esc_html( ideanet_opt( 'hero_cta2' ) ); ?> <span aria-hidden="true">&#8595;</span></a>
			</div>
		</div>

		<div class="hero__visual reveal" aria-hidden="true">
			<div class="phone">
				<div class="phone__screen">
					<span class="phone__bar"></span>
					<span class="phone__play"></span>
					<span class="phone__caption"><?php echo esc_html( ideanet_opt( 'hero_caption' ) ); ?></span>
				</div>
			</div>
			<?php foreach ( $chips as $i => $chip ) : ?>
				<span class="chip chip--<?php echo (int) ( $i + 1 ); ?>"><?php echo esc_html( $chip ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( $marquee ) : ?>
		<div class="marquee" aria-hidden="true">
			<div class="marquee__track">
				<?php
				/* pás sa posúva donekonečna, preto rovnaký zoznam dvakrát */
				for ( $pass = 0; $pass < 2; $pass++ ) {
					foreach ( $marquee as $word ) {
						echo '<span>' . esc_html( $word ) . '</span><i>◆</i>';
					}
				}
				?>
			</div>
		</div>
	<?php endif; ?>
</section>
