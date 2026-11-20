<?php
/**
 * Disattivazione.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Pulisce gli eventi pianificati. I dati restano fino alla disinstallazione.
 */
final class Deactivator {

	/**
	 * Disattivazione.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( Stats::CRON_HOOK );
	}
}
