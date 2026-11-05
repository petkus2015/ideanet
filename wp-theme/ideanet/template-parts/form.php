<?php
/**
 * Formulár na dopyt.
 *
 * Používa ho sekcia Kontakt na úvodnej stránke aj podstránka s ponukou
 * redizajnu, aby bol formulár na webe len raz a nemuseli sa udržiavať
 * dve kópie. Predvolenú voľbu vo výbere nastavíte tak, že pred vložením
 * tejto časti naplníte $GLOBALS['ideanet_form_type'].
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$sent      = isset( $_GET['dopyt'] ) ? sanitize_key( wp_unslash( $_GET['dopyt'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- len rozhodnutie, ktorú hlášku zobraziť.
$preselect = isset( $GLOBALS['ideanet_form_type'] ) ? (string) $GLOBALS['ideanet_form_type'] : '';
?>
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
							<option<?php selected( $option, $preselect ); ?>><?php echo esc_html( $option ); ?></option>
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
