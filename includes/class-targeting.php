<?php
/**
 * Regole lato server: pagine, utenti e lingua.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Valuta le regole che non dipendono dal visitatore anonimo, quindi compatibili con la cache di pagina.
 *
 * Le regole sul visitatore (dispositivo, provenienza, UTM, frequenza) sono valutate dal browser.
 */
final class Targeting {

	/**
	 * Dati della pagina corrente (calcolati una volta).
	 *
	 * @var array<string, mixed>|null
	 */
	private static $context = null;

	/**
	 * Il popup va mostrato nella pagina corrente?
	 *
	 * @param array<string, mixed> $tg Regole (sezione `targeting` della configurazione).
	 * @return bool
	 */
	public static function matches( array $tg ) {
		$users = isset( $tg['users'] ) ? $tg['users'] : 'all';
		if ( 'in' === $users && ! is_user_logged_in() ) {
			return false;
		}
		if ( 'out' === $users && is_user_logged_in() ) {
			return false;
		}
		if ( 'in' === $users && ! empty( $tg['roles'] ) ) {
			$user = wp_get_current_user();
			if ( ! array_intersect( (array) $tg['roles'], (array) $user->roles ) ) {
				return false;
			}
		}
		if ( ! empty( $tg['languages'] ) && class_exists( 'GRS_I18n' ) ) {
			if ( ! in_array( strtolower( \GRS_I18n::current_language() ), (array) $tg['languages'], true ) ) {
				return false;
			}
		}

		if ( ! empty( $tg['exclude'] ) && self::any_rule( (array) $tg['exclude'] ) ) {
			return false;
		}
		if ( isset( $tg['pages'] ) && 'include' === $tg['pages'] ) {
			return ! empty( $tg['include'] ) && self::any_rule( (array) $tg['include'] );
		}
		return true;
	}

	/**
	 * Almeno una regola è soddisfatta.
	 *
	 * @param array<int, array<string, mixed>> $rules Regole.
	 * @return bool
	 */
	private static function any_rule( array $rules ) {
		foreach ( $rules as $rule ) {
			if ( is_array( $rule ) && self::rule( $rule ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Valuta una regola.
	 *
	 * @param array<string, mixed> $rule Regola.
	 * @return bool
	 */
	private static function rule( array $rule ) {
		$ctx = self::context();
		switch ( isset( $rule['type'] ) ? $rule['type'] : '' ) {
			case 'front_page':
				return is_front_page();
			case 'blog':
				return is_home();
			case 'search':
				return is_search();
			case '404':
				return is_404();
			case 'singular':
				return is_singular( (string) $rule['post_type'] );
			case 'archive':
				$pt = (string) $rule['post_type'];
				if ( 'post' === $pt ) {
					return is_home() || is_category() || is_tag() || is_date() || is_author();
				}
				return is_post_type_archive( $pt );
			case 'posts':
				if ( ! $ctx['object_ids'] ) {
					return false;
				}
				foreach ( (array) $rule['items'] as $item ) {
					if ( in_array( (int) $item['id'], $ctx['object_ids'], true ) ) {
						return true;
					}
				}
				return false;
			case 'terms':
				$ids = wp_list_pluck( (array) $rule['items'], 'id' );
				$ids = array_map( 'intval', $ids );
				$tax = (string) $rule['taxonomy'];
				if ( is_tax( $tax ) || ( 'category' === $tax && is_category() ) || ( 'post_tag' === $tax && is_tag() ) ) {
					$term = get_queried_object();
					return $term instanceof \WP_Term && in_array( (int) $term->term_id, self::translated_ids( $ids, $tax ), true );
				}
				if ( is_singular() && $ctx['post_id'] && is_object_in_taxonomy( (string) get_post_type( $ctx['post_id'] ), $tax ) ) {
					return has_term( self::translated_ids( $ids, $tax ), $tax, $ctx['post_id'] );
				}
				return false;
			case 'url':
				$path  = $ctx['path'];
				$value = (string) $rule['value'];
				if ( 0 !== strpos( $value, '/' ) && false === strpos( $value, '://' ) && 'equals' !== $rule['op'] ) {
					// Testo libero: si cerca nell'indirizzo completo (percorso e parametri).
					return false !== stripos( $path, $value );
				}
				$value = self::normalize_path( $value );
				if ( 'equals' === $rule['op'] ) {
					return untrailingslashit( strtok( $path, '?' ) ) === untrailingslashit( strtok( $value, '?' ) );
				}
				if ( 'starts' === $rule['op'] ) {
					return 0 === stripos( $path, $value );
				}
				return false !== stripos( $path, $value );
		}
		return false;
	}

	/**
	 * Contesto della richiesta corrente.
	 *
	 * @return array<string, mixed>
	 */
	private static function context() {
		if ( null !== self::$context ) {
			return self::$context;
		}
		$post_id    = 0;
		$object_ids = array();
		if ( is_singular() ) {
			$post_id = (int) get_queried_object_id();
		} elseif ( is_home() && ! is_front_page() ) {
			$post_id = (int) get_option( 'page_for_posts' );
		}
		if ( $post_id ) {
			$object_ids[] = $post_id;
			// Con WPML o Polylang (compatibile con questo filtro) si confronta anche l'originale.
			$original = (int) apply_filters( 'wpml_object_id', $post_id, get_post_type( $post_id ), true, apply_filters( 'wpml_default_language', null ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtro di WPML (anche Polylang lo supporta).
			if ( $original && $original !== $post_id ) {
				$object_ids[] = $original;
			}
		}
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- normalizzato sotto, usato solo per confronti.

		self::$context = array(
			'post_id'    => $post_id,
			'object_ids' => $object_ids,
			'path'       => self::normalize_path( (string) $request ),
		);
		return self::$context;
	}

	/**
	 * Percorso relativo alla home (senza dominio), in minuscolo e decodificato.
	 *
	 * @param string $url Indirizzo o percorso.
	 * @return string
	 */
	private static function normalize_path( $url ) {
		$url  = rawurldecode( trim( (string) $url ) );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$qs   = (string) wp_parse_url( $url, PHP_URL_QUERY );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '' !== $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
			$path = '/' . ltrim( substr( $path, strlen( $home ) ), '/' );
		}
		if ( '' === $path ) {
			$path = '/';
		}
		return strtolower( $path . ( '' !== $qs ? '?' . $qs : '' ) );
	}

	/**
	 * ID dei termini anche nelle altre lingue (WPML/Polylang), se presenti.
	 *
	 * @param int[]  $ids ID salvati.
	 * @param string $tax Tassonomia.
	 * @return int[]
	 */
	private static function translated_ids( array $ids, $tax ) {
		$out = $ids;
		foreach ( $ids as $id ) {
			$translated = (int) apply_filters( 'wpml_object_id', $id, $tax, true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtro di WPML (anche Polylang lo supporta).
			if ( $translated && ! in_array( $translated, $out, true ) ) {
				$out[] = $translated;
			}
		}
		return $out;
	}

	/**
	 * Azzera il contesto (test).
	 *
	 * @return void
	 */
	public static function reset() {
		self::$context = null;
	}
}
