<?php
/**
 * Scheda Impostazioni.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop\Admin;

use GRSDigital\Pop\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Palette, dispositivi, statistiche e dati.
 */
final class Settings_Page {

	/**
	 * Rendering.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			echo '<p>' . esc_html__( 'You are not allowed to change the settings.', 'grs-pop' ) . '</p>';
			return;
		}
		$s    = Settings::get();
		$name = Settings::OPTION;

		echo '<form method="post" action="options.php" class="grs-pop-settings">';
		settings_fields( 'grs_pop_settings_group' );

		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Colour palette', 'grs-pop' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'The templates use these colours: set your brand colours once and every popup that uses the palette will follow them.', 'grs-pop' ) . '</p>';
		echo '<div class="grs-pop-palette">';
		foreach ( Settings::palette_labels() as $key => $label ) {
			$id = 'grs-pop-color-' . $key;
			echo '<label class="grs-pop-swatch" for="' . esc_attr( $id ) . '"><input type="color" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name . '[palette][' . $key . ']' ) . '" value="' . esc_attr( $s['palette'][ $key ] ) . '"><span>' . esc_html( $label ) . '<code>' . esc_html( $s['palette'][ $key ] ) . '</code></span></label>';
		}
		echo '</div></div>';

		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Devices and display', 'grs-pop' ) . '</h2><table class="form-table" role="presentation">';
		self::number_row( $name, 'bp_mobile', __( 'Mobile up to (px)', 'grs-pop' ), $s['bp_mobile'], 320, 1200, __( 'Screens up to this width use the mobile settings of each popup.', 'grs-pop' ) );
		self::number_row( $name, 'bp_tablet', __( 'Tablet up to (px)', 'grs-pop' ), $s['bp_tablet'], 321, 2000, __( 'Wider screens use the desktop settings.', 'grs-pop' ) );
		self::number_row( $name, 'max_intrusive', __( 'Popups covering the page at the same time', 'grs-pop' ), $s['max_intrusive'], 1, 5, __( 'Centred popups and full-screen windows: if one is already open, the others wait. Bars, slide-ins and tabs are not counted. Recommended: 1.', 'grs-pop' ) );
		self::number_row( $name, 'z_index', __( 'Stacking level (z-index)', 'grs-pop' ), $s['z_index'], 1000, 2147483000, __( 'Raise it only if a sticky header or a chat widget covers the popups.', 'grs-pop' ) );
		echo '</table></div>';

		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Statistics and data', 'grs-pop' ) . '</h2><table class="form-table" role="presentation">';
		echo '<tr><th scope="row">' . esc_html__( 'Statistics', 'grs-pop' ) . '</th><td><label><input type="checkbox" name="' . esc_attr( $name . '[stats_enabled]' ) . '" value="1"' . checked( ! empty( $s['stats_enabled'] ), true, false ) . '> ' . esc_html__( 'Count views, closes and conversions (anonymous daily totals, no personal data)', 'grs-pop' ) . '</label></td></tr>';
		self::number_row( $name, 'stats_retention', __( 'Keep statistics for (days)', 'grs-pop' ), $s['stats_retention'], 30, 3650, __( 'Older data is deleted automatically every day.', 'grs-pop' ) );
		echo '<tr><th scope="row">' . esc_html__( 'Uninstall', 'grs-pop' ) . '</th><td><label><input type="checkbox" name="' . esc_attr( $name . '[keep_data]' ) . '" value="1"' . checked( ! empty( $s['keep_data'] ), true, false ) . '> ' . esc_html__( 'Keep popups, statistics and settings when the plugin is deleted', 'grs-pop' ) . '</label></td></tr>';
		echo '</table></div>';

		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Integrations', 'grs-pop' ) . '</h2><ul class="grs-pop-integrations">';
		self::integration( defined( 'GRS_FORMS_VERSION' ) ? 'on' : 'off', 'GRS Forms', __( 'forms inside popups, with leads and anti-spam, and popups shown or hidden according to the forms the visitor has already filled in.', 'grs-pop' ) );
		$tracking = 'off';
		if ( defined( 'GRS_TRACKING_VERSION' ) ) {
			$tracking = version_compare( (string) GRS_TRACKING_VERSION, Admin::TRACKING_POP_VERSION, '>=' ) ? 'on' : 'update';
		}
		self::integration( $tracking, 'GRS Tracking', __( 'popup views and conversions sent to GA4 and the other tags (events grs-pop:view and grs-pop:convert).', 'grs-pop' ), Admin::TRACKING_POP_VERSION );
		self::integration( defined( 'ICL_SITEPRESS_VERSION' ) || function_exists( 'pll_current_language' ) ? 'on' : 'off', 'WPML / Polylang', __( 'popup texts translatable with String Translation.', 'grs-pop' ) );
		echo '</ul></div>';

		submit_button( __( 'Save settings', 'grs-pop' ) );
		echo '</form>';
	}

	/**
	 * Riga numerica.
	 *
	 * @param string $name  Nome dell'opzione.
	 * @param string $key   Chiave.
	 * @param string $label Etichetta.
	 * @param int    $value Valore.
	 * @param int    $min   Minimo.
	 * @param int    $max   Massimo.
	 * @param string $help  Descrizione.
	 * @return void
	 */
	private static function number_row( $name, $key, $label, $value, $min, $max, $help ) {
		$id = 'grs-pop-' . $key;
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input type="number" class="small-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" min="' . esc_attr( (string) $min ) . '" max="' . esc_attr( (string) $max ) . '">';
		echo '<p class="description">' . esc_html( $help ) . '</p></td></tr>';
	}

	/**
	 * Stato di un'integrazione.
	 *
	 * @param string $state   'on' (attiva), 'update' (plugin presente ma da aggiornare) o 'off'.
	 * @param string $name    Nome.
	 * @param string $what    Cosa aggiunge.
	 * @param string $version Versione richiesta (per lo stato 'update').
	 * @return void
	 */
	private static function integration( $state, $name, $what, $version = '' ) {
		if ( 'on' === $state ) {
			$badge = '<span class="grs-ui-badge grs-ui-badge--ok">' . esc_html__( 'Active', 'grs-pop' ) . '</span>';
		} elseif ( 'update' === $state ) {
			/* translators: %s: version number. */
			$badge = '<span class="grs-ui-badge grs-ui-badge--warn">' . esc_html( sprintf( __( 'Update to %s', 'grs-pop' ), $version ) ) . '</span>';
		} else {
			$badge = '<span class="grs-ui-badge">' . esc_html__( 'Not detected', 'grs-pop' ) . '</span>';
		}
		echo '<li>' . $badge . ' <strong>' . esc_html( $name ) . '</strong>: ' . esc_html( $what ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $badge composto sopra con escape.
	}
}
