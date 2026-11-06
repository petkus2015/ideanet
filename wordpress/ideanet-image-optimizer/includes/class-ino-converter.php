<?php
defined( 'ABSPATH' ) || exit;

/**
 * Jadro pluginu: výber obrázkov, konverzia, oprava odkazov a mazanie starých súborov.
 */
class INO_Converter {

	const META_DONE = '_ino_optimized';

	/* ------------------------------------------------------------------ nastavenia */

	public static function settings() {
		$defaults = array(
			'format'           => 'webp', // webp | avif
			'quality'          => 80,
			'max_width'        => 2000,   // 0 = bez zmenšovania
			'delete_originals' => 1,
			'only_used'        => 1,
			'cleanup_days'     => 30,     // nemazať obrázky mladšie než N dní
		);
		$saved    = get_option( 'ino_settings', array() );
		return array_merge( $defaults, is_array( $saved ) ? $saved : array() );
	}

	public static function sanitize_settings( $in ) {
		$in = is_array( $in ) ? $in : array();
		return array(
			'format'           => ( isset( $in['format'] ) && 'avif' === $in['format'] ) ? 'avif' : 'webp',
			'quality'          => max( 30, min( 100, (int) ( $in['quality'] ?? 80 ) ) ),
			'max_width'        => max( 0, min( 10000, (int) ( $in['max_width'] ?? 2000 ) ) ),
			'delete_originals' => empty( $in['delete_originals'] ) ? 0 : 1,
			'only_used'        => empty( $in['only_used'] ) ? 0 : 1,
			'cleanup_days'     => max( 0, min( 3650, (int) ( $in['cleanup_days'] ?? 30 ) ) ),
		);
	}

	/** Cieľový MIME typ podľa nastavenia a podpory servera (AVIF → WebP → nič). */
	public static function target_mime( $format = null ) {
		$format = $format ?: self::settings()['format'];
		$order  = ( 'avif' === $format ) ? array( 'image/avif', 'image/webp' ) : array( 'image/webp' );
		foreach ( $order as $mime ) {
			if ( wp_image_editor_supports( array( 'mime_type' => $mime ) ) ) {
				return $mime;
			}
		}
		return null;
	}

	/* ------------------------------------------------------------------ kandidáti */

	/**
	 * ID ďalších neoptimalizovaných JPEG/PNG príloh väčších ako $after_id.
	 */
	public static function candidate_ids( $after_id = 0, $limit = 50 ) {
		global $wpdb;
		return array_map(
			'intval',
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p
					 WHERE p.post_type = 'attachment'
					   AND p.post_mime_type IN ('image/jpeg','image/png')
					   AND p.ID > %d
					   AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = %s)
					 ORDER BY p.ID ASC LIMIT %d",
					$after_id,
					self::META_DONE,
					$limit
				)
			)
		);
	}

	public static function count_candidates() {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				 WHERE p.post_type = 'attachment' AND p.post_mime_type IN ('image/jpeg','image/png')
				   AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = %s)",
				self::META_DONE
			)
		);
	}

	/** Súhrn už hotových obrázkov: počet a ušetrené bajty. */
	public static function totals() {
		global $wpdb;
		$rows  = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META_DONE ) );
		$count = 0;
		$saved = 0;
		foreach ( $rows as $raw ) {
			$d = maybe_unserialize( $raw );
			if ( ! is_array( $d ) ) {
				continue;
			}
			++$count;
			$saved += max( 0, (int) ( $d['before'] ?? 0 ) - (int) ( $d['after'] ?? 0 ) );
		}
		return array( 'count' => $count, 'saved' => $saved );
	}

	/** Je obrázok použitý ako hlavný obrázok alebo v obsahu nejakého článku (post)? Odkazy v stránkach sa pri konverzii len opravia. */
	public static function is_used( $id, $old_file ) {
		global $wpdb;

		// Len články (typ "post"), nie stránky ani iné typy.
				$excluded_state = "('trash','auto-draft','inherit')";
		$post_only      = "= 'post'";

		$featured = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_thumbnail_id' AND pm.meta_value = %d
				   AND p.post_type {$post_only} AND p.post_status NOT IN {$excluded_state} LIMIT 1",
				$id
			)
		);
		if ( $featured ) {
			return true;
		}

		$conds  = array( 'post_content REGEXP %s' );
		$params = array( 'wp-image-' . (int) $id . '([^0-9]|$)' );
		foreach ( self::stem_paths( $old_file, wp_get_attachment_metadata( $id ) ?: array() ) as $stem ) {
			$conds[]  = 'post_content LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $stem ) . '%';
		}
		$sql = "SELECT 1 FROM {$wpdb->posts} WHERE post_type {$post_only} AND post_status NOT IN {$excluded_state}
		        AND (" . implode( ' OR ', $conds ) . ') LIMIT 1';
		return (bool) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/* ------------------------------------------------------------------ optimalizácia jedného obrázka */

	/**
	 * @return array{status:string,message:string,before?:int,after?:int}
	 *   status: ok | skipped | error
	 */
	public static function optimize( $id, $dry_run = false ) {
		$s    = self::settings();
		$mime = self::target_mime( $s['format'] );
		if ( ! $mime ) {
			return self::res( 'error', 'Server nepodporuje WebP ani AVIF (potrebné GD alebo Imagick s podporou formátu).' );
		}

		$old_file = get_attached_file( $id );
		$old_mime = get_post_mime_type( $id );
		if ( ! $old_file || ! file_exists( $old_file ) ) {
			return self::res( 'skipped', 'Zdrojový súbor neexistuje.' );
		}
		if ( ! in_array( $old_mime, array( 'image/jpeg', 'image/png' ), true ) ) {
			return self::res( 'skipped', 'Nepodporovaný typ ' . $old_mime . '.' );
		}
		if ( $s['only_used'] && ! self::is_used( $id, $old_file ) ) {
			return self::res( 'skipped', 'Nepoužitý v žiadnom článku.' );
		}
		if ( $dry_run ) {
			return self::res( 'ok', 'Bol by prevedený na ' . $mime . '.' );
		}

		$old_meta  = wp_get_attachment_metadata( $id );
		$old_meta  = is_array( $old_meta ) ? $old_meta : array();
		$old_files = self::attachment_files( $id, $old_file, $old_meta );
		$before    = self::total_size( $old_files );

		// 1) hlavný súbor
		$editor = wp_get_image_editor( $old_file );
		if ( is_wp_error( $editor ) ) {
			return self::res( 'error', $editor->get_error_message() );
		}
		$size = $editor->get_size();
		if ( $s['max_width'] > 0 && ! empty( $size['width'] ) && $size['width'] > $s['max_width'] ) {
			$editor->resize( $s['max_width'], null, false );
		}
		$editor->set_quality( (int) $s['quality'] );

		$ext      = ( 'image/avif' === $mime ) ? 'avif' : 'webp';
		$dir      = dirname( $old_file );
		$new_name = wp_unique_filename( $dir, pathinfo( $old_file, PATHINFO_FILENAME ) . '.' . $ext );
		$new_file = trailingslashit( $dir ) . $new_name;
		$saved    = $editor->save( $new_file, $mime );
		unset( $editor );
		if ( is_wp_error( $saved ) ) {
			return self::res( 'error', $saved->get_error_message() );
		}
		if ( ! file_exists( $new_file ) ) {
			return self::res( 'error', 'Nový súbor sa nevytvoril.' );
		}

		// Ak by bol nový súbor väčší než pôvodný, nechajte pôvodný.
		if ( filesize( $new_file ) >= filesize( $old_file ) ) {
			wp_delete_file( $new_file );
			update_post_meta( $id, self::META_DONE, array( 'time' => time(), 'before' => $before, 'after' => $before, 'note' => 'ponechaný (nový by bol väčší)' ) );
			return self::res( 'skipped', 'Nový súbor by nebol menší — ponechaný pôvodný.' );
		}

		// 2) prepnúť prílohu a vygenerovať veľkosti už z nového súboru
		update_attached_file( $id, $new_file );
		wp_update_post( array( 'ID' => $id, 'post_mime_type' => $mime ) );
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$new_meta = wp_generate_attachment_metadata( $id, $new_file );
		if ( empty( $new_meta ) || is_wp_error( $new_meta ) || empty( $new_meta['file'] ) ) {
			// vrátiť späť
			update_attached_file( $id, $old_file );
			wp_update_post( array( 'ID' => $id, 'post_mime_type' => $old_mime ) );
			wp_update_attachment_metadata( $id, $old_meta );
			self::delete_files( array_diff( self::attachment_files( $id, $new_file, is_array( $new_meta ) ? $new_meta : array() ), $old_files ) );
			return self::res( 'error', 'Generovanie veľkostí zlyhalo, obrázok ostal nezmenený.' );
		}
		wp_update_attachment_metadata( $id, $new_meta );
		$new_files = self::attachment_files( $id, $new_file, $new_meta );

		// 3) opraviť odkazy v obsahu, metách a nastaveniach
		$map = self::url_map( $old_file, $old_meta, $new_file, $new_meta );
		self::replace_everywhere( $old_file, $old_meta, $map );

		// 4) zmazať staré súbory, ktoré už nič nepoužíva
		if ( $s['delete_originals'] ) {
			$backup = get_post_meta( $id, '_wp_attachment_backup_sizes', true );
			$extra  = array();
			if ( is_array( $backup ) ) {
				foreach ( $backup as $b ) {
					if ( ! empty( $b['file'] ) ) {
						$extra[] = $dir . '/' . $b['file'];
					}
				}
				delete_post_meta( $id, '_wp_attachment_backup_sizes' );
			}
			self::delete_files( array_diff( array_merge( $old_files, $extra ), $new_files ) );
		}

		$after = self::total_size( $new_files );
		update_post_meta( $id, self::META_DONE, array( 'time' => time(), 'from' => $old_mime, 'to' => $mime, 'before' => $before, 'after' => $after ) );
		clean_post_cache( $id );

		$r           = self::res( 'ok', sprintf( '%s → %s', $old_mime, $mime ) );
		$r['before'] = $before;
		$r['after']  = $after;
		return $r;
	}

	private static function res( $status, $message ) {
		return array( 'status' => $status, 'message' => $message );
	}

	/* ------------------------------------------------------------------ súbory a cesty */

	/** Všetky súbory prílohy (hlavný, veľkosti, pôvodný pred -scaled), absolútne cesty. */
	public static function attachment_files( $id, $file, $meta ) {
		$dir   = dirname( $file );
		$files = array( $file );
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $sz ) {
				if ( ! empty( $sz['file'] ) ) {
					$files[] = $dir . '/' . $sz['file'];
				}
				foreach ( (array) ( $sz['sources'] ?? array() ) as $src ) {
					if ( ! empty( $src['file'] ) ) {
						$files[] = $dir . '/' . $src['file'];
					}
				}
			}
		}
		if ( ! empty( $meta['original_image'] ) ) {
			$files[] = $dir . '/' . $meta['original_image'];
		}
		return array_values( array_unique( $files ) );
	}

	private static function total_size( $files ) {
		$t = 0;
		foreach ( $files as $f ) {
			if ( is_file( $f ) ) {
				$t += (int) filesize( $f );
			}
		}
		return $t;
	}

	private static function delete_files( $files ) {
		foreach ( $files as $f ) {
			if ( is_file( $f ) ) {
				wp_delete_file( $f );
			}
		}
	}

	/** Cesta v URL (bez hostiteľa) k súboru, napr. /wp-content/uploads/2026/11/foo.jpg */
	private static function url_path( $abs_file ) {
		$u    = wp_get_upload_dir();
		$base = (string) wp_parse_url( $u['baseurl'], PHP_URL_PATH );
		$rel  = ltrim( str_replace( '\\', '/', substr( $abs_file, strlen( $u['basedir'] ) ) ), '/' );
		return rtrim( $base, '/' ) . '/' . $rel;
	}

	/** Predpony (cesta bez prípony) na vyhľadávanie v databáze, napr. /wp-content/uploads/2026/11/foo */
	public static function stem_paths( $file, $meta ) {
		$stems = array();
		$dir   = dirname( $file );
		$names = array( $file );
		if ( ! empty( $meta['original_image'] ) ) {
			$names[] = $dir . '/' . $meta['original_image'];
		}
		foreach ( $names as $n ) {
			$stems[] = self::url_path( $dir . '/' . pathinfo( $n, PATHINFO_FILENAME ) );
		}
		$stems = array_unique( $stems );
		// ak je jedna predpona začiatkom druhej, stačí kratšia
		foreach ( $stems as $a ) {
			foreach ( $stems as $k => $b ) {
				if ( $a !== $b && 0 === strpos( $b, $a ) ) {
					unset( $stems[ $k ] );
				}
			}
		}
		return array_values( $stems );
	}

	/**
	 * Mapa starých ciest → nových. Veľkosti, ktoré v novej prílohe nevznikli, sa
	 * nahradia hlavným súborom, aby nezostal žiadny rozbitý odkaz.
	 */
	public static function url_map( $old_file, $old_meta, $new_file, $new_meta ) {
		$old_dir = dirname( $old_file );
		$new_dir = dirname( $new_file );
		$new_full = self::url_path( $new_file );
		$map      = array( self::url_path( $old_file ) => $new_full );

		if ( ! empty( $old_meta['original_image'] ) ) {
			$map[ self::url_path( $old_dir . '/' . $old_meta['original_image'] ) ] = $new_full;
		}
		foreach ( (array) ( $old_meta['sizes'] ?? array() ) as $name => $sz ) {
			if ( empty( $sz['file'] ) ) {
				continue;
			}
			$target = ! empty( $new_meta['sizes'][ $name ]['file'] )
				? self::url_path( $new_dir . '/' . $new_meta['sizes'][ $name ]['file'] )
				: $new_full;
			$map[ self::url_path( $old_dir . '/' . $sz['file'] ) ] = $target;
		}
		// nikdy nemapovať cestu na samu seba
		foreach ( $map as $from => $to ) {
			if ( $from === $to ) {
				unset( $map[ $from ] );
			}
		}
		return self::expand_map( $map );
	}

	/** Pridá varianty s escapovanými lomkami (JSON v blokoch/page builderoch) a zoradí od najdlhších. */
	public static function expand_map( $map ) {
		$out = array();
		foreach ( $map as $from => $to ) {
			$out[ $from ] = $to;
			$out[ str_replace( '/', '\/', $from ) ] = str_replace( '/', '\/', $to );
		}
		uksort( $out, function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );
		return $out;
	}

	/* ------------------------------------------------------------------ nahrádzanie v databáze */

	/** Rekurzívne nahradí reťazce v hodnote (reťazec / pole / objekt). Vráti [nová hodnota, zmenená?]. */
	public static function replace_in_value( $value, $map ) {
		if ( is_string( $value ) ) {
			$new = strtr( $value, $map );
			return array( $new, $new !== $value );
		}
		if ( is_array( $value ) ) {
			$changed = false;
			foreach ( $value as $k => $v ) {
				list( $nv, $c ) = self::replace_in_value( $v, $map );
				if ( $c ) {
					$value[ $k ] = $nv;
					$changed     = true;
				}
			}
			return array( $value, $changed );
		}
		if ( is_object( $value ) ) {
			$changed = false;
			foreach ( get_object_vars( $value ) as $k => $v ) {
				list( $nv, $c ) = self::replace_in_value( $v, $map );
				if ( $c ) {
					$value->$k = $nv;
					$changed   = true;
				}
			}
			return array( $value, $changed );
		}
		return array( $value, false );
	}

	private static function replace_everywhere( $old_file, $old_meta, $map ) {
		global $wpdb;
		if ( ! $map ) {
			return;
		}
		$likes = array();
		foreach ( self::stem_paths( $old_file, $old_meta ) as $stem ) {
			$likes[] = '%' . $wpdb->esc_like( $stem ) . '%';
			$likes[] = '%' . $wpdb->esc_like( str_replace( '/', '\/', $stem ) ) . '%'; // JSON
		}
		$fields = array( 'post_content', 'post_excerpt', 'post_content_filtered' );

		// príspevky, stránky a ostatné typy (okrem príloh)
		$where  = array();
		$params = array();
		foreach ( $fields as $f ) {
			foreach ( $likes as $l ) {
				$where[]  = "$f LIKE %s";
				$params[] = $l;
			}
		}
		$sql  = "SELECT ID, post_content, post_excerpt, post_content_filtered FROM {$wpdb->posts}
		         WHERE post_type <> 'attachment' AND (" . implode( ' OR ', $where ) . ')';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( (array) $rows as $row ) {
			$upd = array();
			foreach ( $fields as $f ) {
				$new = strtr( (string) $row->$f, $map );
				if ( $new !== $row->$f ) {
					$upd[ $f ] = $new;
				}
			}
			if ( $upd ) {
				$wpdb->update( $wpdb->posts, $upd, array( 'ID' => $row->ID ) );
				clean_post_cache( $row->ID );
			}
		}

		// post meta (Elementor, ACF, ...) — bezpečne cez (de)serializáciu
		$where  = array();
		$params = array();
		foreach ( $likes as $l ) {
			$where[]  = 'meta_value LIKE %s';
			$params[] = $l;
		}
		$sql  = "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta}
		         WHERE meta_key NOT IN ('_wp_attachment_metadata','_wp_attached_file','_wp_attachment_backup_sizes')
		           AND meta_key NOT LIKE '\\_ino\\_%' AND (" . implode( ' OR ', $where ) . ')';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( (array) $rows as $row ) {
			$raw = $row->meta_value;
			if ( is_serialized( $raw ) ) {
				$val = @unserialize( trim( $raw ), array( 'allowed_classes' => false ) ); // phpcs:ignore
				if ( false === $val && 'b:0;' !== trim( $raw ) ) {
					continue; // poškodená serializácia — radšej nechať
				}
				list( $nv, $changed ) = self::replace_in_value( $val, $map );
				$new_raw              = $changed ? serialize( $nv ) : $raw;
			} else {
				$new_raw = strtr( $raw, $map );
				$changed = $new_raw !== $raw;
			}
			if ( $changed ) {
				$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $new_raw ), array( 'meta_id' => $row->meta_id ) );
				wp_cache_delete( $row->post_id, 'post_meta' );
			}
		}

		// nastavenia (widgety, úpravy témy)
		$where  = array();
		$params = array();
		foreach ( $likes as $l ) {
			$where[]  = 'option_value LIKE %s';
			$params[] = $l;
		}
		$sql   = "SELECT option_name FROM {$wpdb->options}
		          WHERE option_name NOT LIKE '\\_transient\\_%' AND option_name NOT LIKE '\\_site\\_transient\\_%'
		            AND option_name <> 'ino_settings' AND (" . implode( ' OR ', $where ) . ')';
		$names = $wpdb->get_col( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( (array) $names as $name ) {
			$val = get_option( $name );
			list( $nv, $changed ) = self::replace_in_value( $val, $map );
			if ( $changed ) {
				update_option( $name, $nv );
			}
		}
	}
}
