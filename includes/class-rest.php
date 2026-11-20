<?php
/**
 * API REST del plugin.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Namespace `grs-pop/v1`: builder (solo chi gestisce i popup), blocco e statistiche.
 */
final class Rest {

	/**
	 * Namespace.
	 */
	const NS = 'grs-pop/v1';

	/**
	 * Eventi massimi per richiesta di statistiche.
	 */
	const MAX_EVENTS = 40;

	/**
	 * Richieste di statistiche ammesse per indirizzo IP in 10 minuti.
	 */
	const RATE_LIMIT = 120;

	/**
	 * Registra le rotte.
	 *
	 * @return void
	 */
	public static function register_routes() {
		$manage = array( __CLASS__, 'can_manage' );

		register_rest_route(
			self::NS,
			'/popups',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'list_popups' ),
					'permission_callback' => $manage,
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'save_popup' ),
					'permission_callback' => $manage,
					'args'                => self::save_args(),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/popups/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_popup' ),
					'permission_callback' => $manage,
					'args'                => array( 'id' => self::id_arg() ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'save_popup' ),
					'permission_callback' => $manage,
					'args'                => array( 'id' => self::id_arg() ) + self::save_args(),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/lookup',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'lookup' ),
				'permission_callback' => $manage,
				'args'                => array(
					'kind'    => array(
						'type'              => 'string',
						'enum'              => array( 'posts', 'terms' ),
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'subtype' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
					'search'  => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/inline',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'inline_popups' ),
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			self::NS,
			'/stats',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'record_stats' ),
				// Endpoint pubblico: riceve solo contatori anonimi di popup attivi (validati sotto, con limite per IP).
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Permesso di gestione dei popup.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( 'grs_pop_manage_popups' );
	}

	/**
	 * Argomento ID.
	 *
	 * @return array<string, mixed>
	 */
	private static function id_arg() {
		return array(
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
		);
	}

	/**
	 * Argomenti di salvataggio.
	 *
	 * @return array<string, mixed>
	 */
	private static function save_args() {
		return array(
			'title'  => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status' => array(
				'type'    => 'string',
				'enum'    => array( 'publish', 'draft' ),
				'default' => 'draft',
			),
			'config' => array(
				'type'              => 'object',
				'required'          => true,
				'validate_callback' => static function ( $value ) {
					return is_array( $value );
				},
			),
		);
	}

	/**
	 * Elenco dei popup (per il builder: scegliere un popup da aprire con un pulsante).
	 *
	 * @return \WP_REST_Response
	 */
	public static function list_popups() {
		$posts = get_posts(
			array(
				'post_type'        => Post_Type::NAME,
				'post_status'      => array( 'publish', 'draft' ),
				'numberposts'      => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- elenco dei popup del sito (pochi), solo bacheca o rigenerazione dell'indice.
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);
		$out   = array();
		foreach ( $posts as $post ) {
			$out[] = array(
				'id'     => (int) $post->ID,
				'title'  => (string) $post->post_title,
				'status' => (string) $post->post_status,
			);
		}
		return rest_ensure_response( $out );
	}

	/**
	 * Un popup.
	 *
	 * @param \WP_REST_Request $request Richiesta.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_popup( \WP_REST_Request $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post || Post_Type::NAME !== $post->post_type ) {
			return new \WP_Error( 'grs_pop_not_found', __( 'Popup not found.', 'grs-pop' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( self::popup_response( $post ) );
	}

	/**
	 * Risposta con i dati di un popup.
	 *
	 * @param \WP_Post $post Popup.
	 * @return array<string, mixed>
	 */
	private static function popup_response( \WP_Post $post ) {
		return array(
			'id'          => (int) $post->ID,
			'title'       => (string) $post->post_title,
			'status'      => 'publish' === $post->post_status ? 'publish' : 'draft',
			'config'      => Post_Type::get_config( $post->ID ),
			'preview_url' => Frontend::preview_url( $post->ID ),
		);
	}

	/**
	 * Crea o aggiorna un popup.
	 *
	 * @param \WP_REST_Request $request Richiesta.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function save_popup( \WP_REST_Request $request ) {
		$id     = isset( $request['id'] ) ? (int) $request['id'] : 0;
		$title  = trim( (string) $request['title'] );
		$status = 'publish' === $request['status'] ? 'publish' : 'draft';
		$config = (array) $request['config'];

		if ( '' === $title ) {
			$title = __( 'Untitled popup', 'grs-pop' );
		}
		if ( $id ) {
			$post = get_post( $id );
			if ( ! $post || Post_Type::NAME !== $post->post_type ) {
				return new \WP_Error( 'grs_pop_not_found', __( 'Popup not found.', 'grs-pop' ), array( 'status' => 404 ) );
			}
		}

		// Shortcode liberi solo per chi può già inserire HTML non filtrato o shortcode nei contenuti.
		if ( ! current_user_can( 'edit_posts' ) && isset( $config['sections'] ) && is_array( $config['sections'] ) ) {
			foreach ( $config['sections'] as $n => $section ) {
				if ( is_array( $section ) && isset( $section['type'] ) && 'shortcode' === $section['type'] ) {
					unset( $config['sections'][ $n ] );
				}
			}
		}

		$postarr = array(
			'post_type'   => Post_Type::NAME,
			'post_title'  => $title,
			'post_status' => $status,
		);
		if ( $id ) {
			$postarr['ID'] = $id;
			$result        = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$result = wp_insert_post( wp_slash( $postarr ), true );
		}
		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'grs_pop_save_failed', $result->get_error_message(), array( 'status' => 500 ) );
		}
		$id    = (int) $result;
		$clean = Post_Type::save_config( $id, $config );
		Index::rebuild();
		I18n_Bridge::register_popup( $id, $clean );

		return rest_ensure_response( self::popup_response( get_post( $id ) ) );
	}

	/**
	 * Ricerca di contenuti o termini per le regole.
	 *
	 * @param \WP_REST_Request $request Richiesta.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function lookup( \WP_REST_Request $request ) {
		$kind    = (string) $request['kind'];
		$subtype = (string) $request['subtype'];
		$search  = (string) $request['search'];
		$out     = array();

		if ( 'terms' === $kind ) {
			if ( ! taxonomy_exists( $subtype ) ) {
				return new \WP_Error( 'grs_pop_bad_taxonomy', __( 'Unknown taxonomy.', 'grs-pop' ), array( 'status' => 400 ) );
			}
			$terms = get_terms(
				array(
					'taxonomy'   => $subtype,
					'hide_empty' => false,
					'number'     => 20,
					'search'     => $search,
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$out[] = array(
						'id'    => (int) $term->term_id,
						'label' => (string) $term->name,
					);
				}
			}
			return rest_ensure_response( $out );
		}

		$types = get_post_types( array( 'public' => true ) );
		unset( $types['attachment'] );
		$post_type = ( '' !== $subtype && isset( $types[ $subtype ] ) ) ? $subtype : array_values( $types );
		$posts     = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => array( 'publish', 'private', 'future', 'draft' ),
				's'                => $search,
				'numberposts'      => 20,
				'orderby'          => '' === $search ? 'date' : 'relevance',
				'suppress_filters' => false,
			)
		);
		foreach ( $posts as $post ) {
			$type_obj = get_post_type_object( $post->post_type );
			$out[]    = array(
				'id'    => (int) $post->ID,
				'label' => ( '' !== $post->post_title ? $post->post_title : '#' . $post->ID ) . ( $type_obj ? ' (' . $type_obj->labels->singular_name . ')' : '' ),
			);
		}
		return rest_ensure_response( $out );
	}

	/**
	 * Popup in linea attivi (per il blocco).
	 *
	 * @return \WP_REST_Response
	 */
	public static function inline_popups() {
		$out = array();
		$ids = array();
		foreach ( Index::popups() as $id => $entry ) {
			if ( 'inline' === $entry['pl'] ) {
				$ids[] = (int) $id;
			}
		}
		if ( $ids ) {
			$posts = get_posts(
				array(
					'post_type'        => Post_Type::NAME,
					'post__in'         => $ids,
					'numberposts'      => count( $ids ),
					'suppress_filters' => true,
				)
			);
			foreach ( $posts as $post ) {
				$out[] = array(
					'id'    => (int) $post->ID,
					'title' => (string) $post->post_title,
				);
			}
		}
		return rest_ensure_response( $out );
	}

	/**
	 * Registra statistiche inviate dal browser (sendBeacon).
	 *
	 * Formato: `{"e":[{"id":12,"d":2,"t":"v"}]}` (t: v=visualizzazione, x=chiusura, c=conversione, k=clic).
	 *
	 * @param \WP_REST_Request $request Richiesta.
	 * @return \WP_REST_Response
	 */
	public static function record_stats( \WP_REST_Request $request ) {
		$settings = Settings::get();
		$response = new \WP_REST_Response( null, 204 );
		if ( empty( $settings['stats_enabled'] ) || self::is_bot() ) {
			return $response;
		}

		$body = json_decode( (string) $request->get_body(), true );
		if ( ! is_array( $body ) || empty( $body['e'] ) || ! is_array( $body['e'] ) ) {
			return $response;
		}
		if ( ! self::within_rate_limit() ) {
			return new \WP_REST_Response( null, 429 );
		}

		$active = Index::popups();
		$events = array();
		foreach ( array_slice( $body['e'], 0, self::MAX_EVENTS ) as $event ) {
			if ( ! is_array( $event ) || ! isset( $event['id'], $event['t'] ) ) {
				continue;
			}
			$id   = absint( $event['id'] );
			$type = is_string( $event['t'] ) ? $event['t'] : '';
			$dev  = isset( $event['d'] ) ? absint( $event['d'] ) : 0;
			if ( ! isset( $active[ $id ] ) || ! isset( Stats::TYPES[ $type ] ) || $dev > 2 ) {
				continue;
			}
			$events[] = array(
				'id' => $id,
				'd'  => $dev,
				't'  => $type,
			);
		}
		if ( $events ) {
			Stats::record( $events );
		}
		return $response;
	}

	/**
	 * Richiesta da un robot noto?
	 *
	 * @return bool
	 */
	private static function is_bot() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|headless|lighthouse|pingdom|monitor|preview|curl|wget|python|httpclient/i', $ua );
	}

	/**
	 * Limite di richieste per IP (l'IP non viene salvato: solo un hash nella chiave del transient).
	 *
	 * @return bool
	 */
	private static function within_rate_limit() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '' === $ip ) {
			return true;
		}
		$key   = 'grs_pop_rl_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 20 );
		$count = (int) get_transient( $key );
		if ( $count >= (int) apply_filters( 'grs_pop_stats_rate_limit', self::RATE_LIMIT ) ) {
			return false;
		}
		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
		return true;
	}
}
