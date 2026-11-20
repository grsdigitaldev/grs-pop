<?php
/**
 * Blocco "GRS Pop inline".
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Blocco con rendering lato server: inserisce un popup in linea nel contenuto.
 */
final class Block {

	/**
	 * Registrazione.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		if ( is_admin() ) {
			wp_register_script(
				'grs-pop-block-editor',
				GRS_POP_URL . 'assets/js/block.js',
				array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-api-fetch', 'wp-i18n' ),
				GRS_POP_VERSION,
				true
			);
			wp_set_script_translations( 'grs-pop-block-editor', 'grs-pop', GRS_POP_PATH . 'languages' );
		}
		$args = array( 'render_callback' => array( Frontend::class, 'render_block' ) );
		if ( is_admin() ) {
			$args['editor_script'] = 'grs-pop-block-editor';
		}
		register_block_type( GRS_POP_PATH . 'blocks/inline', $args );
	}
}
