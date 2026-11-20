<?php
/**
 * Attivazione e aggiornamenti tra versioni.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Crea opzioni, tabella, capability ed evento cron.
 */
final class Activator {

	/**
	 * Capability del plugin e ruoli che le ricevono all'attivazione.
	 *
	 * @var array<string, string[]>
	 */
	const CAPS = array(
		'grs_pop_manage_popups' => array( 'administrator', 'editor' ),
		'grs_pop_view_stats'    => array( 'administrator', 'editor' ),
	);

	/**
	 * Attivazione.
	 *
	 * @param bool $network_wide Attivazione di rete.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			deactivate_plugins( plugin_basename( GRS_POP_FILE ), true, true );
			wp_die(
				esc_html__( 'GRS Pop cannot be network activated. Activate it on each site individually.', 'grs-pop' ),
				esc_html__( 'Activation not allowed', 'grs-pop' ),
				array( 'back_link' => true )
			);
		}
		self::install();
	}

	/**
	 * Esegue le migrazioni se la versione salvata è diversa.
	 *
	 * L'opzione è in autoload: nessuna query aggiuntiva.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( GRS_POP_VERSION !== get_option( 'grs_pop_version' ) ) {
			self::install();
		}
	}

	/**
	 * Installazione idempotente.
	 *
	 * @return void
	 */
	public static function install() {
		// Opzioni lette nel front-end: sempre presenti e in autoload (un'opzione assente costa una query).
		add_option( Settings::OPTION, Settings::defaults(), '', 'yes' );
		add_option( Index::OPTION, array(), '', 'yes' );
		add_option( Maintenance::OPTION, Maintenance::initial(), '', 'yes' );

		Stats::create_table();
		self::add_caps();

		if ( ! wp_next_scheduled( Stats::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', Stats::CRON_HOOK );
		}

		update_option( 'grs_pop_version', GRS_POP_VERSION, true );
		Index::rebuild();
	}

	/**
	 * Assegna le capability ai ruoli previsti.
	 *
	 * @return void
	 */
	public static function add_caps() {
		foreach ( self::CAPS as $cap => $roles ) {
			foreach ( $roles as $role_name ) {
				$role = get_role( $role_name );
				if ( $role && ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Rimuove le capability da tutti i ruoli.
	 *
	 * @return void
	 */
	public static function remove_caps() {
		$roles = wp_roles();
		foreach ( array_keys( self::CAPS ) as $cap ) {
			foreach ( array_keys( $roles->roles ) as $role_name ) {
				$role = get_role( $role_name );
				if ( $role ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}
}
