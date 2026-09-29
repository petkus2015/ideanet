<?php
/**
 * IDEANET — Kontakt (formulár + kontaktné údaje).
 *
 * Shortcode: [ideanet_contact]
 * Formulár momentálne otvára e-mailového klienta (mailto:), rovnako ako
 * na pôvodnom statickom webe — žiadne dáta sa neposielajú na server.
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Contact {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_contact', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Kontakt', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_contact',
				'icon'      => 'fusiona-mail',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kicker', 'ideanet-avada-blocks' ),
						'param_name' => 'kicker',
						'value'      => 'Kontakt',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => 'Povedzte mi,',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená časť', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => 'čo potrebujete',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Podnadpis', 'ideanet-avada-blocks' ),
						'param_name' => 'sub',
						'value'      => 'Napíšte pár viet o projekte. Ozvem sa do 24 hodín s návrhom postupu a orientačnou cenou.',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'E-mail', 'ideanet-avada-blocks' ),
						'param_name' => 'email',
						'value'      => 'ahoj@ideanet.sk',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Telefón', 'ideanet-avada-blocks' ),
						'param_name' => 'phone',
						'value'      => '+421 900 000 000',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Štítok pôsobenia', 'ideanet-avada-blocks' ),
						'param_name' => 'location_label',
						'value'      => 'Pôsobenie',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Text pôsobenia', 'ideanet-avada-blocks' ),
						'param_name' => 'location_text',
						'value'      => 'Slovensko & Česko, po dohode aj ďalej',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis boxu dostupnosti (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'avail_title',
						'value'      => 'Expresné termíny',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Text boxu dostupnosti (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'avail_text',
						'value'      => 'Na eventy si každý týždeň držím voľné okno. Aj dopyt na zajtra má zmysel poslať.',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Zoznam služieb do formulára (jedna na riadok)', 'ideanet-avada-blocks' ),
						'param_name' => 'services_list',
						'value'      => "Videografia\nGrafika\nSociálne siete\nŠkolenia pre tím",
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Placeholder pre meno', 'ideanet-avada-blocks' ),
						'param_name' => 'name_placeholder',
						'value'      => 'Jana Nováková, Kaviareň Zrno',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Placeholder pre e-mail', 'ideanet-avada-blocks' ),
						'param_name' => 'email_placeholder',
						'value'      => 'jana@zrno.sk',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Placeholder pre správu', 'ideanet-avada-blocks' ),
						'param_name' => 'msg_placeholder',
						'value'      => 'Otvárame druhú prevádzku a potrebujeme obsah na otvorenie…',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Text tlačidla', 'ideanet-avada-blocks' ),
						'param_name' => 'submit_text',
						'value'      => 'Odoslať dopyt',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Poznámka pod tlačidlom', 'ideanet-avada-blocks' ),
						'param_name' => 'submit_note',
						'value'      => 'Odoslaním sa otvorí váš e-mailový klient s predvyplnenou správou.',
					),
				),
			)
		);
	}

	public function render( $atts, $content = '' ) {
		ideanet_blocks_enqueue_assets();

		$args = shortcode_atts(
			array(
				'kicker'            => '',
				'heading'           => '',
				'heading_emphasis'  => '',
				'sub'               => '',
				'email'             => '',
				'phone'             => '',
				'location_label'    => '',
				'location_text'     => '',
				'avail_title'       => '',
				'avail_text'        => '',
				'services_list'     => '',
				'name_placeholder'  => '',
				'email_placeholder' => '',
				'msg_placeholder'   => '',
				'submit_text'       => '',
				'submit_note'       => '',
			),
			$atts,
			'ideanet_contact'
		);

		$services = ideanet_blocks_lines( $args['services_list'] );
		$uid      = ideanet_blocks_uid( 'ib-contact' );
		$phone_href = 'tel:' . preg_replace( '/[^0-9+]/', '', $args['phone'] );

		ob_start();
		?>
		<section class="ib-scope">
			<div class="ib-wrap">
				<div class="ib-contact">
					<div class="ib-reveal">
						<?php echo ideanet_blocks_head( $args['kicker'], $args['heading'], $args['heading_emphasis'], $args['sub'], 'ib-head--inline' ); ?>

						<ul class="ib-contact__list">
							<?php if ( $args['email'] ) : ?>
								<li><span class="ib-contact__lab"><?php esc_html_e( 'E-mail', 'ideanet-avada-blocks' ); ?></span>
									<a href="mailto:<?php echo esc_attr( $args['email'] ); ?>"><?php echo esc_html( $args['email'] ); ?></a></li>
							<?php endif; ?>
							<?php if ( $args['phone'] ) : ?>
								<li><span class="ib-contact__lab"><?php esc_html_e( 'Telefón', 'ideanet-avada-blocks' ); ?></span>
									<a href="<?php echo esc_attr( $phone_href ); ?>"><?php echo esc_html( $args['phone'] ); ?></a></li>
							<?php endif; ?>
							<?php if ( $args['location_text'] ) : ?>
								<li><span class="ib-contact__lab"><?php echo esc_html( $args['location_label'] ?: __( 'Pôsobenie', 'ideanet-avada-blocks' ) ); ?></span>
									<span><?php echo esc_html( $args['location_text'] ); ?></span></li>
							<?php endif; ?>
						</ul>

						<?php if ( $args['avail_title'] || $args['avail_text'] ) : ?>
							<div class="ib-avail">
								<span class="ib-avail__dot" aria-hidden="true"></span>
								<p>
									<?php if ( $args['avail_title'] ) : ?><b><?php echo esc_html( $args['avail_title'] ); ?></b><?php endif; ?>
									<?php echo esc_html( $args['avail_text'] ); ?>
								</p>
							</div>
						<?php endif; ?>
					</div>

					<form class="ib-form ib-reveal" data-ib-contact data-mailto="<?php echo esc_attr( $args['email'] ); ?>" novalidate>
						<div class="ib-field">
							<label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Meno a firma', 'ideanet-avada-blocks' ); ?></label>
							<input id="<?php echo esc_attr( $uid ); ?>-name" name="ib_name" type="text" autocomplete="name"
								placeholder="<?php echo esc_attr( $args['name_placeholder'] ); ?>" required>
							<p class="ib-err" data-for="<?php echo esc_attr( $uid ); ?>-name" role="alert"></p>
						</div>

						<div class="ib-field">
							<label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'E-mail', 'ideanet-avada-blocks' ); ?></label>
							<input id="<?php echo esc_attr( $uid ); ?>-email" name="ib_email" type="email" autocomplete="email"
								placeholder="<?php echo esc_attr( $args['email_placeholder'] ); ?>" required>
							<p class="ib-err" data-for="<?php echo esc_attr( $uid ); ?>-email" role="alert"></p>
						</div>

						<?php if ( $services ) : ?>
							<fieldset class="ib-field">
								<legend><?php esc_html_e( 'O čo máte záujem?', 'ideanet-avada-blocks' ); ?></legend>
								<div class="ib-chips">
									<?php foreach ( $services as $s ) : ?>
										<label class="ib-chip">
											<input type="checkbox" name="ib_sluzba" value="<?php echo esc_attr( $s ); ?>">
											<span><?php echo esc_html( $s ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</fieldset>
						<?php endif; ?>

						<div class="ib-field">
							<label for="<?php echo esc_attr( $uid ); ?>-msg"><?php esc_html_e( 'Správa', 'ideanet-avada-blocks' ); ?></label>
							<textarea id="<?php echo esc_attr( $uid ); ?>-msg" name="ib_msg" rows="5"
								placeholder="<?php echo esc_attr( $args['msg_placeholder'] ); ?>" required></textarea>
							<p class="ib-err" data-for="<?php echo esc_attr( $uid ); ?>-msg" role="alert"></p>
						</div>

						<button class="ib-btn ib-btn--full" type="submit"><?php echo esc_html( $args['submit_text'] ); ?></button>
						<?php if ( $args['submit_note'] ) : ?><p class="ib-form__note"><?php echo esc_html( $args['submit_note'] ); ?></p><?php endif; ?>
						<p class="ib-form__status" role="status"></p>
					</form>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Contact();
