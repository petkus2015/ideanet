<?php
/**
 * IDEANET — Cenník (balíky).
 *
 * Shortcode: [ideanet_pricing]
 * Repeater:  plans (názov, cena, popis, funkcie, tlačidlo)
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Pricing {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_pricing', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Cenník', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_pricing',
				'icon'      => 'fusiona-price-list',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kicker', 'ideanet-avada-blocks' ),
						'param_name' => 'kicker',
						'value'      => 'Cenník',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => 'Orientačné balíky',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená časť', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => 'bez skrytých položiek',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Podnadpis', 'ideanet-avada-blocks' ),
						'param_name' => 'sub',
						'value'      => 'Ceny sú bez DPH a slúžia na predstavu. Finálna ponuka aj termíny dodania vždy vychádzajú z dohody a rozsahu prác.',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Cenové balíky', 'ideanet-avada-blocks' ),
						'param_name'  => 'plans',
						'value'       => array(
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Názov balíka', 'ideanet-avada-blocks' ),
								'param_name' => 'title',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Cena (napr. od 390 €)', 'ideanet-avada-blocks' ),
								'param_name' => 'price',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Dodatok k cene (napr. / natáčací deň)', 'ideanet-avada-blocks' ),
								'param_name' => 'price_suffix',
								'value'      => '',
							),
							array(
								'type'       => 'textarea',
								'heading'    => __( 'Krátky popis', 'ideanet-avada-blocks' ),
								'param_name' => 'description',
								'value'      => '',
							),
							array(
								'type'       => 'textarea',
								'heading'    => __( 'Zahrnuté (jedna položka na riadok)', 'ideanet-avada-blocks' ),
								'param_name' => 'features',
								'value'      => '',
							),
							array(
								'type'       => 'radio_button_set',
								'heading'    => __( 'Zvýrazniť ako odporúčaný balík', 'ideanet-avada-blocks' ),
								'param_name' => 'featured',
								'value'      => array(
									'no'  => __( 'Nie', 'ideanet-avada-blocks' ),
									'yes' => __( 'Áno', 'ideanet-avada-blocks' ),
								),
								'default'    => 'no',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Štítok odporúčaného balíka', 'ideanet-avada-blocks' ),
								'param_name' => 'featured_label',
								'value'      => 'Najčastejšia voľba',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Text tlačidla', 'ideanet-avada-blocks' ),
								'param_name' => 'button_text',
								'value'      => 'Mám záujem',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Odkaz tlačidla', 'ideanet-avada-blocks' ),
								'param_name' => 'button_link',
								'value'      => '#kontakt',
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
				'plans'            => array(),
			),
			$atts,
			'ideanet_pricing'
		);

		$plans = ideanet_blocks_multiple( $args['plans'] );

		ob_start();
		?>
		<section class="ib-scope">
			<div class="ib-wrap">
				<?php echo ideanet_blocks_head( $args['kicker'], $args['heading'], $args['heading_emphasis'], $args['sub'] ); ?>

				<?php if ( $plans ) : ?>
					<div class="ib-price-cards">
						<?php foreach ( $plans as $plan ) : ?>
							<?php
							$featured = 'yes' === ( $plan['featured'] ?? 'no' );
							$features = ideanet_blocks_lines( $plan['features'] ?? '' );
							?>
							<article class="ib-price ib-reveal <?php echo $featured ? 'ib-price--hot' : ''; ?>">
								<?php if ( $featured && ! empty( $plan['featured_label'] ) ) : ?>
									<span class="ib-price__flag"><?php echo esc_html( $plan['featured_label'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $plan['title'] ) ) : ?><h3><?php echo esc_html( $plan['title'] ); ?></h3><?php endif; ?>
								<p class="ib-price__val">
									<?php echo esc_html( $plan['price'] ?? '' ); ?>
									<?php if ( ! empty( $plan['price_suffix'] ) ) : ?><span><?php echo esc_html( $plan['price_suffix'] ); ?></span><?php endif; ?>
								</p>
								<?php if ( ! empty( $plan['description'] ) ) : ?><p class="ib-price__desc"><?php echo esc_html( $plan['description'] ); ?></p><?php endif; ?>
								<?php if ( $features ) : ?>
									<ul class="ib-ticks">
										<?php foreach ( $features as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<?php if ( ! empty( $plan['button_text'] ) ) : ?>
									<a class="ib-btn <?php echo $featured ? '' : 'ib-btn--ghost'; ?> ib-btn--full" href="<?php echo esc_url( $plan['button_link'] ?? '#kontakt' ); ?>">
										<?php echo esc_html( $plan['button_text'] ); ?>
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

new Ideanet_Blocks_Pricing();
