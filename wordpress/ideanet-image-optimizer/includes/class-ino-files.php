<?php
defined( 'ABSPATH' ) || exit;

/**
 * Zmenšeniny (súbory typu meno-300x200.jpg), ktoré sa dajú zmazať tak, aby ostal len hlavný súbor.
 * Odkazy na zmenšeniny sa v obsahu nahradia hlavným súborom, takže nič nezostane rozbité.
 */
class INO_Sizes {

	/** Zmenšeniny prílohy (z metadát aj osirelé súbory podľa názvu), absolútne cesty. */
	public static function sized_files( $id ) {
		global $wpdb;
		$file = get_attached_file( $id );
		if ( ! $file || ! is_file( $file ) ) {
			return array();
		}
		$meta = wp_get_attachment_metadata( $id );
		$meta = is_array( $meta ) ? $meta : array();
		$dir  = dirname( $file );
		$keep = array( wp_normalize_path( $file ) );
		$stems = array( pathinfo( $file, PATHINFO_FILENAME ) );
		if ( ! empty( $meta['original_image'] ) ) {
			$keep[]  = wp_normalize_path( $dir . '/' . $meta['original_image'] );
			$stems[] = pathinfo( $meta['original_image'], PATHINFO_FILENAME );
		}
		$found = array();
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $sz ) {
			if ( ! empty( $sz['file'] ) ) {
				$found[ $dir . '/' . $sz['file'] ] = 1;
			}
			foreach ( (array) ( $sz['sources'] ?? array() ) as $src ) {
				if ( ! empty( $src['file'] ) ) {
					$found[ $dir . '/' . $src['file'] ] = 1;
				}
			}
		}
		$ext = preg_quote( pathinfo( $file, PATHINFO_EXTENSION ), '/' );
		foreach ( (array) @scandir( $dir ) as $n ) { // phpcs:ignore
			foreach ( $stems as $st ) {
				if ( preg_match( '/^' . preg_quote( $st, '/' ) . '-\d+x\d+\.' . $ext . '$/i', $n ) ) {
					$found[ $dir . '/' . $n ] = 1;
				}
			}
		}
		$out = array();
		foreach ( array_keys( $found ) as $f ) {
			if ( ! in_array( wp_normalize_path( $f ), $keep, true ) && is_file( $f ) ) {
				$out[] = $f;
			}
		}
		if ( $out ) { // súbory, ktoré sú hlavným súborom inej prílohy, nechať
			$rels = array_map( '_wp_relative_upload_path', $out );
			$in   = implode( ',', array_fill( 0, count( $rels ), '%s' ) );
			$own  = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND post_id <> %d AND meta_value IN ($in)", array_merge( array( $id ), $rels ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			if ( $own ) {
				$out = array_values( array_filter( $out, function ( $f ) use ( $own ) {
					return ! in_array( _wp_relative_upload_path( $f ), $own, true );
				} ) );
			}
		}
		return $out;
	}

	public static function bytes( $files ) {
		$t = 0;
		foreach ( $files as $f ) {
			$t += (int) @filesize( $f ); // phpcs:ignore
		}
		return $t;
	}

	public static function image_ids( $after_id, $limit ) {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%%' AND ID > %d ORDER BY ID ASC LIMIT %d",
			$after_id, $limit
		) ) );
	}

	public static function describe( $id, $files ) {
		return array(
			'key'   => (int) $id,
			'id'    => (int) $id,
			'title' => get_the_title( $id ) ?: basename( (string) get_attached_file( $id ) ),
			'thumb' => (string) wp_get_attachment_image_url( $id, 'full' ),
			'count' => count( $files ),
			'bytes' => self::bytes( $files ),
		);
	}

	/** Nahradí odkazy na zmenšeniny hlavným súborom a zmenšeniny zmaže. Vráti počet zmazaných súborov. */
	public static function remove( $id ) {
		$files = self::sized_files( $id );
		if ( ! $files ) {
			return 0;
		}
		$file = get_attached_file( $id );
		$meta = wp_get_attachment_metadata( $id );
		$meta = is_array( $meta ) ? $meta : array();
		$full = INO_Converter::url_path( $file );
		$map  = array();
		foreach ( $files as $f ) {
			$map[ INO_Converter::url_path( $f ) ] = $full;
		}
		INO_Converter::replace_everywhere( $file, $meta, INO_Converter::expand_map( $map ) );
		foreach ( $files as $f ) {
			wp_delete_file( $f );
		}
		$meta['sizes'] = array();
		wp_update_attachment_metadata( $id, $meta );
		clean_post_cache( $id );
		return count( $files );
	}
}

/**
 * Osirelé súbory: obrázky v uploads/RRRR/MM, ktoré nepatria žiadnej prílohe a nikde sa nepoužívajú.
 */
class INO_Orphans {

	const EXT = 'jpe?g|png|gif|webp|avif';

	/** Podpriečinky RRRR a RRRR/MM (relatívne k uploads). */
	public static function dirs() {
		$base = wp_get_upload_dir()['basedir'];
		$out  = array();
		foreach ( (array) @scandir( $base ) as $y ) { // phpcs:ignore
			if ( ! preg_match( '/^\d{4}$/', $y ) || ! is_dir( "$base/$y" ) ) {
				continue;
			}
			$out[] = $y;
			foreach ( (array) @scandir( "$base/$y" ) as $m ) { // phpcs:ignore
				if ( preg_match( '/^\d{2}$/', $m ) && is_dir( "$base/$y/$m" ) ) {
					$out[] = "$y/$m";
				}
			}
		}
		return $out;
	}

	/** Množina všetkých súborov, ktoré patria nejakej prílohe (kľúče = relatívne cesty). Cacheuje sa na hodinu. */
	public static function known_set( $rebuild ) {
		global $wpdb;
		$set = $rebuild ? false : get_transient( 'ino_known_files' );
		if ( is_array( $set ) ) {
			return $set;
		}
		$set    = array();
		$offset = 0;
		do {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ('_wp_attached_file','_wp_attachment_metadata','_wp_attachment_backup_sizes') LIMIT %d OFFSET %d",
				1000, $offset
			) );
			foreach ( $rows as $r ) {
				if ( '_wp_attached_file' === $r->meta_key ) {
					$set[ $r->meta_value ] = 1;
					continue;
				}
				$v = maybe_unserialize( $r->meta_value );
				if ( ! is_array( $v ) ) {
					continue;
				}
				if ( '_wp_attachment_backup_sizes' === $r->meta_key ) {
					continue; // názvy bez priečinka, nižšie ich zachytí kontrola mena
				}
				$d = isset( $v['file'] ) ? dirname( $v['file'] ) : '.';
				$d = ( '.' === $d ) ? '' : $d . '/';
				if ( ! empty( $v['file'] ) ) {
					$set[ $v['file'] ] = 1;
				}
				if ( ! empty( $v['original_image'] ) ) {
					$set[ $d . $v['original_image'] ] = 1;
				}
				foreach ( (array) ( $v['sizes'] ?? array() ) as $sz ) {
					if ( ! empty( $sz['file'] ) ) {
						$set[ $d . $sz['file'] ] = 1;
					}
					foreach ( (array) ( $sz['sources'] ?? array() ) as $src ) {
						if ( ! empty( $src['file'] ) ) {
							$set[ $d . $src['file'] ] = 1;
						}
					}
				}
			}
			$offset += 1000;
		} while ( count( $rows ) === 1000 );
		set_transient( 'ino_known_files', $set, HOUR_IN_SECONDS );
		return $set;
	}

	/** Osirelý = obrázok bez prílohy a nie zmenšenina existujúceho hlavného súboru (tie rieši INO_Sizes). */
	public static function is_orphan( $rel, $known ) {
		if ( ! preg_match( '#^\d{4}(/\d{2})?/[^/]+$#', $rel ) || false !== strpos( $rel, '..' ) ) {
			return false;
		}
		$name = basename( $rel );
		if ( ! preg_match( '/\.(' . self::EXT . ')$/i', $name ) || isset( $known[ $rel ] ) ) {
			return false;
		}
		$base = wp_get_upload_dir()['basedir'];
		if ( ! is_file( "$base/$rel" ) ) {
			return false;
		}
		if ( preg_match( '/^(.+)-\d+x\d+(\.[a-z0-9]+)$/i', $name, $m ) ) {
			$d = dirname( $rel );
			if ( is_file( "$base/$d/{$m[1]}{$m[2]}" ) ) {
				return false; // zmenšenina súboru, ktorý existuje
			}
		}
		return true;
	}

	/** Pred zmazaním: znova overiť v databáze (bez cache). */
	public static function is_deletable( $rel ) {
		global $wpdb;
		$name = basename( $rel );
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1", $rel ) ) ) {
			return false;
		}
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key IN ('_wp_attachment_metadata','_wp_attachment_backup_sizes') AND meta_value LIKE %s LIMIT 1", '%' . $wpdb->esc_like( '"' . $name . '"' ) . '%' ) ) ) {
			return false;
		}
		return self::is_orphan( $rel, array() ) && ! INO_Cleaner::is_file_used( $rel );
	}
}
