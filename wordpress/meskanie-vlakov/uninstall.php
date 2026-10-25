<?php
/**
 * Odinštalovanie: zmaže nastavenia a stiahnuté dáta.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'mv_settings' );
delete_option( 'mv_status' );
delete_transient( 'mv_tt_running' );

$mv_upload = wp_upload_dir( null, false );
$mv_dir    = trailingslashit( $mv_upload['basedir'] ) . 'meskanie-vlakov/';
foreach ( (array) glob( $mv_dir . '*' ) as $mv_file ) {
	if ( is_file( $mv_file ) ) {
		wp_delete_file( $mv_file );
	}
}
if ( is_dir( $mv_dir ) ) {
	rmdir( $mv_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
}
