/**
 * Anteprima dal vivo: riproduce il markup di includes/class-renderer.php.
 * Se cambi classi o struttura nel renderer PHP, aggiorna anche questo file.
 *
 * @package GRSDigital\Pop
 */

import { DATA, cssColor, localToMs } from './utils';
import { __ } from './i18n';
import { useForms, formLabel } from './forms';

const { useState, useEffect, useRef } = wp.element;

const ICONS = {
	close: 'M6 6l12 12M18 6L6 18',
	arrow: 'M5 12h14M13 6l6 6-6 6',
	whatsapp: 'M4 20l1.3-4A8 8 0 1 1 8.4 19L4 20z M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-1.9-1-0.9 0.7a4 4 0 0 1-2.2-2.2l0.7-0.9-1-1.9L9 9.5z',
	phone: 'M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
	mail: 'M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z M3 7l9 6 9-6',
	gift: 'M4 8h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z M12 8v13M5 12v9h14v-9M12 8C10 4 7 4 7 6s3 2 5 2c2 0 5 0 5-2s-3-2-5 2',
	download: 'M12 4v11M7 10l5 5 5-5M5 20h14',
	calendar: 'M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z M3 10h18M8 3v4M16 3v4',
	cart: 'M2 3h3l2.5 12h11L21 7H6.2 M9 20a1.5 1.5 0 1 0 0.01 0 M18 20a1.5 1.5 0 1 0 0.01 0',
	check: 'M5 12.5l4.5 4.5L19 7.5',
	chevron: 'M6 9l6 6 6-6',
	star: 'M12 3.5l2.6 5.4 5.9 0.8-4.3 4.1 1 5.8L12 16.8l-5.2 2.8 1-5.8-4.3-4.1 5.9-0.8z',
};

export function Icon( { name, className } ) {
	if ( ! ICONS[ name ] ) {
		return null;
	}
	return (
		<svg className={ className || 'grs-pop__icon' } viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">
			<path d={ ICONS[ name ] } />
		</svg>
	);
}

function Rich( { tag, className, html, style, id } ) {
	const Tag = tag || 'div';
	// Il testo è già limitato ai tag ammessi lato server; in anteprima si mostra così com'è scritto.
	return <Tag id={ id } className={ className } style={ style } dangerouslySetInnerHTML={ { __html: html } } />;
}

function nl2br( text ) {
	return String( text || '' ).replace( /\n/g, '<br>' );
}

function cleanHtml( text ) {
	// Anteprima: rimuove script ed eventi inline per sicurezza (il server applica wp_kses).
	return String( text || '' )
		.replace( /<\/?(script|style|iframe|object|embed)[^>]*>/gi, '' )
		.replace( /\son\w+="[^"]*"/gi, '' );
}

function pad( n ) {
	return n < 10 ? '0' + n : String( n );
}

function Countdown( { s } ) {
	const [ now, setNow ] = useState( Date.now() );
	const start = useRef( Date.now() );
	useEffect( () => {
		const t = setInterval( () => setNow( Date.now() ), 1000 );
		return () => clearInterval( t );
	}, [] );
	const end = s.mode === 'evergreen' ? start.current + s.minutes * 60000 : localToMs( s.end );
	if ( ! end ) {
		return <div className="gpb-placeholder">{ __( 'Set the end date of the countdown.', 'grs-pop' ) }</div>;
	}
	const left = Math.max( 0, Math.floor( ( end - now ) / 1000 ) );
	const vals = { d: Math.floor( left / 86400 ), h: Math.floor( ( left % 86400 ) / 3600 ), m: Math.floor( ( left % 3600 ) / 60 ), s: left % 60 };
	const labels = {
		d: s.l_days || __( 'Days', 'grs-pop' ),
		h: s.l_hours || __( 'Hours', 'grs-pop' ),
		m: s.l_min || __( 'Minutes', 'grs-pop' ),
		s: s.l_sec || __( 'Seconds', 'grs-pop' ),
	};
	return (
		<div className="grs-pop__countdown" role="timer">
			{ [ 'd', 'h', 'm', 's' ].map( ( u ) =>
				u === 'd' && vals.d === 0 ? null : (
					<span className="grs-pop__cd-unit" key={ u }>
						<span className="grs-pop__cd-num">{ pad( vals[ u ] ) }</span>
						<span className="grs-pop__cd-lbl">{ labels[ u ] }</span>
					</span>
				)
			) }
		</div>
	);
}

function FormMock( { formId } ) {
	useForms();
	const form = formId && DATA.formsActive ? { label: formLabel( formId ) || '#' + formId } : null;
	if ( ! form ) {
		return <div className="gpb-placeholder">{ DATA.formsActive ? __( 'Choose a GRS Forms form in the section settings.', 'grs-pop' ) : __( 'Install and activate GRS Forms to add a form here.', 'grs-pop' ) }</div>;
	}
	return (
		<div className="gpb-form-mock" aria-hidden="true">
			<span className="gpb-form-mock__name">{ form.label }</span>
			<span className="gpb-form-mock__field"></span>
			<span className="gpb-form-mock__field"></span>
			<span className="grs-pop__btn grs-pop__btn--primary grs-pop__btn--full">
				<span>{ __( 'Send', 'grs-pop' ) }</span>
			</span>
		</div>
	);
}

function Section( { s, headingId, selected, onSelect } ) {
	let inner = null;
	switch ( s.type ) {
		case 'heading': {
			if ( ! s.text ) {
				return null;
			}
			const Tag = s.tag || 'h2';
			inner = <Rich tag={ Tag } id={ headingId || undefined } className={ 'grs-pop__heading grs-pop__heading--' + s.size } style={ s.color ? { color: cssColor( s.color ) } : undefined } html={ cleanHtml( s.text ) } />;
			break;
		}
		case 'text':
			if ( ! s.text ) {
				return null;
			}
			inner = <Rich className={ 'grs-pop__text grs-pop__text--' + s.size } style={ s.color ? { color: cssColor( s.color ) } : undefined } html={ nl2br( cleanHtml( s.text ) ) } />;
			break;
		case 'button': {
			if ( ! s.label ) {
				return null;
			}
			const cls = 'grs-pop__btn grs-pop__btn--' + s.style + ( s.full ? ' grs-pop__btn--full' : '' );
			inner = (
				<span className={ cls }>
					{ s.icon !== 'none' && <Icon name={ s.icon } /> }
					<span>{ s.label }</span>
				</span>
			);
			break;
		}
		case 'form':
			inner = <FormMock formId={ s.form_id } />;
			break;
		case 'coupon':
			if ( ! s.code ) {
				return null;
			}
			inner = (
				<div className="grs-pop__coupon">
					<code className="grs-pop__code">{ s.code }</code>
					<span className="grs-pop__copy">{ s.label || __( 'Copy', 'grs-pop' ) }</span>
				</div>
			);
			break;
		case 'countdown':
			inner = <Countdown s={ s } />;
			break;
		case 'list':
			if ( ! s.items.length ) {
				return null;
			}
			inner = (
				<ul className={ 'grs-pop__list grs-pop__list--' + s.icon }>
					{ s.items.map( ( item, n ) => (
						<li key={ n }>
							{ s.icon !== 'dot' && <Icon name={ s.icon } className="grs-pop__list-icon" /> }
							<Rich tag="span" html={ cleanHtml( item ) } />
						</li>
					) ) }
				</ul>
			);
			break;
		case 'spacer':
			inner = <div className={ 'grs-pop__spacer grs-pop__spacer--' + s.size + ( s.line ? ' grs-pop__spacer--line' : '' ) } aria-hidden="true" />;
			break;
		case 'dismiss':
			inner = <span className="grs-pop__dismiss">{ s.text || __( 'No thanks', 'grs-pop' ) }</span>;
			break;
		case 'shortcode':
			inner = <div className="gpb-placeholder">{ s.code || __( 'Shortcode', 'grs-pop' ) }</div>;
			break;
	}
	if ( ! inner ) {
		return null;
	}
	const align = s.align !== 'inherit' ? ' grs-pop__sec--align-' + s.align : '';
	return (
		<div
			className={ 'grs-pop__sec grs-pop__sec--' + s.type + align + ( selected ? ' gpb-is-selected' : '' ) }
			data-sid={ s.id }
			onClick={ onSelect ? ( e ) => {
				e.preventDefault();
				e.stopPropagation();
				onSelect( s.id );
			} : undefined }
		>
			{ inner }
		</div>
	);
}

function styleVars( d ) {
	return {
		'--gp-bg': cssColor( d.bg ),
		'--gp-text': cssColor( d.text ),
		'--gp-head': cssColor( d.heading ),
		'--gp-btn': cssColor( d.btn ),
		'--gp-btn-txt': cssColor( d.btn_text ),
		'--gp-accent': cssColor( d.accent ),
		'--gp-ov': cssColor( d.overlay ),
		'--gp-ov-o': String( d.overlay_opacity / 100 ),
		'--gp-radius': d.radius + 'px',
		'--gp-w': d.width + 'px',
		'--gp-stroke': d.stroke + 'px',
		'--gp-stroke-c': cssColor( d.stroke_color ),
		'--gp-close': cssColor( d.close_color ),
		'--gp-dim': String( d.bg_dim / 100 ),
		'--gp-fs': String( d.font_scale / 100 ),
	};
}

/**
 * Il popup (markup uguale a quello del sito).
 */
export function PopupMarkup( { config, device, selected, onSelect, title } ) {
	const d = config.design;
	const inline = config.placement === 'inline';
	const conf = inline ? null : config.display[ device ];
	const hasImage = d.image && d.image.url && d.layout !== 'none';
	const classes = [
		'grs-pop',
		inline ? 'grs-pop--inline' : 'grs-pop--floating',
		'grs-pop--layout-' + ( hasImage ? d.layout : 'none' ),
		'grs-pop--pad-' + d.padding,
		'grs-pop--align-' + d.align,
		'grs-pop--shadow-' + d.shadow,
		'grs-pop--anim-' + d.anim,
		'is-open',
	];
	if ( ! d.image_mobile ) {
		classes.push( 'grs-pop--no-img-mobile' );
	}
	if ( conf ) {
		classes.push( 'grs-pop--fmt-' + conf.format, 'grs-pop--pos-' + conf.position );
		if ( conf.format === 'bar' && config.display.bar_mode === 'push' ) {
			classes.push( 'grs-pop--bar-push' );
		}
	}
	let headingId = null;
	const vars = styleVars( d );
	return (
		<>
			<div className={ classes.join( ' ' ) } style={ vars } role={ inline ? 'region' : 'dialog' } aria-label={ title || undefined }>
				{ ! inline && <div className="grs-pop__overlay" /> }
				<div className="grs-pop__box">
					{ config.close.button && (
						<span className="grs-pop__close" aria-hidden="true">
							<Icon name="close" className="grs-pop__close-icon" />
						</span>
					) }
					{ hasImage && (
						<div className="grs-pop__media">
							<img src={ d.image.url } alt={ d.image.alt || '' } />
						</div>
					) }
					<div className="grs-pop__body">
						{ config.sections.map( ( s ) => {
							let hid = null;
							if ( s.type === 'heading' && s.text && ! headingId ) {
								headingId = 'gpb-preview-t';
								hid = headingId;
							}
							return <Section key={ s.id } s={ s } headingId={ hid } selected={ selected === s.id } onSelect={ onSelect } />;
						} ) }
						{ config.sections.length === 0 && <div className="gpb-placeholder">{ __( 'Add content from the Content panel.', 'grs-pop' ) }</div> }
					</div>
				</div>
			</div>
			{ conf && conf.format === 'tab' && (
				<span className={ 'grs-pop-tab is-visible is-active grs-pop-tab--pos-' + conf.position } style={ vars }>
					{ config.display.tab_label || __( 'Open', 'grs-pop' ) }
				</span>
			) }
		</>
	);
}

const FRAMES = {
	desktop: { w: 1280, h: 760 },
	tablet: { w: 820, h: 1000 },
	mobile: { w: 390, h: 760 },
};

/**
 * Finta pagina del sito.
 */
function FakePage( { children, inline } ) {
	return (
		<div className="gpb-page" aria-hidden="true">
			<div className="gpb-page__header">
				<span className="gpb-page__logo">{ DATA.siteName || 'Site' }</span>
				<span className="gpb-page__nav"></span>
			</div>
			<div className="gpb-page__content">
				<span className="gpb-page__h"></span>
				<span className="gpb-page__p"></span>
				<span className="gpb-page__p gpb-page__p--short"></span>
				{ inline && <div className="gpb-page__inline">{ children }</div> }
				<span className="gpb-page__p"></span>
				<span className="gpb-page__p"></span>
				<span className="gpb-page__p gpb-page__p--short"></span>
			</div>
		</div>
	);
}

export function Preview( { config, device, selected, onSelect, title } ) {
	const wrap = useRef( null );
	const [ scale, setScale ] = useState( 1 );
	const frame = FRAMES[ device ];
	useEffect( () => {
		const update = () => {
			if ( ! wrap.current ) {
				return;
			}
			const w = wrap.current.clientWidth - 32;
			const h = Math.max( 420, window.innerHeight - 220 );
			setScale( Math.min( 1, w / frame.w, h / frame.h ) );
		};
		update();
		window.addEventListener( 'resize', update );
		return () => window.removeEventListener( 'resize', update );
	}, [ device ] );

	const inline = config.placement === 'inline';
	const conf = inline ? null : config.display[ device ];
	const popup = <PopupMarkup config={ config } device={ device } selected={ selected } onSelect={ onSelect } title={ title } />;

	return (
		<div className="gpb-preview" ref={ wrap }>
			<div className="gpb-preview__stage" style={ { width: frame.w * scale, height: frame.h * scale } }>
				<div className={ 'gpb-frame gpb-frame--' + device } style={ { width: frame.w, height: frame.h, transform: 'scale(' + scale + ')' } }>
					<FakePage inline={ inline }>{ inline && popup }</FakePage>
					{ ! inline && conf && conf.enabled && popup }
					{ ! inline && conf && ! conf.enabled && (
						<div className="gpb-frame__off">{ __( 'Not shown on this device', 'grs-pop' ) }</div>
					) }
				</div>
			</div>
		</div>
	);
}
