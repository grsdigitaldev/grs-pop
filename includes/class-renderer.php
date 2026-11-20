<?php
/**
 * HTML dei popup.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Genera il markup di un popup a partire dalla configurazione.
 *
 * La stessa struttura è riprodotta dall'anteprima del builder (assets/src/builder/preview.jsx):
 * se cambi classi o ordine degli elementi qui, aggiorna anche l'anteprima.
 */
final class Renderer {

	/**
	 * Icone SVG (24x24, tratto con currentColor).
	 *
	 * @var array<string, string>
	 */
	const ICON_PATHS = array(
		'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'whatsapp' => '<path d="M4 20l1.3-4A8 8 0 1 1 8.4 19L4 20z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-1.9-1-0.9 0.7a4 4 0 0 1-2.2-2.2l0.7-0.9-1-1.9L9 9.5z"/>',
		'phone'    => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'gift'     => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v9h14v-9M12 8C10 4 7 4 7 6s3 2 5 2c2 0 5 0 5-2s-3-2-5 2"/>',
		'download' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
		'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 12h11L21 7H6.2"/>',
		'check'    => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'chevron'  => '<path d="M6 9l6 6 6-6"/>',
		'star'     => '<path d="M12 3.5l2.6 5.4 5.9 0.8-4.3 4.1 1 5.8L12 16.8l-5.2 2.8 1-5.8-4.3-4.1 5.9-0.8z"/>',
	);

	/**
	 * SVG di un'icona.
	 *
	 * @param string $name      Nome.
	 * @param string $css_class Classe CSS.
	 * @return string
	 */
	public static function icon( $name, $css_class = 'grs-pop__icon' ) {
		if ( ! isset( self::ICON_PATHS[ $name ] ) ) {
			return '';
		}
		return '<svg class="' . esc_attr( $css_class ) . '" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . self::ICON_PATHS[ $name ] . '</svg>';
	}

	/**
	 * Colore della configurazione come valore CSS.
	 *
	 * @param string $color Colore (`#hex`, `var:nome`, `transparent` o vuoto).
	 * @return string
	 */
	public static function css_color( $color ) {
		$color = (string) $color;
		if ( 0 === strpos( $color, 'var:' ) ) {
			return 'var(--grs-pop-' . str_replace( '_', '-', sanitize_key( substr( $color, 4 ) ) ) . ')';
		}
		if ( 'transparent' === $color ) {
			return 'transparent';
		}
		$hex = sanitize_hex_color( $color );
		return $hex ? $hex : '';
	}

	/**
	 * Variabili CSS della palette (per :root).
	 *
	 * @return string
	 */
	public static function palette_css() {
		$settings = Settings::get();
		$vars     = array();
		foreach ( $settings['palette'] as $key => $value ) {
			$hex = sanitize_hex_color( (string) $value );
			if ( $hex ) {
				$vars[] = '--grs-pop-' . str_replace( '_', '-', $key ) . ':' . $hex;
			}
		}
		$vars[] = '--grs-pop-z:' . (int) $settings['z_index'];
		return ':root{' . implode( ';', $vars ) . '}';
	}

	/**
	 * Testo traducibile (ponte GRS i18n).
	 *
	 * @param string $value Testo originale.
	 * @param int    $id    ID del popup.
	 * @param string $name  Nome del campo (senza prefisso).
	 * @return string
	 */
	private static function t( $value, $id, $name ) {
		$value = (string) $value;
		if ( '' === $value || ! class_exists( 'GRS_I18n' ) ) {
			return $value;
		}
		return \GRS_I18n::translate( $value, I18n_Bridge::CONTEXT, I18n_Bridge::name( $id, $name ) );
	}

	/**
	 * Markup completo di un popup.
	 *
	 * @param int                  $id     ID del popup.
	 * @param array<string, mixed> $config Configurazione ripulita.
	 * @param string               $title  Titolo interno (per aria-label).
	 * @return string
	 */
	public static function render( $id, array $config, $title = '' ) {
		$id     = (int) $id;
		$d      = $config['design'];
		$inline = 'inline' === $config['placement'];
		$dom_id = 'grs-pop-' . $id;

		$classes = array(
			'grs-pop',
			'grs-pop-id-' . $id,
			$inline ? 'grs-pop--inline' : 'grs-pop--floating',
			'grs-pop--layout-' . ( '' === $d['image']['url'] ? 'none' : $d['layout'] ),
			'grs-pop--pad-' . $d['padding'],
			'grs-pop--align-' . $d['align'],
			'grs-pop--shadow-' . $d['shadow'],
			'grs-pop--anim-' . $d['anim'],
		);
		if ( ! $d['image_mobile'] ) {
			$classes[] = 'grs-pop--no-img-mobile';
		}
		if ( '' !== $config['advanced']['css_class'] ) {
			$classes[] = $config['advanced']['css_class'];
		}

		$style = self::style_vars( $d );

		$heading_id = '';
		foreach ( $config['sections'] as $section ) {
			if ( 'heading' === $section['type'] && '' !== $section['text'] ) {
				$heading_id = $dom_id . '-t';
				break;
			}
		}

		$label_attr = '' !== $heading_id ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : ' aria-label="' . esc_attr( '' !== $title ? $title : __( 'Popup', 'grs-pop' ) ) . '"';

		$html  = '<div id="' . esc_attr( $dom_id ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-grs-pop-id="' . esc_attr( (string) $id ) . '"';
		$html .= $inline ? ' role="region"' : ' role="dialog" aria-modal="false" hidden';
		$html .= $label_attr . ' style="' . esc_attr( $style ) . '">';

		if ( ! $inline ) {
			$html .= '<div class="grs-pop__overlay" data-grs-pop-close="overlay"></div>';
		}
		$html .= '<div class="grs-pop__box"' . ( $inline ? '' : ' tabindex="-1"' ) . '>';

		if ( $config['close']['button'] ) {
			$html .= '<button type="button" class="grs-pop__close" data-grs-pop-close="button" aria-label="' . esc_attr__( 'Close', 'grs-pop' ) . '">' . self::icon( 'close', 'grs-pop__close-icon' ) . '</button>';
		}

		if ( '' !== $d['image']['url'] && 'none' !== $d['layout'] ) {
			$alt   = self::t( $d['image']['alt'], $id, 'image_alt' );
			$html .= '<div class="grs-pop__media"><img src="' . esc_url( $d['image']['url'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async"></div>';
		}

		$html .= '<div class="grs-pop__body">';
		$first = true;
		foreach ( $config['sections'] as $section ) {
			$html .= self::section( $id, $section, $first && 'heading' === $section['type'] ? $heading_id : '' );
			if ( 'heading' === $section['type'] && '' !== $section['text'] ) {
				$first = false;
			}
		}
		$html .= '</div></div></div>';

		if ( ! $inline && self::uses_format( $config, 'tab' ) ) {
			$label = '' !== $config['display']['tab_label'] ? self::t( $config['display']['tab_label'], $id, 'tab_label' ) : __( 'Open', 'grs-pop' );
			$html .= '<button type="button" class="grs-pop-tab grs-pop-tab-id-' . esc_attr( (string) $id ) . '" data-grs-pop-tab="' . esc_attr( (string) $id ) . '" aria-controls="' . esc_attr( $dom_id ) . '" aria-expanded="false" hidden style="' . esc_attr( $style ) . '">' . esc_html( $label ) . '</button>';
		}

		if ( self::uses_reopen( $config ) ) {
			$html .= self::reopen_button( $id, $config );
		}

		return $html;
	}

	/**
	 * Il popup mostra la linguetta per riaprire (solo barre e slide-in flottanti)?
	 *
	 * @param array<string, mixed> $config Configurazione.
	 * @return bool
	 */
	public static function uses_reopen( array $config ) {
		return 'inline' !== $config['placement'] && ! empty( $config['close']['reopen']['enabled'] ) && ( self::uses_format( $config, 'bar' ) || self::uses_format( $config, 'slidein' ) );
	}

	/**
	 * Linguetta per riaprire il popup dopo la chiusura (nascosta: la mostra lo script).
	 *
	 * @param int                  $id     ID del popup.
	 * @param array<string, mixed> $config Configurazione.
	 * @return string
	 */
	public static function reopen_button( $id, array $config ) {
		$ro    = $config['close']['reopen'];
		$d     = $config['design'];
		$label = '' !== $ro['label'] ? self::t( $ro['label'], $id, 'reopen_label' ) : __( 'Reopen', 'grs-pop' );
		$vars  = array(
			'--gp-ro-bg:' . self::css_color( '' !== $ro['bg'] ? $ro['bg'] : $d['bg'] ),
			'--gp-ro-txt:' . self::css_color( '' !== $ro['text'] ? $ro['text'] : $d['text'] ),
		);
		if ( $ro['container'] > 0 ) {
			$vars[] = '--gp-ro-cw:' . (int) $ro['container'] . 'px';
		}
		$classes = 'grs-pop-reopen grs-pop-reopen-id-' . (int) $id . ( $ro['container'] > 0 ? ' grs-pop-reopen--contained' : '' );
		if ( '' !== $config['advanced']['css_class'] ) {
			foreach ( explode( ' ', $config['advanced']['css_class'] ) as $extra ) {
				$classes .= ' ' . $extra . '-reopen'; // Es. "promo" → "promo-reopen": stili del tema senza toccare il popup.
			}
		}
		/* translators: %s: text of the tab that reopens the popup. */
		$aria  = sprintf( __( 'Reopen: %s', 'grs-pop' ), $label );
		$html  = '<button type="button" class="' . esc_attr( $classes ) . '" data-grs-pop-reopen="' . esc_attr( (string) (int) $id ) . '" aria-controls="grs-pop-' . esc_attr( (string) (int) $id ) . '" aria-label="' . esc_attr( $aria ) . '" hidden style="' . esc_attr( implode( ';', $vars ) ) . '">';
		$html .= 'none' !== $ro['icon'] ? self::icon( $ro['icon'], 'grs-pop-reopen__icon' ) : '';
		$html .= '<span class="grs-pop-reopen__label">' . esc_html( $label ) . '</span>';
		$html .= self::icon( 'chevron', 'grs-pop-reopen__chev' );
		$html .= '</button>';
		return $html;
	}

	/**
	 * Il popup usa un formato su almeno un dispositivo attivo?
	 *
	 * @param array<string, mixed> $config Configurazione.
	 * @param string               $format Formato.
	 * @return bool
	 */
	public static function uses_format( array $config, $format ) {
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( $config['display'][ $device ]['enabled'] && $format === $config['display'][ $device ]['format'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Variabili CSS del popup.
	 *
	 * Nota: nessun nome con "border-width" & simili, WP 6.0 li intercetta nei selettori [style*=...].
	 *
	 * @param array<string, mixed> $d Aspetto.
	 * @return string
	 */
	private static function style_vars( array $d ) {
		$vars = array(
			'--gp-bg'       => self::css_color( $d['bg'] ),
			'--gp-text'     => self::css_color( $d['text'] ),
			'--gp-head'     => self::css_color( $d['heading'] ),
			'--gp-btn'      => self::css_color( $d['btn'] ),
			'--gp-btn-txt'  => self::css_color( $d['btn_text'] ),
			'--gp-accent'   => self::css_color( $d['accent'] ),
			'--gp-ov'       => self::css_color( $d['overlay'] ),
			'--gp-ov-o'     => (string) ( $d['overlay_opacity'] / 100 ),
			'--gp-radius'   => (int) $d['radius'] . 'px',
			'--gp-w'        => (int) $d['width'] . 'px',
			'--gp-stroke'   => (int) $d['stroke'] . 'px',
			'--gp-stroke-c' => self::css_color( $d['stroke_color'] ),
			'--gp-close'    => self::css_color( $d['close_color'] ),
			'--gp-dim'      => (string) ( $d['bg_dim'] / 100 ),
			'--gp-fs'       => (string) ( $d['font_scale'] / 100 ),
		);
		$out  = array();
		foreach ( $vars as $name => $value ) {
			if ( '' !== $value ) {
				$out[] = $name . ':' . $value;
			}
		}
		return implode( ';', $out );
	}

	/**
	 * Una sezione.
	 *
	 * @param int                  $id         ID del popup.
	 * @param array<string, mixed> $s          Sezione.
	 * @param string               $heading_id ID da assegnare al titolo (solo il primo).
	 * @return string
	 */
	private static function section( $id, array $s, $heading_id ) {
		$sid   = $s['id'];
		$inner = '';
		switch ( $s['type'] ) {
			case 'heading':
				if ( '' === $s['text'] ) {
					return '';
				}
				$tag   = in_array( $s['tag'], array( 'h2', 'h3', 'p' ), true ) ? $s['tag'] : 'h2';
				$color = '' !== $s['color'] ? ' style="color:' . esc_attr( self::css_color( $s['color'] ) ) . '"' : '';
				$inner = '<' . $tag . ( '' !== $heading_id ? ' id="' . esc_attr( $heading_id ) . '"' : '' ) . ' class="grs-pop__heading grs-pop__heading--' . esc_attr( $s['size'] ) . '"' . $color . '>' . wp_kses( self::t( $s['text'], $id, $sid . '_text' ), Schema::allowed_html() ) . '</' . $tag . '>';
				break;

			case 'text':
				if ( '' === $s['text'] ) {
					return '';
				}
				$color = '' !== $s['color'] ? ' style="color:' . esc_attr( self::css_color( $s['color'] ) ) . '"' : '';
				$inner = '<div class="grs-pop__text grs-pop__text--' . esc_attr( $s['size'] ) . '"' . $color . '>' . wp_kses( nl2br( self::t( $s['text'], $id, $sid . '_text' ) ), Schema::allowed_html() ) . '</div>';
				break;

			case 'button':
				$label = self::t( $s['label'], $id, $sid . '_label' );
				if ( '' === $label ) {
					return '';
				}
				$classes = 'grs-pop__btn grs-pop__btn--' . $s['style'] . ( $s['full'] ? ' grs-pop__btn--full' : '' );
				$content = ( 'none' !== $s['icon'] ? self::icon( $s['icon'] ) : '' ) . '<span>' . esc_html( $label ) . '</span>';
				if ( 'close' === $s['action'] ) {
					$inner = '<button type="button" class="' . esc_attr( $classes ) . '" data-grs-pop-close="button">' . $content . '</button>';
				} else {
					$url = self::t( $s['url'], $id, $sid . '_url' );
					if ( '' === $url ) {
						$url = '#';
					}
					$attrs = ' href="' . esc_url( $url, array( 'http', 'https', 'mailto', 'tel', 'sms' ) ) . '"';
					if ( $s['new_tab'] ) {
						$attrs .= ' target="_blank" rel="noopener"';
					}
					if ( $s['convert'] ) {
						$attrs .= ' data-grs-pop-convert="click"';
					}
					$inner = '<a class="' . esc_attr( $classes ) . '"' . $attrs . '>' . $content . '</a>';
				}
				break;

			case 'form':
				if ( $s['form_id'] <= 0 || ! shortcode_exists( 'grs_form' ) ) {
					return '';
				}
				$inner = do_shortcode( '[grs_form id="' . (int) $s['form_id'] . '"]' );
				break;

			case 'coupon':
				if ( '' === $s['code'] ) {
					return '';
				}
				$label  = '' !== $s['label'] ? self::t( $s['label'], $id, $sid . '_label' ) : __( 'Copy', 'grs-pop' );
				$copied = '' !== $s['copied'] ? self::t( $s['copied'], $id, $sid . '_copied' ) : __( 'Copied!', 'grs-pop' );
				$inner  = '<div class="grs-pop__coupon"><code class="grs-pop__code">' . esc_html( $s['code'] ) . '</code><button type="button" class="grs-pop__copy" data-grs-pop-copy="' . esc_attr( $s['code'] ) . '" data-copied="' . esc_attr( $copied ) . '">' . esc_html( $label ) . '</button><span class="grs-pop__sr" aria-live="polite"></span></div>';
				break;

			case 'countdown':
				$data = '';
				if ( 'evergreen' === $s['mode'] ) {
					$data = ' data-minutes="' . esc_attr( (string) $s['minutes'] ) . '"';
				} else {
					$end = self::local_to_ms( $s['end'] );
					if ( ! $end ) {
						return '';
					}
					$data = ' data-end="' . esc_attr( (string) $end ) . '"';
				}
				$units = array(
					'd' => '' !== $s['l_days'] ? self::t( $s['l_days'], $id, $sid . '_l_days' ) : __( 'Days', 'grs-pop' ),
					'h' => '' !== $s['l_hours'] ? self::t( $s['l_hours'], $id, $sid . '_l_hours' ) : __( 'Hours', 'grs-pop' ),
					'm' => '' !== $s['l_min'] ? self::t( $s['l_min'], $id, $sid . '_l_min' ) : __( 'Minutes', 'grs-pop' ),
					's' => '' !== $s['l_sec'] ? self::t( $s['l_sec'], $id, $sid . '_l_sec' ) : __( 'Seconds', 'grs-pop' ),
				);
				$inner = '<div class="grs-pop__countdown" data-grs-pop-countdown' . $data . ' data-expire="' . esc_attr( $s['expire'] ) . '" role="timer">';
				foreach ( $units as $unit => $label ) {
					$inner .= '<span class="grs-pop__cd-unit"><span class="grs-pop__cd-num" data-u="' . esc_attr( $unit ) . '">00</span><span class="grs-pop__cd-lbl">' . esc_html( $label ) . '</span></span>';
				}
				$inner .= '</div>';
				break;

			case 'list':
				if ( empty( $s['items'] ) ) {
					return '';
				}
				$icon  = 'dot' === $s['icon'] ? '' : self::icon( 'arrow' === $s['icon'] ? 'arrow' : $s['icon'], 'grs-pop__list-icon' );
				$inner = '<ul class="grs-pop__list grs-pop__list--' . esc_attr( $s['icon'] ) . '">';
				foreach ( $s['items'] as $n => $item ) {
					$inner .= '<li>' . $icon . '<span>' . wp_kses( self::t( $item, $id, $sid . '_item_' . $n ), Schema::allowed_html() ) . '</span></li>';
				}
				$inner .= '</ul>';
				break;

			case 'spacer':
				$inner = '<div class="grs-pop__spacer grs-pop__spacer--' . esc_attr( $s['size'] ) . ( $s['line'] ? ' grs-pop__spacer--line' : '' ) . '" aria-hidden="true"></div>';
				break;

			case 'dismiss':
				$text  = '' !== $s['text'] ? self::t( $s['text'], $id, $sid . '_text' ) : __( 'No thanks', 'grs-pop' );
				$inner = '<button type="button" class="grs-pop__dismiss" data-grs-pop-close="dismiss">' . esc_html( $text ) . '</button>';
				break;

			case 'shortcode':
				if ( '' === $s['code'] ) {
					return '';
				}
				$inner = do_shortcode( $s['code'] );
				break;
		}

		if ( '' === $inner ) {
			return '';
		}
		$align = 'inherit' !== $s['align'] ? ' grs-pop__sec--align-' . $s['align'] : '';
		return '<div class="grs-pop__sec grs-pop__sec--' . esc_attr( $s['type'] . $align ) . '" data-sid="' . esc_attr( $sid ) . '">' . $inner . '</div>';
	}

	/**
	 * Data e ora locali del sito (`AAAA-MM-GGTHH:MM`) in millisecondi UTC.
	 *
	 * @param string $local Data e ora.
	 * @return int 0 se non valida.
	 */
	public static function local_to_ms( $local ) {
		if ( '' === (string) $local ) {
			return 0;
		}
		try {
			$date = new \DateTime( str_replace( 'T', ' ', (string) $local ), wp_timezone() );
			return $date->getTimestamp() * 1000;
		} catch ( \Exception $e ) {
			return 0;
		}
	}

	/**
	 * Dati per lo script del sito (regole lato browser, trigger, frequenza).
	 *
	 * @param int                  $id        ID.
	 * @param array<string, mixed> $config    Configurazione.
	 * @param string               $title     Titolo interno (nome negli eventi di tracciamento).
	 * @param bool                 $on_demand Solo all'apertura con clic.
	 * @return array<string, mixed>
	 */
	public static function client_data( $id, array $config, $title, $on_demand = false ) {
		$co    = $config['conditions'];
		$devs  = array();
		$short = array(
			'desktop' => 'd',
			'tablet'  => 't',
			'mobile'  => 'm',
		);
		foreach ( $short as $device => $key ) {
			$dev          = $config['display'][ $device ];
			$devs[ $key ] = array(
				'on' => (bool) $dev['enabled'],
				'f'  => $dev['format'],
				'p'  => $dev['position'],
			);
		}
		return array(
			'id'   => (int) $id,
			'name' => (string) $title,
			'in'   => 'inline' === $config['placement'],
			'od'   => (bool) $on_demand,
			'pr'   => (int) $config['advanced']['priority'],
			'dv'   => $devs,
			'bar'  => $config['display']['bar_mode'],
			'bo'   => self::bar_offset_data( $config ),
			'ro'   => self::uses_reopen( $config ) ? array(
				'pos' => $config['close']['reopen']['position'],
				'top' => (int) $config['close']['reopen']['top'],
			) : null,
			'tr'   => $config['triggers'],
			'co'   => array(
				'pv'   => (int) $co['pageviews'],
				'vis'  => $co['visitor'],
				'ref'  => $co['referrer'],
				'par'  => $co['params'],
				'from' => self::local_to_ms( $co['schedule']['start'] ),
				'to'   => self::local_to_ms( $co['schedule']['end'] ),
				'days' => $co['schedule']['days'],
				'hf'   => $co['schedule']['from'],
				'ht'   => $co['schedule']['to'],
				'fm'   => self::forms_rule_data( $config ),
			),
			'fq'   => $config['frequency'],
			'cl'   => array_diff_key( $config['close'], array( 'reopen' => true ) ),
		);
	}

	/**
	 * Spostamento degli elementi fissi per la barra in alto: null se non serve.
	 *
	 * @param array<string, mixed> $config Configurazione.
	 * @return array<string, mixed>|null
	 */
	private static function bar_offset_data( array $config ) {
		$bo = $config['display']['bar_offset'];
		if ( empty( $bo['enabled'] ) || 'fixed' !== $config['display']['bar_mode'] ) {
			return null;
		}
		$top_bar = false;
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			$dev = $config['display'][ $device ];
			if ( $dev['enabled'] && 'bar' === $dev['format'] && 'top' === $dev['position'] ) {
				$top_bar = true;
			}
		}
		if ( ! $top_bar ) {
			return null;
		}
		return array(
			'a' => (bool) $bo['auto'],
			's' => (string) $bo['selectors'],
		);
	}

	/**
	 * Regola sui form per lo script: null se non usata o se GRS Forms non è attivo
	 * (senza GRS Forms nessun form può essere inviato: la regola viene ignorata).
	 *
	 * @param array<string, mixed> $config Configurazione.
	 * @return array<string, mixed>|null
	 */
	private static function forms_rule_data( array $config ) {
		$rule = Schema::forms_rule( $config );
		if ( null === $rule || ! defined( 'GRS_FORMS_VERSION' ) ) {
			return null;
		}
		return array(
			'm'   => 'done' === $rule['mode'] ? 'done' : 'not',
			'ids' => array_map( 'intval', $rule['ids'] ),
			'd'   => (int) $rule['days'],
		);
	}
}
