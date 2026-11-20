<?php
/**
 * Tabella dei popup.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop\Admin;

use GRSDigital\Pop\Frontend;
use GRSDigital\Pop\Post_Type;
use GRSDigital\Pop\Stats;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Elenco con formato, trigger, risultati degli ultimi 30 giorni e stato.
 */
final class Popups_Table extends \WP_List_Table {

	/**
	 * Totali delle statistiche (30 giorni).
	 *
	 * @var array<int, array<string, int>>
	 */
	private $totals = array();

	/**
	 * Costruttore.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'popup',
				'plural'   => 'popups',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Ricerca corrente.
	 *
	 * @return string
	 */
	public function search_term() {
		return isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ricerca.
	}

	/**
	 * Colonne.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox">',
			'title'    => __( 'Name', 'grs-pop' ),
			'format'   => __( 'Format', 'grs-pop' ),
			'triggers' => __( 'When', 'grs-pop' ),
			'results'  => __( 'Last 30 days', 'grs-pop' ),
			'status'   => __( 'Status', 'grs-pop' ),
		);
	}

	/**
	 * Colonne ordinabili.
	 *
	 * @return array<string, array<int, string|bool>>
	 */
	protected function get_sortable_columns() {
		return array(
			'title' => array( 'title', false ),
		);
	}

	/**
	 * Azioni di gruppo.
	 *
	 * @return array<string, string>
	 */
	protected function get_bulk_actions() {
		return array(
			'activate'   => __( 'Activate', 'grs-pop' ),
			'deactivate' => __( 'Deactivate', 'grs-pop' ),
			'delete'     => __( 'Delete permanently', 'grs-pop' ),
		);
	}

	/**
	 * Carica le righe.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 30;
		$paged    = $this->get_pagenum();
		$orderby  = isset( $_GET['orderby'] ) && 'title' === $_GET['orderby'] ? 'title' : 'modified'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ordinamento.
		$order    = isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ordinamento.

		$query = new \WP_Query(
			array(
				'post_type'      => Post_Type::NAME,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => $per_page,
				'paged'          => $paged,
				'orderby'        => $orderby,
				'order'          => $order,
				's'              => $this->search_term(),
			)
		);

		$this->items           = $query->posts;
		$this->totals          = Stats::totals( 30 );
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'title' );
		$this->set_pagination_args(
			array(
				'total_items' => (int) $query->found_posts,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Messaggio senza risultati.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No popups found.', 'grs-pop' );
	}

	/**
	 * Casella di selezione.
	 *
	 * @param \WP_Post $item Popup.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return '<input type="checkbox" name="popup[]" value="' . esc_attr( (string) $item->ID ) . '">';
	}

	/**
	 * URL di un'azione di riga.
	 *
	 * @param int    $id     ID.
	 * @param string $action Azione.
	 * @return string
	 */
	private function row_url( $id, $action ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'grs_pop_row',
					'do'     => $action,
					'id'     => (int) $id,
				),
				admin_url( 'admin-post.php' )
			),
			'grs_pop_row_' . (int) $id
		);
	}

	/**
	 * Nome e azioni.
	 *
	 * @param \WP_Post $item Popup.
	 * @return string
	 */
	protected function column_title( $item ) {
		$edit    = Admin::url( 'edit', array( 'id' => $item->ID ) );
		$active  = 'publish' === $item->post_status;
		$actions = array(
			'edit'      => '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'grs-pop' ) . '</a>',
			'preview'   => '<a href="' . esc_url( Frontend::preview_url( $item->ID ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Preview on site', 'grs-pop' ) . '</a>',
			'toggle'    => '<a href="' . esc_url( $this->row_url( $item->ID, 'toggle' ) ) . '">' . ( $active ? esc_html__( 'Deactivate', 'grs-pop' ) : esc_html__( 'Activate', 'grs-pop' ) ) . '</a>',
			'duplicate' => '<a href="' . esc_url( $this->row_url( $item->ID, 'duplicate' ) ) . '">' . esc_html__( 'Duplicate', 'grs-pop' ) . '</a>',
			'delete'    => '<a class="grs-pop-delete" href="' . esc_url( $this->row_url( $item->ID, 'delete' ) ) . '" onclick="return window.confirm(this.dataset.confirm);" data-confirm="' . esc_attr__( 'Delete this popup and its statistics permanently?', 'grs-pop' ) . '">' . esc_html__( 'Delete', 'grs-pop' ) . '</a>',
		);
		$title   = '' !== $item->post_title ? $item->post_title : __( '(no name)', 'grs-pop' );
		return '<a class="row-title" href="' . esc_url( $edit ) . '">' . esc_html( $title ) . '</a> <span class="grs-pop-id">ID ' . esc_html( (string) $item->ID ) . '</span>' . $this->row_actions( $actions );
	}

	/**
	 * Formato per dispositivo.
	 *
	 * @param \WP_Post $item Popup.
	 * @return string
	 */
	protected function column_format( $item ) {
		$config = Post_Type::get_config( $item->ID );
		if ( 'inline' === $config['placement'] ) {
			return '<span class="grs-pop-chip">' . esc_html__( 'Inline', 'grs-pop' ) . '</span>';
		}
		$labels  = self::format_labels();
		$devices = array(
			'desktop' => __( 'Desktop', 'grs-pop' ),
			'tablet'  => __( 'Tablet', 'grs-pop' ),
			'mobile'  => __( 'Mobile', 'grs-pop' ),
		);
		$out     = array();
		foreach ( $devices as $device => $device_label ) {
			$dev   = $config['display'][ $device ];
			$value = $dev['enabled'] ? $labels[ $dev['format'] ] : __( 'off', 'grs-pop' );
			$out[] = '<span class="grs-pop-chip' . ( $dev['enabled'] ? '' : ' grs-pop-chip--off' ) . '" title="' . esc_attr( $device_label ) . '">' . esc_html( $device_label . ': ' . $value ) . '</span>';
		}
		return implode( ' ', $out );
	}

	/**
	 * Etichette dei formati.
	 *
	 * @return array<string, string>
	 */
	public static function format_labels() {
		return array(
			'modal'      => __( 'Popup', 'grs-pop' ),
			'slidein'    => __( 'Slide-in', 'grs-pop' ),
			'bar'        => __( 'Bar', 'grs-pop' ),
			'fullscreen' => __( 'Full screen', 'grs-pop' ),
			'tab'        => __( 'Floating tab', 'grs-pop' ),
		);
	}

	/**
	 * Riassunto dei trigger.
	 *
	 * @param \WP_Post $item Popup.
	 * @return string
	 */
	protected function column_triggers( $item ) {
		$config = Post_Type::get_config( $item->ID );
		if ( 'inline' === $config['placement'] ) {
			$positions = array(
				'manual'          => __( 'Shortcode or block', 'grs-pop' ),
				'before_content'  => __( 'Before the content', 'grs-pop' ),
				'after_content'   => __( 'After the content', 'grs-pop' ),
				/* translators: %d: paragraph number. */
				'after_paragraph' => sprintf( __( 'After paragraph %d', 'grs-pop' ), $config['inline']['paragraph'] ),
			);
			return esc_html( $positions[ $config['inline']['position'] ] );
		}
		$t   = $config['triggers'];
		$out = array();
		if ( $t['load']['on'] ) {
			/* translators: %d: seconds. */
			$out[] = 0 === (int) $t['load']['delay'] ? __( 'On page load', 'grs-pop' ) : sprintf( __( 'After %d s', 'grs-pop' ), $t['load']['delay'] );
		}
		if ( $t['scroll']['on'] ) {
			/* translators: %d: scroll percentage. */
			$out[] = sprintf( __( 'Scroll %d%%', 'grs-pop' ), $t['scroll']['percent'] );
		}
		if ( $t['exit']['on'] ) {
			$out[] = __( 'Exit intent', 'grs-pop' );
		}
		if ( $t['inactivity']['on'] ) {
			/* translators: %d: seconds. */
			$out[] = sprintf( __( 'Inactive %d s', 'grs-pop' ), $t['inactivity']['seconds'] );
		}
		if ( $t['element']['on'] ) {
			$out[] = __( 'Element visible', 'grs-pop' );
		}
		if ( empty( $out ) ) {
			$out[] = __( 'Only on click', 'grs-pop' );
		}
		return esc_html( implode( ', ', $out ) );
	}

	/**
	 * Risultati.
	 *
	 * @param \WP_Post $item Popup.
	 * @return string
	 */
	protected function column_results( $item ) {
		$t = isset( $this->totals[ $item->ID ] ) ? $this->totals[ $item->ID ] : array(
			'views'       => 0,
			'conversions' => 0,
		);
		if ( 0 === $t['views'] ) {
			return '<span class="description">' . esc_html__( 'No views yet', 'grs-pop' ) . '</span>';
		}
		$rate = $t['views'] > 0 ? round( $t['conversions'] / $t['views'] * 100, 1 ) : 0;
		$url  = Admin::url( 'stats', array( 'popup' => $item->ID ) );
		return '<a href="' . esc_url( $url ) . '" class="grs-pop-results">' . sprintf(
			/* translators: 1: views, 2: conversions, 3: conversion rate. */
			esc_html__( '%1$s views · %2$s conversions · %3$s%%', 'grs-pop' ),
			esc_html( number_format_i18n( $t['views'] ) ),
			esc_html( number_format_i18n( $t['conversions'] ) ),
			esc_html( number_format_i18n( $rate, 1 ) )
		) . '</a>';
	}

	/**
	 * Stato.
	 *
	 * @param \WP_Post $item Popup.
	 * @return string
	 */
	protected function column_status( $item ) {
		if ( 'publish' === $item->post_status ) {
			return '<span class="grs-ui-badge grs-ui-badge--ok">' . esc_html__( 'Active', 'grs-pop' ) . '</span>';
		}
		return '<span class="grs-ui-badge">' . esc_html__( 'Inactive', 'grs-pop' ) . '</span>';
	}
}
