<?php
/**
 * Plugin Name:       GRS Pop
 * Plugin URI:        https://grsdigital.it
 * Description:       Lightweight popups, slide-ins, bars, welcome mats, inline boxes and floating tabs with ready-made presets, smart triggers, targeting and mobile-first display rules.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            GRS Digital
 * Author URI:        https://grsdigital.it
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       grs-pop
 * Domain Path:       /languages
 * Update URI:        https://github.com/grsdigitaldev/grs-pop
 *
 * @package GRSDigital\Pop
 */

defined( 'ABSPATH' ) || exit;

define( 'GRS_POP_VERSION', '1.3.0' );
define( 'GRS_POP_FILE', __FILE__ );
define( 'GRS_POP_PATH', plugin_dir_path( __FILE__ ) );
define( 'GRS_POP_URL', plugin_dir_url( __FILE__ ) );

// Guard versione PHP: questo file deve restare valido anche su PHP vecchi.
// Testo fisso in inglese: il controllo può avvenire prima del caricamento delle traduzioni.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html( 'GRS Pop requires PHP 7.4 or higher.' ) . '</p></div>';
		}
	);
	return;
}

require_once GRS_POP_PATH . 'includes/grs-devs/loader.php'; // Menu condiviso GRS DEVs.
require_once GRS_POP_PATH . 'includes/grs-i18n/loader.php'; // Ponte traduzioni GRS i18n.

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'GRSDigital\\Pop\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$parts    = explode( '\\', $relative );
		$name     = array_pop( $parts );
		$dir      = '';
		foreach ( $parts as $part ) {
			$dir .= strtolower( $part ) . '/';
		}
		$file = GRS_POP_PATH . 'includes/' . $dir . 'class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'GRSDigital\\Pop\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'GRSDigital\\Pop\\Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'GRSDigital\\Pop\\Plugin', 'instance' ) );

// Traduzioni incluse nel plugin (non è su wordpress.org).
add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'grs-pop', false, dirname( plugin_basename( GRS_POP_FILE ) ) . '/languages' );
	},
	1
);
