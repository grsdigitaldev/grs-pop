<?php
/**
 * Sito pubblico: scelta dei popup, asset e markup.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Nelle pagine senza popup non viene eseguita nessuna query e non viene caricato nessun file.
 */
final class Frontend {

	/**
	 * Popup flottanti da stampare nel footer: ID => array( config, title, on_demand ).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $floating = array();

	/**
	 * Popup in linea inseriti automaticamente nel contenuto: ID => array( config, title ).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $auto_inline = array();

	/**
	 * Dati per lo script dei popup in linea già stampati.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $inline_data = array();

	/**
	 * ID del popup in anteprima (0 = nessuna).
	 *
	 * @var int
	 */
	private static $preview = 0;

	/**
	 * Asset accodati.
	 *
	 * @var bool
	 */
	private static $assets = false;

	/**
	 * Footer già stampato.
	 *
	 * @var bool
	 */
	private static $printed = false;

	/**
	 * Dati per lo script completo stampati.
	 *
	 * @var bool
	 */
	private static $data_printed = false;

	/**
	 * Numero massimo di form inviati ricordati nel browser (i più recenti).
	 */
	const FORMS_MAX = 50;

	/**
	 * Avvio (solo richieste del sito pubblico).
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'prepare' ), 20 );
	}

	/**
	 * Sceglie i popup della pagina corrente.
	 *
	 * @return void
	 */
	public static function prepare() {
		if ( is_feed() || is_embed() || is_robots() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_customize_preview() ) {
			return;
		}

		self::maybe_preview();

		// In anteprima si mostra solo il popup richiesto.
		$popups = self::$preview ? array() : Index::popups();
		if ( empty( $popups ) && ! self::$preview ) {
			return;
		}

		// Regola "ha / non ha compilato il form": si registrano gli invii di GRS Forms anche
		// nelle pagine senza popup (solo se un popup attivo la usa e GRS Forms è attivo).
		if ( ! self::$preview && self::watch_forms() ) {
			add_action( 'wp_footer', array( __CLASS__, 'print_forms_watcher' ), 20 );
		}

		$floating = array();
		$inline   = array();
		foreach ( $popups as $id => $entry ) {
			if ( self::$preview === (int) $id ) {
				continue;
			}
			if ( 'inline' === $entry['pl'] ) {
				if ( 'manual' !== $entry['ip'] && is_singular() && Targeting::matches( (array) $entry['tg'] ) ) {
					$inline[ (int) $id ] = (int) $entry['pr'];
				}
				continue;
			}
			if ( Targeting::matches( (array) $entry['tg'] ) ) {
				$floating[ (int) $id ] = (int) $entry['pr'];
			}
		}

		$ids = array_merge( array_keys( $floating ), array_keys( $inline ) );
		if ( self::$preview ) {
			$ids[] = self::$preview;
		}
		if ( empty( $ids ) ) {
			return;
		}

		// Una sola query per i meta (configurazioni); i titoli sono già nell'indice.
		update_meta_cache( 'post', $ids );
		$titles = array();
		foreach ( $popups as $id => $entry ) {
			$titles[ (int) $id ] = isset( $entry['t'] ) ? (string) $entry['t'] : '';
		}
		if ( self::$preview ) {
			$titles[ self::$preview ] = (string) get_the_title( self::$preview );
		}

		arsort( $floating );
		foreach ( array_keys( $floating ) as $id ) {
			self::$floating[ $id ] = array(
				'config' => Post_Type::get_config( $id ),
				'title'  => isset( $titles[ $id ] ) ? $titles[ $id ] : '',
				'od'     => false,
			);
		}
		if ( self::$preview ) {
			$config = Post_Type::get_config( self::$preview );
			if ( 'inline' === $config['placement'] ) {
				// Anteprima di un box in linea: mostrato come finestra al centro.
				$config['placement'] = 'floating';
				foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
					$config['display'][ $device ] = array(
						'enabled'  => true,
						'format'   => 'modal',
						'position' => 'center',
					);
				}
			}
			self::$floating = array(
				self::$preview => array(
					'config' => $config,
					'title'  => isset( $titles[ self::$preview ] ) ? $titles[ self::$preview ] : '',
					'od'     => false,
				),
			) + self::$floating;
		}
		arsort( $inline );
		foreach ( array_keys( $inline ) as $id ) {
			self::$auto_inline[ $id ] = array(
				'config' => Post_Type::get_config( $id ),
				'title'  => isset( $titles[ $id ] ) ? $titles[ $id ] : '',
			);
		}

		if ( self::$floating ) {
			self::hook_assets();
		}
		if ( self::$auto_inline ) {
			add_filter( 'the_content', array( __CLASS__, 'inject_inline' ), 20 );
		}
	}

	/**
	 * Anteprima di un popup (anche disattivato) per chi può gestirli: `?grs_pop_preview=ID&_grspop=nonce`.
	 *
	 * @return void
	 */
	private static function maybe_preview() {
		if ( ! isset( $_GET['grs_pop_preview'], $_GET['_grspop'] ) || ! current_user_can( 'grs_pop_manage_popups' ) ) {
			return;
		}
		$id = absint( wp_unslash( $_GET['grs_pop_preview'] ) );
		if ( ! $id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_grspop'] ) ), 'grs_pop_preview_' . $id ) ) {
			return;
		}
		if ( Post_Type::NAME === get_post_type( $id ) ) {
			self::$preview = $id;
		}
	}

	/**
	 * Indirizzo di anteprima di un popup.
	 *
	 * @param int $id ID.
	 * @return string
	 */
	public static function preview_url( $id ) {
		return add_query_arg(
			array(
				'grs_pop_preview' => (int) $id,
				'_grspop'         => wp_create_nonce( 'grs_pop_preview_' . (int) $id ),
			),
			home_url( '/' )
		);
	}


	/**
	 * Registra accodamento degli asset e stampa nel footer.
	 *
	 * @return void
	 */
	private static function hook_assets() {
		if ( did_action( 'wp_enqueue_scripts' ) ) {
			self::enqueue();
		} else {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		}
		if ( ! has_action( 'wp_footer', array( __CLASS__, 'print_footer' ) ) ) {
			add_action( 'wp_footer', array( __CLASS__, 'print_footer' ), 5 );
		}
	}

	/**
	 * Accoda CSS e JS (una volta).
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( self::$assets ) {
			return;
		}
		self::$assets = true;
		$suffix       = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$css          = file_exists( GRS_POP_PATH . 'assets/css/grs-pop' . $suffix . '.css' ) ? $suffix : '';
		$js           = file_exists( GRS_POP_PATH . 'assets/js/grs-pop' . $suffix . '.js' ) ? $suffix : '';

		wp_enqueue_style( 'grs-pop', GRS_POP_URL . 'assets/css/grs-pop' . $css . '.css', array(), GRS_POP_VERSION );
		wp_add_inline_style( 'grs-pop', Renderer::palette_css() );
		wp_enqueue_script(
			'grs-pop',
			GRS_POP_URL . 'assets/js/grs-pop' . $js . '.js',
			array(),
			GRS_POP_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Footer: popup flottanti e dati per lo script.
	 *
	 * @return void
	 */
	public static function print_footer() {
		if ( self::$printed ) {
			return;
		}
		self::$printed = true;

		$data = array();
		foreach ( self::$floating as $id => $item ) {
			echo Renderer::render( $id, $item['config'], $item['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup già sottoposto a escape nel renderer.
			$data[] = Renderer::client_data( $id, $item['config'], $item['title'], ! empty( $item['od'] ) );
		}
		foreach ( self::$inline_data as $item ) {
			$data[] = $item;
		}
		if ( empty( $data ) ) {
			return;
		}

		$settings = Settings::get();
		$params   = array();
		foreach ( $data as $item ) {
			foreach ( $item['co']['par'] as $param ) {
				$params[ $param['name'] ] = true;
			}
		}
		$payload = array(
			'v'       => GRS_POP_VERSION,
			'popups'  => $data,
			'bp'      => array(
				'm' => (int) $settings['bp_mobile'],
				't' => (int) $settings['bp_tablet'],
			),
			'maxI'    => (int) $settings['max_intrusive'],
			'stats'   => $settings['stats_enabled'] && ! self::$preview && ! current_user_can( 'grs_pop_manage_popups' ),
			'rest'    => esc_url_raw( rest_url( 'grs-pop/v1/stats' ) ),
			'tz'      => self::timezone_name(),
			'off'     => (int) round( (float) get_option( 'gmt_offset', 0 ) * 60 ),
			'params'  => array_keys( $params ),
			'preview' => self::$preview,
			'fw'      => ! self::$preview && self::watch_forms(),
		);

		self::$data_printed = true;
		echo '<script type="application/json" id="grs-pop-data">' . wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON con JSON_HEX_TAG.
	}

	/**
	 * Si registrano gli invii dei form di GRS Forms?
	 *
	 * @return bool
	 */
	private static function watch_forms() {
		return defined( 'GRS_FORMS_VERSION' ) && Index::watch_forms();
	}

	/**
	 * Ascoltatore minimo dell'evento grs-forms:submitted per le pagine senza lo script dei popup.
	 *
	 * Salva in localStorage "grs_pop" (chiave f) solo l'ID del form e la data dell'invio, nello
	 * stesso formato dello script completo. Nessun dato del form.
	 *
	 * @return void
	 */
	public static function print_forms_watcher() {
		if ( self::$data_printed ) {
			return; // Lo script completo è nella pagina e registra già gli invii.
		}
		$js = '(function(){document.addEventListener("grs-forms:submitted",function(e){try{var id=parseInt((e.detail||{}).form_id,10);if(!(id>0)){return;}var k="grs_pop",s=JSON.parse(localStorage.getItem(k)||"null");if(!s||typeof s!=="object"||typeof s.v!=="number"){s={v:1,first:Date.now(),pv:0,p:{}};}if(!s.f||typeof s.f!=="object"){s.f={};}s.f[id]=Date.now();Object.keys(s.f).sort(function(a,b){return s.f[b]-s.f[a];}).slice(' . self::FORMS_MAX . ').forEach(function(x){delete s.f[x];});localStorage.setItem(k,JSON.stringify(s));}catch(x){}});})();';
		wp_print_inline_script_tag( $js, array( 'id' => 'grs-pop-forms-watch' ) );
	}

	/**
	 * Fuso orario del sito se è un nome IANA (altrimenti vuoto: lo script usa lo scostamento).
	 *
	 * @return string
	 */
	private static function timezone_name() {
		$tz = (string) get_option( 'timezone_string', '' );
		return ( '' !== $tz && false === strpos( $tz, 'UTC' ) ) ? $tz : '';
	}

	/**
	 * Inserisce i popup in linea automatici nel contenuto.
	 *
	 * @param string $content Contenuto.
	 * @return string
	 */
	public static function inject_inline( $content ) {
		if ( ! in_the_loop() || ! is_main_query() || ! is_singular() || (int) get_the_ID() !== (int) get_queried_object_id() ) {
			return $content;
		}
		foreach ( self::$auto_inline as $id => $item ) {
			$html = self::render_inline( $id, $item['config'], $item['title'] );
			switch ( $item['config']['inline']['position'] ) {
				case 'before_content':
					$content = $html . $content;
					break;
				case 'after_paragraph':
					$content = self::after_paragraph( $content, $html, (int) $item['config']['inline']['paragraph'] );
					break;
				default:
					$content .= $html;
			}
		}
		self::$auto_inline = array();
		return $content;
	}

	/**
	 * Inserisce dopo l'N-esimo paragrafo (in coda se i paragrafi sono meno).
	 *
	 * @param string $content Contenuto.
	 * @param string $html    Markup da inserire.
	 * @param int    $n       Numero del paragrafo.
	 * @return string
	 */
	private static function after_paragraph( $content, $html, $n ) {
		$parts = explode( '</p>', $content );
		if ( count( $parts ) <= $n ) {
			return $content . $html;
		}
		$out = '';
		foreach ( $parts as $i => $part ) {
			$out .= $part;
			if ( $i < count( $parts ) - 1 ) {
				$out .= '</p>';
			}
			if ( $i + 1 === $n ) {
				$out .= $html;
			}
		}
		return $out;
	}

	/**
	 * Markup di un popup in linea e registrazione dei suoi dati per lo script.
	 *
	 * @param int                  $id     ID.
	 * @param array<string, mixed> $config Configurazione.
	 * @param string               $title  Titolo.
	 * @return string
	 */
	private static function render_inline( $id, array $config, $title ) {
		self::hook_assets();
		self::$inline_data[ $id ] = Renderer::client_data( $id, $config, $title );
		return Renderer::render( $id, $config, $title );
	}

	/**
	 * Shortcode `[grs_pop id="12"]`.
	 *
	 * Popup in linea: lo mostra qui. Popup flottante: con un testo (`[grs_pop id="12"]Apri[/grs_pop]`
	 * o `text="..."`) stampa un link che lo apre e lo aggiunge alla pagina anche se le regole non lo prevedono.
	 *
	 * @param array<string, string>|string $atts    Attributi.
	 * @param string|null                  $content Testo del link.
	 * @return string
	 */
	public static function shortcode( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'text'  => '',
				'class' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'grs_pop'
		);
		$id   = absint( $atts['id'] );
		if ( ! $id || is_admin() || ! Index::is_active( $id ) ) {
			return '';
		}
		$entry = Index::popups()[ $id ];
		$text  = '' !== (string) $content ? (string) $content : (string) $atts['text'];

		if ( 'inline' === $entry['pl'] ) {
			if ( isset( self::$inline_data[ $id ] ) ) {
				return ''; // Già presente in questa pagina.
			}
			return self::render_inline( $id, Post_Type::get_config( $id ), isset( $entry['t'] ) ? (string) $entry['t'] : '' );
		}

		if ( '' === trim( $text ) ) {
			return '';
		}
		if ( ! isset( self::$floating[ $id ] ) ) {
			self::$floating[ $id ] = array(
				'config' => Post_Type::get_config( $id ),
				'title'  => isset( $entry['t'] ) ? (string) $entry['t'] : '',
				'od'     => true,
			);
		}
		self::hook_assets();
		$class = trim( 'grs-pop-link ' . implode( ' ', array_map( 'sanitize_html_class', explode( ' ', (string) $atts['class'] ) ) ) );
		return '<a href="#grs-pop-' . esc_attr( (string) $id ) . '" class="' . esc_attr( $class ) . '">' . esc_html( wp_strip_all_tags( $text ) ) . '</a>';
	}

	/**
	 * Rendering del blocco "GRS Pop inline".
	 *
	 * @param array<string, mixed> $attributes Attributi del blocco.
	 * @return string
	 */
	public static function render_block( $attributes ) {
		$id = isset( $attributes['popupId'] ) ? absint( $attributes['popupId'] ) : 0;
		if ( ! $id ) {
			return '';
		}
		return self::shortcode( array( 'id' => (string) $id ) );
	}
}
