<?php
/**
 * Scheda Statistiche.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop\Admin;

use GRSDigital\Pop\Post_Type;
use GRSDigital\Pop\Settings;
use GRSDigital\Pop\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Totali per popup e dettaglio per giorno e dispositivo.
 */
final class Stats_Page {

	/**
	 * Periodi disponibili (giorni).
	 */
	const PERIODS = array( 7, 30, 90, 365 );

	/**
	 * Rendering.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'grs_pop_view_stats' ) ) {
			echo '<p>' . esc_html__( 'You are not allowed to view statistics.', 'grs-pop' ) . '</p>';
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtri di sola lettura.
		$days  = isset( $_GET['days'] ) ? absint( wp_unslash( $_GET['days'] ) ) : 30;
		$popup = isset( $_GET['popup'] ) ? absint( wp_unslash( $_GET['popup'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $days, self::PERIODS, true ) ) {
			$days = 30;
		}

		$settings = Settings::get();
		if ( empty( $settings['stats_enabled'] ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Statistics are turned off in the settings: no new data is being collected.', 'grs-pop' ) . '</p></div>';
		}

		self::period_nav( $days, $popup );

		if ( $popup && Post_Type::NAME === get_post_type( $popup ) ) {
			self::render_detail( $popup, $days );
			return;
		}
		self::render_totals( $days );
	}

	/**
	 * Scelta del periodo.
	 *
	 * @param int $days  Giorni.
	 * @param int $popup Popup selezionato.
	 * @return void
	 */
	private static function period_nav( $days, $popup ) {
		echo '<div class="grs-pop-periods" role="group" aria-label="' . esc_attr__( 'Period', 'grs-pop' ) . '">';
		foreach ( self::PERIODS as $period ) {
			$args = array( 'days' => $period );
			if ( $popup ) {
				$args['popup'] = $popup;
			}
			printf(
				'<a class="button%1$s" href="%2$s"%3$s>%4$s</a> ',
				$period === $days ? ' button-primary' : '',
				esc_url( Admin::url( 'stats', $args ) ),
				$period === $days ? ' aria-current="true"' : '',
				/* translators: %d: number of days. */
				esc_html( sprintf( _n( 'Last %d day', 'Last %d days', $period, 'grs-pop' ), $period ) )
			);
		}
		echo '</div>';
	}

	/**
	 * Tasso di conversione formattato.
	 *
	 * @param int $conversions Conversioni.
	 * @param int $views       Visualizzazioni.
	 * @return string
	 */
	private static function rate( $conversions, $views ) {
		if ( $views <= 0 ) {
			return '–';
		}
		return number_format_i18n( $conversions / $views * 100, 1 ) . '%';
	}

	/**
	 * Totali per popup.
	 *
	 * @param int $days Giorni.
	 * @return void
	 */
	private static function render_totals( $days ) {
		$totals = Stats::totals( $days );
		$posts  = get_posts(
			array(
				'post_type'        => Post_Type::NAME,
				'post_status'      => array( 'publish', 'draft' ),
				'numberposts'      => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- elenco dei popup del sito (pochi), solo bacheca o rigenerazione dell'indice.
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);

		echo '<div class="grs-ui-card"><h2>' . esc_html__( 'Results by popup', 'grs-pop' ) . '</h2>';
		if ( empty( $posts ) ) {
			echo '<p>' . esc_html__( 'No popups yet.', 'grs-pop' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped grs-pop-stats"><thead><tr>';
		echo '<th scope="col">' . esc_html__( 'Popup', 'grs-pop' ) . '</th>';
		echo '<th scope="col" class="num">' . esc_html__( 'Views', 'grs-pop' ) . '</th>';
		echo '<th scope="col" class="num">' . esc_html__( 'Conversions', 'grs-pop' ) . '</th>';
		echo '<th scope="col" class="num">' . esc_html__( 'Conversion rate', 'grs-pop' ) . '</th>';
		echo '<th scope="col" class="num">' . esc_html__( 'Button clicks', 'grs-pop' ) . '</th>';
		echo '<th scope="col" class="num">' . esc_html__( 'Closed', 'grs-pop' ) . '</th>';
		echo '</tr></thead><tbody>';
		$sum = array(
			'views'       => 0,
			'conversions' => 0,
			'clicks'      => 0,
			'closes'      => 0,
		);
		foreach ( $posts as $post ) {
			$t = isset( $totals[ $post->ID ] ) ? $totals[ $post->ID ] : array(
				'views'       => 0,
				'conversions' => 0,
				'clicks'      => 0,
				'closes'      => 0,
			);
			foreach ( $sum as $key => $unused ) {
				$sum[ $key ] += $t[ $key ];
			}
			$url = Admin::url(
				'stats',
				array(
					'popup' => $post->ID,
					'days'  => $days,
				)
			);
			echo '<tr><td><a class="row-title" href="' . esc_url( $url ) . '">' . esc_html( '' !== $post->post_title ? $post->post_title : '#' . $post->ID ) . '</a>';
			if ( 'publish' !== $post->post_status ) {
				echo ' <span class="grs-ui-badge">' . esc_html__( 'Inactive', 'grs-pop' ) . '</span>';
			}
			echo '</td>';
			echo '<td class="num">' . esc_html( number_format_i18n( $t['views'] ) ) . '</td>';
			echo '<td class="num">' . esc_html( number_format_i18n( $t['conversions'] ) ) . '</td>';
			echo '<td class="num"><strong>' . esc_html( self::rate( $t['conversions'], $t['views'] ) ) . '</strong></td>';
			echo '<td class="num">' . esc_html( number_format_i18n( $t['clicks'] ) ) . '</td>';
			echo '<td class="num">' . esc_html( number_format_i18n( $t['closes'] ) ) . '</td></tr>';
		}
		echo '</tbody><tfoot><tr><th scope="row">' . esc_html__( 'Total', 'grs-pop' ) . '</th>';
		echo '<td class="num">' . esc_html( number_format_i18n( $sum['views'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format_i18n( $sum['conversions'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( self::rate( $sum['conversions'], $sum['views'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format_i18n( $sum['clicks'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format_i18n( $sum['closes'] ) ) . '</td></tr></tfoot></table>';
		echo '<p class="description">' . esc_html__( 'A conversion is a form sent from the popup, a click on a button marked as a conversion or a copied coupon code. Visits by users who can manage popups are not counted.', 'grs-pop' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Dettaglio di un popup.
	 *
	 * @param int $popup Popup.
	 * @param int $days  Giorni.
	 * @return void
	 */
	private static function render_detail( $popup, $days ) {
		$detail = Stats::detail( $popup, $days );
		$title  = get_the_title( $popup );

		echo '<p><a href="' . esc_url( Admin::url( 'stats', array( 'days' => $days ) ) ) . '">&larr; ' . esc_html__( 'All popups', 'grs-pop' ) . '</a></p>';

		$total = array(
			'views'       => 0,
			'conversions' => 0,
			'clicks'      => 0,
			'closes'      => 0,
		);
		foreach ( $detail['days'] as $row ) {
			foreach ( $total as $key => $unused ) {
				$total[ $key ] += $row[ $key ];
			}
		}

		echo '<div class="grs-ui-card"><h2>' . esc_html( $title ) . '</h2>';
		echo '<div class="grs-pop-kpis">';
		self::kpi( __( 'Views', 'grs-pop' ), number_format_i18n( $total['views'] ) );
		self::kpi( __( 'Conversions', 'grs-pop' ), number_format_i18n( $total['conversions'] ) );
		self::kpi( __( 'Conversion rate', 'grs-pop' ), self::rate( $total['conversions'], $total['views'] ) );
		self::kpi( __( 'Button clicks', 'grs-pop' ), number_format_i18n( $total['clicks'] ) );
		self::kpi( __( 'Closed', 'grs-pop' ), number_format_i18n( $total['closes'] ) );
		echo '</div>';

		// Barre giornaliere (visualizzazioni e conversioni).
		$max = 1;
		foreach ( $detail['days'] as $row ) {
			$max = max( $max, $row['views'] );
		}
		echo '<h3>' . esc_html__( 'Day by day', 'grs-pop' ) . '</h3>';
		if ( empty( $detail['days'] ) ) {
			echo '<p class="description">' . esc_html__( 'No data in this period.', 'grs-pop' ) . '</p>';
		} else {
			echo '<table class="widefat striped grs-pop-days"><thead><tr><th scope="col">' . esc_html__( 'Day', 'grs-pop' ) . '</th><th scope="col">' . esc_html__( 'Views and conversions', 'grs-pop' ) . '</th><th scope="col" class="num">' . esc_html__( 'Views', 'grs-pop' ) . '</th><th scope="col" class="num">' . esc_html__( 'Conversions', 'grs-pop' ) . '</th><th scope="col" class="num">' . esc_html__( 'Rate', 'grs-pop' ) . '</th></tr></thead><tbody>';
			foreach ( array_reverse( $detail['days'], true ) as $day => $row ) {
				$vw = round( $row['views'] / $max * 100, 1 );
				$cw = round( $row['conversions'] / $max * 100, 1 );
				echo '<tr><td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $day . ' 12:00:00' ) ) ) . '</td>';
				echo '<td><span class="grs-pop-bar" aria-hidden="true"><span class="grs-pop-bar__v" style="width:' . esc_attr( (string) $vw ) . '%"></span><span class="grs-pop-bar__c" style="width:' . esc_attr( (string) $cw ) . '%"></span></span></td>';
				echo '<td class="num">' . esc_html( number_format_i18n( $row['views'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format_i18n( $row['conversions'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( self::rate( $row['conversions'], $row['views'] ) ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h3>' . esc_html__( 'By device', 'grs-pop' ) . '</h3>';
		echo '<table class="widefat striped grs-pop-devices"><thead><tr><th scope="col">' . esc_html__( 'Device', 'grs-pop' ) . '</th><th scope="col" class="num">' . esc_html__( 'Views', 'grs-pop' ) . '</th><th scope="col" class="num">' . esc_html__( 'Conversions', 'grs-pop' ) . '</th><th scope="col" class="num">' . esc_html__( 'Rate', 'grs-pop' ) . '</th><th scope="col" class="num">' . esc_html__( 'Closed', 'grs-pop' ) . '</th></tr></thead><tbody>';
		$labels = array(
			'desktop' => __( 'Desktop', 'grs-pop' ),
			'tablet'  => __( 'Tablet', 'grs-pop' ),
			'mobile'  => __( 'Mobile', 'grs-pop' ),
		);
		foreach ( $labels as $device => $label ) {
			$row = isset( $detail['devices'][ $device ] ) ? $detail['devices'][ $device ] : array(
				'views'       => 0,
				'conversions' => 0,
				'closes'      => 0,
			);
			echo '<tr><td>' . esc_html( $label ) . '</td><td class="num">' . esc_html( number_format_i18n( $row['views'] ) ) . '</td><td class="num">' . esc_html( number_format_i18n( $row['conversions'] ) ) . '</td><td class="num">' . esc_html( self::rate( $row['conversions'], $row['views'] ) ) . '</td><td class="num">' . esc_html( number_format_i18n( $row['closes'] ) ) . '</td></tr>';
		}
		echo '</tbody></table>';

		if ( current_user_can( 'grs_pop_manage_popups' ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="grs-pop-reset">';
			echo '<input type="hidden" name="action" value="grs_pop_reset_stats"><input type="hidden" name="id" value="' . esc_attr( (string) $popup ) . '">';
			wp_nonce_field( 'grs_pop_reset_stats_' . $popup );
			echo '<button type="submit" class="button grs-ui-button--danger" onclick="return window.confirm(this.dataset.confirm);" data-confirm="' . esc_attr__( 'Reset all statistics of this popup?', 'grs-pop' ) . '">' . esc_html__( 'Reset statistics', 'grs-pop' ) . '</button>';
			echo '</form>';
		}
		echo '</div>';
	}

	/**
	 * Riquadro numerico.
	 *
	 * @param string $label Etichetta.
	 * @param string $value Valore.
	 * @return void
	 */
	private static function kpi( $label, $value ) {
		echo '<div class="grs-pop-kpi"><span class="grs-pop-kpi__value">' . esc_html( $value ) . '</span><span class="grs-pop-kpi__label">' . esc_html( $label ) . '</span></div>';
	}
}
