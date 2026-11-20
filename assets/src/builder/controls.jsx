/**
 * Controlli del builder (HTML propri con lo stile GRS, uguali da WP 6.0 in poi).
 *
 * @package GRSDigital\Pop
 */

import { DATA, resolveHex } from './utils';
import { __ } from './i18n';

const { useState, useEffect, useRef } = wp.element;

let uid = 0;
function useId( prefix ) {
	const ref = useRef( null );
	if ( ref.current === null ) {
		uid += 1;
		ref.current = ( prefix || 'gpb' ) + '-' + uid;
	}
	return ref.current;
}

export function Field( { label, help, children, id, inline } ) {
	return (
		<div className={ 'gpb-field' + ( inline ? ' gpb-field--inline' : '' ) }>
			{ label && (
				<label className="gpb-field__label" htmlFor={ id }>
					{ label }
				</label>
			) }
			<div className="gpb-field__control">{ children }</div>
			{ help && <p className="gpb-field__help">{ help }</p> }
		</div>
	);
}

export function TextInput( { label, help, value, onChange, placeholder, type, multiline, rows, maxLength } ) {
	const id = useId( 'gpb-text' );
	return (
		<Field label={ label } help={ help } id={ id }>
			{ multiline ? (
				<textarea
					id={ id }
					className="gpb-input"
					value={ value || '' }
					rows={ rows || 3 }
					maxLength={ maxLength }
					placeholder={ placeholder }
					onChange={ ( e ) => onChange( e.target.value ) }
				/>
			) : (
				<input
					id={ id }
					className="gpb-input"
					type={ type || 'text' }
					value={ value || '' }
					maxLength={ maxLength }
					placeholder={ placeholder }
					onChange={ ( e ) => onChange( e.target.value ) }
				/>
			) }
		</Field>
	);
}

export function NumberInput( { label, help, value, onChange, min, max, step, suffix } ) {
	const id = useId( 'gpb-num' );
	return (
		<Field label={ label } help={ help } id={ id } inline>
			<span className="gpb-number">
				<input
					id={ id }
					className="gpb-input gpb-input--number"
					type="number"
					value={ value }
					min={ min }
					max={ max }
					step={ step || 1 }
					onChange={ ( e ) => onChange( e.target.value === '' ? min || 0 : parseInt( e.target.value, 10 ) ) }
				/>
				{ suffix && <span className="gpb-number__suffix">{ suffix }</span> }
			</span>
		</Field>
	);
}

export function Range( { label, value, onChange, min, max, step, suffix } ) {
	const id = useId( 'gpb-range' );
	return (
		<Field label={ label } id={ id }>
			<span className="gpb-range">
				<input id={ id } type="range" value={ value } min={ min } max={ max } step={ step || 1 } onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) ) } />
				<output htmlFor={ id }>
					{ value }
					{ suffix }
				</output>
			</span>
		</Field>
	);
}

export function Select( { label, help, value, onChange, options } ) {
	const id = useId( 'gpb-select' );
	return (
		<Field label={ label } help={ help } id={ id }>
			<select id={ id } className="gpb-input" value={ value } onChange={ ( e ) => onChange( e.target.value ) }>
				{ options.map( ( o ) => (
					<option key={ o.value } value={ o.value } disabled={ o.disabled }>
						{ o.label }
					</option>
				) ) }
			</select>
		</Field>
	);
}

export function Toggle( { label, help, checked, onChange } ) {
	const id = useId( 'gpb-toggle' );
	return (
		<div className="gpb-field gpb-field--toggle">
			<label className="gpb-toggle" htmlFor={ id }>
				<input id={ id } type="checkbox" checked={ !! checked } onChange={ ( e ) => onChange( e.target.checked ) } />
				<span className="gpb-toggle__track" aria-hidden="true"></span>
				<span className="gpb-toggle__label">{ label }</span>
			</label>
			{ help && <p className="gpb-field__help">{ help }</p> }
		</div>
	);
}

export function Segmented( { label, value, onChange, options } ) {
	const id = useId( 'gpb-seg' );
	return (
		<div className="gpb-field">
			{ label && (
				<span className="gpb-field__label" id={ id }>
					{ label }
				</span>
			) }
			<div className="gpb-seg" role="radiogroup" aria-labelledby={ label ? id : undefined }>
				{ options.map( ( o ) => (
					<button
						type="button"
						key={ o.value }
						role="radio"
						aria-checked={ value === o.value }
						className={ 'gpb-seg__btn' + ( value === o.value ? ' is-active' : '' ) }
						onClick={ () => onChange( o.value ) }
						title={ o.title || undefined }
					>
						{ o.label }
					</button>
				) ) }
			</div>
		</div>
	);
}

/**
 * Colore: palette del sito o colore libero.
 */
export function ColorField( { label, value, onChange, allowEmpty } ) {
	const id = useId( 'gpb-color' );
	const palette = DATA.palette || {};
	const labels = DATA.paletteLabels || {};
	const isVar = value && value.indexOf( 'var:' ) === 0;
	return (
		<div className="gpb-field gpb-color">
			<span className="gpb-field__label" id={ id }>
				{ label }
			</span>
			<div className="gpb-color__row" role="group" aria-labelledby={ id }>
				{ Object.keys( palette ).map( ( key ) => (
					<button
						type="button"
						key={ key }
						className={ 'gpb-swatch' + ( value === 'var:' + key ? ' is-active' : '' ) }
						style={ { background: palette[ key ] } }
						title={ labels[ key ] || key }
						aria-label={ labels[ key ] || key }
						aria-pressed={ value === 'var:' + key }
						onClick={ () => onChange( 'var:' + key ) }
					/>
				) ) }
				<label className={ 'gpb-swatch gpb-swatch--custom' + ( value && ! isVar && value !== 'transparent' ? ' is-active' : '' ) } title={ __( 'Custom colour', 'grs-pop' ) }>
					<input
						type="color"
						value={ resolveHex( value ) }
						onChange={ ( e ) => onChange( e.target.value ) }
						aria-label={ __( 'Custom colour', 'grs-pop' ) }
					/>
				</label>
				{ allowEmpty && (
					<button type="button" className={ 'gpb-swatch gpb-swatch--none' + ( ! value ? ' is-active' : '' ) } title={ __( 'Default', 'grs-pop' ) } aria-label={ __( 'Default', 'grs-pop' ) } onClick={ () => onChange( '' ) } />
				) }
			</div>
		</div>
	);
}

/**
 * Immagine dalla libreria media.
 */
export function MediaField( { label, value, onChange } ) {
	const frame = useRef( null );
	const openMedia = () => {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}
		if ( ! frame.current ) {
			frame.current = window.wp.media( {
				title: __( 'Choose an image', 'grs-pop' ),
				button: { text: __( 'Use this image', 'grs-pop' ) },
				library: { type: 'image' },
				multiple: false,
			} );
		}
		frame.current.off( 'select' );
		frame.current.on( 'select', () => {
			const a = frame.current.state().get( 'selection' ).first().toJSON();
			const size = ( a.sizes && ( a.sizes.large || a.sizes.medium_large || a.sizes.full ) ) || a;
			onChange( { id: a.id, url: size.url || a.url, alt: a.alt || value.alt || '' } );
		} );
		frame.current.open();
	};
	return (
		<div className="gpb-field gpb-media">
			<span className="gpb-field__label">{ label }</span>
			{ value.url ? (
				<div className="gpb-media__preview">
					<img src={ value.url } alt="" />
					<div className="gpb-media__actions">
						<button type="button" className="button" onClick={ openMedia }>
							{ __( 'Replace', 'grs-pop' ) }
						</button>
						<button type="button" className="button grs-ui-button--danger" onClick={ () => onChange( { id: 0, url: '', alt: '' } ) }>
							{ __( 'Remove', 'grs-pop' ) }
						</button>
					</div>
				</div>
			) : (
				<button type="button" className="button gpb-media__add" onClick={ openMedia }>
					{ __( 'Choose an image', 'grs-pop' ) }
				</button>
			) }
		</div>
	);
}

/**
 * Ricerca di contenuti o termini (REST lookup) con elementi scelti.
 */
export function ItemsPicker( { kind, subtype, items, onChange } ) {
	const [ search, setSearch ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ busy, setBusy ] = useState( false );
	useEffect( () => {
		let alive = true;
		const timer = setTimeout( () => {
			setBusy( true );
			wp.apiFetch( {
				path: '/grs-pop/v1/lookup?kind=' + kind + '&subtype=' + encodeURIComponent( subtype || '' ) + '&search=' + encodeURIComponent( search ),
			} )
				.then( ( r ) => {
					if ( alive ) {
						setResults( r || [] );
					}
				} )
				.catch( () => alive && setResults( [] ) )
				.finally( () => alive && setBusy( false ) );
		}, 250 );
		return () => {
			alive = false;
			clearTimeout( timer );
		};
	}, [ kind, subtype, search ] );

	const chosen = items || [];
	return (
		<div className="gpb-picker">
			{ chosen.length > 0 && (
				<ul className="gpb-chips">
					{ chosen.map( ( item ) => (
						<li key={ item.id } className="gpb-chip">
							{ item.label || '#' + item.id }
							<button type="button" aria-label={ __( 'Remove', 'grs-pop' ) } onClick={ () => onChange( chosen.filter( ( i ) => i.id !== item.id ) ) }>
								×
							</button>
						</li>
					) ) }
				</ul>
			) }
			<input
				type="search"
				className="gpb-input"
				placeholder={ __( 'Search…', 'grs-pop' ) }
				value={ search }
				onChange={ ( e ) => setSearch( e.target.value ) }
				aria-label={ __( 'Search', 'grs-pop' ) }
			/>
			<ul className={ 'gpb-picker__results' + ( busy ? ' is-busy' : '' ) }>
				{ results
					.filter( ( r ) => ! chosen.some( ( c ) => c.id === r.id ) )
					.slice( 0, 8 )
					.map( ( r ) => (
						<li key={ r.id }>
							<button type="button" onClick={ () => onChange( chosen.concat( [ r ] ) ) }>
								+ { r.label }
							</button>
						</li>
					) ) }
				{ ! busy && results.length === 0 && <li className="gpb-picker__empty">{ __( 'No results', 'grs-pop' ) }</li> }
			</ul>
		</div>
	);
}

export function Notice( { type, children } ) {
	return <div className={ 'gpb-notice gpb-notice--' + ( type || 'info' ) }>{ children }</div>;
}

export function Group( { title, children, open: initialOpen } ) {
	const [ open, setOpen ] = useState( initialOpen !== false );
	return (
		<section className={ 'gpb-group' + ( open ? ' is-open' : '' ) }>
			<h3 className="gpb-group__title">
				<button type="button" aria-expanded={ open } onClick={ () => setOpen( ! open ) }>
					{ title }
					<span className="gpb-group__chev" aria-hidden="true">
						⌄
					</span>
				</button>
			</h3>
			{ open && <div className="gpb-group__body">{ children }</div> }
		</section>
	);
}
