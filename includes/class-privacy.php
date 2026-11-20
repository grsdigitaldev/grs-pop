<?php
/**
 * Testo suggerito per l'informativa privacy.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * GRS Pop non salva dati personali: solo contatori aggregati e preferenze tecniche nel browser.
 */
final class Privacy {

	/**
	 * Aggiunge il testo nella guida all'informativa (Impostazioni → Privacy).
	 *
	 * @return void
	 */
	public static function add_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$content  = '<p class="privacy-policy-tutorial">' . esc_html__( 'GRS Pop does not collect personal data and does not load resources from third parties.', 'grs-pop' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Our popups store some technical information in your browser (localStorage and sessionStorage) to avoid showing you the same message too often: the date of the first visit, the number of pages viewed, the time of the last activity on the site and, for each popup, when it was shown, closed or used. This information is not sent to us and does not identify you. You can delete it by clearing your browser data.', 'grs-pop' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If some popups are shown only to those who have (or have not) already filled in one of our forms, the browser also remembers which forms were sent and when, without the data entered.', 'grs-pop' ) . '</p>';
		$content .= '<p>' . esc_html__( 'While the site is in "coming soon" or maintenance mode, whoever opens the preview link we send receives a technical cookie (grs_pop_access) that lets them see the site for a few days. It contains no personal data.', 'grs-pop' ) . '</p>';
		$content .= '<p>' . esc_html__( 'To measure how our popups perform we count, in aggregate form per day and per device type, how many times they are shown, closed or used. We do not store your IP address or other identifiers.', 'grs-pop' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If a popup contains a form, the data you enter is processed as described in the section about contact forms.', 'grs-pop' ) . '</p>';
		wp_add_privacy_policy_content( 'GRS Pop', wp_kses_post( $content ) );
	}
}
