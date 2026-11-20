<?php
/**
 * Impostazioni generali.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Opzione unica in autoload con palette, punti di rottura e statistiche.
 */
final class Settings {

	/**
	 * Nome dell'opzione.
	 */
	const OPTION = 'grs_pop_settings';

	/**
	 * Cache della richiesta.
	 *
	 * @var array<string, mixed>|null
	 */
	private static $cache = null;

	/**
	 * Valori predefiniti.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'palette'         => array(
				'brand'      => '#1d4ed8',
				'brand_text' => '#ffffff',
				'ink'        => '#111827',
				'surface'    => '#ffffff',
				'muted'      => '#6b7280',
				'soft'       => '#f3f4f6',
			),
			'bp_mobile'       => 767,
			'bp_tablet'       => 1024,
			'max_intrusive'   => 1,
			'z_index'         => 99990,
			'stats_enabled'   => 1,
			'stats_retention' => 365,
			'keep_data'       => 1,
		);
	}

	/**
	 * Nomi dei colori della palette.
	 *
	 * @return array<string, string>
	 */
	public static function palette_labels() {
		return array(
			'brand'      => __( 'Brand', 'grs-pop' ),
			'brand_text' => __( 'Text on brand', 'grs-pop' ),
			'ink'        => __( 'Dark text', 'grs-pop' ),
			'surface'    => __( 'Background', 'grs-pop' ),
			'muted'      => __( 'Secondary text', 'grs-pop' ),
			'soft'       => __( 'Soft background', 'grs-pop' ),
		);
	}

	/**
	 * Registra l'opzione (su init, così la sanitizzazione vale in ogni contesto).
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			'grs_pop_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush_cache' ) );
		add_action( 'update_option_' . self::OPTION, array( Index::class, 'purge_page_cache' ) );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'flush_cache' ) );
	}

	/**
	 * Svuota la cache della richiesta.
	 *
	 * @return void
	 */
	public static function flush_cache() {
		self::$cache = null;
	}

	/**
	 * Impostazioni correnti, unite ai valori predefiniti.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		if ( null === self::$cache ) {
			$saved    = get_option( self::OPTION, array() );
			$defaults = self::defaults();
			$saved    = is_array( $saved ) ? $saved : array();
			$merged   = array_merge( $defaults, $saved );
			$palette  = isset( $saved['palette'] ) && is_array( $saved['palette'] ) ? $saved['palette'] : array();

			$merged['palette'] = array_merge( $defaults['palette'], $palette );
			self::$cache       = $merged;
		}
		return self::$cache;
	}

	/**
	 * Sanitizzazione.
	 *
	 * @param mixed $input Valori inviati.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$out      = $defaults;

		foreach ( array_keys( $defaults['palette'] ) as $key ) {
			$color = isset( $input['palette'][ $key ] ) ? sanitize_hex_color( (string) $input['palette'][ $key ] ) : '';
			if ( $color ) {
				$out['palette'][ $key ] = $color;
			}
		}

		$out['bp_mobile']       = self::int_between( $input, 'bp_mobile', 320, 1200, $defaults['bp_mobile'] );
		$out['bp_tablet']       = self::int_between( $input, 'bp_tablet', $out['bp_mobile'] + 1, 2000, max( $defaults['bp_tablet'], $out['bp_mobile'] + 1 ) );
		$out['max_intrusive']   = self::int_between( $input, 'max_intrusive', 1, 5, $defaults['max_intrusive'] );
		$out['z_index']         = self::int_between( $input, 'z_index', 1000, 2147483000, $defaults['z_index'] );
		$out['stats_retention'] = self::int_between( $input, 'stats_retention', 30, 3650, $defaults['stats_retention'] );
		$out['stats_enabled']   = empty( $input['stats_enabled'] ) ? 0 : 1;
		$out['keep_data']       = empty( $input['keep_data'] ) ? 0 : 1;

		self::flush_cache();
		return $out;
	}

	/**
	 * Intero compreso tra due valori.
	 *
	 * @param array<string, mixed> $input    Valori.
	 * @param string               $key      Chiave.
	 * @param int                  $min      Minimo.
	 * @param int                  $max      Massimo.
	 * @param int                  $fallback Valore se assente.
	 * @return int
	 */
	private static function int_between( array $input, $key, $min, $max, $fallback ) {
		if ( ! isset( $input[ $key ] ) || '' === $input[ $key ] ) {
			return (int) $fallback;
		}
		return max( (int) $min, min( (int) $max, (int) $input[ $key ] ) );
	}
}
