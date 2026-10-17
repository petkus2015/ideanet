<?php
/**
 * IDEANET — O mne (väčšia osobná fotka, krátky príbeh, pohľad na
 * tvorbu a zvýraznené body). Odporúčané miesto: nižšie na stránke,
 * napr. tesne pred blokom Kontakt — nejde o úvodný Hero prvok.
 *
 * Shortcode: [ideanet_about]
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_About {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_about', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — O mne', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_about',
				'icon'      => 'fusiona-user',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Kotva pre odkazy (id sekcie, nepovinné)', 'ideanet-avada-blocks' ),
						'param_name'  => 'anchor',
						'value'       => 'o-mne',
						'description' => __( 'Z iných blokov (napr. navigácia) naň môžete odkazovať cez #o-mne.', 'ideanet-avada-blocks' ),
					),
					array(
						'type'       => 'upload',
						'heading'    => __( 'Fotka', 'ideanet-avada-blocks' ),
						'param_name' => 'photo',
						'value'      => '',
					),
					array(
						'type'       => 'radio_button_set',
						'heading'    => __( 'Strana fotky (desktop)', 'ideanet-avada-blocks' ),
						'param_name' => 'photo_position',
						'value'      => array(
							'right' => __( 'Vpravo', 'ideanet-avada-blocks' ),
							'left'  => __( 'Vľavo', 'ideanet-avada-blocks' ),
						),
						'default'    => 'right',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kicker (malý štítok)', 'ideanet-avada-blocks' ),
						'param_name' => 'kicker',
						'value'      => 'O mne',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => 'Peter Miškus',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená časť', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => '— videotvorca a content creator',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Krátky príbeh / bio', 'ideanet-avada-blocks' ),
						'param_name' => 'bio',
						'value'      => 'Video a grafiku robím pre menšie firmy a eventy už niekoľko rokov — od prvého nápadu po hotový strih, väčšinou sám, bez zdĺhavého vybavovania s agentúrou. Mám rád, keď obsah vznikne rýchlo a vyzerá, akoby za ním stál celý tím.',
					),
					array(
						'type'        => 'textarea',
						'heading'     => __( 'Pohľad na tvorbu (nepovinné)', 'ideanet-avada-blocks' ),
						'description' => __( 'Krátka osobná myšlienka o tom, ako pristupujete k tvorbe — zobrazí sa zvýraznene, ako citát.', 'ideanet-avada-blocks' ),
						'param_name'  => 'approach',
						'value'       => 'Nemám rád obsah naokolo bez dôvodu — každé video alebo vizuál musí niečo riešiť. Radšej menej, ale premyslené, než desať verzií toho istého.',
					),
					array(
						'type'        => 'textarea',
						'heading'     => __( 'Zvýraznené body (oddeľte bodkočiarkou ;)', 'ideanet-avada-blocks' ),
						'param_name'  => 'highlights',
						'description' => __( 'Krátke fakty, napr. roky skúseností alebo počet projektov.', 'ideanet-avada-blocks' ),
						'value'       => "5+ rokov skúseností s videom a grafikou; 100+ dokončených projektov pre firmy a eventy; Celý proces — nápad, natáčanie, strih — zvládam sám",
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tlačidlo — text (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_text',
						'value'      => 'Dohodnúť termín',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tlačidlo — odkaz', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_link',
						'value'      => '#kontakt',
					),
				),
			)
		);
	}

	public function render( $atts, $content = '' ) {
		ideanet_blocks_enqueue_assets();

		$args = shortcode_atts(
			array(
				'anchor'           => '',
				'photo'            => '',
				'photo_position'   => 'right',
				'kicker'           => '',
				'heading'          => '',
				'heading_emphasis' => '',
				'bio'              => '',
				'approach'         => '',
				'highlights'       => '',
				'cta_text'         => '',
				'cta_link'         => '#kontakt',
			),
			$atts,
			'ideanet_about'
		);

		$position   = 'left' === $args['photo_position'] ? 'left' : 'right';
		$highlights = ideanet_blocks_lines( $args['highlights'] );

		ob_start();
		?>
		<section class="ib-scope ib-about" data-photo-pos="<?php echo esc_attr( $position ); ?>" <?php echo $args['anchor'] ? 'id="' . esc_attr( sanitize_html_class( $args['anchor'] ) ) . '"' : ''; ?>>
			<div class="ib-wrap">
				<div class="ib-about__grid">
					<div class="ib-about__copy ib-reveal">
						<?php echo ideanet_blocks_head( $args['kicker'], $args['heading'], $args['heading_emphasis'], '' ); ?>

						<?php if ( $args['bio'] ) : ?>
							<p class="ib-about__bio"><?php echo wp_kses_post( $args['bio'] ); ?></p>
						<?php endif; ?>

						<?php if ( $args['approach'] ) : ?>
							<blockquote class="ib-about__approach"><p><?php echo wp_kses_post( $args['approach'] ); ?></p></blockquote>
						<?php endif; ?>

						<?php if ( $highlights ) : ?>
							<ul class="ib-ticks">
								<?php foreach ( $highlights as $point ) : ?>
									<li><?php echo esc_html( $point ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<?php if ( $args['cta_text'] ) : ?>
							<a class="ib-btn" href="<?php echo esc_url( $args['cta_link'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
						<?php endif; ?>
					</div>

					<?php if ( $args['photo'] ) : ?>
						<div class="ib-about__media ib-reveal">
							<span class="ib-about__glow" aria-hidden="true"></span>
							<img class="ib-about__photo" src="<?php echo ideanet_blocks_esc_media_url( $args['photo'] ); ?>" alt="<?php echo esc_attr( $args['heading'] ); ?>" loading="lazy" decoding="async">
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_About();
