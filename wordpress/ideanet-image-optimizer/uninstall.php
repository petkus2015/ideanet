<?php
/**
 * Odinštalovanie: zmaže nastavenia. Už optimalizované obrázky ostanú v novom formáte.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ino_settings' );
delete_post_meta_by_key( '_ino_optimized' );
