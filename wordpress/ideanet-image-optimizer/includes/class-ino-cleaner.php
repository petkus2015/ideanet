<?php
defined( 'ABSPATH' ) || exit;

/**
 * Hľadanie a mazanie nepoužitých obrázkov z knižnice médií.
 *
 * Obrázok sa považuje za použitý, ak naň odkazuje čokoľvek v databáze:
 * hlavný obrázok, obsah/výťah akéhokoľvek príspevku, stránky alebo vlastného typu
 * (okrem koša), post meta (ACF, page buildery, galérie produktov...), term meta,
 * user meta, nastavenia (logo, ikona webu, widgety, úpravy témy).
 * Pri pochybnosti sa obrázok radšej ponechá.
 */
class INO_Cleaner {

	/** Je obrázok niekde použitý? */
	public static function is_used( $id ) {
		$file = get_attached_file( $id );
		if ( ! $file ) {
			return true; // nevieme overiť → ponechať
		}
		$meta = wp_get_attachment_metadata( $id );
		return self::referenced( self::likes( INO_Converter::stem_paths( $file, is_array( $meta ) ? $meta : array() ) ), (int) $id );
	}

	/** Odkazuje na konkrétny súbor (relatívna cesta v uploads, napr. 2026/11/foo-300x200.jpg) niečo v databáze? */
	public static function is_file_used( $rel ) {
		$u = wp_get_upload_dir();
		return self::referenced( self::likes( array( INO_Converter::url_path( trailingslashit( $u['basedir'] ) . ltrim( $rel, '/' ) ) ) ), 0 );
	}

	private static function likes( $paths ) {
		global $wpdb;
		$likes = array();
		foreach ( $paths as $stem ) {
			$likes[] = '%' . $wpdb->esc_like( $stem ) . '%';
			$likes[] = '%' . $wpdb->esc_like( str_replace( '/', '\/', $stem ) ) . '%'; // JSON
		}
		return $likes;
	}

	/** $id = 0: hľadá sa len podľa cesty súboru. */
	private static function referenced( $likes, $id ) {
		global $wpdb;

		if ( $id ) {
			$id_re      = '(^|[^0-9])' . $id . '([^0-9]|$)';
			$content_re = implode( '|', array(
				'wp-image-' . $id . '([^0-9]|$)',                  // klasické a blokové obrázky
				'[iI]d":' . $id . '([^0-9]|$)',                    // "id":12, "mediaId":12 v blokoch
				'ids.{1,3}([0-9, ]*[^0-9])?' . $id . '([^0-9]|$)', // [gallery ids="..."], "ids":[...]
			) );
		} else {
			$id_re = $content_re = 'a^'; // nikdy nezodpovedá
		}

		$like_sql = function ( $col ) use ( $likes ) {
			return '(' . implode( ' OR ', array_fill( 0, count( $likes ), "$col LIKE %s" ) ) . ')';
		};
		$live = "post_status NOT IN ('trash','auto-draft') AND post_type NOT IN ('attachment','revision')";

		// 1) hlavný obrázok
		if ( $id && $wpdb->get_var( $wpdb->prepare(
			"SELECT 1 FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_thumbnail_id' AND pm.meta_value = %d AND p.post_status NOT IN ('trash','auto-draft')
			   AND p.post_type NOT IN ('attachment','revision') LIMIT 1", $id ) ) ) {
			return true;
		}

		// 2) obsah a výťah všetkých príspevkov, stránok a vlastných typov
		$sql = "SELECT 1 FROM {$wpdb->posts} WHERE $live AND (post_content REGEXP %s OR post_excerpt REGEXP %s OR "
			. $like_sql( 'post_content' ) . ' OR ' . $like_sql( 'post_excerpt' ) . ') LIMIT 1';
		if ( $wpdb->get_var( $wpdb->prepare( $sql, array_merge( array( $content_re, $content_re ), $likes, $likes ) ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
			return true;
		}

		// 3) post meta (okrem interných kľúčov WordPressu)
		$skip = "meta_key NOT LIKE '\\_wp\\_%' AND meta_key NOT LIKE '\\_edit\\_%' AND meta_key NOT LIKE '\\_oembed\\_%'
		         AND meta_key NOT LIKE '\\_ino\\_%' AND meta_key <> '_thumbnail_id'";
		$sql  = "SELECT 1 FROM {$wpdb->postmeta} WHERE $skip AND (meta_value REGEXP %s OR " . $like_sql( 'meta_value' ) . ') LIMIT 1';
		if ( $wpdb->get_var( $wpdb->prepare( $sql, array_merge( array( $id_re ), $likes ) ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
			return true;
		}

		// 4) term meta (obrázky kategórií) a user meta (vlastné avatary)
		$sql = "SELECT 1 FROM {$wpdb->termmeta} WHERE meta_value REGEXP %s OR " . $like_sql( 'meta_value' ) . ' LIMIT 1';
		if ( $wpdb->get_var( $wpdb->prepare( $sql, array_merge( array( $id_re ), $likes ) ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
			return true;
		}
		$sql = "SELECT 1 FROM {$wpdb->usermeta} WHERE meta_value = %s OR " . $like_sql( 'meta_value' ) . ' LIMIT 1';
		if ( $wpdb->get_var( $wpdb->prepare( $sql, array_merge( array( $id ? (string) $id : '-1' ), $likes ) ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
			return true;
		}

		// 5) nastavenia: logo, ikona webu, widgety, úpravy témy, texty v optiách
		$sql = "SELECT 1 FROM {$wpdb->options}
		        WHERE option_name NOT LIKE '\\_transient\\_%' AND option_name NOT LIKE '\\_site\\_transient\\_%' AND option_name <> 'ino_settings'
		          AND ( " . $like_sql( 'option_value' ) . "
		             OR (option_name IN ('site_icon','site_logo') AND option_value = %s)
		             OR ((option_name LIKE 'theme\\_mods\\_%%' OR option_name LIKE 'widget\\_%%') AND option_value REGEXP %s) ) LIMIT 1";
		$ser = $id ? 'i:' . $id . ';|' . $content_re : 'a^';
		if ( $wpdb->get_var( $wpdb->prepare( $sql, array_merge( $likes, array( $id ? (string) $id : '-1', $ser ) ) ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
			return true;
		}

		/** Umožní iným pluginom/témam označiť obrázok ako použitý (vlastné tabuľky, page buildery). */
		return (bool) apply_filters( 'ino_image_is_used', false, $id, $likes );
	}

	/** Podmienky pre kandidáta na zmazanie: obrázok, dosť starý, nepoužitý. */
	public static function is_deletable( $id, $min_age_days ) {
		$post = get_post( $id );
		if ( ! $post || 'attachment' !== $post->post_type || 0 !== strpos( (string) $post->post_mime_type, 'image/' ) ) {
			return false;
		}
		if ( $min_age_days > 0 && strtotime( $post->post_date_gmt . ' UTC' ) > time() - $min_age_days * DAY_IN_SECONDS ) {
			return false;
		}
		return ! self::is_used( $id );
	}

	/** Ďalšie ID obrázkov (staré aspoň $min_age_days) väčšie než $after_id. */
	public static function image_ids( $after_id, $limit, $min_age_days ) {
		global $wpdb;
		$cut = gmdate( 'Y-m-d H:i:s', time() - max( 0, (int) $min_age_days ) * DAY_IN_SECONDS );
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%%'
			   AND ID > %d AND post_date_gmt <= %s ORDER BY ID ASC LIMIT %d",
			$after_id, $cut, $limit
		) ) );
	}

	/** Údaje o obrázku na zobrazenie v zozname. */
	public static function describe( $id ) {
		$file  = get_attached_file( $id );
		$bytes = 0;
		foreach ( INO_Converter::attachment_files( $id, (string) $file, (array) wp_get_attachment_metadata( $id ) ) as $f ) {
			if ( is_file( $f ) ) {
				$bytes += (int) filesize( $f );
			}
		}
		return array(
			'key'   => (int) $id,
			'id'    => (int) $id,
			'title' => get_the_title( $id ) ?: basename( (string) $file ),
			'thumb' => (string) wp_get_attachment_image_url( $id, 'thumbnail' ),
			'date'  => get_the_date( 'j. n. Y', $id ),
			'bytes' => $bytes,
		);
	}
}
