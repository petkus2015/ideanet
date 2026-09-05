<?php
/**
 * Odinštalovanie pluginu.
 * Bloky, ktoré ste už vložili do stránok, ani položky v Avada Library nemažeme —
 * sú to vaše dáta. Odstránime len vlastné nastavenie pluginu.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ak_bloky_verzia' );
delete_option( 'ak_bloky_ucet' );
