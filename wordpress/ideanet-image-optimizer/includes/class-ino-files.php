<?php
defined( 'ABSPATH' ) || exit;

/**
 * Miniatúry (súbory typu meno-300x200.jpg) kdekoľvek pod wp-content/uploads.
 * Miniatúra = názov meno-ŠxV.prípona, v rovnakom priečinku existuje väčší súbor meno.prípona,
 * skutočné rozmery miniatúry sedia s názvom a nejde o hlavný súbor žiadnej prílohy.
 * Odkazy na miniatúry sa v obsahu nahradia hlavným súborom, takže nič nezostane rozbité.
 */
class INO_Sizes {

	/** Relatívne cesty všetkých priečinkov pod uploads ('' = koreň). */
	public static function all_dirs() {
		$base = wp_get_upload_dir()['basedir'];
		$out  = array( '' );
		if ( ! is_dir( $base ) ) {
			return $out;
		}
		$it = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ),
				function ( $cur ) {
					return $cur->isDir() && ! $cur->isLink() && '.' !== substr( $cur->getFilename(), 0, 1 );
				}
			),
			RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ( $it as $dir ) {
			$out[] = ltrim( str_replace( '\\', '/', substr( $dir->getPathname(), strlen( $base ) ) ), '/' );
		}
		sort( $out );
		return $out;
	}

	/** Hlavné súbory príloh (relatívne cesty). Cacheuje sa na hodinu. */
	public static function main_set( $rebuild ) {
		global $wpdb;
		$set = $rebuild ? false : get_transient( 'ino_main_files' );
		if ( ! is_array( $set ) ) {
			$set = array_fill_keys( (array) $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'" ), 1 );
			set_transient( 'ino_main_files', $set, HOUR_IN_SECONDS );
		}
		return $set;
	}

	/** Miniatúry v jednom priečinku zoskupené podľa hlavného súboru: [ base_rel => [ thumb_rel, ... ] ]. */
	public static function thumbs_in_dir( $rel_dir, $main ) {
		$u    = wp_get_upload_dir();
		$abs  = $u['basedir'] . ( '' !== $rel_dir ? '/' . $rel_dir : '' );
		$pre  = '' !== $rel_dir ? $rel_dir . '/' : '';
		$list = @scandir( $abs ); // phpcs:ignore
		if ( ! $list ) {
			return array();
		}
		$have   = array_flip( $list );
		$exts   = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' );
		$groups = array();
		$dims   = array();
		foreach ( $list as $n ) {
			if ( ! preg_match( '/^(.+)-(\d+)x(\d+)\.(' . INO_Orphans::EXT . ')$/i', $n, $m ) || isset( $main[ $pre . $n ] ) ) {
				continue;
			}
			$base_name = null;
			foreach ( array_unique( array_merge( array( strtolower( $m[4] ), $m[4] ), $exts ) ) as $e ) {
				$cand = $m[1] . '.' . $e;
				if ( $cand !== $n && isset( $have[ $cand ] ) ) {
					$base_name = $cand;
					break;
				}
			}
			if ( ! $base_name ) {
				continue;
			}
			$d = @getimagesize( "$abs/$n" ); // phpcs:ignore
			if ( ! $d || (int) $d[0] !== (int) $m[2] || (int) $d[1] !== (int) $m[3] ) {
				continue; // rozmery nesedia s názvom → nie je to vygenerovaná miniatúra
			}
			if ( ! isset( $dims[ $base_name ] ) ) {
				$dims[ $base_name ] = @getimagesize( "$abs/$base_name" ); // phpcs:ignore
			}
			$b = $dims[ $base_name ];
			if ( ! $b || $b[0] < $d[0] || $b[1] < $d[1] || ( $b[0] === $d[0] && $b[1] === $d[1] ) ) {
				continue; // hlavný súbor musí byť väčší
			}
			$groups[ $pre . $base_name ][] = $pre . $n;
		}
		return $groups;
	}

	public static function bytes( $rels ) {
		$base = wp_get_upload_dir()['basedir'];
		$t    = 0;
		foreach ( $rels as $r ) {
			$t += (int) @filesize( $base . '/' . $r ); // phpcs:ignore
		}
		return $t;
	}

	public static function valid_rel( $rel ) {
		return is_string( $rel ) && '' !== $rel && false === strpos( $rel, '..' ) && 0 !== strpos( $rel, '/' )
			&& preg_match( '/\.(' . INO_Orphans::EXT . ')$/i', $rel );
	}

	/** Nahradí odkazy na miniatúry hlavným súborom a miniatúry zmaže. Vráti počet zmazaných súborov. */
	public static function remove( $base_rel ) {
		global $wpdb;
		if ( ! self::valid_rel( $base_rel ) ) {
			return 0;
		}
		$dir    = dirname( $base_rel );
		$dir    = ( '.' === $dir ) ? '' : $dir;
		$main   = self::main_set( true );
		$groups = self::thumbs_in_dir( $dir, $main ); // nanovo overené, nespoliehame sa na zoznam z prehliadača
		if ( empty( $groups[ $base_rel ] ) ) {
			return 0;
		}
		$u     = wp_get_upload_dir();
		$file  = $u['basedir'] . '/' . $base_rel;
		$id    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1", $base_rel ) );
		$meta  = $id ? wp_get_attachment_metadata( $id ) : array();
		$meta  = is_array( $meta ) ? $meta : array();
		$full  = INO_Converter::url_path( $file );
		$map   = array();
		foreach ( $groups[ $base_rel ] as $t ) {
			$map[ INO_Converter::url_path( $u['basedir'] . '/' . $t ) ] = $full;
		}
		INO_Converter::replace_everywhere( $file, $meta, INO_Converter::expand_map( $map ) );
		foreach ( $groups[ $base_rel ] as $t ) {
			wp_delete_file( $u['basedir'] . '/' . $t );
		}
		if ( $id ) {
			$meta['sizes'] = array();
			wp_update_attachment_metadata( $id, $meta );
			clean_post_cache( $id );
		}
		return count( $groups[ $base_rel ] );
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
			foreach ( array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' ) as $e ) {
				if ( is_file( "$base/$d/{$m[1]}.$e" ) ) {
					return false; // zmenšenina súboru, ktorý existuje (rieši INO_Sizes)
				}
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
