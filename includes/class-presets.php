<?php
/**
 * Modelli pronti.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop;

defined( 'ABSPATH' ) || exit;

/**
 * Modelli di partenza: struttura completa di popup, con testi traducibili e colori della palette.
 *
 * I colori usano la palette delle impostazioni (`var:brand`...): cambiando la palette
 * tutti i modelli prendono i colori del sito.
 */
final class Presets {

	/**
	 * Contatore per gli ID delle sezioni.
	 *
	 * @var int
	 */
	private static $n = 0;

	/**
	 * Categorie.
	 *
	 * @return array<string, string>
	 */
	public static function categories() {
		return array(
			'lead'    => __( 'Newsletter & leads', 'grs-pop' ),
			'offer'   => __( 'Discounts & sales', 'grs-pop' ),
			'info'    => __( 'Announcements', 'grs-pop' ),
			'contact' => __( 'Contact', 'grs-pop' ),
		);
	}

	/**
	 * Sezione con ID stabile nel modello.
	 *
	 * @param string               $type  Tipo.
	 * @param array<string, mixed> $props Proprietà.
	 * @return array<string, mixed>
	 */
	private static function s( $type, array $props = array() ) {
		++self::$n;
		$section       = array_merge( Schema::section_defaults( $type ), $props );
		$section['id'] = 's' . self::$n;
		return $section;
	}

	/**
	 * Schemi di colore.
	 *
	 * @param string $scheme light, dark, brand, soft.
	 * @return array<string, string>
	 */
	private static function scheme( $scheme ) {
		switch ( $scheme ) {
			case 'dark':
				return array(
					'bg'          => 'var:ink',
					'text'        => '#d1d5db',
					'heading'     => '#ffffff',
					'btn'         => 'var:brand',
					'btn_text'    => 'var:brand_text',
					'accent'      => '#1f2937',
					'close_color' => '#9ca3af',
				);
			case 'brand':
				return array(
					'bg'          => 'var:brand',
					'text'        => 'var:brand_text',
					'heading'     => 'var:brand_text',
					'btn'         => 'var:surface',
					'btn_text'    => 'var:ink',
					'accent'      => 'transparent',
					'close_color' => 'var:brand_text',
				);
			case 'soft':
				return array(
					'bg'          => 'var:soft',
					'text'        => 'var:ink',
					'heading'     => 'var:ink',
					'btn'         => 'var:brand',
					'btn_text'    => 'var:brand_text',
					'accent'      => 'var:surface',
					'close_color' => 'var:muted',
				);
		}
		return array(
			'bg'          => 'var:surface',
			'text'        => 'var:muted',
			'heading'     => 'var:ink',
			'btn'         => 'var:brand',
			'btn_text'    => 'var:brand_text',
			'accent'      => 'var:soft',
			'close_color' => 'var:muted',
		);
	}

	/**
	 * Visualizzazione per dispositivo.
	 *
	 * @param string $desktop Formato desktop (e tablet).
	 * @param string $pos     Posizione desktop.
	 * @param string $mobile  Formato mobile.
	 * @param string $mpos    Posizione mobile.
	 * @return array<string, mixed>
	 */
	private static function display( $desktop, $pos, $mobile, $mpos ) {
		return array(
			'desktop' => array(
				'enabled'  => true,
				'format'   => $desktop,
				'position' => $pos,
			),
			'tablet'  => array(
				'enabled'  => true,
				'format'   => $desktop,
				'position' => $pos,
			),
			'mobile'  => array(
				'enabled'  => true,
				'format'   => $mobile,
				'position' => $mpos,
			),
		);
	}

	/**
	 * Unione ricorsiva (i valori di $b vincono; le liste vengono sostituite).
	 *
	 * @param array<string, mixed> $a Base.
	 * @param array<string, mixed> $b Modifiche.
	 * @return array<string, mixed>
	 */
	public static function merge( array $a, array $b ) {
		foreach ( $b as $key => $value ) {
			if ( is_array( $value ) && isset( $a[ $key ] ) && is_array( $a[ $key ] ) && ! wp_is_numeric_array( $value ) && ! wp_is_numeric_array( $a[ $key ] ) ) {
				$a[ $key ] = self::merge( $a[ $key ], $value );
			} else {
				$a[ $key ] = $value;
			}
		}
		return $a;
	}

	/**
	 * Data e ora locali fra N giorni (per i conti alla rovescia di esempio).
	 *
	 * @param int $days Giorni.
	 * @return string
	 */
	private static function in_days( $days ) {
		return wp_date( 'Y-m-d\T23:59', time() + (int) $days * DAY_IN_SECONDS );
	}

	/**
	 * Elenco dei modelli.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		self::$n = 0;
		$list    = array();

		$no_thanks = __( 'No thanks', 'grs-pop' );

		// --- Finestre al centro (modal) ---------------------------------------------------------

		$list[] = self::preset(
			'newsletter-classic',
			'lead',
			__( 'Newsletter: classic', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'light' ) + array( 'width' => 460 ),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Join our newsletter', 'grs-pop' ),
							'size' => 'lg',
						)
					),
					self::s( 'text', array( 'text' => __( 'One useful email a month: news, tips and offers reserved for subscribers. No spam, unsubscribe anytime.', 'grs-pop' ) ) ),
					self::s( 'form' ),
					self::s( 'dismiss', array( 'text' => $no_thanks ) ),
				),
				'triggers' => array(
					'load'   => array(
						'on'    => false,
						'delay' => 10,
					),
					'scroll' => array(
						'on'      => true,
						'percent' => 50,
					),
				),
			)
		);

		$list[] = self::preset(
			'newsletter-split',
			'lead',
			__( 'Newsletter with image', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'light' ) + array(
					'layout'       => 'left',
					'width'        => 760,
					'align'        => 'left',
					'image_mobile' => false,
				),
				'sections' => array(
					self::s(
						'text',
						array(
							'text' => '<strong>' . __( 'NEWSLETTER', 'grs-pop' ) . '</strong>',
							'size' => 'sm',
						)
					),
					self::s( 'heading', array( 'text' => __( 'Get our best ideas in your inbox', 'grs-pop' ) ) ),
					self::s( 'text', array( 'text' => __( 'Practical guides and early access to new products. Join thousands of readers.', 'grs-pop' ) ) ),
					self::s( 'form' ),
				),
			)
		);

		$list[] = self::preset(
			'discount-welcome',
			'offer',
			__( 'Welcome discount', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'light' ) + array( 'width' => 480 ),
				'sections' => array(
					self::s(
						'text',
						array(
							'text' => '<strong>' . __( 'WELCOME', 'grs-pop' ) . '</strong>',
							'size' => 'sm',
						)
					),
					self::s(
						'heading',
						array(
							'text' => __( 'Get 10% off your first order', 'grs-pop' ),
							'size' => 'xl',
						)
					),
					self::s( 'text', array( 'text' => __( 'Sign up and receive your discount code by email.', 'grs-pop' ) ) ),
					self::s( 'form' ),
					self::s( 'dismiss', array( 'text' => $no_thanks ) ),
				),
			)
		);

		$list[] = self::preset(
			'coupon-reveal',
			'offer',
			__( 'Coupon code', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'brand' ) + array( 'width' => 460 ),
				'sections' => array(
					self::s( 'heading', array( 'text' => __( 'A gift for you', 'grs-pop' ) ) ),
					self::s( 'text', array( 'text' => __( 'Use this code at checkout to save 15% on your order.', 'grs-pop' ) ) ),
					self::s( 'coupon', array( 'code' => 'WELCOME15' ) ),
					self::s(
						'button',
						array(
							'label' => __( 'Shop now', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'icon'  => 'cart',
						)
					),
				),
			)
		);

		$list[] = self::preset(
			'lead-magnet',
			'lead',
			__( 'Free guide (lead magnet)', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'light' ) + array(
					'width' => 520,
					'align' => 'left',
				),
				'sections' => array(
					self::s( 'heading', array( 'text' => __( 'Download the free guide', 'grs-pop' ) ) ),
					self::s(
						'list',
						array(
							'items' => array(
								__( 'The 7 mistakes to avoid', 'grs-pop' ),
								__( 'A ready-to-use checklist', 'grs-pop' ),
								__( 'Real examples you can copy', 'grs-pop' ),
							),
						)
					),
					self::s( 'form' ),
				),
				'triggers' => array(
					'load'   => array( 'on' => false ),
					'scroll' => array(
						'on'      => true,
						'percent' => 40,
					),
				),
			)
		);

		$list[] = self::preset(
			'exit-wait',
			'offer',
			__( 'Exit intent: wait!', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'dark' ) + array( 'width' => 480 ),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Wait, before you go…', 'grs-pop' ),
							'size' => 'xl',
						)
					),
					self::s( 'text', array( 'text' => __( 'Free shipping on your first order if you complete it today.', 'grs-pop' ) ) ),
					self::s(
						'button',
						array(
							'label' => __( 'Continue shopping', 'grs-pop' ),
							'url'   => home_url( '/' ),
						)
					),
					self::s( 'dismiss', array( 'text' => __( 'Maybe later', 'grs-pop' ) ) ),
				),
				'triggers' => array(
					'load' => array( 'on' => false ),
					'exit' => array(
						'on'     => true,
						'mobile' => 'scroll_up',
					),
				),
			)
		);

		$list[] = self::preset(
			'consultation',
			'contact',
			__( 'Free consultation', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'soft' ) + array( 'width' => 500 ),
				'sections' => array(
					self::s( 'heading', array( 'text' => __( 'Book a free 20-minute call', 'grs-pop' ) ) ),
					self::s( 'text', array( 'text' => __( 'Tell us about your project: we will answer your questions and suggest the next steps, with no obligation.', 'grs-pop' ) ) ),
					self::s(
						'button',
						array(
							'label' => __( 'Choose a date', 'grs-pop' ),
							'url'   => home_url( '/contact/' ),
							'icon'  => 'calendar',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Call us', 'grs-pop' ),
							'url'   => 'tel:+390000000000',
							'style' => 'outline',
							'icon'  => 'phone',
						)
					),
				),
				'triggers' => array(
					'load' => array(
						'on'    => true,
						'delay' => 20,
					),
				),
			)
		);

		$list[] = self::preset(
			'event-countdown',
			'info',
			__( 'Event or webinar', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'dark' ) + array( 'width' => 520 ),
				'sections' => array(
					self::s(
						'text',
						array(
							'text' => '<strong>' . __( 'LIVE WEBINAR', 'grs-pop' ) . '</strong>',
							'size' => 'sm',
						)
					),
					self::s( 'heading', array( 'text' => __( 'How to double your results in 90 days', 'grs-pop' ) ) ),
					self::s(
						'countdown',
						array(
							'mode' => 'date',
							'end'  => self::in_days( 14 ),
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Save my seat', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'icon'  => 'arrow',
						)
					),
				),
			)
		);

		$list[] = self::preset(
			'flash-sale',
			'offer',
			__( 'Flash sale', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'brand' ) + array( 'width' => 500 ),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Flash sale: up to 40% off', 'grs-pop' ),
							'size' => 'xl',
						)
					),
					self::s( 'text', array( 'text' => __( 'Only for a few hours, while stocks last.', 'grs-pop' ) ) ),
					self::s(
						'countdown',
						array(
							'mode'    => 'evergreen',
							'minutes' => 120,
							'expire'  => 'hide_popup',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'See the offers', 'grs-pop' ),
							'url'   => home_url( '/' ),
						)
					),
				),
			)
		);

		$list[] = self::preset(
			'announcement',
			'info',
			__( 'News announcement', 'grs-pop' ),
			array(
				'display'   => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'    => self::scheme( 'light' ) + array(
					'width'  => 440,
					'layout' => 'stack',
				),
				'sections'  => array(
					self::s(
						'text',
						array(
							'text' => '<strong>' . __( 'NEW', 'grs-pop' ) . '</strong>',
							'size' => 'sm',
						)
					),
					self::s( 'heading', array( 'text' => __( 'Our new collection is here', 'grs-pop' ) ) ),
					self::s( 'text', array( 'text' => __( 'Discover the new products designed with you in mind.', 'grs-pop' ) ) ),
					self::s(
						'button',
						array(
							'label' => __( 'Discover more', 'grs-pop' ),
							'url'   => home_url( '/' ),
						)
					),
				),
				'frequency' => array( 'mode' => 'once' ),
			)
		);

		$list[] = self::preset(
			'contact-whatsapp',
			'contact',
			__( 'Questions? Contact us', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'light' ) + array( 'width' => 440 ),
				'sections' => array(
					self::s( 'heading', array( 'text' => __( 'Need help choosing?', 'grs-pop' ) ) ),
					self::s( 'text', array( 'text' => __( 'Write to us: a real person will answer within a few minutes during office hours.', 'grs-pop' ) ) ),
					self::s(
						'button',
						array(
							'label'   => __( 'Chat on WhatsApp', 'grs-pop' ),
							'url'     => 'https://wa.me/390000000000',
							'new_tab' => true,
							'icon'    => 'whatsapp',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Send an email', 'grs-pop' ),
							'url'   => 'mailto:info@example.com',
							'style' => 'outline',
							'icon'  => 'mail',
						)
					),
				),
				'triggers' => array(
					'load'       => array( 'on' => false ),
					'inactivity' => array(
						'on'      => true,
						'seconds' => 40,
					),
				),
			)
		);

		$list[] = self::preset(
			'promo-background',
			'offer',
			__( 'Promo with background image', 'grs-pop' ),
			array(
				'display'  => self::display( 'modal', 'center', 'slidein', 'bottom' ),
				'design'   => array(
					'layout'      => 'background',
					'width'       => 560,
					'bg'          => 'var:ink',
					'text'        => '#f3f4f6',
					'heading'     => '#ffffff',
					'btn'         => 'var:brand',
					'btn_text'    => 'var:brand_text',
					'close_color' => '#ffffff',
					'bg_dim'      => 50,
					'padding'     => 'lg',
				),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'The season sale has started', 'grs-pop' ),
							'size' => 'xl',
						)
					),
					self::s( 'text', array( 'text' => __( 'Hundreds of products at special prices, for a limited time.', 'grs-pop' ) ) ),
					self::s(
						'button',
						array(
							'label' => __( 'Shop the sale', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'full'  => false,
						)
					),
				),
			)
		);

		// --- Riquadri laterali (slide-in) --------------------------------------------------------

		$list[] = self::preset(
			'slidein-newsletter',
			'lead',
			__( 'Slide-in: newsletter', 'grs-pop' ),
			array(
				'display'  => self::display( 'slidein', 'bottom-right', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'light' ) + array(
					'width'   => 360,
					'padding' => 'sm',
					'align'   => 'left',
					'anim'    => 'slide',
				),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Enjoying this article?', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s(
						'text',
						array(
							'text' => __( 'Get the next ones by email.', 'grs-pop' ),
							'size' => 'sm',
						)
					),
					self::s( 'form' ),
				),
				'triggers' => array(
					'load'   => array( 'on' => false ),
					'scroll' => array(
						'on'      => true,
						'percent' => 60,
					),
				),
			)
		);

		$list[] = self::preset(
			'slidein-whatsapp',
			'contact',
			__( 'Slide-in: WhatsApp chat', 'grs-pop' ),
			array(
				'display'  => self::display( 'slidein', 'bottom-right', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'light' ) + array(
					'width'    => 340,
					'padding'  => 'sm',
					'align'    => 'left',
					'anim'     => 'slide',
					'btn'      => '#25d366',
					'btn_text' => '#06331a',
				),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Hi! How can we help?', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s(
						'text',
						array(
							'text' => __( 'We usually reply within a few minutes.', 'grs-pop' ),
							'size' => 'sm',
						)
					),
					self::s(
						'button',
						array(
							'label'   => __( 'Start the chat', 'grs-pop' ),
							'url'     => 'https://wa.me/390000000000',
							'new_tab' => true,
							'icon'    => 'whatsapp',
						)
					),
				),
				'triggers' => array(
					'load' => array(
						'on'    => true,
						'delay' => 15,
					),
				),
			)
		);

		$list[] = self::preset(
			'slidein-related',
			'info',
			__( 'Slide-in: read next', 'grs-pop' ),
			array(
				'display'  => self::display( 'slidein', 'bottom-left', 'bar', 'bottom' ),
				'design'   => self::scheme( 'soft' ) + array(
					'width'   => 340,
					'padding' => 'sm',
					'align'   => 'left',
					'anim'    => 'slide',
				),
				'sections' => array(
					self::s(
						'text',
						array(
							'text' => __( 'Read next', 'grs-pop' ),
							'size' => 'sm',
						)
					),
					self::s(
						'heading',
						array(
							'text' => __( 'The complete guide for beginners', 'grs-pop' ),
							'size' => 'sm',
							'tag'  => 'p',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Read the guide', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'style' => 'link',
							'icon'  => 'arrow',
							'full'  => false,
						)
					),
				),
				'triggers' => array(
					'load'   => array( 'on' => false ),
					'scroll' => array(
						'on'      => true,
						'percent' => 70,
					),
				),
			)
		);

		$list[] = self::preset(
			'slidein-coupon',
			'offer',
			__( 'Slide-in: discount code', 'grs-pop' ),
			array(
				'display'  => self::display( 'slidein', 'bottom-right', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'dark' ) + array(
					'width'   => 340,
					'padding' => 'sm',
					'anim'    => 'slide',
				),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( '10% off today', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s( 'coupon', array( 'code' => 'TODAY10' ) ),
				),
			)
		);

		$list[] = self::preset(
			'slidein-callback',
			'contact',
			__( 'Slide-in: request a callback', 'grs-pop' ),
			array(
				'display'  => self::display( 'slidein', 'bottom-right', 'slidein', 'bottom' ),
				'design'   => self::scheme( 'light' ) + array(
					'width'   => 360,
					'padding' => 'sm',
					'align'   => 'left',
					'anim'    => 'slide',
				),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Shall we call you back?', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s(
						'text',
						array(
							'text' => __( 'Leave your number: we will call you within one business day.', 'grs-pop' ),
							'size' => 'sm',
						)
					),
					self::s( 'form' ),
				),
				'triggers' => array(
					'load' => array(
						'on'    => true,
						'delay' => 30,
					),
				),
			)
		);

		// --- Barre (hello bar) -------------------------------------------------------------------

		$bar = array(
			'width'   => 1200,
			'padding' => 'sm',
			'radius'  => 0,
			'shadow'  => 'none',
			'anim'    => 'slide',
		);

		$list[] = self::preset(
			'bar-shipping',
			'offer',
			__( 'Bar: free shipping', 'grs-pop' ),
			array(
				'display'   => self::display( 'bar', 'top', 'bar', 'top' ),
				'design'    => self::scheme( 'dark' ) + $bar,
				'sections'  => array(
					self::s(
						'text',
						array(
							'text' => '<strong>' . __( 'Free shipping', 'grs-pop' ) . '</strong> ' . __( 'on orders over €50', 'grs-pop' ),
							'size' => 'sm',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Shop now', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'full'  => false,
						)
					),
				),
				'triggers'  => array(
					'load' => array(
						'on'    => true,
						'delay' => 0,
					),
				),
				'frequency' => array(
					'mode'             => 'always',
					'after_close_days' => 3,
				),
			)
		);

		$list[] = self::preset(
			'bar-countdown',
			'offer',
			__( 'Bar: sale countdown', 'grs-pop' ),
			array(
				'display'   => self::display( 'bar', 'top', 'bar', 'bottom' ),
				'design'    => self::scheme( 'brand' ) + $bar,
				'sections'  => array(
					self::s(
						'text',
						array(
							'text' => '<strong>' . __( 'The sale ends in', 'grs-pop' ) . '</strong>',
							'size' => 'sm',
						)
					),
					self::s(
						'countdown',
						array(
							'mode'   => 'date',
							'end'    => self::in_days( 3 ),
							'expire' => 'hide_popup',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Shop the sale', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'full'  => false,
						)
					),
				),
				'triggers'  => array(
					'load' => array(
						'on'    => true,
						'delay' => 0,
					),
				),
				'frequency' => array(
					'mode'             => 'always',
					'after_close_days' => 1,
				),
			)
		);

		$list[] = self::preset(
			'bar-announcement',
			'info',
			__( 'Bar: announcement', 'grs-pop' ),
			array(
				'display'       => self::display( 'bar', 'top', 'bar', 'top' ),
				'design'        => self::scheme( 'soft' ) + $bar,
				'sections'      => array(
					self::s(
						'text',
						array(
							'text' => __( 'Our offices will be closed from 10 to 20 August.', 'grs-pop' ),
							'size' => 'sm',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Details', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'style' => 'link',
							'full'  => false,
						)
					),
				),
				'display_extra' => array( 'bar_mode' => 'push' ),
				'triggers'      => array(
					'load' => array(
						'on'    => true,
						'delay' => 0,
					),
				),
				'frequency'     => array(
					'mode'             => 'always',
					'after_close_days' => 30,
				),
			)
		);

		$list[] = self::preset(
			'bar-coupon',
			'offer',
			__( 'Bar: coupon code', 'grs-pop' ),
			array(
				'display'  => self::display( 'bar', 'bottom', 'bar', 'bottom' ),
				'design'   => self::scheme( 'dark' ) + $bar,
				'sections' => array(
					self::s(
						'text',
						array(
							'text' => __( 'Use this code for 10% off:', 'grs-pop' ),
							'size' => 'sm',
						)
					),
					self::s( 'coupon', array( 'code' => 'SAVE10' ) ),
				),
				'triggers' => array(
					'load' => array(
						'on'    => true,
						'delay' => 3,
					),
				),
			)
		);

		$list[] = self::preset(
			'bar-phone',
			'contact',
			__( 'Bar: call us', 'grs-pop' ),
			array(
				'display'   => self::display( 'bar', 'top', 'bar', 'bottom' ),
				'design'    => self::scheme( 'brand' ) + $bar,
				'sections'  => array(
					self::s(
						'text',
						array(
							'text' => __( 'Do you need a quote? Talk to an expert.', 'grs-pop' ),
							'size' => 'sm',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Call now', 'grs-pop' ),
							'url'   => 'tel:+390000000000',
							'icon'  => 'phone',
							'full'  => false,
						)
					),
				),
				'triggers'  => array(
					'load' => array(
						'on'    => true,
						'delay' => 0,
					),
				),
				'frequency' => array(
					'mode'             => 'always',
					'after_close_days' => 7,
				),
			)
		);

		// --- A tutto schermo (welcome mat) ------------------------------------------------------

		$list[] = self::preset(
			'welcome-mat',
			'info',
			__( 'Welcome mat', 'grs-pop' ),
			array(
				'display'   => array(
					'desktop' => array(
						'enabled'  => true,
						'format'   => 'fullscreen',
						'position' => 'center',
					),
					'tablet'  => array(
						'enabled'  => true,
						'format'   => 'fullscreen',
						'position' => 'center',
					),
					'mobile'  => array(
						'enabled'  => false,
						'format'   => 'slidein',
						'position' => 'bottom',
					),
				),
				'design'    => self::scheme( 'brand' ) + array(
					'width'   => 720,
					'padding' => 'lg',
					'anim'    => 'fade',
				),
				'sections'  => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Welcome! Start here', 'grs-pop' ),
							'size' => 'xl',
							'tag'  => 'h2',
						)
					),
					self::s(
						'text',
						array(
							'text' => __( 'Discover in two minutes what we can do for you and how other customers got results.', 'grs-pop' ),
							'size' => 'lg',
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Find out how', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'full'  => false,
							'icon'  => 'arrow',
						)
					),
					self::s( 'dismiss', array( 'text' => __( 'Continue to the site', 'grs-pop' ) ) ),
				),
				'triggers'  => array(
					'load' => array(
						'on'    => true,
						'delay' => 2,
					),
				),
				'frequency' => array( 'mode' => 'once' ),
			)
		);

		$list[] = self::preset(
			'fullscreen-launch',
			'offer',
			__( 'Full screen: launch countdown', 'grs-pop' ),
			array(
				'display'  => array(
					'desktop' => array(
						'enabled'  => true,
						'format'   => 'fullscreen',
						'position' => 'center',
					),
					'tablet'  => array(
						'enabled'  => true,
						'format'   => 'fullscreen',
						'position' => 'center',
					),
					'mobile'  => array(
						'enabled'  => true,
						'format'   => 'slidein',
						'position' => 'bottom',
					),
				),
				'design'   => self::scheme( 'dark' ) + array(
					'width'   => 720,
					'padding' => 'lg',
					'anim'    => 'fade',
				),
				'sections' => array(
					self::s(
						'text',
						array(
							'text' => '<strong>' . __( 'COMING SOON', 'grs-pop' ) . '</strong>',
							'size' => 'sm',
						)
					),
					self::s(
						'heading',
						array(
							'text' => __( 'Something new is about to arrive', 'grs-pop' ),
							'size' => 'xl',
						)
					),
					self::s(
						'countdown',
						array(
							'mode' => 'date',
							'end'  => self::in_days( 7 ),
						)
					),
					self::s( 'form' ),
					self::s( 'dismiss', array( 'text' => __( 'Continue to the site', 'grs-pop' ) ) ),
				),
				'triggers' => array(
					'load' => array(
						'on'    => true,
						'delay' => 3,
					),
				),
			)
		);

		$list[] = self::preset(
			'fullscreen-lead',
			'lead',
			__( 'Full screen: lead form', 'grs-pop' ),
			array(
				'display'  => array(
					'desktop' => array(
						'enabled'  => true,
						'format'   => 'fullscreen',
						'position' => 'center',
					),
					'tablet'  => array(
						'enabled'  => true,
						'format'   => 'fullscreen',
						'position' => 'center',
					),
					'mobile'  => array(
						'enabled'  => true,
						'format'   => 'slidein',
						'position' => 'bottom',
					),
				),
				'design'   => self::scheme( 'light' ) + array(
					'layout'       => 'right',
					'width'        => 900,
					'padding'      => 'lg',
					'align'        => 'left',
					'anim'         => 'fade',
					'image_mobile' => false,
				),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Get a free quote in 24 hours', 'grs-pop' ),
							'size' => 'xl',
						)
					),
					self::s( 'text', array( 'text' => __( 'Tell us what you need: an expert will contact you with a tailored proposal.', 'grs-pop' ) ) ),
					self::s( 'form' ),
				),
				'triggers' => array(
					'load' => array( 'on' => false ),
					'exit' => array(
						'on'     => true,
						'mobile' => 'none',
					),
				),
			)
		);

		// --- Linguetta flottante (tab) ----------------------------------------------------------

		$list[] = self::preset(
			'tab-contact',
			'contact',
			__( 'Floating tab: contact form', 'grs-pop' ),
			array(
				'display'       => self::display( 'tab', 'right', 'tab', 'bottom-right' ),
				'display_extra' => array( 'tab_label' => __( 'Contact us', 'grs-pop' ) ),
				'design'        => self::scheme( 'light' ) + array(
					'width'   => 380,
					'padding' => 'sm',
					'align'   => 'left',
					'anim'    => 'slide',
				),
				'sections'      => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Write to us', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s( 'form' ),
				),
				'triggers'      => array(
					'load' => array(
						'on'    => true,
						'delay' => 0,
					),
				),
				'frequency'     => array(
					'mode'          => 'always',
					'after_convert' => 'keep',
				),
			)
		);

		$list[] = self::preset(
			'tab-offer',
			'offer',
			__( 'Floating tab: offer', 'grs-pop' ),
			array(
				'display'       => self::display( 'tab', 'left', 'tab', 'bottom-left' ),
				'display_extra' => array( 'tab_label' => __( '10% off', 'grs-pop' ) ),
				'design'        => self::scheme( 'brand' ) + array(
					'width'   => 340,
					'padding' => 'sm',
					'anim'    => 'slide',
				),
				'sections'      => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Your discount code', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s( 'coupon', array( 'code' => 'TAB10' ) ),
				),
				'triggers'      => array(
					'load' => array(
						'on'    => true,
						'delay' => 5,
					),
				),
				'frequency'     => array( 'mode' => 'always' ),
			)
		);

		// --- In linea nel contenuto -------------------------------------------------------------

		$inline_base = array(
			'placement' => 'inline',
			'inline'    => array( 'position' => 'after_content' ),
		);

		$list[] = self::preset(
			'inline-newsletter',
			'lead',
			__( 'Inline: newsletter box', 'grs-pop' ),
			$inline_base + array(
				'design'   => self::scheme( 'soft' ) + array(
					'width'  => 760,
					'shadow' => 'none',
					'anim'   => 'none',
				),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Did you like this article?', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s( 'text', array( 'text' => __( 'Subscribe to receive the next ones directly by email.', 'grs-pop' ) ) ),
					self::s( 'form' ),
				),
				'close'    => array( 'button' => false ),
			)
		);

		$list[] = self::preset(
			'inline-cta',
			'info',
			__( 'Inline: call to action', 'grs-pop' ),
			$inline_base + array(
				'design'   => self::scheme( 'dark' ) + array(
					'width'  => 760,
					'shadow' => 'none',
					'anim'   => 'none',
					'align'  => 'left',
				),
				'sections' => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Do you want results like these?', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s( 'text', array( 'text' => __( 'Let\'s talk about your project in a free call.', 'grs-pop' ) ) ),
					self::s(
						'button',
						array(
							'label' => __( 'Book a call', 'grs-pop' ),
							'url'   => home_url( '/contact/' ),
							'full'  => false,
							'icon'  => 'arrow',
						)
					),
				),
				'close'    => array( 'button' => false ),
			)
		);

		$list[] = self::preset(
			'inline-lead-magnet',
			'lead',
			__( 'Inline: free resource', 'grs-pop' ),
			array(
				'placement' => 'inline',
				'inline'    => array(
					'position'  => 'after_paragraph',
					'paragraph' => 4,
				),
				'design'    => self::scheme( 'light' ) + array(
					'layout' => 'left',
					'width'  => 760,
					'shadow' => 'soft',
					'anim'   => 'none',
					'align'  => 'left',
					'stroke' => 1,
				),
				'sections'  => array(
					self::s(
						'heading',
						array(
							'text' => __( 'Free checklist', 'grs-pop' ),
							'size' => 'md',
						)
					),
					self::s(
						'list',
						array(
							'items' => array(
								__( 'Every step in order', 'grs-pop' ),
								__( 'Printable PDF', 'grs-pop' ),
							),
						)
					),
					self::s(
						'button',
						array(
							'label' => __( 'Download it', 'grs-pop' ),
							'url'   => home_url( '/' ),
							'icon'  => 'download',
							'full'  => false,
						)
					),
				),
				'close'     => array( 'button' => false ),
			)
		);

		$list[] = self::preset(
			'blank',
			'info',
			__( 'Blank popup', 'grs-pop' ),
			array(
				'sections' => array(
					self::s( 'heading', array( 'text' => __( 'Your title', 'grs-pop' ) ) ),
					self::s( 'text', array( 'text' => __( 'Write your message here.', 'grs-pop' ) ) ),
					self::s(
						'button',
						array(
							'label' => __( 'Button', 'grs-pop' ),
							'url'   => home_url( '/' ),
						)
					),
				),
			)
		);

		/**
		 * Modelli disponibili nel builder.
		 *
		 * @param array<int, array<string, mixed>> $list Modelli.
		 */
		return (array) apply_filters( 'grs_pop_presets', $list );
	}

	/**
	 * Un modello completo (configurazione ripulita).
	 *
	 * @param string               $id      ID.
	 * @param string               $cat     Categoria.
	 * @param string               $name    Nome.
	 * @param array<string, mixed> $partial Parte di configurazione.
	 * @return array<string, mixed>
	 */
	private static function preset( $id, $cat, $name, array $partial ) {
		if ( isset( $partial['display_extra'] ) ) {
			$partial['display'] = array_merge( isset( $partial['display'] ) ? $partial['display'] : array(), $partial['display_extra'] );
			unset( $partial['display_extra'] );
		}
		$config = self::merge( Schema::new_popup_defaults(), $partial );
		return array(
			'id'     => $id,
			'cat'    => $cat,
			'name'   => $name,
			'format' => 'inline' === $config['placement'] ? 'inline' : $config['display']['desktop']['format'],
			'config' => Schema::sanitize( $config ),
		);
	}
}
