<?php
/**
 * Orchestratore del plugin.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Registra gli hook in base al contesto (bacheca, sito, REST, cron).
 */
final class Plugin {

	/**
	 * Istanza unica.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Restituisce (e crea) l'istanza.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Avvio.
	 */
	private function __construct() {
		Activator::maybe_upgrade();

		add_action( 'init', array( Post_Type::class, 'register' ) );
		add_action( 'init', array( Settings::class, 'register' ) );
		add_action( 'init', array( Block::class, 'register' ) );
		add_action( 'init', array( Maintenance::class, 'register' ) );
		Maintenance::init();
		add_shortcode( 'grs_pop', array( Frontend::class, 'shortcode' ) );

		add_action( 'rest_api_init', array( Rest::class, 'register_routes' ) );
		add_action( Stats::CRON_HOOK, array( Stats::class, 'cleanup' ) );

		// Indice dei popup attivi: rigenerato quando cambia un popup.
		add_action( 'save_post_' . Post_Type::NAME, array( Index::class, 'schedule_rebuild' ) );
		add_action( 'deleted_post', array( Index::class, 'on_deleted_post' ), 10, 2 );
		add_action( 'trashed_post', array( Index::class, 'on_deleted_post' ) );
		add_action( 'untrashed_post', array( Index::class, 'on_deleted_post' ) );

		if ( is_admin() ) {
			Admin\Admin::init();
			Updater::init();
		} elseif ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			Updater::init();
		} else {
			Maintenance::init_frontend();
			Frontend::init();
		}
	}
}
