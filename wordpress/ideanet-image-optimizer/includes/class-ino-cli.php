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
}

WP_CLI::add_command( 'ideanet-images', 'INO_CLI' );
