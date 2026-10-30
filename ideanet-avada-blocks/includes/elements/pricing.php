<?php
/**
 * IDEANET — Ferové ceny (interaktívna kalkulačka).
 *
 * Namiesto statických balíkov počíta orientačnú cenu naživo podľa
 * počtu reelsov a zvolenej rýchlosti dodania (cena za kus × príplatok
 * podľa rýchlosti) — pozri assets/js/blocks.js → initCalculator().
 *
 * Shortcode: [ideanet_pricing]
 * Repeater:  speeds (úroveň rýchlosti dodania, dni, príplatok v %)
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
				'name'      => __( 'IDEANET — Ferové ceny (kalkulačka)', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_pricing',
				'icon'      => 'fusiona-price-list',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kotva pre odkazy (id sekcie, nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'anchor',
						'value'      => 'cennik',
						'description' => __( 'Z iných blokov (napr. navigácia) naň môžete odkazovať cez #cennik.', 'ideanet-avada-blocks' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kicker', 'ideanet-avada-blocks' ),
						'param_name' => 'kicker',
						'value'      => 'Ferové ceny',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => 'Koľko stojí',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená časť', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => 'vaša séria reelsov?',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Podnadpis', 'ideanet-avada-blocks' ),
						'param_name' => 'sub',
						'value'      => 'Vyberte počet reelsov a rýchlosť dodania — cena sa prepočíta hneď.',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Cena za jeden reels (€, bez DPH)', 'ideanet-avada-blocks' ),
						'param_name' => 'price_per_reel',
						'value'      => '45',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Mena / symbol', 'ideanet-avada-blocks' ),
						'param_name' => 'currency',
						'value'      => '€',
					),
					array(
						'type'       => 'range',
						'heading'    => __( 'Minimálny počet reelsov', 'ideanet-avada-blocks' ),
						'param_name' => 'reels_min',
						'min'        => '1',
						'max'        => '10',
						'step'       => '1',
						'value'      => '1',
					),
					array(
						'type'       => 'range',
						'heading'    => __( 'Maximálny počet reelsov', 'ideanet-avada-blocks' ),
						'param_name' => 'reels_max',
						'min'        => '5',
						'max'        => '40',
						'step'       => '1',
						'value'      => '20',
					),
					array(
						'type'       => 'range',
						'heading'    => __( 'Predvolený počet reelsov pri načítaní', 'ideanet-avada-blocks' ),
						'param_name' => 'reels_default',
						'min'        => '1',
						'max'        => '40',
						'step'       => '1',
						'value'      => '4',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Úrovne rýchlosti dodania', 'ideanet-avada-blocks' ),
						'description' => __( 'Príplatok je percento navyše k základnej cene (0 = bez príplatku).', 'ideanet-avada-blocks' ),
						'param_name'  => 'speeds',
						'value'       => array(
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Názov úrovne', 'ideanet-avada-blocks' ),
								'param_name' => 'label',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Dodanie za (napr. 5–7 pracovných dní)', 'ideanet-avada-blocks' ),
								'param_name' => 'days_text',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Príplatok v % (0 pre štandard)', 'ideanet-avada-blocks' ),
								'param_name' => 'surcharge',
								'value'      => '0',
							),
						),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tlačidlo — text', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_text',
						'value'      => 'Dohodnúť termín',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tlačidlo — odkaz', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_link',
						'value'      => '#kontakt',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Poznámka pod kalkulačkou', 'ideanet-avada-blocks' ),
						'param_name' => 'note',
						'value'      => 'Orientačná cena bez DPH. Finálna ponuka vždy vychádza z dohody a rozsahu prác.',
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
				'kicker'           => '',
				'heading'          => '',
				'heading_emphasis' => '',
				'sub'              => '',
				'price_per_reel'   => '45',
				'currency'         => '€',
				'reels_min'        => '1',
				'reels_max'        => '20',
				'reels_default'    => '4',
				'speeds'           => array(),
				'cta_text'         => '',
				'cta_link'         => '#kontakt',
				'note'             => '',
			),
			$atts,
			'ideanet_pricing'
		);

		$speeds        = ideanet_blocks_resolve_items( 'pricing_speeds', $args['speeds'], ideanet_blocks_default_speeds() );
		$price_per_reel = (float) str_replace( ',', '.', $args['price_per_reel'] );
		$reels_min     = max( 1, (int) $args['reels_min'] );
		$reels_max     = max( $reels_min, (int) $args['reels_max'] );
		$reels_default = min( $reels_max, max( $reels_min, (int) $args['reels_default'] ) );

		ob_start();
		?>
		<section class="ib-scope" <?php echo $args['anchor'] ? 'id="' . esc_attr( sanitize_html_class( $args['anchor'] ) ) . '"' : ''; ?>>
			<div class="ib-wrap">
				<?php echo ideanet_blocks_head( $args['kicker'], $args['heading'], $args['heading_emphasis'], $args['sub'] ); ?>

				<div class="ib-calc ib-reveal"
					data-price-per-reel="<?php echo esc_attr( $price_per_reel ); ?>"
					data-currency="<?php echo esc_attr( $args['currency'] ); ?>"
					data-min="<?php echo esc_attr( $reels_min ); ?>"
					data-max="<?php echo esc_attr( $reels_max ); ?>">
					<div class="ib-calc__row">
						<div class="ib-calc__field">
							<span class="ib-calc__label"><?php esc_html_e( 'Počet reelsov', 'ideanet-avada-blocks' ); ?></span>
							<div class="ib-calc__stepper">
								<button type="button" class="ib-calc__step" data-dir="-1" aria-label="<?php esc_attr_e( 'Ubrať reels', 'ideanet-avada-blocks' ); ?>">−</button>
								<span class="ib-calc__count" data-ib-count><?php echo esc_html( $reels_default ); ?></span>
								<button type="button" class="ib-calc__step" data-dir="1" aria-label="<?php esc_attr_e( 'Pridať reels', 'ideanet-avada-blocks' ); ?>">+</button>
							</div>
						</div>

						<?php if ( $speeds ) : ?>
							<div class="ib-calc__field">
								<span class="ib-calc__label"><?php esc_html_e( 'Rýchlosť dodania', 'ideanet-avada-blocks' ); ?></span>
								<div class="ib-calc__speeds" role="radiogroup" aria-label="<?php esc_attr_e( 'Rýchlosť dodania', 'ideanet-avada-blocks' ); ?>">
									<?php foreach ( $speeds as $i => $speed ) : ?>
										<?php
										$label     = $speed['label'] ?? '';
										$days_text = $speed['days_text'] ?? '';
										$surcharge = (float) str_replace( ',', '.', $speed['surcharge'] ?? '0' );
										if ( '' === trim( (string) $label ) ) {
											continue;
										}
										?>
										<button type="button" class="ib-calc__speed<?php echo 0 === $i ? ' is-active' : ''; ?>"
											data-surcharge="<?php echo esc_attr( $surcharge ); ?>"
											role="radio" aria-checked="<?php echo 0 === $i ? 'true' : 'false'; ?>">
											<strong><?php echo esc_html( $label ); ?></strong>
											<?php if ( $days_text ) : ?><span><?php echo esc_html( $days_text ); ?></span><?php endif; ?>
										</button>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<div class="ib-calc__result">
						<span class="ib-calc__result-lab"><?php esc_html_e( 'Orientačná cena', 'ideanet-avada-blocks' ); ?></span>
						<p class="ib-calc__total">
							<span data-ib-total><?php echo esc_html( number_format_i18n( $reels_default * $price_per_reel ) ); ?></span>
							<span class="ib-calc__currency"><?php echo esc_html( $args['currency'] ); ?></span>
						</p>
					</div>

					<?php if ( $args['cta_text'] ) : ?>
						<a class="ib-btn ib-btn--full ib-calc__cta" href="<?php echo esc_url( $args['cta_link'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
					<?php endif; ?>

					<?php if ( $args['note'] ) : ?>
						<p class="ib-calc__note"><?php echo esc_html( $args['note'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Pricing();
