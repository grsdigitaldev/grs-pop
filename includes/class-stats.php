<?php
/**
 * Statistiche aggregate per giorno.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- tabella propria del plugin, letture già aggregate.

/**
 * Una riga per popup, giorno e dispositivo: nessun dato personale, nessun identificativo del visitatore.
 */
final class Stats {

	/**
	 * Versione dello schema della tabella.
	 */
	const DB_VERSION = '1';

	/**
	 * Evento di pulizia.
	 */
	const CRON_HOOK = 'grs_pop_stats_cleanup';

	/**
	 * Dispositivi (indice salvato nella tabella).
	 */
	const DEVICES = array( 'desktop', 'tablet', 'mobile' );

	/**
	 * Tipi di evento inviati dal browser => colonna.
	 */
	const TYPES = array(
		'v' => 'views',
		'x' => 'closes',
		'c' => 'conversions',
		'k' => 'clicks',
	);

	/**
	 * Nome della tabella.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'grs_pop_stats';
	}

	/**
	 * Crea o aggiorna la tabella.
	 *
	 * @return void
	 */
	public static function create_table() {
		if ( self::DB_VERSION === get_option( 'grs_pop_db_version' ) ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$collate = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
popup_id bigint(20) unsigned NOT NULL,
day date NOT NULL,
device tinyint(3) unsigned NOT NULL DEFAULT 0,
views int(10) unsigned NOT NULL DEFAULT 0,
closes int(10) unsigned NOT NULL DEFAULT 0,
conversions int(10) unsigned NOT NULL DEFAULT 0,
clicks int(10) unsigned NOT NULL DEFAULT 0,
PRIMARY KEY  (popup_id,day,device),
KEY day (day)
) {$collate};"
		);
		update_option( 'grs_pop_db_version', self::DB_VERSION, true );
	}

	/**
	 * Registra eventi già validati.
	 *
	 * @param array<int, array{id:int, d:int, t:string}> $events Eventi.
	 * @return int Righe aggiornate.
	 */
	public static function record( array $events ) {
		global $wpdb;
		$rows = array();
		foreach ( $events as $event ) {
			$key = $event['id'] . ':' . $event['d'];
			if ( ! isset( $rows[ $key ] ) ) {
				$rows[ $key ] = array(
					'id'  => (int) $event['id'],
					'd'   => (int) $event['d'],
					'col' => array_fill_keys( array_values( self::TYPES ), 0 ),
				);
			}
			$rows[ $key ]['col'][ self::TYPES[ $event['t'] ] ] = 1; // Al massimo 1 per tipo, popup e richiesta.
		}

		$day   = wp_date( 'Y-m-d' );
		$table = self::table();
		$count = 0;
		foreach ( $rows as $row ) {
			$c      = $row['col'];
			$result = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$table} (popup_id, day, device, views, closes, conversions, clicks) VALUES (%d, %s, %d, %d, %d, %d, %d) ON DUPLICATE KEY UPDATE views = views + VALUES(views), closes = closes + VALUES(closes), conversions = conversions + VALUES(conversions), clicks = clicks + VALUES(clicks)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nome della tabella interno.
					$row['id'],
					$day,
					$row['d'],
					$c['views'],
					$c['closes'],
					$c['conversions'],
					$c['clicks']
				)
			);
			$count += $result ? 1 : 0;
		}
		return $count;
	}

	/**
	 * Totali per popup da una data in poi.
	 *
	 * @param int $days Giorni (oggi compreso).
	 * @return array<int, array<string, int>> ID => totali.
	 */
	public static function totals( $days ) {
		global $wpdb;
		$table = self::table();
		$since = self::since( $days );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT popup_id, SUM(views) AS views, SUM(closes) AS closes, SUM(conversions) AS conversions, SUM(clicks) AS clicks FROM {$table} WHERE day >= %s GROUP BY popup_id", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nome della tabella interno.
				$since
			),
			ARRAY_A
		);
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['popup_id'] ] = self::row_ints( $row );
		}
		return $out;
	}

	/**
	 * Dati di un popup per giorno e per dispositivo.
	 *
	 * @param int $popup_id ID.
	 * @param int $days     Giorni.
	 * @return array{days: array<string, array<string, int>>, devices: array<string, array<string, int>>}
	 */
	public static function detail( $popup_id, $days ) {
		global $wpdb;
		$table  = self::table();
		$since  = self::since( $days );
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT day, device, views, closes, conversions, clicks FROM {$table} WHERE popup_id = %d AND day >= %s ORDER BY day ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nome della tabella interno.
				(int) $popup_id,
				$since
			),
			ARRAY_A
		);
		$by_day = array();
		$by_dev = array();
		$zero   = array_fill_keys( array_values( self::TYPES ), 0 );
		foreach ( (array) $rows as $row ) {
			$day    = (string) $row['day'];
			$device = isset( self::DEVICES[ (int) $row['device'] ] ) ? self::DEVICES[ (int) $row['device'] ] : 'desktop';
			$vals   = self::row_ints( $row );
			if ( ! isset( $by_day[ $day ] ) ) {
				$by_day[ $day ] = $zero;
			}
			if ( ! isset( $by_dev[ $device ] ) ) {
				$by_dev[ $device ] = $zero;
			}
			foreach ( $zero as $col => $unused ) {
				$by_day[ $day ][ $col ]    += $vals[ $col ];
				$by_dev[ $device ][ $col ] += $vals[ $col ];
			}
		}
		return array(
			'days'    => $by_day,
			'devices' => $by_dev,
		);
	}

	/**
	 * Valori interi di una riga.
	 *
	 * @param array<string, mixed> $row Riga.
	 * @return array<string, int>
	 */
	private static function row_ints( array $row ) {
		$out = array();
		foreach ( self::TYPES as $col ) {
			$out[ $col ] = isset( $row[ $col ] ) ? (int) $row[ $col ] : 0;
		}
		return $out;
	}

	/**
	 * Data di inizio del periodo (fuso del sito).
	 *
	 * @param int $days Giorni.
	 * @return string
	 */
	private static function since( $days ) {
		$days = max( 1, (int) $days );
		return wp_date( 'Y-m-d', time() - ( $days - 1 ) * DAY_IN_SECONDS );
	}

	/**
	 * Elimina le righe più vecchie della conservazione impostata.
	 *
	 * @return void
	 */
	public static function cleanup() {
		global $wpdb;
		$settings = Settings::get();
		$table    = self::table();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE day < %s", self::since( (int) $settings['stats_retention'] + 1 ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nome della tabella interno.
	}

	/**
	 * Elimina le statistiche di un popup.
	 *
	 * @param int $popup_id ID.
	 * @return void
	 */
	public static function delete_popup( $popup_id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'popup_id' => (int) $popup_id ), array( '%d' ) );
	}

	/**
	 * Azzera le statistiche di un popup (alias leggibile).
	 *
	 * @param int $popup_id ID.
	 * @return void
	 */
	public static function reset_popup( $popup_id ) {
		self::delete_popup( $popup_id );
	}
}
