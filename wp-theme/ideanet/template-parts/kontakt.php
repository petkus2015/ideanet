<?php
/**
 * Kontakt a formulár na dopyt.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$facts  = ideanet_pairs( 'contact_facts', 2 );
$email  = ideanet_opt( 'contact_email' );
$phone  = ideanet_opt( 'contact_phone' );
$social = ideanet_opt( 'contact_social' );
?>
<section class="section section--contact" id="kontakt">
	<div class="wrap contact">
		<div class="contact__copy reveal">
			<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_opt( 'kontakt_eyebrow' ) ); ?></p>
			<h2><?php ideanet_text( 'kontakt_title' ); ?></h2>
			<p class="lead"><?php ideanet_text( 'kontakt_note' ); ?></p>

			<ul class="contact__list" role="list">
				<?php if ( $email ) : ?>
					<li><span aria-hidden="true">✉</span><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
				<?php endif; ?>
				<?php if ( $phone ) : ?>
					<li><span aria-hidden="true">☎</span><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li>
				<?php endif; ?>
				<?php if ( $social ) : ?>
					<li><span aria-hidden="true">◎</span><a href="<?php echo esc_url( ideanet_opt( 'contact_social_url' ) ? ideanet_opt( 'contact_social_url' ) : '#portfolio' ); ?>"><?php echo esc_html( $social ); ?></a></li>
				<?php endif; ?>
			</ul>

			<?php if ( $facts ) : ?>
				<ul class="contact__facts" role="list">
					<?php foreach ( $facts as $fact ) : ?>
						<li>
							<b><?php echo esc_html( $fact[0] ); ?></b>
							<?php if ( '' !== $fact[1] ) : ?>
								<span><?php echo esc_html( $fact[1] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php get_template_part( 'template-parts/form' ); ?>
	</div>
</section>
