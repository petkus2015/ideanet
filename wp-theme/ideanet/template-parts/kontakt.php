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
$sent   = isset( $_GET['dopyt'] ) ? sanitize_key( wp_unslash( $_GET['dopyt'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- len rozhodnutie, ktorú hlášku zobraziť.
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

		<form class="form reveal" id="contactForm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
			<input type="hidden" name="action" value="ideanet_dopyt">
			<?php wp_nonce_field( 'ideanet_dopyt', 'ideanet_nonce' ); ?>

			<p class="ideanet-trap" aria-hidden="true" style="position:absolute;left:-9999px">
				<label for="ideanet_web"><?php esc_html_e( 'Toto pole nevypĺňajte', 'ideanet' ); ?></label>
				<input type="text" id="ideanet_web" name="ideanet_web" tabindex="-1" autocomplete="off">
			</p>

			<div class="field">
				<label for="name"><?php echo esc_html( ideanet_opt( 'form_name' ) ); ?></label>
				<input id="name" name="name" type="text" autocomplete="name" required placeholder="<?php echo esc_attr( ideanet_opt( 'form_name_ph' ) ); ?>">
				<small class="err" data-for="name"></small>
			</div>
			<div class="field">
				<label for="email"><?php echo esc_html( ideanet_opt( 'form_email' ) ); ?></label>
				<input id="email" name="email" type="email" autocomplete="email" required placeholder="<?php echo esc_attr( ideanet_opt( 'form_email_ph' ) ); ?>">
				<small class="err" data-for="email"></small>
			</div>
			<div class="field">
				<label for="type"><?php echo esc_html( ideanet_opt( 'form_type' ) ); ?></label>
				<select id="type" name="type">
					<?php foreach ( ideanet_form_options() as $group => $options ) : ?>
						<optgroup label="<?php echo esc_attr( $group ); ?>">
							<?php foreach ( $options as $option ) : ?>
								<option><?php echo esc_html( $option ); ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field">
				<label for="msg"><?php echo esc_html( ideanet_opt( 'form_msg' ) ); ?></label>
				<textarea id="msg" name="msg" rows="4" required placeholder="<?php echo esc_attr( ideanet_opt( 'form_msg_ph' ) ); ?>"></textarea>
				<small class="err" data-for="msg"></small>
			</div>

			<?php if ( ideanet_opt( 'form_gdpr' ) ) : ?>
				<p class="form__gdpr"><?php ideanet_text( 'form_gdpr' ); ?></p>
			<?php endif; ?>

			<button class="btn btn--primary btn--full" type="submit"><?php echo esc_html( ideanet_opt( 'form_submit' ) ); ?></button>
			<p class="form__status" id="formStatus" role="status" aria-live="polite">
				<?php
				if ( 'ok' === $sent ) {
					echo esc_html( ideanet_tokens( ideanet_opt( 'form_ok' ) ) );
				} elseif ( 'chyba' === $sent ) {
					echo esc_html( ideanet_tokens( ideanet_opt( 'form_err' ) ) );
				}
				?>
			</p>
		</form>
	</div>
</section>
