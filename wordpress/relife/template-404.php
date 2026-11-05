<?php
/**
 * Template Name: Stránka 404
 *
 * Stránka s obsahom 404 pre prípad, že chcete mať odkaz na ňu aj ako na bežnú stránku (napr. v presmerovaní).
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'template-parts/not-found' );
get_footer();
