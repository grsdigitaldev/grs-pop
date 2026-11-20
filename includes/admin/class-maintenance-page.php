<?php
/**
 * Scheda "Coming soon": modalità coming soon / manutenzione.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop\Admin;

use GRSDigital\Pop\Maintenance;

defined( 'ABSPATH' ) || exit;

/**
 * Modulo delle impostazioni (options.php) con anteprima desktop e smartphone.
 */
final class Maintenance_Page {

	/**
	 * Rigenera la chiave del link segreto.
	 *
	 * @return void
	 */
	public static function handle_new_key() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'grs-pop' ), 403 );
		}
		check_admin_referer( 'grs_pop_ms_new_key' );
		$values        = Maintenance::get();
		$values['key'] = Maintenance::new_key();
		update_option( Maintenance::OPTION, $values, true );
		wp_safe_redirect( Admin::url( 'maintenance', array( 'grs_msg' => 'newkey' ) ) );
		exit;
	}

	/**
	 * Modelli di partenza: testi e opzioni della pagina, senza toccare modalità, accessi, logo,
	 * contatti e form.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function presets() {
		return array(
			'launch'                => array(
				'name'   => __( 'Coming soon with launch', 'grs-pop' ),
				'desc'   => __( 'Countdown to the opening date, the site opens by itself when the time comes.', 'grs-pop' ),
				'mode'   => 'coming_soon',
				'values' => array(
					'style'           => 'light',
					'heading'         => __( 'We are about to launch', 'grs-pop' ),
					'text'            => __( 'We are putting the finishing touches to our new website. The countdown shows when we open.', 'grs-pop' ),
					'countdown'       => 1,
					'countdown_title' => __( 'We open in', 'grs-pop' ),
					'auto_off'        => 1,
				),
				'days'   => 14,
			),
			'minimal'               => array(
				'name'   => __( 'Minimal coming soon', 'grs-pop' ),
				'desc'   => __( 'Logo, one sentence and contacts, without countdown.', 'grs-pop' ),
				'mode'   => 'coming_soon',
				'values' => array(
					'style'           => 'light',
					'heading'         => __( 'Coming soon', 'grs-pop' ),
					'text'            => __( 'Our new website is on its way. In the meantime, you can contact us here.', 'grs-pop' ),
					'countdown'       => 0,
					'countdown_title' => '',
					'auto_off'        => 0,
				),
				'days'   => 0,
			),
			'maintenance_short'     => array(
				'name'   => __( 'Short maintenance', 'grs-pop' ),
				'desc'   => __( 'Background in the brand colour and contacts for anything urgent, without countdown.', 'grs-pop' ),
				'mode'   => 'maintenance',
				'values' => array(
					'style'           => 'brand',
					'heading'         => __( 'We will be back shortly', 'grs-pop' ),
					'text'            => __( 'We are carrying out some maintenance on the site. For anything urgent you can call or write to us.', 'grs-pop' ),
					'countdown'       => 0,
					'countdown_title' => '',
					'auto_off'        => 0,
				),
				'days'   => 0,
			),
			'maintenance_scheduled' => array(
				'name'   => __( 'Scheduled maintenance', 'grs-pop' ),
				'desc'   => __( 'End date of the works (also sent to Google as Retry-After), countdown and contacts for emergencies.', 'grs-pop' ),
				'mode'   => 'maintenance',
				'values' => array(
					'style'           => 'light',
					'heading'         => __( 'Scheduled maintenance', 'grs-pop' ),
					'text'            => __( 'The site is temporarily offline for scheduled work and will be back online at the time shown below. For emergencies you can contact us.', 'grs-pop' ),
					'countdown'       => 1,
					'countdown_title' => __( 'Back online in', 'grs-pop' ),
					'auto_off'        => 1,
				),
				'days'   => 1,
			),
		);
	}

	/**
	 * Applica un modello.
	 *
	 * @return void
	 */
	public static function handle_preset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'grs-pop' ), 403 );
		}
		check_admin_referer( 'grs_pop_ms_preset' );
		$id      = isset( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : '';
		$presets = self::presets();
		if ( ! isset( $presets[ $id ] ) ) {
			wp_safe_redirect( Admin::url( 'maintenance' ) );
			exit;
		}
		$preset = $presets[ $id ];
		$values = array_merge( Maintenance::get(), $preset['values'] );

		// Data di lancio: si tiene quella futura già salvata, altrimenti una proposta da correggere.
		if ( $preset['days'] > 0 && Maintenance::launch_timestamp( $values ) <= time() ) {
			$date             = new \DateTimeImmutable( 'now', wp_timezone() );
			$date             = $date->modify( '+' . (int) $preset['days'] . ' days' )->setTime( 9, 0 );
			$values['launch'] = $date->format( 'Y-m-d\TH:i' );
		}

		update_option( Maintenance::OPTION, $values, true );
		wp_safe_redirect(
			Admin::url(
				'maintenance',
				array(
					'grs_msg'    => 'preset',
					'grs_preset' => $id,
				)
			)
		);
		exit;
	}

	/**
	 * Script e stili della scheda.
	 *
	 * @return void
	 */
	public static function enqueue() {
		wp_enqueue_media();
		wp_enqueue_script( 'grs-pop-maintenance-admin', GRS_POP_URL . 'assets/js/maintenance-admin.js', array( 'jquery' ), GRS_POP_VERSION, true );
	}

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
		$s    = Maintenance::get();
		$name = Maintenance::OPTION;
		$mode = Maintenance::mode();

		settings_errors();

		if ( 'off' !== $mode ) {
			$label = 'maintenance' === $mode ? __( 'Maintenance mode is active: visitors see the courtesy page with status 503.', 'grs-pop' ) : __( 'Coming soon mode is active: visitors see the courtesy page and search engines are asked not to index the site.', 'grs-pop' );
			echo '<div class="notice notice-warning inline grs-pop-ms-status"><p><strong>' . esc_html( $label ) . '</strong></p></div>';
		} elseif ( 'off' !== $s['mode'] ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'The launch date has passed and the mode switched off automatically: the site is public.', 'grs-pop' ) . '</p></div>';
		}

		// Modulo separato per la rigenerazione della chiave (i form non si annidano).
		echo '<form id="grs-pop-ms-newkey" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="grs_pop_ms_new_key">';
		wp_nonce_field( 'grs_pop_ms_new_key' );
		echo '</form>';

		// Modelli di partenza (modulo separato).
		echo '<form id="grs-pop-ms-preset" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="grs_pop_ms_preset">';
		wp_nonce_field( 'grs_pop_ms_preset' );
		echo '</form>';
		echo '<div class="grs-ui-card grs-pop-ms-presets-card"><h2>' . esc_html__( 'Start from a template', 'grs-pop' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'A template replaces title, text, style and countdown settings. Mode, logo, contacts, form and secret link stay as they are.', 'grs-pop' ) . '</p>';
		echo '<div class="grs-pop-ms-presets">';
		$modes_label = array(
			'coming_soon' => __( 'Coming soon', 'grs-pop' ),
			'maintenance' => __( 'Maintenance', 'grs-pop' ),
		);
		foreach ( self::presets() as $id => $preset ) {
			echo '<button type="submit" form="grs-pop-ms-preset" name="preset" value="' . esc_attr( $id ) . '" class="grs-pop-ms-preset" data-confirm="' . esc_attr__( 'Apply this template? Title, text, style and countdown settings will be replaced, and unsaved changes on this page will be lost.', 'grs-pop' ) . '">';
			echo '<span class="grs-pop-ms-preset__mode">' . esc_html( $modes_label[ $preset['mode'] ] ) . '</span>';
			echo '<strong class="grs-pop-ms-preset__name">' . esc_html( $preset['name'] ) . '</strong>';
			echo '<span class="grs-pop-ms-preset__desc">' . esc_html( $preset['desc'] ) . '</span>';
			echo '</button>';
		}
		echo '</div></div>';

		echo '<form method="post" action="options.php" class="grs-pop-settings grs-pop-ms-admin">';
		settings_fields( 'grs_pop_maintenance_group' );

		// Stato.
		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Mode', 'grs-pop' ) . '</h2>';
		echo '<fieldset class="grs-pop-ms-modes">';
		$modes = array(
			'off'         => array( __( 'Off', 'grs-pop' ), __( 'The site is public.', 'grs-pop' ) ),
			'coming_soon' => array( __( 'Coming soon', 'grs-pop' ), __( 'For a site not launched yet: courtesy page with status 200 and "noindex".', 'grs-pop' ) ),
			'maintenance' => array( __( 'Maintenance', 'grs-pop' ), __( 'For a site already online: status 503 with "Retry-After", so search engines come back later without losing rankings.', 'grs-pop' ) ),
		);
		foreach ( $modes as $value => $info ) {
			echo '<label class="grs-pop-ms-mode"><input type="radio" name="' . esc_attr( $name . '[mode]' ) . '" value="' . esc_attr( $value ) . '"' . checked( $s['mode'], $value, false ) . '> <strong>' . esc_html( $info[0] ) . '</strong><span class="description">' . esc_html( $info[1] ) . '</span></label>';
		}
		echo '</fieldset>';
		echo '<p class="description">' . esc_html__( 'Login, dashboard, REST API, cron and robots.txt always remain reachable. The page cache is cleared at every save.', 'grs-pop' ) . '</p>';
		echo '</div>';

		// Accesso.
		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Who sees the real site', 'grs-pop' ) . '</h2><table class="form-table" role="presentation">';
		echo '<tr><th scope="row">' . esc_html__( 'Logged-in users', 'grs-pop' ) . '</th><td><fieldset>';
		foreach ( wp_roles()->get_names() as $role => $role_name ) {
			if ( 'administrator' === $role ) {
				continue; // Sempre ammessi: non si può togliere.
			}
			echo '<label class="grs-pop-ms-check"><input type="checkbox" name="' . esc_attr( $name . '[roles][]' ) . '" value="' . esc_attr( $role ) . '"' . checked( in_array( $role, (array) $s['roles'], true ), true, false ) . '> ' . esc_html( translate_user_role( $role_name ) ) . '</label>';
		}
		echo '<p class="description">' . esc_html__( 'Administrators always see the site.', 'grs-pop' ) . '</p></fieldset></td></tr>';

		echo '<tr><th scope="row"><label for="grs-pop-ms-link">' . esc_html__( 'Secret preview link', 'grs-pop' ) . '</label></th><td>';
		echo '<div class="grs-pop-ms-link"><input type="text" readonly id="grs-pop-ms-link" class="large-text code" value="' . esc_attr( Maintenance::access_url() ) . '"> ';
		echo '<button type="button" class="button grs-pop-ms-copy" data-target="grs-pop-ms-link" data-done="' . esc_attr__( 'Copied', 'grs-pop' ) . '">' . esc_html__( 'Copy', 'grs-pop' ) . '</button> ';
		echo '<button type="submit" form="grs-pop-ms-newkey" class="button">' . esc_html__( 'Generate a new link', 'grs-pop' ) . '</button></div>';
		echo '<p class="description">' . esc_html__( 'Send it to a client who is not logged in: opening it shows the real site in that browser. A new link makes the old one stop working.', 'grs-pop' ) . '</p>';
		echo '<p><label for="grs-pop-ms-days">' . esc_html__( 'The link stays valid in the browser for (days)', 'grs-pop' ) . '</label> <input type="number" class="small-text" id="grs-pop-ms-days" name="' . esc_attr( $name . '[key_days]' ) . '" value="' . esc_attr( (string) $s['key_days'] ) . '" min="1" max="60"></p>';
		echo '</td></tr></table></div>';

		// Contenuto.
		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Page content', 'grs-pop' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Colours come from the palette in Settings. Texts are translatable with WPML or Polylang (context "GRS Pop").', 'grs-pop' ) . '</p>';
		echo '<table class="form-table" role="presentation">';

		echo '<tr><th scope="row">' . esc_html__( 'Logo', 'grs-pop' ) . '</th><td><fieldset>';
		$logos = array(
			'site'   => __( 'Site logo (from the theme)', 'grs-pop' ),
			'custom' => __( 'Another image', 'grs-pop' ),
			'none'   => __( 'No logo', 'grs-pop' ),
		);
		foreach ( $logos as $value => $label ) {
			echo '<label class="grs-pop-ms-check"><input type="radio" name="' . esc_attr( $name . '[logo]' ) . '" value="' . esc_attr( $value ) . '"' . checked( $s['logo'], $value, false ) . '> ' . esc_html( $label ) . '</label>';
		}
		$logo_url = $s['logo_id'] ? wp_get_attachment_image_url( (int) $s['logo_id'], 'medium' ) : '';
		echo '<div class="grs-pop-ms-media" data-title="' . esc_attr__( 'Choose the logo', 'grs-pop' ) . '" data-button="' . esc_attr__( 'Use this image', 'grs-pop' ) . '">';
		echo '<input type="hidden" name="' . esc_attr( $name . '[logo_id]' ) . '" value="' . esc_attr( (string) $s['logo_id'] ) . '" class="grs-pop-ms-media__id">';
		echo '<span class="grs-pop-ms-media__preview">' . ( $logo_url ? '<img src="' . esc_url( $logo_url ) . '" alt="">' : '' ) . '</span> ';
		echo '<button type="button" class="button grs-pop-ms-media__pick">' . esc_html__( 'Choose image', 'grs-pop' ) . '</button> ';
		echo '<button type="button" class="button-link grs-pop-ms-media__remove"' . ( $logo_url ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'grs-pop' ) . '</button>';
		echo '</div>';
		if ( 'site' === $s['logo'] && ! get_theme_mod( 'custom_logo' ) ) {
			echo '<p class="description">' . esc_html__( 'The theme has no logo set: the site name is shown instead.', 'grs-pop' ) . '</p>';
		}
		echo '<p><label for="grs-pop-ms-logo-width">' . esc_html__( 'Logo width (px)', 'grs-pop' ) . '</label> <input type="number" class="small-text" id="grs-pop-ms-logo-width" name="' . esc_attr( $name . '[logo_width]' ) . '" value="' . esc_attr( (string) $s['logo_width'] ) . '" min="40" max="480"></p>';
		echo '</fieldset></td></tr>';

		echo '<tr><th scope="row"><label for="grs-pop-ms-style">' . esc_html__( 'Style', 'grs-pop' ) . '</label></th><td><select id="grs-pop-ms-style" name="' . esc_attr( $name . '[style]' ) . '">';
		echo '<option value="light"' . selected( $s['style'], 'light', false ) . '>' . esc_html__( 'Light background, card in the brand colour', 'grs-pop' ) . '</option>';
		echo '<option value="brand"' . selected( $s['style'], 'brand', false ) . '>' . esc_html__( 'Background in the brand colour', 'grs-pop' ) . '</option>';
		echo '</select></td></tr>';

		self::text_row( $name, 'heading', __( 'Title', 'grs-pop' ), $s['heading'], __( 'Empty = "Coming soon" or "We are working on the site", in the visitor\'s language.', 'grs-pop' ) );
		echo '<tr><th scope="row"><label for="grs-pop-ms-text">' . esc_html__( 'Text', 'grs-pop' ) . '</label></th><td><textarea id="grs-pop-ms-text" class="large-text" rows="4" name="' . esc_attr( $name . '[text]' ) . '">' . esc_textarea( $s['text'] ) . '</textarea><p class="description">' . esc_html__( 'You can use <strong>, <em> and links. Empty = a standard text.', 'grs-pop' ) . '</p></td></tr>';
		echo '</table></div>';

		// Contatti.
		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Contacts', 'grs-pop' ) . '</h2><table class="form-table" role="presentation">';
		self::text_row( $name, 'email', __( 'Email', 'grs-pop' ), $s['email'], '', 'email' );
		self::text_row( $name, 'phone', __( 'Phone', 'grs-pop' ), $s['phone'], '', 'tel' );
		self::text_row( $name, 'whatsapp', __( 'WhatsApp number', 'grs-pop' ), $s['whatsapp'], __( 'International format, digits only (e.g. 393331234567).', 'grs-pop' ), 'tel' );
		echo '<tr><th scope="row"><label for="grs-pop-ms-address">' . esc_html__( 'Address', 'grs-pop' ) . '</label></th><td><textarea id="grs-pop-ms-address" class="large-text" rows="2" name="' . esc_attr( $name . '[address]' ) . '">' . esc_textarea( $s['address'] ) . '</textarea></td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Social profiles', 'grs-pop' ) . '</th><td class="grs-pop-ms-social">';
		foreach ( Maintenance::SOCIALS as $network => $label ) {
			echo '<label><span>' . esc_html( $label ) . '</span><input type="url" class="regular-text" name="' . esc_attr( $name . '[social][' . $network . ']' ) . '" value="' . esc_attr( $s['social'][ $network ] ) . '" placeholder="https://"></label>';
		}
		echo '</td></tr></table></div>';

		// Conto alla rovescia.
		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Launch countdown', 'grs-pop' ) . '</h2><table class="form-table" role="presentation">';
		echo '<tr><th scope="row">' . esc_html__( 'Countdown', 'grs-pop' ) . '</th><td><label><input type="checkbox" name="' . esc_attr( $name . '[countdown]' ) . '" value="1"' . checked( ! empty( $s['countdown'] ), true, false ) . '> ' . esc_html__( 'Show a countdown to the launch date', 'grs-pop' ) . '</label></td></tr>';
		/* translators: %s: time zone name. */
		$tz_help = sprintf( __( 'Site time zone (%s).', 'grs-pop' ), wp_timezone_string() );
		self::text_row( $name, 'launch', __( 'Launch date and time', 'grs-pop' ), $s['launch'], $tz_help, 'datetime-local' );
		self::text_row( $name, 'countdown_title', __( 'Text above the countdown', 'grs-pop' ), $s['countdown_title'], __( 'For example "We open in".', 'grs-pop' ) );
		echo '<tr><th scope="row">' . esc_html__( 'At the launch date', 'grs-pop' ) . '</th><td><label><input type="checkbox" name="' . esc_attr( $name . '[auto_off]' ) . '" value="1"' . checked( ! empty( $s['auto_off'] ), true, false ) . '> ' . esc_html__( 'Switch the mode off automatically and open the site', 'grs-pop' ) . '</label><p class="description">' . esc_html__( 'Visitors with the page open see the site as soon as the countdown ends. In maintenance mode the date is also sent to search engines as "Retry-After".', 'grs-pop' ) . '</p></td></tr>';
		echo '</table></div>';

		// Form.
		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Form', 'grs-pop' ) . '</h2>';
		if ( ! defined( 'GRS_FORMS_VERSION' ) ) {
			echo '<p class="description">' . esc_html__( 'Install and activate GRS Forms to add a form to the page (for example "notify me when we launch").', 'grs-pop' ) . '</p>';
			echo '<input type="hidden" name="' . esc_attr( $name . '[form_id]' ) . '" value="' . esc_attr( (string) $s['form_id'] ) . '">';
		} else {
			$forms = Maintenance::forms_list();
			echo '<p><label for="grs-pop-ms-form">' . esc_html__( 'GRS Forms form', 'grs-pop' ) . '</label><br><select id="grs-pop-ms-form" name="' . esc_attr( $name . '[form_id]' ) . '"><option value="0">' . esc_html__( 'No form', 'grs-pop' ) . '</option>';
			$found = false;
			foreach ( (array) $forms as $form ) {
				$found = $found || (int) $form['id'] === (int) $s['form_id'];
				$label = '' !== $form['title'] ? $form['title'] : '#' . $form['id'];
				if ( ! $form['active'] ) {
					$label .= ' (' . __( 'inactive', 'grs-pop' ) . ')';
				}
				echo '<option value="' . esc_attr( (string) $form['id'] ) . '"' . selected( (int) $s['form_id'], (int) $form['id'], false ) . '>' . esc_html( $label ) . '</option>';
			}
			if ( ! $found && (int) $s['form_id'] > 0 ) {
				/* translators: %d: form ID. */
				echo '<option value="' . esc_attr( (string) $s['form_id'] ) . '" selected>' . esc_html( sprintf( __( 'Form #%d', 'grs-pop' ), (int) $s['form_id'] ) ) . '</option>';
			}
			echo '</select></p>';
			if ( null === $forms ) {
				echo '<p class="description">' . esc_html__( 'The list of forms could not be loaded from GRS Forms. Update GRS Forms or reload the page.', 'grs-pop' ) . '</p>';
			}
			echo '<p class="description">' . esc_html__( 'Leads, anti-spam and notifications are handled by GRS Forms.', 'grs-pop' ) . '</p>';
		}
		echo '</div>';

		submit_button( __( 'Save', 'grs-pop' ) );
		echo '</form>';

		// Anteprima (impostazioni salvate).
		echo '<div class="grs-ui-card grs-pop-ms-preview"><h2>' . esc_html__( 'Preview', 'grs-pop' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'The page as visitors will see it, with the saved settings.', 'grs-pop' ) . '</p>';
		echo '<p class="grs-pop-ms-preview__bar"><button type="button" class="button grs-pop-ms-device is-active" data-width="100%" aria-pressed="true">' . esc_html__( 'Desktop', 'grs-pop' ) . '</button> <button type="button" class="button grs-pop-ms-device" data-width="390px" aria-pressed="false">' . esc_html__( 'Mobile', 'grs-pop' ) . '</button> <a class="button" href="' . esc_url( Maintenance::preview_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open in a new tab', 'grs-pop' ) . '</a></p>';
		echo '<div class="grs-pop-ms-preview__frame"><iframe title="' . esc_attr__( 'Preview', 'grs-pop' ) . '" src="' . esc_url( Maintenance::preview_url() ) . '" loading="lazy"></iframe></div>';
		echo '</div>';
	}

	/**
	 * Riga di testo.
	 *
	 * @param string $name  Nome dell'opzione.
	 * @param string $key   Chiave.
	 * @param string $label Etichetta.
	 * @param string $value Valore.
	 * @param string $help  Descrizione.
	 * @param string $type  Tipo del campo.
	 * @return void
	 */
	private static function text_row( $name, $key, $label, $value, $help = '', $type = 'text' ) {
		$id = 'grs-pop-ms-' . str_replace( '_', '-', $key );
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input type="' . esc_attr( $type ) . '" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '">';
		if ( '' !== $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	}
}
