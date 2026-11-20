<?php
/**
 * Tipo di contenuto dei popup.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Tipo di contenuto `grs_popup`, non pubblico: la configurazione è nel meta `_grs_pop_config`.
 *
 * Stato `publish` = attivo, `draft` = disattivato.
 */
final class Post_Type {

	/**
	 * Nome del tipo di contenuto.
	 */
	const NAME = 'grs_popup';

	/**
	 * Meta con la configurazione JSON.
	 */
	const META = '_grs_pop_config';

	/**
	 * Registrazione.
	 *
	 * @return void
	 */
	public static function register() {
		register_post_type(
			self::NAME,
			array(
				'labels'              => array(
					'name'          => __( 'Popups', 'grs-pop' ),
					'singular_name' => __( 'Popup', 'grs-pop' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => true,
				'supports'            => array( 'title' ),
				'capabilities'        => array(
					'edit_post'          => 'grs_pop_manage_popups',
					'read_post'          => 'grs_pop_manage_popups',
					'delete_post'        => 'grs_pop_manage_popups',
					'edit_posts'         => 'grs_pop_manage_popups',
					'edit_others_posts'  => 'grs_pop_manage_popups',
					'delete_posts'       => 'grs_pop_manage_popups',
					'publish_posts'      => 'grs_pop_manage_popups',
					'read_private_posts' => 'grs_pop_manage_popups',
					'create_posts'       => 'grs_pop_manage_popups',
				),
				'map_meta_cap'        => false,
			)
		);
	}

	/**
	 * Configurazione salvata di un popup (già ripulita).
	 *
	 * @param int $post_id ID del popup.
	 * @return array<string, mixed>
	 */
	public static function get_config( $post_id ) {
		$raw    = get_post_meta( (int) $post_id, self::META, true );
		$config = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : array();
		return Schema::sanitize( is_array( $config ) ? $config : array() );
	}

	/**
	 * Salva la configurazione (ripulendola).
	 *
	 * @param int                  $post_id ID del popup.
	 * @param array<string, mixed> $config  Configurazione.
	 * @return array<string, mixed> Configurazione salvata.
	 */
	public static function save_config( $post_id, array $config ) {
		$clean = Schema::sanitize( $config );
		update_post_meta( (int) $post_id, self::META, wp_slash( (string) wp_json_encode( $clean ) ) );
		return $clean;
	}
}
