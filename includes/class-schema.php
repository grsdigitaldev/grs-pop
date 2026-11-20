<?php
/**
 * Struttura della configurazione di un popup e sua sanitizzazione.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Ogni configurazione passa da sanitize(): valori ammessi, limiti e default.
 * Il builder in JavaScript usa la stessa struttura (vedi assets/src/builder/defaults.js).
 */
final class Schema {

	/**
	 * Versione della struttura.
	 */
	const VERSION = 1;

	/**
	 * Formati per i popup flottanti.
	 */
	const FORMATS = array( 'modal', 'slidein', 'bar', 'fullscreen', 'tab' );

	/**
	 * Posizioni ammesse per formato.
	 */
	const POSITIONS = array(
		'modal'      => array( 'center', 'top' ),
		'slidein'    => array( 'bottom-right', 'bottom-left', 'top-right', 'top-left', 'bottom' ),
		'bar'        => array( 'top', 'bottom' ),
		'fullscreen' => array( 'center' ),
		'tab'        => array( 'right', 'left', 'bottom-right', 'bottom-left' ),
	);

	/**
	 * Tipi di sezione.
	 */
	const SECTION_TYPES = array( 'heading', 'text', 'button', 'form', 'coupon', 'countdown', 'list', 'spacer', 'dismiss', 'shortcode' );

	/**
	 * Icone disponibili per i pulsanti.
	 */
	const ICONS = array( 'none', 'arrow', 'whatsapp', 'phone', 'mail', 'gift', 'download', 'calendar', 'cart' );

	/**
	 * Tipi di regola sulle pagine.
	 */
	const RULE_TYPES = array( 'front_page', 'blog', 'singular', 'posts', 'terms', 'archive', 'search', '404', 'url' );

	/**
	 * Posizioni della linguetta per riaprire.
	 */
	const REOPEN_POSITIONS = array( 'auto', 'top-right', 'top-left', 'bottom-right', 'bottom-left' );

	/**
	 * Tag HTML ammessi nei testi.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_html() {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'u'      => array(),
			's'      => array(),
			'br'     => array(),
			'span'   => array( 'class' => true ),
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);
	}

	/**
	 * Configurazione predefinita (popup modale vuoto).
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'v'          => self::VERSION,
			'placement'  => 'floating',
			'display'    => array(
				'desktop'    => array(
					'enabled'  => true,
					'format'   => 'modal',
					'position' => 'center',
				),
				'tablet'     => array(
					'enabled'  => true,
					'format'   => 'modal',
					'position' => 'center',
				),
				'mobile'     => array(
					'enabled'  => true,
					'format'   => 'slidein',
					'position' => 'bottom',
				),
				'bar_mode'   => 'fixed',
				'tab_label'  => '',
				// Barra in alto fissa: sposta in basso gli elementi fissi del sito (header, menu).
				'bar_offset' => array(
					'enabled'   => false,
					'auto'      => true,
					'selectors' => '',
				),
			),
			'inline'     => array(
				'position'  => 'manual',
				'paragraph' => 3,
			),
			'design'     => array(
				'layout'          => 'stack',
				'width'           => 520,
				'padding'         => 'md',
				'radius'          => 16,
				'align'           => 'center',
				'bg'              => 'var:surface',
				'text'            => 'var:ink',
				'heading'         => 'var:ink',
				'btn'             => 'var:brand',
				'btn_text'        => 'var:brand_text',
				'accent'          => 'var:soft',
				'overlay'         => '#000000',
				'overlay_opacity' => 60,
				'stroke'          => 0,
				'stroke_color'    => 'var:soft',
				'shadow'          => 'soft',
				'anim'            => 'zoom',
				'close_color'     => 'var:muted',
				'image'           => array(
					'id'  => 0,
					'url' => '',
					'alt' => '',
				),
				'image_mobile'    => true,
				'bg_dim'          => 45,
				'font_scale'      => 100,
			),
			'sections'   => array(),
			'triggers'   => array(
				'load'       => array(
					'on'    => true,
					'delay' => 5,
				),
				'scroll'     => array(
					'on'      => false,
					'percent' => 50,
				),
				'exit'       => array(
					'on'     => false,
					'mobile' => 'scroll_up',
				),
				'inactivity' => array(
					'on'      => false,
					'seconds' => 30,
				),
				'element'    => array(
					'on'       => false,
					'selector' => '',
				),
				'click'      => array(
					'selector' => '',
				),
			),
			'conditions' => array(
				'pageviews' => 0,
				'visitor'   => 'all',
				'referrer'  => array(
					'type'  => 'any',
					'value' => '',
				),
				'params'    => array(),
				'forms'     => array(
					'mode' => 'any',
					'ids'  => array(),
					'days' => 0,
				),
				'schedule'  => array(
					'start' => '',
					'end'   => '',
					'days'  => array(),
					'from'  => '',
					'to'    => '',
				),
			),
			'frequency'  => array(
				'mode'               => 'days',
				'days'               => 7,
				'after_close_days'   => 7,
				'after_convert'      => 'never',
				'after_convert_days' => 90,
				'max_views'          => 0,
				'visit_minutes'      => 30,
			),
			'close'      => array(
				'button'           => true,
				'overlay'          => true,
				'esc'              => true,
				'after_form'       => 'close',
				'after_form_delay' => 4,
				'auto_close'       => 0,
				// Linguetta per riaprire barre e slide-in dopo la chiusura.
				'reopen'           => array(
					'enabled'   => false,
					'label'     => '',
					'icon'      => 'none',
					'position'  => 'auto',
					'top'       => 0,
					'container' => 0,
					'bg'        => '',
					'text'      => '',
				),
			),
			'targeting'  => array(
				'pages'     => 'all',
				'include'   => array(),
				'exclude'   => array(),
				'users'     => 'all',
				'roles'     => array(),
				'languages' => array(),
			),
			'advanced'   => array(
				'priority'  => 10,
				'css_class' => '',
			),
		);
	}

	/**
	 * Configurazione di partenza per i popup NUOVI (builder e modelli).
	 *
	 * Diversa da defaults() solo dove una funzione nuova va accesa per i popup nuovi ma non per
	 * quelli già salvati (la sanitizzazione applica defaults() alle chiavi mancanti).
	 *
	 * @return array<string, mixed>
	 */
	public static function new_popup_defaults() {
		$config = self::defaults();

		$config['display']['bar_offset']['enabled'] = true;
		return $config;
	}

	/**
	 * Valori predefiniti di una sezione.
	 *
	 * @param string $type Tipo di sezione.
	 * @return array<string, mixed>
	 */
	public static function section_defaults( $type ) {
		$base = array(
			'id'    => '',
			'type'  => $type,
			'align' => 'inherit',
		);
		switch ( $type ) {
			case 'heading':
				return $base + array(
					'text'  => '',
					'tag'   => 'h2',
					'size'  => 'lg',
					'color' => '',
				);
			case 'text':
				return $base + array(
					'text'  => '',
					'size'  => 'md',
					'color' => '',
				);
			case 'button':
				return $base + array(
					'label'   => '',
					'action'  => 'url',
					'url'     => '',
					'new_tab' => false,
					'style'   => 'primary',
					'full'    => true,
					'icon'    => 'none',
					'convert' => true,
				);
			case 'form':
				return $base + array( 'form_id' => 0 );
			case 'coupon':
				return $base + array(
					'code'   => '',
					'label'  => '',
					'copied' => '',
				);
			case 'countdown':
				return $base + array(
					'mode'    => 'date',
					'end'     => '',
					'minutes' => 30,
					'expire'  => 'hide_section',
					'l_days'  => '',
					'l_hours' => '',
					'l_min'   => '',
					'l_sec'   => '',
				);
			case 'list':
				return $base + array(
					'items' => array(),
					'icon'  => 'check',
				);
			case 'spacer':
				return $base + array(
					'size' => 'md',
					'line' => false,
				);
			case 'dismiss':
				return $base + array( 'text' => '' );
			case 'shortcode':
				return $base + array( 'code' => '' );
		}
		return $base;
	}

	/**
	 * Ripulisce una configurazione completa.
	 *
	 * @param array<string, mixed> $in Configurazione grezza.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $in ) {
		$d   = self::defaults();
		$out = array( 'v' => self::VERSION );

		$out['placement'] = self::enum( self::get( $in, 'placement' ), array( 'floating', 'inline' ), 'floating' );

		// Visualizzazione per dispositivo.
		$display = self::arr( $in, 'display' );
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			$dev    = self::arr( $display, $device );
			$def    = $d['display'][ $device ];
			$format = self::enum( self::get( $dev, 'format' ), self::FORMATS, $def['format'] );

			$out['display'][ $device ] = array(
				'enabled'  => self::bool( $dev, 'enabled', $def['enabled'] ),
				'format'   => $format,
				'position' => self::enum( self::get( $dev, 'position' ), self::POSITIONS[ $format ], self::POSITIONS[ $format ][0] ),
			);
		}
		$out['display']['bar_mode']   = self::enum( self::get( $display, 'bar_mode' ), array( 'fixed', 'push' ), 'fixed' );
		$out['display']['tab_label']  = self::text( self::get( $display, 'tab_label' ), 60 );
		$offset                       = self::arr( $display, 'bar_offset' );
		$out['display']['bar_offset'] = array(
			'enabled'   => self::bool( $offset, 'enabled', false ),
			'auto'      => self::bool( $offset, 'auto', true ),
			'selectors' => self::selector( self::get( $offset, 'selectors' ), 300 ),
		);

		$inline        = self::arr( $in, 'inline' );
		$out['inline'] = array(
			'position'  => self::enum( self::get( $inline, 'position' ), array( 'manual', 'before_content', 'after_content', 'after_paragraph' ), 'manual' ),
			'paragraph' => self::int( self::get( $inline, 'paragraph' ), 1, 50, 3 ),
		);

		$out['design']   = self::sanitize_design( self::arr( $in, 'design' ) );
		$out['sections'] = self::sanitize_sections( self::get( $in, 'sections' ) );

		// Trigger.
		$tr              = self::arr( $in, 'triggers' );
		$td              = $d['triggers'];
		$load            = self::arr( $tr, 'load' );
		$scroll          = self::arr( $tr, 'scroll' );
		$exit            = self::arr( $tr, 'exit' );
		$inact           = self::arr( $tr, 'inactivity' );
		$elem            = self::arr( $tr, 'element' );
		$click           = self::arr( $tr, 'click' );
		$out['triggers'] = array(
			'load'       => array(
				'on'    => self::bool( $load, 'on', $td['load']['on'] ),
				'delay' => self::int( self::get( $load, 'delay' ), 0, 600, 5 ),
			),
			'scroll'     => array(
				'on'      => self::bool( $scroll, 'on', false ),
				'percent' => self::int( self::get( $scroll, 'percent' ), 1, 100, 50 ),
			),
			'exit'       => array(
				'on'     => self::bool( $exit, 'on', false ),
				'mobile' => self::enum( self::get( $exit, 'mobile' ), array( 'scroll_up', 'none' ), 'scroll_up' ),
			),
			'inactivity' => array(
				'on'      => self::bool( $inact, 'on', false ),
				'seconds' => self::int( self::get( $inact, 'seconds' ), 5, 3600, 30 ),
			),
			'element'    => array(
				'on'       => self::bool( $elem, 'on', false ),
				'selector' => self::selector( self::get( $elem, 'selector' ) ),
			),
			'click'      => array(
				'selector' => self::selector( self::get( $click, 'selector' ) ),
			),
		);

		// Condizioni lato browser.
		$co       = self::arr( $in, 'conditions' );
		$ref      = self::arr( $co, 'referrer' );
		$schedule = self::arr( $co, 'schedule' );
		$params   = array();
		foreach ( (array) self::get( $co, 'params', array() ) as $param ) {
			if ( ! is_array( $param ) ) {
				continue;
			}
			$name = preg_replace( '/[^A-Za-z0-9_\-\[\]]/', '', (string) self::get( $param, 'name', '' ) );
			if ( '' === $name ) {
				continue;
			}
			$params[] = array(
				'name'  => substr( $name, 0, 60 ),
				'value' => self::text( self::get( $param, 'value' ), 120 ),
			);
			if ( count( $params ) >= 5 ) {
				break;
			}
		}
		$days = array();
		foreach ( (array) self::get( $schedule, 'days', array() ) as $day ) {
			$day = (int) $day;
			if ( $day >= 0 && $day <= 6 && ! in_array( $day, $days, true ) ) {
				$days[] = $day;
			}
		}
		sort( $days );
		// Form di GRS Forms già inviati dal visitatore (contratto dev/contratto-grs-pop-forms.md).
		$fm       = self::arr( $co, 'forms' );
		$form_ids = array();
		foreach ( (array) self::get( $fm, 'ids', array() ) as $form_id ) {
			$form_id = absint( $form_id );
			if ( $form_id && ! in_array( $form_id, $form_ids, true ) ) {
				$form_ids[] = $form_id;
			}
			if ( count( $form_ids ) >= 20 ) {
				break;
			}
		}
		$out['conditions'] = array(
			'pageviews' => self::int( self::get( $co, 'pageviews' ), 0, 100, 0 ),
			'visitor'   => self::enum( self::get( $co, 'visitor' ), array( 'all', 'new', 'returning' ), 'all' ),
			'referrer'  => array(
				'type'  => self::enum( self::get( $ref, 'type' ), array( 'any', 'search', 'social', 'direct', 'external', 'contains' ), 'any' ),
				'value' => self::text( self::get( $ref, 'value' ), 120 ),
			),
			'params'    => $params,
			'forms'     => array(
				'mode' => self::enum( self::get( $fm, 'mode' ), array( 'any', 'done', 'not_done' ), 'any' ),
				'ids'  => $form_ids,
				'days' => self::int( self::get( $fm, 'days' ), 0, 3650, 0 ),
			),
			'schedule'  => array(
				'start' => self::datetime( self::get( $schedule, 'start' ) ),
				'end'   => self::datetime( self::get( $schedule, 'end' ) ),
				'days'  => $days,
				'from'  => self::time( self::get( $schedule, 'from' ) ),
				'to'    => self::time( self::get( $schedule, 'to' ) ),
			),
		);

		$fr               = self::arr( $in, 'frequency' );
		$out['frequency'] = array(
			'mode'               => self::enum( self::get( $fr, 'mode' ), array( 'always', 'session', 'visit', 'days', 'once' ), 'days' ),
			'days'               => self::int( self::get( $fr, 'days' ), 1, 365, 7 ),
			'after_close_days'   => self::int( self::get( $fr, 'after_close_days' ), 0, 365, 7 ),
			'after_convert'      => self::enum( self::get( $fr, 'after_convert' ), array( 'never', 'days', 'keep' ), 'never' ),
			'after_convert_days' => self::int( self::get( $fr, 'after_convert_days' ), 1, 3650, 90 ),
			'max_views'          => self::int( self::get( $fr, 'max_views' ), 0, 1000, 0 ),
			'visit_minutes'      => self::int( self::get( $fr, 'visit_minutes' ), 5, 240, 30 ),
		);

		$cl           = self::arr( $in, 'close' );
		$out['close'] = array(
			'button'           => self::bool( $cl, 'button', true ),
			'overlay'          => self::bool( $cl, 'overlay', true ),
			'esc'              => self::bool( $cl, 'esc', true ),
			'after_form'       => self::enum( self::get( $cl, 'after_form' ), array( 'close', 'keep' ), 'close' ),
			'after_form_delay' => self::int( self::get( $cl, 'after_form_delay' ), 0, 60, 4 ),
			'auto_close'       => self::int( self::get( $cl, 'auto_close' ), 0, 600, 0 ),
			'reopen'           => self::sanitize_reopen( self::arr( $cl, 'reopen' ) ),
		);
		// Senza pulsante, clic fuori ed Esc il visitatore resterebbe bloccato: il pulsante torna attivo.
		if ( ! $out['close']['button'] && ! $out['close']['overlay'] && ! $out['close']['esc'] ) {
			$out['close']['button'] = true;
		}

		$out['targeting'] = self::sanitize_targeting( self::arr( $in, 'targeting' ) );

		$adv             = self::arr( $in, 'advanced' );
		$out['advanced'] = array(
			'priority'  => self::int( self::get( $adv, 'priority' ), 1, 100, 10 ),
			'css_class' => implode( ' ', array_filter( array_map( 'sanitize_html_class', explode( ' ', (string) self::get( $adv, 'css_class', '' ) ) ) ) ),
		);

		return $out;
	}

	/**
	 * Aspetto.
	 *
	 * @param array<string, mixed> $in Valori.
	 * @return array<string, mixed>
	 */
	private static function sanitize_design( array $in ) {
		$d     = self::defaults()['design'];
		$image = self::arr( $in, 'image' );
		$url   = esc_url_raw( (string) self::get( $image, 'url', '' ), array( 'http', 'https' ) );
		return array(
			'layout'          => self::enum( self::get( $in, 'layout' ), array( 'stack', 'left', 'right', 'background', 'none' ), $d['layout'] ),
			'width'           => self::int( self::get( $in, 'width' ), 240, 1200, $d['width'] ),
			'padding'         => self::enum( self::get( $in, 'padding' ), array( 'sm', 'md', 'lg' ), $d['padding'] ),
			'radius'          => self::int( self::get( $in, 'radius' ), 0, 40, $d['radius'] ),
			'align'           => self::enum( self::get( $in, 'align' ), array( 'left', 'center' ), $d['align'] ),
			'bg'              => self::color( self::get( $in, 'bg' ), $d['bg'] ),
			'text'            => self::color( self::get( $in, 'text' ), $d['text'] ),
			'heading'         => self::color( self::get( $in, 'heading' ), $d['heading'] ),
			'btn'             => self::color( self::get( $in, 'btn' ), $d['btn'] ),
			'btn_text'        => self::color( self::get( $in, 'btn_text' ), $d['btn_text'] ),
			'accent'          => self::color( self::get( $in, 'accent' ), $d['accent'] ),
			'overlay'         => self::color( self::get( $in, 'overlay' ), $d['overlay'] ),
			'overlay_opacity' => self::int( self::get( $in, 'overlay_opacity' ), 0, 95, $d['overlay_opacity'] ),
			'stroke'          => self::int( self::get( $in, 'stroke' ), 0, 8, 0 ),
			'stroke_color'    => self::color( self::get( $in, 'stroke_color' ), $d['stroke_color'] ),
			'shadow'          => self::enum( self::get( $in, 'shadow' ), array( 'none', 'soft', 'strong' ), $d['shadow'] ),
			'anim'            => self::enum( self::get( $in, 'anim' ), array( 'none', 'fade', 'zoom', 'slide' ), $d['anim'] ),
			'close_color'     => self::color( self::get( $in, 'close_color' ), $d['close_color'] ),
			'image'           => array(
				'id'  => max( 0, (int) self::get( $image, 'id', 0 ) ),
				'url' => $url,
				'alt' => self::text( self::get( $image, 'alt' ), 200 ),
			),
			'image_mobile'    => self::bool( $in, 'image_mobile', true ),
			'bg_dim'          => self::int( self::get( $in, 'bg_dim' ), 0, 90, $d['bg_dim'] ),
			'font_scale'      => self::int( self::get( $in, 'font_scale' ), 70, 150, 100 ),
		);
	}

	/**
	 * Sezioni del contenuto.
	 *
	 * @param mixed $sections Elenco grezzo.
	 * @return array<int, array<string, mixed>>
	 */
	private static function sanitize_sections( $sections ) {
		$out  = array();
		$seen = array();
		if ( ! is_array( $sections ) ) {
			return $out;
		}
		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}
			$type = (string) self::get( $section, 'type', '' );
			if ( ! in_array( $type, self::SECTION_TYPES, true ) ) {
				continue;
			}
			$id = substr( sanitize_key( (string) self::get( $section, 'id', '' ) ), 0, 20 );
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = 's' . strtolower( wp_generate_password( 6, false, false ) );
			}
			$seen[ $id ] = true;

			$s = array(
				'id'    => $id,
				'type'  => $type,
				'align' => self::enum( self::get( $section, 'align' ), array( 'inherit', 'left', 'center', 'right' ), 'inherit' ),
			);
			switch ( $type ) {
				case 'heading':
					$s['text']  = self::rich( self::get( $section, 'text' ), 300 );
					$s['tag']   = self::enum( self::get( $section, 'tag' ), array( 'h2', 'h3', 'p' ), 'h2' );
					$s['size']  = self::enum( self::get( $section, 'size' ), array( 'sm', 'md', 'lg', 'xl' ), 'lg' );
					$s['color'] = self::color( self::get( $section, 'color' ), '' );
					break;
				case 'text':
					$s['text']  = self::rich( self::get( $section, 'text' ), 3000 );
					$s['size']  = self::enum( self::get( $section, 'size' ), array( 'sm', 'md', 'lg' ), 'md' );
					$s['color'] = self::color( self::get( $section, 'color' ), '' );
					break;
				case 'button':
					$s['label']   = self::text( self::get( $section, 'label' ), 120 );
					$s['action']  = self::enum( self::get( $section, 'action' ), array( 'url', 'close' ), 'url' );
					$s['url']     = self::link( self::get( $section, 'url' ) );
					$s['new_tab'] = self::bool( $section, 'new_tab', false );
					$s['style']   = self::enum( self::get( $section, 'style' ), array( 'primary', 'secondary', 'outline', 'link' ), 'primary' );
					$s['full']    = self::bool( $section, 'full', true );
					$s['icon']    = self::enum( self::get( $section, 'icon' ), self::ICONS, 'none' );
					$s['convert'] = self::bool( $section, 'convert', true );
					break;
				case 'form':
					$s['form_id'] = max( 0, (int) self::get( $section, 'form_id', 0 ) );
					break;
				case 'coupon':
					$s['code']   = self::text( self::get( $section, 'code' ), 60 );
					$s['label']  = self::text( self::get( $section, 'label' ), 60 );
					$s['copied'] = self::text( self::get( $section, 'copied' ), 60 );
					break;
				case 'countdown':
					$s['mode']    = self::enum( self::get( $section, 'mode' ), array( 'date', 'evergreen' ), 'date' );
					$s['end']     = self::datetime( self::get( $section, 'end' ) );
					$s['minutes'] = self::int( self::get( $section, 'minutes' ), 1, 100000, 30 );
					$s['expire']  = self::enum( self::get( $section, 'expire' ), array( 'hide_section', 'hide_popup', 'zero' ), 'hide_section' );
					foreach ( array( 'l_days', 'l_hours', 'l_min', 'l_sec' ) as $label ) {
						$s[ $label ] = self::text( self::get( $section, $label ), 20 );
					}
					break;
				case 'list':
					$items = array();
					foreach ( (array) self::get( $section, 'items', array() ) as $item ) {
						if ( is_scalar( $item ) ) {
							$item = self::rich( $item, 300 );
							if ( '' !== $item ) {
								$items[] = $item;
							}
						}
						if ( count( $items ) >= 12 ) {
							break;
						}
					}
					$s['items'] = $items;
					$s['icon']  = self::enum( self::get( $section, 'icon' ), array( 'check', 'dot', 'star', 'arrow' ), 'check' );
					break;
				case 'spacer':
					$s['size'] = self::enum( self::get( $section, 'size' ), array( 'sm', 'md', 'lg' ), 'md' );
					$s['line'] = self::bool( $section, 'line', false );
					break;
				case 'dismiss':
					$s['text'] = self::text( self::get( $section, 'text' ), 120 );
					break;
				case 'shortcode':
					$s['code'] = self::shortcode( self::get( $section, 'code' ) );
					break;
			}
			$out[] = $s;
			if ( count( $out ) >= 30 ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Regole sulle pagine e sugli utenti.
	 *
	 * @param array<string, mixed> $in Valori.
	 * @return array<string, mixed>
	 */
	private static function sanitize_targeting( array $in ) {
		$roles = array();
		foreach ( (array) self::get( $in, 'roles', array() ) as $role ) {
			$role = sanitize_key( (string) $role );
			if ( '' !== $role ) {
				$roles[] = $role;
			}
		}
		$languages = array();
		foreach ( (array) self::get( $in, 'languages', array() ) as $lang ) {
			$lang = strtolower( preg_replace( '/[^A-Za-z_\-]/', '', (string) $lang ) );
			if ( '' !== $lang ) {
				$languages[] = substr( $lang, 0, 10 );
			}
		}
		return array(
			'pages'     => self::enum( self::get( $in, 'pages' ), array( 'all', 'include' ), 'all' ),
			'include'   => self::sanitize_rules( self::get( $in, 'include' ) ),
			'exclude'   => self::sanitize_rules( self::get( $in, 'exclude' ) ),
			'users'     => self::enum( self::get( $in, 'users' ), array( 'all', 'in', 'out' ), 'all' ),
			'roles'     => array_values( array_unique( $roles ) ),
			'languages' => array_values( array_unique( $languages ) ),
		);
	}

	/**
	 * Elenco di regole sulle pagine.
	 *
	 * @param mixed $rules Regole grezze.
	 * @return array<int, array<string, mixed>>
	 */
	private static function sanitize_rules( $rules ) {
		$out = array();
		if ( ! is_array( $rules ) ) {
			return $out;
		}
		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			$type = (string) self::get( $rule, 'type', '' );
			if ( ! in_array( $type, self::RULE_TYPES, true ) ) {
				continue;
			}
			$r = array( 'type' => $type );
			switch ( $type ) {
				case 'singular':
				case 'archive':
					$r['post_type'] = sanitize_key( (string) self::get( $rule, 'post_type', '' ) );
					if ( '' === $r['post_type'] ) {
						continue 2;
					}
					break;
				case 'posts':
				case 'terms':
					if ( 'terms' === $type ) {
						$r['taxonomy'] = sanitize_key( (string) self::get( $rule, 'taxonomy', '' ) );
						if ( '' === $r['taxonomy'] ) {
							continue 2;
						}
					}
					$items = array();
					foreach ( (array) self::get( $rule, 'items', array() ) as $item ) {
						if ( ! is_array( $item ) ) {
							continue;
						}
						$id = (int) self::get( $item, 'id', 0 );
						if ( $id > 0 ) {
							$items[] = array(
								'id'    => $id,
								'label' => self::text( self::get( $item, 'label' ), 120 ),
							);
						}
						if ( count( $items ) >= 100 ) {
							break;
						}
					}
					if ( empty( $items ) ) {
						continue 2;
					}
					$r['items'] = $items;
					break;
				case 'url':
					$r['op']    = self::enum( self::get( $rule, 'op' ), array( 'contains', 'equals', 'starts' ), 'contains' );
					$r['value'] = substr( trim( wp_strip_all_tags( (string) self::get( $rule, 'value', '' ) ) ), 0, 200 );
					if ( '' === $r['value'] ) {
						continue 2;
					}
					break;
			}
			$out[] = $r;
			if ( count( $out ) >= 50 ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Regola "ha / non ha compilato il form" in uso, oppure null.
	 *
	 * @param array<string, mixed> $config Configurazione già ripulita.
	 * @return array<string, mixed>|null
	 */
	public static function forms_rule( array $config ) {
		if ( empty( $config['conditions']['forms'] ) || ! is_array( $config['conditions']['forms'] ) ) {
			return null;
		}
		$rule = $config['conditions']['forms'];
		if ( empty( $rule['ids'] ) || ! in_array( $rule['mode'], array( 'done', 'not_done' ), true ) ) {
			return null;
		}
		return $rule;
	}

	/**
	 * Valore da un array.
	 *
	 * @param array<string, mixed> $arr      Array.
	 * @param string               $key      Chiave.
	 * @param mixed                $fallback Valore se assente.
	 * @return mixed
	 */
	private static function get( array $arr, $key, $fallback = null ) {
		return array_key_exists( $key, $arr ) ? $arr[ $key ] : $fallback;
	}

	/**
	 * Sotto-array (vuoto se assente o non valido).
	 *
	 * @param array<string, mixed> $arr Array.
	 * @param string               $key Chiave.
	 * @return array<string, mixed>
	 */
	private static function arr( array $arr, $key ) {
		return isset( $arr[ $key ] ) && is_array( $arr[ $key ] ) ? $arr[ $key ] : array();
	}

	/**
	 * Valore tra quelli ammessi.
	 *
	 * @param mixed    $value    Valore.
	 * @param string[] $allowed  Ammessi.
	 * @param string   $fallback Predefinito.
	 * @return string
	 */
	private static function enum( $value, array $allowed, $fallback ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		return in_array( $value, $allowed, true ) ? $value : (string) $fallback;
	}

	/**
	 * Intero con limiti.
	 *
	 * @param mixed $value    Valore.
	 * @param int   $min      Minimo.
	 * @param int   $max      Massimo.
	 * @param int   $fallback Predefinito.
	 * @return int
	 */
	private static function int( $value, $min, $max, $fallback ) {
		if ( ! is_numeric( $value ) ) {
			return (int) $fallback;
		}
		return max( (int) $min, min( (int) $max, (int) $value ) );
	}

	/**
	 * Booleano.
	 *
	 * @param array<string, mixed> $arr      Array.
	 * @param string               $key      Chiave.
	 * @param bool                 $fallback Predefinito.
	 * @return bool
	 */
	private static function bool( array $arr, $key, $fallback ) {
		if ( ! array_key_exists( $key, $arr ) ) {
			return (bool) $fallback;
		}
		$value = $arr[ $key ];
		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
		}
		return (bool) $value;
	}

	/**
	 * Testo semplice con lunghezza massima.
	 *
	 * @param mixed $value Valore.
	 * @param int   $max   Lunghezza massima.
	 * @return string
	 */
	private static function text( $value, $max ) {
		$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
	}

	/**
	 * Testo con poche formattazioni ammesse.
	 *
	 * @param mixed $value Valore.
	 * @param int   $max   Lunghezza massima.
	 * @return string
	 */
	private static function rich( $value, $max ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
		return trim( wp_kses( $value, self::allowed_html() ) );
	}

	/**
	 * Indirizzo di un pulsante (http, https, mailto, tel o ancora).
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	private static function link( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( '' === $value ) {
			return '';
		}
		if ( 0 === strpos( $value, '#' ) ) {
			return '#' . preg_replace( '/[^A-Za-z0-9_\-]/', '', substr( $value, 1 ) );
		}
		return esc_url_raw( $value, array( 'http', 'https', 'mailto', 'tel', 'sms' ) );
	}

	/**
	 * Colore: esadecimale, colore della palette (`var:brand`) o vuoto se ammesso.
	 *
	 * @param mixed  $value    Valore.
	 * @param string $fallback Predefinito.
	 * @return string
	 */
	public static function color( $value, $fallback ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( 0 === strpos( $value, 'var:' ) && array_key_exists( substr( $value, 4 ), Settings::defaults()['palette'] ) ) {
			return $value;
		}
		if ( 'transparent' === $value ) {
			return $value;
		}
		$hex = sanitize_hex_color( $value );
		return $hex ? $hex : (string) $fallback;
	}

	/**
	 * Linguetta per riaprire.
	 *
	 * @param array<string, mixed> $ro Valori grezzi.
	 * @return array<string, mixed>
	 */
	private static function sanitize_reopen( array $ro ) {
		$bg   = self::get( $ro, 'bg', '' );
		$text = self::get( $ro, 'text', '' );
		return array(
			'enabled'   => self::bool( $ro, 'enabled', false ),
			'label'     => self::text( self::get( $ro, 'label' ), 40 ),
			'icon'      => self::enum( self::get( $ro, 'icon' ), self::ICONS, 'none' ),
			'position'  => self::enum( self::get( $ro, 'position' ), self::REOPEN_POSITIONS, 'auto' ),
			'top'       => self::int( self::get( $ro, 'top' ), 0, 400, 0 ),
			'container' => self::int( self::get( $ro, 'container' ), 0, 3000, 0 ),
			'bg'        => '' === $bg || 'transparent' === $bg ? '' : self::color( $bg, '' ),
			'text'      => '' === $text || 'transparent' === $text ? '' : self::color( $text, '' ),
		);
	}

	/**
	 * Selettore CSS (caratteri sicuri, lunghezza limitata).
	 *
	 * @param mixed $value Valore.
	 * @param int   $max   Lunghezza massima.
	 * @return string
	 */
	private static function selector( $value, $max = 200 ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = preg_replace( '/[^A-Za-z0-9_\-\.#\[\]=\'":,\s>*~+()]/', '', $value );
		return substr( trim( (string) $value ), 0, $max );
	}

	/**
	 * Data e ora locale del sito in formato `AAAA-MM-GGTHH:MM`.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	private static function datetime( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value ) ? $value : '';
	}

	/**
	 * Ora `HH:MM`.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	private static function time( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
	}

	/**
	 * Shortcode: un solo shortcode tra parentesi quadre.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	private static function shortcode( $value ) {
		$value = is_scalar( $value ) ? trim( wp_strip_all_tags( (string) $value ) ) : '';
		if ( ! preg_match( '/^\[[A-Za-z0-9_\-]+[^\]]*\](.*\[\/[A-Za-z0-9_\-]+\])?$/s', $value ) ) {
			return '';
		}
		return substr( $value, 0, 500 );
	}
}
