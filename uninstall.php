<?php
/**
 * Disinstallazione: elimina i dati solo se "Conserva i dati" è disattivato.
 *
 * @package GRSDigital\Pop
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Pulizia di un sito.
 *
 * @return void
 */
function grs_pop_uninstall_site() {
	global $wpdb;

	// Eventi pianificati e cache di Plugin Update Checker: sempre eliminati.
	wp_clear_scheduled_hook( 'grs_pop_stats_cleanup' );
	wp_clear_scheduled_hook( 'puc_cron_check_updates-grs-pop' );
	delete_option( 'external_updates-grs-pop' );

	// Capability: rimosse sempre (una nuova attivazione le riassegna).
	$roles = wp_roles();
	foreach ( array_keys( $roles->roles ) as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			$role->remove_cap( 'grs_pop_manage_popups' );
			$role->remove_cap( 'grs_pop_view_stats' );
		}
	}

	$settings = get_option( 'grs_pop_settings', array() );
	$keep     = ! is_array( $settings ) || ! isset( $settings['keep_data'] ) || ! empty( $settings['keep_data'] );
	if ( $keep ) {
		return;
	}

	$ids = get_posts(
		array(
			'post_type'        => 'grs_popup',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);
	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}

	$table = $wpdb->prefix . 'grs_pop_stats';
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabella del plugin.

	foreach ( array( 'grs_pop_settings', 'grs_pop_index', 'grs_pop_maintenance', 'grs_pop_version', 'grs_pop_db_version' ) as $option ) {
		delete_option( $option );
	}
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_grs\\_pop\\_%' OR option_name LIKE '\\_transient\\_timeout\\_grs\\_pop\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- transient del plugin.
}

if ( is_multisite() ) {
	$grs_pop_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $grs_pop_sites as $grs_pop_site ) {
		switch_to_blog( (int) $grs_pop_site );
		grs_pop_uninstall_site();
		restore_current_blog();
	}
} else {
	grs_pop_uninstall_site();
}
