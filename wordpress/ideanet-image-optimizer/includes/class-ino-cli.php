<?php
defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI: wp ideanet-images optimize [--id=<ID>] [--dry-run]
 */
class INO_CLI {

	/**
	 * Optimalizuje obrázky použité v článkoch.
	 *
	 * ## OPTIONS
	 *
	 * [--id=<id>]
	 * : Spracuje iba jednu prílohu.
	 *
	 * [--dry-run]
	 * : Iba vypíše, čo by sa spracovalo.
	 */
	public function optimize( $args, $assoc ) {
		$dry = ! empty( $assoc['dry-run'] );
		$ids = ! empty( $assoc['id'] ) ? array( (int) $assoc['id'] ) : array();
		if ( ! $ids ) {
			$cursor = 0;
			while ( $batch = INO_Converter::candidate_ids( $cursor, 200 ) ) {
				$ids    = array_merge( $ids, $batch );
				$cursor = end( $batch );
			}
		}
		$ok = $skipped = $err = $saved = 0;
		foreach ( $ids as $id ) {
			$r = INO_Converter::optimize( $id, $dry );
			WP_CLI::log( sprintf( '#%d [%s] %s', $id, $r['status'], $r['message'] ) );
			if ( 'ok' === $r['status'] ) {
				++$ok;
				$saved += max( 0, ( $r['before'] ?? 0 ) - ( $r['after'] ?? 0 ) );
			} elseif ( 'skipped' === $r['status'] ) {
				++$skipped;
			} else {
				++$err;
			}
		}
		WP_CLI::success( sprintf( 'Prevedených %d, preskočených %d, chýb %d, ušetrené %s.', $ok, $skipped, $err, size_format( $saved ) ) );
	}

	/**
	 * Zmaže obrázky z knižnice, ktoré nie sú nikde použité.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Iba vypíše nepoužité obrázky.
	 *
	 * [--yes]
	 * : Bez potvrdenia.
	 */
	public function cleanup( $args, $assoc ) {
		$days   = (int) INO_Converter::settings()['cleanup_days'];
		$cursor = 0;
		$found  = array();
		while ( $ids = INO_Cleaner::image_ids( $cursor, 200, $days ) ) {
			foreach ( $ids as $id ) {
				$cursor = $id;
				if ( ! INO_Cleaner::is_used( $id ) ) {
					$found[] = $id;
					WP_CLI::log( sprintf( '#%d %s', $id, basename( (string) get_attached_file( $id ) ) ) );
				}
			}
		}
		WP_CLI::log( sprintf( 'Nepoužitých obrázkov: %d', count( $found ) ) );
		if ( ! $found || ! empty( $assoc['dry-run'] ) ) {
			return;
		}
		WP_CLI::confirm( 'Natrvalo zmazať tieto obrázky?', $assoc );
		$n = 0;
		foreach ( $found as $id ) {
			if ( INO_Cleaner::is_deletable( $id, $days ) && wp_delete_attachment( $id, true ) ) {
				++$n;
			}
		}
		WP_CLI::success( sprintf( 'Zmazaných: %d.', $n ) );
	}
}

WP_CLI::add_command( 'ideanet-images', 'INO_CLI' );
