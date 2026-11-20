<?php
/**
 * Registrazione dei testi dei popup nel ponte GRS i18n.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Nomi stabili: `popup_{ID}_{ID sezione}_{proprietà}` (l'ID della sezione non cambia mai,
 * così riordinare le sezioni non fa perdere le traduzioni).
 */
final class I18n_Bridge {

	/**
	 * Contesto nel sistema di traduzione (non cambiarlo mai).
	 */
	const CONTEXT = 'GRS Pop';

	/**
	 * Nome completo di un testo.
	 *
	 * @param int    $id   ID del popup.
	 * @param string $name Nome del campo.
	 * @return string
	 */
	public static function name( $id, $name ) {
		return 'popup_' . (int) $id . '_' . $name;
	}

	/**
	 * Testi traducibili di un popup.
	 *
	 * @param int                  $id     ID.
	 * @param array<string, mixed> $config Configurazione.
	 * @return array{0: array<string, string>, 1: string[]} Testi (nome => valore) e nomi su più righe.
	 */
	public static function strings( $id, array $config ) {
		$strings   = array();
		$multiline = array();

		if ( '' !== $config['display']['tab_label'] && Renderer::uses_format( $config, 'tab' ) ) {
			$strings[ self::name( $id, 'tab_label' ) ] = $config['display']['tab_label'];
		}
		if ( '' !== $config['close']['reopen']['label'] && Renderer::uses_reopen( $config ) ) {
			$strings[ self::name( $id, 'reopen_label' ) ] = $config['close']['reopen']['label'];
		}
		if ( '' !== $config['design']['image']['alt'] ) {
			$strings[ self::name( $id, 'image_alt' ) ] = $config['design']['image']['alt'];
		}

		foreach ( $config['sections'] as $s ) {
			$sid   = $s['id'];
			$props = array();
			switch ( $s['type'] ) {
				case 'heading':
				case 'dismiss':
					$props = array( 'text' );
					break;
				case 'text':
					$props       = array( 'text' );
					$multiline[] = self::name( $id, $sid . '_text' );
					break;
				case 'button':
					$props = array( 'label', 'url' );
					break;
				case 'coupon':
					$props = array( 'label', 'copied' );
					break;
				case 'countdown':
					$props = array( 'l_days', 'l_hours', 'l_min', 'l_sec' );
					break;
				case 'list':
					foreach ( $s['items'] as $n => $item ) {
						$strings[ self::name( $id, $sid . '_item_' . $n ) ] = $item;
					}
					break;
			}
			foreach ( $props as $prop ) {
				if ( isset( $s[ $prop ] ) && '' !== (string) $s[ $prop ] && '#' !== (string) $s[ $prop ] ) {
					$strings[ self::name( $id, $sid . '_' . $prop ) ] = (string) $s[ $prop ];
				}
			}
		}
		return array( $strings, $multiline );
	}

	/**
	 * Registra i testi di un popup.
	 *
	 * @param int                  $id     ID.
	 * @param array<string, mixed> $config Configurazione.
	 * @return void
	 */
	public static function register_popup( $id, array $config ) {
		if ( ! class_exists( 'GRS_I18n' ) ) {
			return;
		}
		list( $strings, $multiline ) = self::strings( $id, $config );
		\GRS_I18n::register_many( self::CONTEXT, $strings, $multiline );
	}

	/**
	 * Registra i testi di tutti i popup, solo nelle pagine dove serve
	 * (Polylang mostra solo i testi registrati nella richiesta corrente).
	 *
	 * @return void
	 */
	public static function maybe_register_all() {
		if ( ! class_exists( 'GRS_I18n' ) || ! \GRS_I18n::is_multilingual() ) {
			return;
		}
		$page  = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo lettura della pagina corrente.
		$pages = (array) apply_filters( 'grs_pop_i18n_register_pages', array( 'mlang_strings', 'wpml-string-translation/menu/string-translation.php', 'grs-pop' ) );
		if ( '' === $page || ! in_array( $page, $pages, true ) ) {
			return;
		}
		$ids = get_posts(
			array(
				'post_type'        => Post_Type::NAME,
				'post_status'      => array( 'publish', 'draft' ),
				'numberposts'      => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- elenco dei popup del sito (pochi), solo bacheca o rigenerazione dell'indice.
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		foreach ( (array) $ids as $id ) {
			self::register_popup( (int) $id, Post_Type::get_config( (int) $id ) );
		}
		Maintenance::register_strings();
	}
}
