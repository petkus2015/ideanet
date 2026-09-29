<?php
/**
 * IDEANET — Služby (karty disciplín).
 *
 * Shortcode: [ideanet_services]
 * Repeater:  services (ikona, názov, popis, zoznam bodov, odkaz)
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Services {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_services', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Služby', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_services',
				'icon'      => 'fusiona-list',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kicker (malý štítok)', 'ideanet-avada-blocks' ),
						'param_name' => 'kicker',
						'value'      => 'Služby',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => 'Videotvorca, grafik a content creator',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená časť', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => 'v jednej osobe',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Podnadpis', 'ideanet-avada-blocks' ),
						'param_name' => 'sub',
						'value'      => 'Môžete si objednať len jednu vec. Väčšina klientov ale zistí, že video, grafika aj komunikácia fungujú lepšie, keď za nimi stojí jeden content creator a nie tri oddelené firmy.',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Karty služieb', 'ideanet-avada-blocks' ),
						'description' => __( 'Odporúčané 3 karty (zmestia sa vedľa seba), viac sa zalomí na ďalší riadok.', 'ideanet-avada-blocks' ),
						'param_name'  => 'services',
						'value'       => array(
							array(
								'type'       => 'select',
								'heading'    => __( 'Ikona', 'ideanet-avada-blocks' ),
								'param_name' => 'icon',
								'value'      => ideanet_blocks_icon_choices(),
								'default'    => 'video',
							),
							array(
								'type'       => 'textarea',
								'heading'    => __( 'Vlastné SVG (len pri ikone „Vlastné SVG“)', 'ideanet-avada-blocks' ),
								'param_name' => 'custom_icon',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Názov', 'ideanet-avada-blocks' ),
								'param_name' => 'title',
								'value'      => '',
							),
							array(
								'type'       => 'textarea',
								'heading'    => __( 'Popis', 'ideanet-avada-blocks' ),
								'param_name' => 'description',
								'value'      => '',
							),
							array(
								'type'       => 'textarea',
								'heading'    => __( 'Zoznam bodov (jeden na riadok)', 'ideanet-avada-blocks' ),
								'param_name' => 'points',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Text odkazu', 'ideanet-avada-blocks' ),
								'param_name' => 'link_text',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Cieľ odkazu (napr. #video)', 'ideanet-avada-blocks' ),
								'param_name' => 'link_url',
								'value'      => '',
							),
						),
					),
				),
			)
		);
	}

	public function render( $atts, $content = '' ) {
		ideanet_blocks_enqueue_assets();

		$args = shortcode_atts(
			array(
				'kicker'           => '',
				'heading'          => '',
				'heading_emphasis' => '',
				'sub'              => '',
				'services'         => array(),
			),
			$atts,
			'ideanet_services'
		);

		$services = ideanet_blocks_multiple( $args['services'] );

		ob_start();
		?>
		<section class="ib-scope">
			<div class="ib-wrap">
				<?php echo ideanet_blocks_head( $args['kicker'], $args['heading'], $args['heading_emphasis'], $args['sub'] ); ?>

				<?php if ( $services ) : ?>
					<div class="ib-cards">
						<?php foreach ( $services as $s ) : ?>
							<?php
							$title = $s['title'] ?? '';
							$desc  = $s['description'] ?? '';
							$icon  = $s['icon'] ?? 'video';
							$custom = $s['custom_icon'] ?? '';
							$points = ideanet_blocks_lines( $s['points'] ?? '' );
							$link_text = $s['link_text'] ?? '';
							$link_url  = $s['link_url'] ?? '';
							?>
							<article class="ib-card ib-reveal">
								<span class="ib-card__ico" aria-hidden="true"><?php echo ideanet_blocks_render_icon( $icon, $custom ); ?></span>
								<?php if ( $title ) : ?><h3><?php echo esc_html( $title ); ?></h3><?php endif; ?>
								<?php if ( $desc ) : ?><p><?php echo esc_html( $desc ); ?></p><?php endif; ?>
								<?php if ( $points ) : ?>
									<ul class="ib-ticks">
										<?php foreach ( $points as $point ) : ?>
											<li><?php echo esc_html( $point ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<?php if ( $link_text ) : ?>
									<a class="ib-card__link" href="<?php echo esc_url( $link_url ); ?>">
										<?php echo esc_html( $link_text ); ?> <span aria-hidden="true">→</span>
									</a>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Services();
