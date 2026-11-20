<?php
/**
 * Indice compilato dei popup attivi.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Opzione in autoload con le sole regole lato server dei popup attivi.
 *
 * Nel front-end si legge solo questa opzione (già in memoria): senza popup attivi
 * o senza popup per la pagina corrente il plugin non esegue query né carica file.
 */
final class Index {

	/**
	 * Nome dell'opzione.
	 */
	const OPTION = 'grs_pop_index';

	/**
	 * Rigenerazione già pianificata in questa richiesta.
	 *
	 * @var bool
	 */
	private static $scheduled = false;

	/**
	 * Pianifica la rigenerazione a fine richiesta (dopo il salvataggio dei meta).
	 *
	 * @return void
	 */
	public static function schedule_rebuild() {
		if ( self::$scheduled ) {
			return;
		}
		self::$scheduled = true;
		add_action( 'shutdown', array( __CLASS__, 'rebuild' ) );
	}

	/**
	 * Popup eliminato, spostato nel cestino o ripristinato.
	 *
	 * @param int           $post_id ID.
	 * @param \WP_Post|null $post    Post (solo per deleted_post).
	 * @return void
	 */
	public static function on_deleted_post( $post_id, $post = null ) {
		$type = $post instanceof \WP_Post ? $post->post_type : get_post_type( $post_id );
		if ( Post_Type::NAME === $type ) {
			self::schedule_rebuild();
		}
	}

	/**
	 * Rigenera l'indice.
	 *
	 * @return void
	 */
	public static function rebuild() {
		$ids = get_posts(
			array(
				'post_type'        => Post_Type::NAME,
				'post_status'      => 'publish',
				'numberposts'      => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- elenco dei popup del sito (pochi), solo bacheca o rigenerazione dell'indice.
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);
		$ids = array_map( 'intval', (array) $ids );
		if ( $ids ) {
			update_meta_cache( 'post', $ids );
		}

		$titles = array();
		if ( $ids ) {
			foreach ( get_posts(
				array(
					'post_type'              => Post_Type::NAME,
					'post__in'               => $ids,
					'numberposts'            => count( $ids ),
					'suppress_filters'       => true,
					'update_post_term_cache' => false,
				)
			) as $post ) {
				$titles[ (int) $post->ID ] = (string) $post->post_title;
			}
		}

		$popups = array();
		$forms  = false;
		foreach ( $ids as $id ) {
			$config = Post_Type::get_config( $id );
			if ( empty( $config['sections'] ) ) {
				continue;
			}
			$enabled = $config['display']['desktop']['enabled'] || $config['display']['tablet']['enabled'] || $config['display']['mobile']['enabled'];
			if ( 'floating' === $config['placement'] && ! $enabled ) {
				continue;
			}
			$popups[ $id ] = array(
				't'  => isset( $titles[ $id ] ) ? $titles[ $id ] : '',
				'pl' => $config['placement'],
				'pr' => (int) $config['advanced']['priority'],
				'ip' => $config['inline']['position'],
				'pg' => (int) $config['inline']['paragraph'],
				'tg' => $config['targeting'],
			);
			if ( null !== Schema::forms_rule( $config ) ) {
				$forms = true;
			}
		}

		$index = array(
			'v'      => 1,
			'popups' => $popups,
			// Almeno un popup attivo usa la regola sui form di GRS Forms: nel sito pubblico
			// si ascolta l'invio dei form anche nelle pagine senza popup.
			'fw'     => $forms,
		);
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, $index, '', 'yes' );
		} else {
			update_option( self::OPTION, $index, true );
		}
		// Popup salvati o cambiati di stato: le pagine in cache vanno rigenerate (marcatura e dati
		// dei popup sono nell'HTML).
		self::purge_page_cache();
	}

	/**
	 * Svuota la cache di pagina con gli hook pubblici dei plugin di cache, solo se presenti
	 * (regola dell'ecosistema GRS, dev/ecosistema.md).
	 *
	 * Chiamata a ogni rigenerazione dell'indice (salvataggio, cambio di stato, cestino di un popup)
	 * e al salvataggio delle impostazioni.
	 *
	 * @return void
	 */
	public static function purge_page_cache() {
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook pubblico di LiteSpeed Cache.
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
	}

	/**
	 * Popup dell'indice.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function popups() {
		$index = get_option( self::OPTION, array() );
		return is_array( $index ) && ! empty( $index['popups'] ) && is_array( $index['popups'] ) ? $index['popups'] : array();
	}

	/**
	 * Un popup attivo usa la regola "ha / non ha compilato il form"?
	 *
	 * @return bool
	 */
	public static function watch_forms() {
		$index = get_option( self::OPTION, array() );
		return is_array( $index ) && ! empty( $index['fw'] );
	}

	/**
	 * Il popup è attivo?
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public static function is_active( $id ) {
		$popups = self::popups();
		return isset( $popups[ (int) $id ] );
	}
}
