<?php
/**
 * Modalità "Coming soon" e "Manutenzione".
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Pagina servita al posto del sito, prima del tema.
 *
 * - Coming soon: risposta 200 con noindex (sito non ancora lanciato).
 * - Manutenzione: risposta 503 con Retry-After (sito già online: i motori di ricerca ripassano).
 *
 * Chi vede il sito normale: utenti collegati con i ruoli scelti (gli amministratori sempre) e chi
 * apre il link segreto di anteprima (cookie tecnico per qualche giorno). Restano raggiungibili
 * bacheca, login, REST, AJAX, cron, robots.txt e favicon. La pagina non carica il tema né gli
 * script degli altri plugin: solo il proprio CSS e gli asset del form di GRS Forms, se scelto.
 * Opzione in autoload: con la modalità spenta il costo nel front-end è una lettura in memoria.
 */
final class Maintenance {

	/**
	 * Nome dell'opzione (autoload).
	 */
	const OPTION = 'grs_pop_maintenance';

	/**
	 * Cookie del link segreto di anteprima.
	 */
	const COOKIE = 'grs_pop_access';

	/**
	 * Parametro del link segreto.
	 */
	const ACCESS_ARG = 'grs_pop_access';

	/**
	 * Parametro dell'anteprima dalla bacheca.
	 */
	const PREVIEW_ARG = 'grs_pop_ms_preview';

	/**
	 * Reti social accettate (etichette fisse: nomi propri).
	 */
	const SOCIALS = array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'linkedin'  => 'LinkedIn',
		'youtube'   => 'YouTube',
		'tiktok'    => 'TikTok',
		'x'         => 'X',
	);

	/**
	 * Icone generiche (tratti SVG 24x24).
	 */
	const ICONS = array(
		'mail'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'chat'  => '<path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4A8 8 0 1 1 20 11.5z"/>',
		'pin'   => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'link'  => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
	);

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
		$social = array();
		foreach ( array_keys( self::SOCIALS ) as $key ) {
			$social[ $key ] = '';
		}
		return array(
			'mode'            => 'off',
			'roles'           => array( 'administrator', 'editor' ),
			'key'             => '',
			'key_days'        => 7,
			'logo'            => 'site',
			'logo_id'         => 0,
			'logo_width'      => 180,
			'style'           => 'light',
			'heading'         => '',
			'text'            => '',
			'email'           => '',
			'phone'           => '',
			'whatsapp'        => '',
			'address'         => '',
			'social'          => $social,
			'countdown'       => 0,
			'launch'          => '',
			'countdown_title' => '',
			'auto_off'        => 0,
			'form_id'         => 0,
		);
	}

	/**
	 * Valori iniziali all'installazione (con la chiave del link segreto già generata).
	 *
	 * @return array<string, mixed>
	 */
	public static function initial() {
		$values        = self::defaults();
		$values['key'] = self::new_key();
		return $values;
	}

	/**
	 * Nuova chiave del link segreto.
	 *
	 * @return string
	 */
	public static function new_key() {
		return wp_generate_password( 20, false, false );
	}

	/**
	 * Registra l'opzione (su init, così la sanitizzazione vale in ogni contesto).
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			'grs_pop_maintenance_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'after_save' ), 10, 2 );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'after_update' ), 10, 2 );
	}

	/**
	 * Dopo l'aggiornamento: cache della richiesta, ponte traduzioni, cache di pagina.
	 *
	 * @param mixed $old_value Valore precedente.
	 * @param mixed $value     Valore nuovo.
	 * @return void
	 */
	public static function after_update( $old_value, $value ) {
		self::after_save( self::OPTION, $value );
	}

	/**
	 * Dopo il salvataggio.
	 *
	 * Si svuota sempre la cache di pagina: all'accensione le pagine già in cache verrebbero
	 * ancora servite ai visitatori al posto della pagina di cortesia.
	 *
	 * @param string $option Nome dell'opzione.
	 * @param mixed  $value  Valore salvato.
	 * @return void
	 */
	public static function after_save( $option, $value ) {
		self::$cache = null;
		if ( is_array( $value ) ) {
			self::register_strings( array_merge( self::defaults(), $value ) );
		}
		Index::purge_page_cache();
	}

	/**
	 * Impostazioni correnti, unite ai valori predefiniti.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		if ( null === self::$cache ) {
			$saved    = get_option( self::OPTION, array() );
			$saved    = is_array( $saved ) ? $saved : array();
			$defaults = self::defaults();
			$merged   = array_merge( $defaults, $saved );

			$merged['social'] = array_merge( $defaults['social'], isset( $saved['social'] ) && is_array( $saved['social'] ) ? $saved['social'] : array() );
			$merged['roles']  = is_array( $merged['roles'] ) ? $merged['roles'] : array();
			self::$cache      = $merged;
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
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$current  = self::get();
		$out      = $defaults;

		$out['mode'] = self::pick( $input, 'mode', array( 'off', 'coming_soon', 'maintenance' ), 'off' );

		$roles = array();
		foreach ( (array) ( isset( $input['roles'] ) ? $input['roles'] : array() ) as $role ) {
			$role = sanitize_key( (string) $role );
			if ( '' !== $role && get_role( $role ) && ! in_array( $role, $roles, true ) ) {
				$roles[] = $role;
			}
		}
		$out['roles'] = $roles;

		// La chiave non è nel modulo: si conserva, oppure arriva nuova dall'azione "Rigenera".
		$key = isset( $input['key'] ) ? (string) $input['key'] : '';
		if ( ! preg_match( '/^[A-Za-z0-9]{20}$/', $key ) ) {
			$key = preg_match( '/^[A-Za-z0-9]{20}$/', (string) $current['key'] ) ? (string) $current['key'] : self::new_key();
		}
		$out['key'] = $key;

		$out['key_days']   = self::int_between( $input, 'key_days', 1, 60, $defaults['key_days'] );
		$out['logo']       = self::pick( $input, 'logo', array( 'site', 'custom', 'none' ), 'site' );
		$out['logo_id']    = isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;
		$out['logo_width'] = self::int_between( $input, 'logo_width', 40, 480, $defaults['logo_width'] );
		$out['style']      = self::pick( $input, 'style', array( 'light', 'brand' ), 'light' );

		$out['heading'] = isset( $input['heading'] ) ? sanitize_text_field( wp_unslash( (string) $input['heading'] ) ) : '';
		$out['text']    = isset( $input['text'] ) ? wp_kses( wp_unslash( (string) $input['text'] ), self::allowed_html() ) : '';
		$out['heading'] = mb_substr( $out['heading'], 0, 160 );
		$out['text']    = mb_substr( trim( $out['text'] ), 0, 3000 );

		$email           = isset( $input['email'] ) ? sanitize_email( wp_unslash( (string) $input['email'] ) ) : '';
		$out['email']    = is_email( $email ) ? $email : '';
		$out['phone']    = isset( $input['phone'] ) ? mb_substr( preg_replace( '/[^0-9+()\s.\-]/', '', (string) $input['phone'] ), 0, 40 ) : '';
		$out['phone']    = trim( $out['phone'] );
		$whatsapp        = isset( $input['whatsapp'] ) ? preg_replace( '/[^0-9]/', '', (string) $input['whatsapp'] ) : '';
		$out['whatsapp'] = strlen( $whatsapp ) >= 6 && strlen( $whatsapp ) <= 20 ? $whatsapp : '';
		$out['address']  = isset( $input['address'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( (string) $input['address'] ) ), 0, 300 ) : '';

		foreach ( array_keys( self::SOCIALS ) as $network ) {
			$url = isset( $input['social'][ $network ] ) ? esc_url_raw( trim( (string) $input['social'][ $network ] ), array( 'https', 'http' ) ) : '';

			$out['social'][ $network ] = mb_substr( $url, 0, 300 );
		}

		$out['countdown']       = empty( $input['countdown'] ) ? 0 : 1;
		$out['launch']          = self::datetime( isset( $input['launch'] ) ? (string) $input['launch'] : '' );
		$out['countdown_title'] = isset( $input['countdown_title'] ) ? mb_substr( sanitize_text_field( wp_unslash( (string) $input['countdown_title'] ) ), 0, 120 ) : '';
		$out['auto_off']        = empty( $input['auto_off'] ) || '' === $out['launch'] ? 0 : 1;
		$out['form_id']         = isset( $input['form_id'] ) ? absint( $input['form_id'] ) : 0;

		self::$cache = null;
		return $out;
	}

	/**
	 * Tag ammessi nel testo.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_html() {
		return array(
			'p'      => array(),
			'br'     => array(),
			'strong' => array(),
			'em'     => array(),
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);
	}

	/**
	 * Valore da un elenco.
	 *
	 * @param array<string, mixed> $input    Valori.
	 * @param string               $key      Chiave.
	 * @param string[]             $allowed  Valori ammessi.
	 * @param string               $fallback Valore se non valido.
	 * @return string
	 */
	private static function pick( array $input, $key, array $allowed, $fallback ) {
		$value = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
		return in_array( $value, $allowed, true ) ? $value : $fallback;
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

	/**
	 * Data e ora locali nel formato del campo datetime-local (Y-m-d\TH:i), oppure vuoto.
	 *
	 * @param string $value Valore inviato.
	 * @return string
	 */
	private static function datetime( $value ) {
		$value = trim( $value );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/', $value, $m ) ) {
			return '';
		}
		if ( ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) || (int) $m[4] > 23 || (int) $m[5] > 59 ) {
			return '';
		}
		return sprintf( '%s-%s-%sT%s:%s', $m[1], $m[2], $m[3], $m[4], $m[5] );
	}

	/**
	 * Timestamp (UTC) della data di lancio, 0 se assente.
	 *
	 * @param array<string, mixed>|null $s Impostazioni.
	 * @return int
	 */
	public static function launch_timestamp( $s = null ) {
		$s = null === $s ? self::get() : $s;
		if ( '' === (string) $s['launch'] ) {
			return 0;
		}
		try {
			$date = new \DateTimeImmutable( str_replace( 'T', ' ', (string) $s['launch'] ), wp_timezone() );
		} catch ( \Exception $e ) {
			return 0;
		}
		return (int) $date->getTimestamp();
	}

	/**
	 * Modalità in vigore ('off', 'coming_soon', 'maintenance'), con lo spegnimento automatico
	 * alla data di lancio.
	 *
	 * @return string
	 */
	public static function mode() {
		$s = self::get();
		if ( 'off' === $s['mode'] ) {
			return 'off';
		}
		if ( ! empty( $s['auto_off'] ) ) {
			$launch = self::launch_timestamp( $s );
			if ( $launch && time() >= $launch ) {
				return 'off';
			}
		}
		return (string) $s['mode'];
	}

	/**
	 * La modalità è attiva?
	 *
	 * @return bool
	 */
	public static function is_active() {
		return 'off' !== self::mode();
	}

	// Avvio.

	/**
	 * Hook comuni (bacheca e sito): avviso nella barra di amministrazione.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 100 );
	}

	/**
	 * Hook del sito pubblico.
	 *
	 * @return void
	 */
	public static function init_frontend() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve' ), 1 );
	}

	/**
	 * Voce nella barra di amministrazione quando la modalità è attiva.
	 *
	 * @param \WP_Admin_Bar $bar Barra.
	 * @return void
	 */
	public static function admin_bar( $bar ) {
		if ( ! self::is_active() || ! current_user_can( 'manage_options' ) || ! is_object( $bar ) ) {
			return;
		}
		$label = 'maintenance' === self::mode() ? __( 'Maintenance mode active', 'grs-pop' ) : __( 'Coming soon mode active', 'grs-pop' );
		$bar->add_node(
			array(
				'id'     => 'grs-pop-maintenance',
				'parent' => 'top-secondary',
				'title'  => '<span style="display:inline-block;padding:0 8px;border-radius:3px;background:#b42318;color:#fff;line-height:22px;margin-top:5px;">' . esc_html( $label ) . '</span>',
				'href'   => admin_url( 'admin.php?page=grs-pop&tab=maintenance' ),
				'meta'   => array( 'title' => __( 'Visitors see the courtesy page. Click to change.', 'grs-pop' ) ),
			)
		);
	}

	// Accesso.

	/**
	 * Valore atteso del cookie per la chiave corrente (cambiando chiave i link vecchi smettono di valere).
	 *
	 * @param string $key Chiave.
	 * @return string
	 */
	private static function cookie_value( $key ) {
		return hash_hmac( 'sha256', 'grs_pop_access|' . $key, wp_salt( 'auth' ) );
	}

	/**
	 * Il visitatore ha aperto il link segreto (cookie valido)?
	 *
	 * @return bool
	 */
	private static function has_access_cookie() {
		$s = self::get();
		if ( '' === (string) $s['key'] || empty( $_COOKIE[ self::COOKIE ] ) ) {
			return false;
		}
		$cookie = sanitize_text_field( wp_unslash( (string) $_COOKIE[ self::COOKIE ] ) );
		return hash_equals( self::cookie_value( (string) $s['key'] ), $cookie );
	}

	/**
	 * L'utente collegato può vedere il sito normale?
	 *
	 * @return bool
	 */
	private static function user_can_bypass() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$user  = wp_get_current_user();
		$roles = (array) self::get()['roles'];
		return (bool) array_intersect( (array) $user->roles, $roles );
	}

	/**
	 * Link segreto di anteprima.
	 *
	 * @return string
	 */
	public static function access_url() {
		return add_query_arg( self::ACCESS_ARG, (string) self::get()['key'], home_url( '/' ) );
	}

	/**
	 * Indirizzo dell'anteprima della pagina dalla bacheca (anche con la modalità spenta).
	 *
	 * @return string
	 */
	public static function preview_url() {
		return add_query_arg(
			array(
				self::PREVIEW_ARG => 1,
				'_grspop'         => wp_create_nonce( 'grs_pop_ms_preview' ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Impedisce ai plugin di cache di salvare la risposta corrente.
	 *
	 * @return void
	 */
	private static function no_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- costante standard dei plugin di cache.
		}
		do_action( 'litespeed_control_set_nocache', 'grs-pop maintenance' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook pubblico di LiteSpeed Cache.
		add_filter( 'nocache_headers', array( __CLASS__, 'strict_nocache' ) );
		nocache_headers();
	}

	/**
	 * Intestazioni anti-cache complete anche su WP 6.0 (no-store e private per CDN e proxy).
	 *
	 * @param array<string, string> $headers Intestazioni.
	 * @return array<string, string>
	 */
	public static function strict_nocache( $headers ) {
		$headers                  = is_array( $headers ) ? $headers : array();
		$headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0, no-store, private';
		return $headers;
	}

	// Risposta.

	/**
	 * Decide se servire la pagina di cortesia.
	 *
	 * @return void
	 */
	public static function maybe_serve() {
		if ( is_robots() || ( function_exists( 'is_favicon' ) && is_favicon() ) || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Anteprima dalla bacheca (anche con la modalità spenta).
		if ( isset( $_GET[ self::PREVIEW_ARG ], $_GET['_grspop'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_grspop'] ) ), 'grs_pop_ms_preview' ) ) {
			$saved = (string) self::get()['mode'];
			self::render( 'off' === $saved ? 'coming_soon' : $saved, true );
			exit;
		}

		$mode = self::mode();
		if ( 'off' === $mode ) {
			return;
		}

		// Link segreto: imposta il cookie e torna allo stesso indirizzo senza il parametro.
		if ( isset( $_GET[ self::ACCESS_ARG ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- link pubblico con chiave segreta.
			$given = sanitize_text_field( wp_unslash( (string) $_GET[ self::ACCESS_ARG ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- link pubblico con chiave segreta.
			$key   = (string) self::get()['key'];
			if ( '' !== $key && hash_equals( $key, $given ) ) {
				$days = (int) self::get()['key_days'];
				setcookie( self::COOKIE, self::cookie_value( $key ), time() + $days * DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', (string) COOKIE_DOMAIN, is_ssl(), true );
				self::no_cache();
				wp_safe_redirect( remove_query_arg( self::ACCESS_ARG ) );
				exit;
			}
		}

		if ( self::user_can_bypass() ) {
			return;
		}
		if ( self::has_access_cookie() ) {
			// Visitatore anonimo con il link segreto: la pagina del sito vero non deve finire in cache.
			self::no_cache();
			return;
		}

		self::render( $mode, false );
		exit;
	}

	/**
	 * Testo traducibile (ponte GRS i18n).
	 *
	 * @param string $value Testo salvato.
	 * @param string $name  Nome del campo.
	 * @return string
	 */
	private static function t( $value, $name ) {
		$value = (string) $value;
		if ( '' === $value || ! class_exists( 'GRS_I18n' ) ) {
			return $value;
		}
		return (string) \GRS_I18n::translate( $value, I18n_Bridge::CONTEXT, 'maintenance_' . $name );
	}

	/**
	 * Testi della pagina per il ponte traduzioni.
	 *
	 * @param array<string, mixed> $s Impostazioni.
	 * @return array{0: array<string, string>, 1: string[]}
	 */
	public static function strings( array $s ) {
		$strings = array();
		foreach ( array( 'heading', 'text', 'countdown_title', 'address' ) as $field ) {
			if ( '' !== trim( (string) $s[ $field ] ) ) {
				$strings[ 'maintenance_' . $field ] = (string) $s[ $field ];
			}
		}
		return array( $strings, array( 'maintenance_text', 'maintenance_address' ) );
	}

	/**
	 * Registra i testi nel ponte GRS i18n.
	 *
	 * @param array<string, mixed>|null $s Impostazioni.
	 * @return void
	 */
	public static function register_strings( $s = null ) {
		if ( ! class_exists( 'GRS_I18n' ) ) {
			return;
		}
		$s                           = null === $s ? self::get() : $s;
		list( $strings, $multiline ) = self::strings( $s );
		\GRS_I18n::register_many( I18n_Bridge::CONTEXT, $strings, $multiline );
	}

	/**
	 * SVG di un'icona generica.
	 *
	 * @param string $name Nome.
	 * @return string
	 */
	private static function icon( $name ) {
		if ( ! isset( self::ICONS[ $name ] ) ) {
			return '';
		}
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . self::ICONS[ $name ] . '</svg>';
	}

	/**
	 * HTML del logo.
	 *
	 * @param array<string, mixed> $s Impostazioni.
	 * @return string
	 */
	private static function logo_html( array $s ) {
		$name  = get_bloginfo( 'name' );
		$width = (int) $s['logo_width'];
		$id    = 0;
		if ( 'custom' === $s['logo'] ) {
			$id = (int) $s['logo_id'];
		} elseif ( 'site' === $s['logo'] ) {
			$id = (int) get_theme_mod( 'custom_logo' );
		} else {
			return '';
		}
		if ( $id && wp_attachment_is_image( $id ) ) {
			return wp_get_attachment_image(
				$id,
				'medium_large',
				false,
				array(
					'class'    => 'grs-pop-ms__logo',
					'alt'      => $name,
					'style'    => 'width:' . $width . 'px',
					'loading'  => 'eager',
					'decoding' => 'async',
				)
			);
		}
		return '<p class="grs-pop-ms__sitename">' . esc_html( $name ) . '</p>';
	}

	/**
	 * Pagina di cortesia completa.
	 *
	 * @param string $mode    'coming_soon' o 'maintenance'.
	 * @param bool   $preview Anteprima dalla bacheca.
	 * @return void
	 */
	public static function render( $mode, $preview = false ) {
		$s        = self::get();
		$launch   = self::launch_timestamp( $s );
		$now      = time();
		$settings = Settings::get();

		if ( ! headers_sent() ) {
			self::no_cache();
			if ( 'maintenance' === $mode && ! $preview ) {
				status_header( 503 );
				$retry = $launch > $now ? min( $launch - $now, 7 * DAY_IN_SECONDS ) : HOUR_IN_SECONDS;
				header( 'Retry-After: ' . (int) $retry );
			} else {
				status_header( 200 );
			}
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		}

		// Contenuto prima dell'intestazione: il form di GRS Forms accoda i suoi file al rendering.
		$body = self::body_html( $s, $mode, $launch, $now );

		$heading = self::t( $s['heading'], 'heading' );
		$title   = '' !== $heading ? $heading . ' - ' . get_bloginfo( 'name' ) : get_bloginfo( 'name' );
		$palette = Renderer::palette_css();
		$classes = 'grs-pop-ms grs-pop-ms--' . ( 'brand' === $s['style'] ? 'brand' : 'light' );
		$css     = GRS_POP_URL . 'assets/css/maintenance' . ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' ) . '.css';
		if ( ! file_exists( GRS_POP_PATH . 'assets/css/maintenance.min.css' ) ) {
			$css = GRS_POP_URL . 'assets/css/maintenance.css';
		}
		wp_enqueue_style( 'grs-pop-maintenance', $css, array(), GRS_POP_VERSION );
		wp_add_inline_style( 'grs-pop-maintenance', $palette );
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( wp_strip_all_tags( $title ) ); ?></title>
		<?php
		if ( function_exists( 'wp_site_icon' ) ) {
			wp_site_icon();
		}
		?>
		<?php wp_styles()->do_items(); // Stile della pagina e quelli accodati al rendering (form di GRS Forms), senza il tema. ?>
</head>
<body class="<?php echo esc_attr( $classes ); ?>">
		<?php
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composto in body_html() con escape.
		wp_scripts()->do_items(); // Solo gli script accodati al rendering (form di GRS Forms).
		?>
</body>
</html>
		<?php
	}

	/**
	 * Corpo della pagina.
	 *
	 * @param array<string, mixed> $s      Impostazioni.
	 * @param string               $mode   Modalità.
	 * @param int                  $launch Data di lancio (UTC).
	 * @param int                  $now    Ora corrente.
	 * @return string
	 */
	private static function body_html( array $s, $mode, $launch, $now ) {
		$heading = self::t( $s['heading'], 'heading' );
		if ( '' === $heading ) {
			$heading = 'maintenance' === $mode ? __( 'We are working on the site', 'grs-pop' ) : __( 'Coming soon', 'grs-pop' );
		}
		$text = self::t( $s['text'], 'text' );
		if ( '' === trim( $text ) ) {
			$text = 'maintenance' === $mode ? __( 'We will be back online shortly. Thank you for your patience.', 'grs-pop' ) : __( 'Our new website is on its way. Stay tuned!', 'grs-pop' );
		}

		$html  = '<main class="grs-pop-ms__wrap"><div class="grs-pop-ms__card">';
		$html .= self::logo_html( $s );
		$html .= '<h1 class="grs-pop-ms__title">' . esc_html( $heading ) . '</h1>';
		$html .= '<div class="grs-pop-ms__text">' . wpautop( wp_kses( $text, self::allowed_html() ) ) . '</div>';

		// Conto alla rovescia.
		if ( ! empty( $s['countdown'] ) && $launch > $now ) {
			$left   = $launch - $now;
			$values = array(
				(int) floor( $left / DAY_IN_SECONDS ),
				(int) floor( ( $left % DAY_IN_SECONDS ) / HOUR_IN_SECONDS ),
				(int) floor( ( $left % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ),
				(int) ( $left % MINUTE_IN_SECONDS ),
			);
			$labels = array( __( 'Days', 'grs-pop' ), __( 'Hours', 'grs-pop' ), __( 'Minutes', 'grs-pop' ), __( 'Seconds', 'grs-pop' ) );
			$title  = self::t( $s['countdown_title'], 'countdown_title' );
			$html  .= '<div class="grs-pop-ms__countdown">';
			if ( '' !== $title ) {
				$html .= '<p class="grs-pop-ms__cd-title">' . esc_html( $title ) . '</p>';
			}
			$html .= '<div class="grs-pop-ms__cd" data-end="' . esc_attr( (string) ( $launch * 1000 ) ) . '"' . ( ! empty( $s['auto_off'] ) ? ' data-reload="1"' : '' ) . ' role="timer">';
			foreach ( $values as $i => $value ) {
				$html .= '<span class="grs-pop-ms__cd-unit"><span class="grs-pop-ms__cd-num" data-u="' . (int) $i . '">' . esc_html( $i ? str_pad( (string) $value, 2, '0', STR_PAD_LEFT ) : (string) $value ) . '</span><span class="grs-pop-ms__cd-label">' . esc_html( $labels[ $i ] ) . '</span></span>';
			}
			$html .= '</div></div>';
			$html .= '<script>(function(){var el=document.querySelector(".grs-pop-ms__cd");if(!el){return;}var end=+el.getAttribute("data-end"),h;function p(n){return n<10?"0"+n:String(n);}function t(){var s=Math.max(0,Math.floor((end-Date.now())/1000)),v=[Math.floor(s/86400),Math.floor(s%86400/3600),Math.floor(s%3600/60),s%60];Array.prototype.forEach.call(el.querySelectorAll("[data-u]"),function(n){var i=+n.getAttribute("data-u");n.textContent=i?p(v[i]):String(v[i]);});if(s<=0){clearInterval(h);if(el.getAttribute("data-reload")){setTimeout(function(){window.location.reload();},2000);}}}h=setInterval(t,1000);t();})();</script>';
		}

		// Form di GRS Forms (contratto pubblico: shortcode).
		if ( (int) $s['form_id'] > 0 && defined( 'GRS_FORMS_VERSION' ) && shortcode_exists( 'grs_form' ) ) {
			$html .= '<div class="grs-pop-ms__form">' . do_shortcode( '[grs_form id="' . (int) $s['form_id'] . '"]' ) . '</div>';
		}

		// Contatti.
		$items = array();
		if ( '' !== $s['email'] ) {
			$items[] = '<a href="' . esc_url( 'mailto:' . antispambot( $s['email'] ) ) . '">' . self::icon( 'mail' ) . '<span>' . esc_html( antispambot( $s['email'] ) ) . '</span></a>';
		}
		if ( '' !== $s['phone'] ) {
			$items[] = '<a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $s['phone'] ) ) . '">' . self::icon( 'phone' ) . '<span>' . esc_html( $s['phone'] ) . '</span></a>';
		}
		if ( '' !== $s['whatsapp'] ) {
			$items[] = '<a href="' . esc_url( 'https://wa.me/' . $s['whatsapp'] ) . '" target="_blank" rel="noopener">' . self::icon( 'chat' ) . '<span>WhatsApp</span></a>';
		}
		$address = self::t( $s['address'], 'address' );
		if ( '' !== trim( $address ) ) {
			$items[] = '<span class="grs-pop-ms__address">' . self::icon( 'pin' ) . '<span>' . nl2br( esc_html( $address ) ) . '</span></span>';
		}
		if ( $items ) {
			$html .= '<ul class="grs-pop-ms__contacts"><li>' . implode( '</li><li>', $items ) . '</li></ul>';
		}

		$social = array();
		foreach ( self::SOCIALS as $network => $label ) {
			if ( '' !== (string) $s['social'][ $network ] ) {
				$social[] = '<a href="' . esc_url( $s['social'][ $network ] ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a>';
			}
		}
		if ( $social ) {
			$html .= '<nav class="grs-pop-ms__social" aria-label="' . esc_attr__( 'Social profiles', 'grs-pop' ) . '">' . implode( '', $social ) . '</nav>';
		}

		$html .= '</div></main>';
		$html .= '<footer class="grs-pop-ms__footer">&copy; ' . esc_html( wp_date( 'Y' ) ) . ' ' . esc_html( get_bloginfo( 'name' ) ) . '</footer>';
		return $html;
	}

	// Form di GRS Forms per la bacheca.

	/**
	 * Elenco dei form dalla REST pubblica di GRS Forms (`GET grs-forms/v1/forms`), con lo stesso
	 * formato tollerante del builder. Null se la risposta non è leggibile.
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	public static function forms_list() {
		if ( ! defined( 'GRS_FORMS_VERSION' ) || ! function_exists( 'rest_do_request' ) ) {
			return array();
		}
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/grs-forms/v1/forms' ) );
		if ( $response->is_error() ) {
			return null;
		}
		$data = $response->get_data();
		if ( is_array( $data ) && isset( $data['forms'] ) && is_array( $data['forms'] ) ) {
			$data = $data['forms'];
		} elseif ( is_array( $data ) && isset( $data['items'] ) && is_array( $data['items'] ) ) {
			$data = $data['items'];
		}
		if ( ! is_array( $data ) ) {
			return null;
		}
		$out = array();
		foreach ( $data as $item ) {
			$item = is_object( $item ) ? (array) $item : $item;
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id = 0;
			foreach ( array( 'id', 'ID', 'value' ) as $k ) {
				if ( isset( $item[ $k ] ) ) {
					$id = absint( $item[ $k ] );
					break;
				}
			}
			if ( ! $id || isset( $out[ $id ] ) ) {
				continue;
			}
			$title = '';
			foreach ( array( 'title', 'name', 'label' ) as $k ) {
				if ( isset( $item[ $k ] ) ) {
					$title = $item[ $k ];
					break;
				}
			}
			if ( is_array( $title ) ) {
				$title = isset( $title['rendered'] ) ? $title['rendered'] : ( isset( $title['raw'] ) ? $title['raw'] : '' );
			}
			$active = true;
			if ( isset( $item['active'] ) ) {
				$active = (bool) $item['active'];
			} elseif ( isset( $item['status'] ) ) {
				$active = in_array( $item['status'], array( 'publish', 'active' ), true );
			}
			$out[ $id ] = array(
				'id'     => $id,
				'title'  => html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' ),
				'active' => $active,
			);
		}
		return array_values( $out );
	}
}
