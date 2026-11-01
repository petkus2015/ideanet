<?php
/**
 * Formulár vyhľadávania.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uid = wp_unique_id( 'rl-s-' );
?>
<form role="search" method="get" class="rl-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Hľadať', 'relife' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $uid ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Hľadaj tému, napr. ETF alebo spánok', 'relife' ); ?>">
	<button type="submit"><?php esc_html_e( 'Hľadať', 'relife' ); ?></button>
</form>
