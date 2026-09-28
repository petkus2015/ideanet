<?php
// Odinštalovanie pluginu: zmaže nastavenia a uložené ceny.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'lacne_letenky_options' );
delete_option( 'lacne_letenky_last_good' );
delete_option( 'lacne_letenky_status' );
delete_transient( 'lacne_letenky_data' );
delete_transient( 'lacne_letenky_fail' );
delete_option( 'lacne_letenky_live_last' );
delete_option( 'lacne_letenky_live_status' );
delete_transient( 'lacne_letenky_live' );
delete_transient( 'lacne_letenky_live_lock' );
delete_transient( 'lacne_letenky_gbp_rate' );
delete_transient( 'lacne_letenky_live_fail' );
delete_option( 'lacne_letenky_version' );
