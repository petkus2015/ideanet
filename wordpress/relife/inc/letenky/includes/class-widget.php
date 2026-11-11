<?php
/**
 * Widget pre bočné panely a pätičku (Vzhľad → Widgety).
 */

defined( 'ABSPATH' ) || exit;

class Lacne_Letenky_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'lacne_letenky',
			'Lacné letenky',
			array( 'description' => 'Najlacnejšie letenky z Viedne a Bratislavy (Bangkok, Dubaj, Abu Dhabí a kamkoľvek).' )
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput -- výstup témy
		echo Lacne_Letenky_Render::html( // phpcs:ignore WordPress.Security.EscapeOutput -- escapované v html()
			array(
				'limit'   => isset( $instance['limit'] ) ? (int) $instance['limit'] : 3,
				'title'   => isset( $instance['title'] ) ? $instance['title'] : '',
				'compact' => 1,
				'sections' => isset( $instance['sections'] ) ? $instance['sections'] : array(),
			)
		);
		if ( function_exists( 'relife_flights_url' ) && '' !== relife_flights_url() ) {
			echo '<a class="ll-sidebar-more" href="' . esc_url( relife_flights_url() ) . '">' . esc_html__( 'Všetky lacné letenky', 'relife' ) . ' →</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$limit = isset( $instance['limit'] ) ? (int) $instance['limit'] : 3;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Nadpis</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" placeholder="bez nadpisu">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">Počet kariet</label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="1" max="24" value="<?php echo esc_attr( $limit ); ?>">
		</p>
		<p>
			<strong>Zobraziť sekciu</strong><br>
			<?php
			$sel = isset( $instance['sections'] ) && is_array( $instance['sections'] ) ? $instance['sections'] : array();
			foreach ( array( 'weekend' => 'Predĺžený víkend', 'long' => 'Dlhší pobyt (do 3 mesiacov)', 'asia' => 'Ázia a Emiráty' ) as $k => $label ) :
				?>
				<label style="display:block"><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'sections' ) ); ?>[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $sel, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
			<span class="description">Nič nezaškrtnuté = predĺžený víkend (a v plnom bloku všetky sekcie).</span>
		</p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'title' => sanitize_text_field( isset( $new['title'] ) ? $new['title'] : '' ),
			'limit' => max( 1, min( 24, (int) ( isset( $new['limit'] ) ? $new['limit'] : 4 ) ) ),
			'sections' => array_values( array_intersect( array( 'weekend', 'long', 'asia' ), isset( $new['sections'] ) && is_array( $new['sections'] ) ? $new['sections'] : array() ) ),
		);
	}
}
