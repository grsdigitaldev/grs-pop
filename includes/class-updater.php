<?php
/**
 * Aggiornamenti dalle release GitHub.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Update Checker: solo release pubblicate con lo zip `grs-pop-x.y.z.zip` allegato.
 */
final class Updater {

	/**
	 * Repository predefinito.
	 */
	const DEFAULT_REPO = 'https://github.com/grsdigitaldev/grs-pop/';

	/**
	 * Istanza di Plugin Update Checker.
	 *
	 * @var object|null
	 */
	private static $checker = null;

	/**
	 * Avvio (bacheca, cron e WP-CLI).
	 *
	 * @return void
	 */
	public static function init() {
		$repo = self::repository();
		if ( '' === $repo ) {
			return;
		}
		$loader = GRS_POP_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
		if ( ! is_readable( $loader ) ) {
			return;
		}
		require_once $loader;
		if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( $repo, GRS_POP_FILE, 'grs-pop' );
		$api     = method_exists( $checker, 'getVcsApi' ) ? $checker->getVcsApi() : null;
		$const   = $api ? get_class( $api ) . '::REQUIRE_RELEASE_ASSETS' : '';

		if ( $api && defined( $const ) && method_exists( $api, 'enableReleaseAssets' ) ) {
			// Solo lo zip allegato con il nome atteso: mai il sorgente del repository.
			$api->enableReleaseAssets( '/^grs-pop-[0-9]+\.[0-9]+\.[0-9]+\.zip$/i', constant( $const ) );
			add_filter(
				$checker->getUniqueName( 'vcs_update_detection_strategies' ),
				static function ( $strategies ) {
					return isset( $strategies['latest_release'] ) ? array( 'latest_release' => $strategies['latest_release'] ) : array();
				}
			);
		} else {
			add_filter( $checker->getUniqueName( 'vcs_update_detection_strategies' ), '__return_empty_array' );
		}
		self::$checker = $checker;
	}

	/**
	 * Repository degli aggiornamenti (vuoto = aggiornamenti spenti).
	 *
	 * @return string
	 */
	public static function repository() {
		$repo = defined( 'GRS_POP_UPDATE_REPO' ) ? (string) GRS_POP_UPDATE_REPO : self::DEFAULT_REPO;
		return (string) apply_filters( 'grs_pop_update_repository', $repo );
	}

	/**
	 * Istanza di Plugin Update Checker (test).
	 *
	 * @return object|null
	 */
	public static function checker() {
		return self::$checker;
	}
}
