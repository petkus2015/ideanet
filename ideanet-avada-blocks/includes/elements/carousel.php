<?php
/**
 * IDEANET — Portfólio karusel.
 *
 * Jeden znovupoužiteľný element — na stránku vložíte samostatnú inštanciu
 * pre videá, grafiku aj sociálne siete (každá s vlastným nastavením
 * pomeru strán a režimu video/obrázok).
 *
 * Shortcode: [ideanet_carousel]
 * Repeater:  items (kategória, názov, popis, štítok, náhľad, video)
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Carousel {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_carousel', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Portfólio karusel', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_carousel',
				'icon'      => 'fusiona-slider',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kotva pre odkazy (id sekcie, napr. video)', 'ideanet-avada-blocks' ),
						'param_name' => 'anchor',
						'value'      => 'video',
						'description' => __( 'Z iných blokov naň môžete odkazovať cez #video.', 'ideanet-avada-blocks' ),
					),
					array(
						'type'       => 'select',
						'heading'    => __( 'Zdroj obsahu z Nástroje → IDEANET obsah', 'ideanet-avada-blocks' ),
						'param_name' => 'content_source',
						'value'      => array(
							''                  => __( 'Žiadny — len z tohto panelu / defaulty', 'ideanet-avada-blocks' ),
							'carousel_video'    => __( 'Videografia', 'ideanet-avada-blocks' ),
							'carousel_grafika'  => __( 'Grafický dizajn', 'ideanet-avada-blocks' ),
							'carousel_social'   => __( 'Sociálne siete', 'ideanet-avada-blocks' ),
						),
						'default'     => '',
						'description' => __( 'Vyberte, ktorú sekciu z admin stránky „IDEANET obsah“ má tento konkrétny karusel použiť. Nesúvisí s poľom Kotva vyššie — nechajte prázdne, ak chcete spravovať obsah len tu v builderi.', 'ideanet-avada-blocks' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Názov karuselu', 'ideanet-avada-blocks' ),
						'param_name' => 'title',
						'value'      => 'Videografia',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Poznámka pod názvom', 'ideanet-avada-blocks' ),
						'param_name' => 'note',
						'value'      => 'Vertikálne formáty 9:16 — reels, produkt, event',
					),
					array(
						'type'       => 'radio_button_set',
						'heading'    => __( 'Režim kariet', 'ideanet-avada-blocks' ),
						'param_name' => 'mode',
						'value'      => array(
							'video' => __( 'Video (prehrávanie + lightbox)', 'ideanet-avada-blocks' ),
							'image' => __( 'Iba obrázok (lightbox)', 'ideanet-avada-blocks' ),
						),
						'default'    => 'video',
					),
					array(
						'type'       => 'select',
						'heading'    => __( 'Pomer strán karty', 'ideanet-avada-blocks' ),
						'param_name' => 'ratio',
						'value'      => array(
							'9/16' => '9 : 16 (vertikálne video)',
							'4/5'  => '4 : 5 (grafika)',
							'1/1'  => '1 : 1 (štvorec)',
						),
						'default'    => '9/16',
					),
					array(
						'type'       => 'range',
						'heading'    => __( 'Kariet naraz — desktop', 'ideanet-avada-blocks' ),
						'param_name' => 'cards_desktop',
						'min'        => '2',
						'max'        => '6',
						'step'       => '0.5',
						'value'      => '5.5',
					),
					array(
						'type'       => 'range',
						'heading'    => __( 'Kariet naraz — mobil', 'ideanet-avada-blocks' ),
						'param_name' => 'cards_mobile',
						'min'        => '1',
						'max'        => '2',
						'step'       => '0.5',
						'value'      => '1.5',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Ukážky', 'ideanet-avada-blocks' ),
						'description' => __( 'Pridajte ľubovoľný počet ukážok. Pri režime „Iba obrázok“ pole Video nevyplňujte.', 'ideanet-avada-blocks' ),
						'param_name'  => 'items',
						'value'       => array(
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Kategória', 'ideanet-avada-blocks' ),
								'param_name' => 'category',
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
								'type'       => 'textfield',
								'heading'    => __( 'Štítok vpravo hore (dĺžka / formát)', 'ideanet-avada-blocks' ),
								'param_name' => 'meta',
								'value'      => '',
							),
							array(
								'type'       => 'upload',
								'heading'    => __( 'Náhľadový obrázok', 'ideanet-avada-blocks' ),
								'param_name' => 'poster',
								'value'      => '',
							),
							array(
								'type'       => 'upload',
								'heading'    => __( 'Video (MP4) — len pri režime Video', 'ideanet-avada-blocks' ),
								'param_name' => 'video',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Pre koho (firma) — len pri režime Video', 'ideanet-avada-blocks' ),
								'param_name' => 'client',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Tagy — kto je na videu, oddeľte ; — len pri režime Video', 'ideanet-avada-blocks' ),
								'param_name' => 'tags',
								'value'      => '',
							),
							array(
								'type'       => 'radio_button_set',
								'heading'    => __( 'Natáčal — len pri režime Video', 'ideanet-avada-blocks' ),
								'param_name' => 'natacal',
								'value'      => array(
									'yes' => __( 'Áno', 'ideanet-avada-blocks' ),
									'no'  => __( 'Nie', 'ideanet-avada-blocks' ),
								),
								'default'    => 'no',
							),
							array(
								'type'       => 'radio_button_set',
								'heading'    => __( 'Strihal — len pri režime Video', 'ideanet-avada-blocks' ),
								'param_name' => 'strihal',
								'value'      => array(
									'yes' => __( 'Áno', 'ideanet-avada-blocks' ),
									'no'  => __( 'Nie', 'ideanet-avada-blocks' ),
								),
								'default'    => 'no',
							),
							array(
								'type'       => 'radio_button_set',
								'heading'    => __( 'Písal scenár — len pri režime Video', 'ideanet-avada-blocks' ),
								'param_name' => 'scenar',
								'value'      => array(
									'yes' => __( 'Áno', 'ideanet-avada-blocks' ),
									'no'  => __( 'Nie', 'ideanet-avada-blocks' ),
								),
								'default'    => 'no',
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
				'anchor'         => '',
				'content_source' => '',
				'title'          => '',
				'note'           => '',
				'mode'           => 'video',
				'ratio'          => '9/16',
				'cards_desktop'  => '5.5',
				'cards_mobile'   => '1.5',
				'items'          => array(),
			),
			$atts,
			'ideanet_carousel'
		);

		$mode     = 'image' === $args['mode'] ? 'image' : 'video';
		$defaults = 'image' === $mode ? ideanet_blocks_default_graphic_items() : ideanet_blocks_default_video_items();
		// „Zdroj obsahu“ je samostatné pole od Kotvy (tá slúži len na odkazy
		// #video/#grafika a nemá s obsahom nič spoločné — predtým sa z nej
		// obsah odvodzoval, čo spôsobovalo, že karusel s nezmenenou/predvolenou
		// kotvou "video" ticho ťahal obsah z inej sekcie, aj keď bol v
		// režime "Iba obrázok"). Bez explicitnej voľby sa použije len builder/default.
		$items = $args['content_source']
			? ideanet_blocks_resolve_items( $args['content_source'], $args['items'], $defaults )
			: ideanet_blocks_multiple_or_default( $args['items'], $defaults );
		$uid   = ideanet_blocks_uid( 'ib-car' );

		ob_start();
		?>
		<section class="ib-scope" <?php echo $args['anchor'] ? 'id="' . esc_attr( sanitize_html_class( $args['anchor'] ) ) . '"' : ''; ?>>
			<div class="ib-wrap">
				<div class="ib-carousel" id="<?php echo esc_attr( $uid ); ?>" data-mode="<?php echo esc_attr( $mode ); ?>">
					<div class="ib-carousel__head ib-reveal">
						<div>
							<?php if ( $args['title'] ) : ?><h3 class="ib-carousel__title"><?php echo esc_html( $args['title'] ); ?></h3><?php endif; ?>
							<?php if ( $args['note'] ) : ?><p class="ib-carousel__note"><?php echo esc_html( $args['note'] ); ?></p><?php endif; ?>
						</div>
						<div class="ib-carousel__ctrl">
							<button class="ib-arrow" data-dir="-1" aria-label="<?php esc_attr_e( 'Predchádzajúce', 'ideanet-avada-blocks' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>
							</button>
							<button class="ib-arrow" data-dir="1" aria-label="<?php esc_attr_e( 'Ďalšie', 'ideanet-avada-blocks' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
							</button>
						</div>
					</div>
					<div class="ib-rail-wrap">
						<div class="ib-rail" role="listbox" aria-label="<?php echo esc_attr( $args['title'] ); ?>" tabindex="0">
							<?php
							foreach ( $items as $it ) {
								echo ideanet_blocks_render_tile(
									array(
										'mode'     => $mode,
										'category' => $it['category'] ?? '',
										'title'    => $it['title'] ?? '',
										'desc'     => $it['description'] ?? '',
										'meta'     => $it['meta'] ?? '',
										'poster'   => $it['poster'] ?? '',
										'video'    => $it['video'] ?? '',
										'client'   => $it['client'] ?? '',
										'tags'     => $it['tags'] ?? '',
										'natacal'  => $it['natacal'] ?? 'no',
										'strihal'  => $it['strihal'] ?? 'no',
										'scenar'   => $it['scenar'] ?? 'no',
									)
								); // Escapované vnútri ideanet_blocks_render_tile().
							}
							?>
							<span class="ib-rail-end" aria-hidden="true"></span>
						</div>
					</div>
					<div class="ib-progress"><span></span></div>
				</div>
			</div>
		</section>
		<style>
			#<?php echo esc_attr( $uid ); ?>{ --ib-per: <?php echo esc_attr( $args['cards_desktop'] ); ?>; }
			#<?php echo esc_attr( $uid ); ?> .ib-tile{ --ratio: <?php echo esc_attr( $args['ratio'] ); ?>; }
			@media (max-width:560px){ #<?php echo esc_attr( $uid ); ?>{ --ib-per: <?php echo esc_attr( $args['cards_mobile'] ); ?>; } }
		</style>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Carousel();
