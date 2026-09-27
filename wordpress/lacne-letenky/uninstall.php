<?php
// Odinštalovanie pluginu: zmaže nastavenia a uložené ceny.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'lacne_letenky_options' );
delete_option( 'lacne_letenky_last_good' );
delete_option( 'lacne_letenky_status' );
delete_transient( 'lacne_letenky_data' );
delete_transient( 'lacne_letenky_fail' );
