/**
 * Galleria dei modelli.
 *
 * @package GRSDigital\Pop
 */

import { DATA } from './utils';
import { PopupMarkup } from './preview';
import { formatLabels } from './labels';
import { __ } from './i18n';

const { useState } = wp.element;

function Thumb( { preset } ) {
	const config = preset.config;
	const inline = config.placement === 'inline';
	const fmt = inline ? 'inline' : config.display.desktop.format;
	return (
		<div className={ 'gpb-thumb gpb-thumb--' + fmt } aria-hidden="true">
			<div className="gpb-thumb__frame">
				<div className="gpb-thumb__page">
					<span></span>
					<span></span>
					<span></span>
				</div>
				<div className={ inline ? 'gpb-thumb__inline' : 'gpb-thumb__float' }>
					<PopupMarkup config={ config } device="desktop" />
				</div>
			</div>
		</div>
	);
}

export function Gallery( { onPick, onClose, canClose } ) {
	const [ cat, setCat ] = useState( 'all' );
	const [ fmt, setFmt ] = useState( 'all' );
	const fl = formatLabels();
	const cats = DATA.categories || {};
	const presets = ( DATA.presets || [] ).filter( ( p ) => ( cat === 'all' || p.cat === cat ) && ( fmt === 'all' || p.format === fmt ) );
	const formats = [ 'modal', 'slidein', 'bar', 'fullscreen', 'tab', 'inline' ];

	return (
		<div className="gpb-gallery" role="region" aria-label={ __( 'Templates', 'grs-pop' ) }>
			<div className="gpb-gallery__head">
				<div>
					<h2>{ __( 'Choose a template', 'grs-pop' ) }</h2>
					<p>{ __( 'Every template is already optimised for mobile. You can change texts, colours, format and rules afterwards.', 'grs-pop' ) }</p>
				</div>
				{ canClose && (
					<button type="button" className="button" onClick={ onClose }>
						{ __( 'Back to the editor', 'grs-pop' ) }
					</button>
				) }
			</div>
			<div className="gpb-gallery__filters">
				<div className="gpb-filter" role="group" aria-label={ __( 'Goal', 'grs-pop' ) }>
					<button type="button" className={ 'gpb-pill' + ( cat === 'all' ? ' is-active' : '' ) } aria-pressed={ cat === 'all' } onClick={ () => setCat( 'all' ) }>
						{ __( 'All goals', 'grs-pop' ) }
					</button>
					{ Object.keys( cats ).map( ( key ) => (
						<button type="button" key={ key } className={ 'gpb-pill' + ( cat === key ? ' is-active' : '' ) } aria-pressed={ cat === key } onClick={ () => setCat( key ) }>
							{ cats[ key ] }
						</button>
					) ) }
				</div>
				<div className="gpb-filter" role="group" aria-label={ __( 'Format', 'grs-pop' ) }>
					<button type="button" className={ 'gpb-pill' + ( fmt === 'all' ? ' is-active' : '' ) } aria-pressed={ fmt === 'all' } onClick={ () => setFmt( 'all' ) }>
						{ __( 'All formats', 'grs-pop' ) }
					</button>
					{ formats.map( ( key ) => (
						<button type="button" key={ key } className={ 'gpb-pill' + ( fmt === key ? ' is-active' : '' ) } aria-pressed={ fmt === key } onClick={ () => setFmt( key ) }>
							{ fl[ key ] }
						</button>
					) ) }
				</div>
			</div>
			<ul className="gpb-gallery__grid">
				{ presets.map( ( p ) => (
					<li key={ p.id }>
						<button type="button" className="gpb-card" onClick={ () => onPick( p ) }>
							<Thumb preset={ p } />
							<span className="gpb-card__name">{ p.name }</span>
							<span className="gpb-card__meta">
								{ fl[ p.format ] }
								{ p.format !== 'inline' && p.config.display.mobile.format !== p.config.display.desktop.format ? ' · ' + __( 'mobile:', 'grs-pop' ) + ' ' + fl[ p.config.display.mobile.format ] : '' }
							</span>
						</button>
					</li>
				) ) }
				{ presets.length === 0 && <li className="gpb-gallery__empty">{ __( 'No templates with these filters.', 'grs-pop' ) }</li> }
			</ul>
		</div>
	);
}
