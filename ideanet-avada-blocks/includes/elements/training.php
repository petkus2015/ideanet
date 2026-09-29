<?php
/**
 * IDEANET — Školenia pre firmy.
 *
 * Shortcode: [ideanet_training]
 * Repeater:  formats (názov formátu, cena, popis)
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Training {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_training', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Školenia pre firmy', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_training',
				'icon'      => 'fusiona-graduation-cap',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kicker', 'ideanet-avada-blocks' ),
						'param_name' => 'kicker',
						'value'      => 'Pre firmy',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => 'Naučím váš tím',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená časť', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => 'točiť a strihať',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Podnadpis', 'ideanet-avada-blocks' ),
						'param_name' => 'sub',
						'value'      => 'Školenie pre marketingové a social media tímy — vrátane asistentov tvorcov obsahu — ktorí chcú vedieť natočiť a zostrihať dobrý obsah sami, bez toho, aby na každý drobný príspevok volali externého videotvorcu.',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis: pre koho', 'ideanet-avada-blocks' ),
						'param_name' => 'audience_heading',
						'value'      => 'Pre koho je školenie',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Text: pre koho', 'ideanet-avada-blocks' ),
						'param_name' => 'audience_text',
						'value'      => 'Pre marketingové oddelenia, social media manažérov, ich asistentov a majiteľov firiem, ktorí si obsah tvoria interne a chcú, aby vyzeral profesionálne aj bez veľkého rozpočtu na produkciu.',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis: čo si odnesú', 'ideanet-avada-blocks' ),
						'param_name' => 'outcomes_heading',
						'value'      => 'Čo si tím odnesie',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Zoznam — čo si odnesú (jeden na riadok)', 'ideanet-avada-blocks' ),
						'param_name' => 'outcomes',
						'value'      => "Ako natočiť kvalitné video mobilom aj bez veľkého vybavenia\nZáklady kompozície, svetla a zvuku\nRýchly strih pre reels, shorts a stories\nPracovný postup, ktorý tím používa aj bez ďalšej pomoci",
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis karty s cenami', 'ideanet-avada-blocks' ),
						'param_name' => 'card_heading',
						'value'      => 'Formáty a ceny',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Formáty', 'ideanet-avada-blocks' ),
						'param_name'  => 'formats',
						'value'       => array(
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Názov formátu (napr. Poldeň · 4 hodiny)', 'ideanet-avada-blocks' ),
								'param_name' => 'name',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Cena (napr. od 490 €)', 'ideanet-avada-blocks' ),
								'param_name' => 'price',
								'value'      => '',
							),
							array(
								'type'       => 'textarea',
								'heading'    => __( 'Popis', 'ideanet-avada-blocks' ),
								'param_name' => 'description',
								'value'      => '',
							),
						),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Text tlačidla', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_text',
						'value'      => 'Mám záujem o školenie',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Odkaz tlačidla', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_link',
						'value'      => '#kontakt',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Hodnota, ktorá sa predvyplní v kontaktnom formulári', 'ideanet-avada-blocks' ),
						'param_name' => 'preselect',
						'value'      => 'Školenia pre tím',
						'description' => __( 'Musí sa zhodovať s jednou z položiek v poli „Zoznam služieb“ kontaktného bloku.', 'ideanet-avada-blocks' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Poznámka pod tlačidlom', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_note',
						'value'      => 'Obsah aj skupinu prispôsobím vášmu odvetviu a nástrojom, ktoré už používate.',
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
				'audience_heading' => '',
				'audience_text'    => '',
				'outcomes_heading' => '',
				'outcomes'         => '',
				'card_heading'     => '',
				'formats'          => array(),
				'cta_text'         => '',
				'cta_link'         => '#kontakt',
				'preselect'        => '',
				'cta_note'         => '',
			),
			$atts,
			'ideanet_training'
		);

		$formats  = ideanet_blocks_multiple( $args['formats'] );
		$outcomes = ideanet_blocks_lines( $args['outcomes'] );

		ob_start();
		?>
		<section class="ib-scope">
			<div class="ib-wrap">
				<?php echo ideanet_blocks_head( $args['kicker'], $args['heading'], $args['heading_emphasis'], $args['sub'] ); ?>

				<div class="ib-train">
					<div class="ib-train__copy ib-reveal">
						<?php if ( $args['audience_heading'] ) : ?><h3><?php echo esc_html( $args['audience_heading'] ); ?></h3><?php endif; ?>
						<?php if ( $args['audience_text'] ) : ?><p><?php echo wp_kses_post( $args['audience_text'] ); ?></p><?php endif; ?>

						<?php if ( $args['outcomes_heading'] ) : ?><h3><?php echo esc_html( $args['outcomes_heading'] ); ?></h3><?php endif; ?>
						<?php if ( $outcomes ) : ?>
							<ul class="ib-ticks">
								<?php foreach ( $outcomes as $o ) : ?><li><?php echo esc_html( $o ); ?></li><?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>

					<div class="ib-train__card ib-reveal">
						<?php if ( $args['card_heading'] ) : ?><h3><?php echo esc_html( $args['card_heading'] ); ?></h3><?php endif; ?>
						<?php if ( $formats ) : ?>
							<ul class="ib-train__opts">
								<?php foreach ( $formats as $f ) : ?>
									<li>
										<div class="ib-train__opt-row">
											<span class="ib-train__opt-name"><?php echo esc_html( $f['name'] ?? '' ); ?></span>
											<span class="ib-train__opt-price"><?php echo esc_html( $f['price'] ?? '' ); ?></span>
										</div>
										<?php if ( ! empty( $f['description'] ) ) : ?><p><?php echo esc_html( $f['description'] ); ?></p><?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( $args['cta_text'] ) : ?>
							<a class="ib-btn ib-btn--full" href="<?php echo esc_url( $args['cta_link'] ); ?>"
								<?php if ( $args['preselect'] ) : ?>data-ib-preselect="<?php echo esc_attr( $args['preselect'] ); ?>"<?php endif; ?>>
								<?php echo esc_html( $args['cta_text'] ); ?>
							</a>
						<?php endif; ?>
						<?php if ( $args['cta_note'] ) : ?><p class="ib-form__note"><?php echo esc_html( $args['cta_note'] ); ?></p><?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Training();
