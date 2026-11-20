/**
 * Funzioni di supporto del builder.
 *
 * @package GRSDigital\Pop
 */

export const DATA = window.grsPopBuilder || {};

/**
 * Copia profonda di un valore JSON.
 *
 * @param {*} value Valore.
 * @return {*} Copia.
 */
export function clone( value ) {
	return JSON.parse( JSON.stringify( value ) );
}

/**
 * Restituisce un nuovo oggetto con il valore impostato al percorso indicato.
 *
 * @param {Object}        obj   Oggetto.
 * @param {Array<string>} path  Percorso.
 * @param {*}             value Valore.
 * @return {Object} Nuovo oggetto.
 */
export function setIn( obj, path, value ) {
	const copy = Array.isArray( obj ) ? obj.slice() : Object.assign( {}, obj );
	if ( path.length === 1 ) {
		copy[ path[ 0 ] ] = value;
		return copy;
	}
	copy[ path[ 0 ] ] = setIn( obj[ path[ 0 ] ] === undefined ? {} : obj[ path[ 0 ] ], path.slice( 1 ), value );
	return copy;
}

/**
 * Nuovo ID di sezione.
 *
 * @param {Array<Object>} sections Sezioni esistenti.
 * @return {string} ID.
 */
export function newSectionId( sections ) {
	let id;
	do {
		id = 's' + Math.random().toString( 36 ).slice( 2, 8 );
	} while ( sections.some( ( s ) => s.id === id ) );
	return id;
}

/**
 * Valore CSS di un colore della configurazione.
 *
 * @param {string} color Colore.
 * @return {string} CSS.
 */
export function cssColor( color ) {
	if ( ! color ) {
		return '';
	}
	if ( color.indexOf( 'var:' ) === 0 ) {
		return 'var(--grs-pop-' + color.slice( 4 ).replace( /_/g, '-' ) + ')';
	}
	return color;
}

/**
 * Esadecimale reale di un colore (i colori della palette vengono risolti).
 *
 * @param {string} color Colore.
 * @return {string} Esadecimale.
 */
export function resolveHex( color ) {
	if ( color && color.indexOf( 'var:' ) === 0 ) {
		return ( DATA.palette || {} )[ color.slice( 4 ) ] || '#000000';
	}
	return /^#[0-9a-f]{3,6}$/i.test( color || '' ) ? color : '#000000';
}

/**
 * Rapporto di contrasto WCAG tra due colori esadecimali.
 *
 * @param {string} a Primo colore.
 * @param {string} b Secondo colore.
 * @return {number} Rapporto (1-21).
 */
export function contrastRatio( a, b ) {
	const lum = ( hex ) => {
		let h = hex.replace( '#', '' );
		if ( h.length === 3 ) {
			h = h.split( '' ).map( ( c ) => c + c ).join( '' );
		}
		const rgb = [ 0, 2, 4 ].map( ( i ) => {
			const v = parseInt( h.slice( i, i + 2 ), 16 ) / 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * rgb[ 0 ] + 0.7152 * rgb[ 1 ] + 0.0722 * rgb[ 2 ];
	};
	const la = lum( a );
	const lb = lum( b );
	return ( Math.max( la, lb ) + 0.05 ) / ( Math.min( la, lb ) + 0.05 );
}

/**
 * Data e ora locale `AAAA-MM-GGTHH:MM` in millisecondi (fuso del browser, solo anteprima).
 *
 * @param {string} local Data.
 * @return {number} Millisecondi.
 */
export function localToMs( local ) {
	if ( ! local ) {
		return 0;
	}
	const t = Date.parse( local );
	return isNaN( t ) ? 0 : t;
}

/**
 * Formati che coprono la pagina.
 */
export const INTRUSIVE = { modal: true, fullscreen: true };

/**
 * Posizioni ammesse per formato (come Schema::POSITIONS).
 */
export const POSITIONS = {
	modal: [ 'center', 'top' ],
	slidein: [ 'bottom-right', 'bottom-left', 'top-right', 'top-left', 'bottom' ],
	bar: [ 'top', 'bottom' ],
	fullscreen: [ 'center' ],
	tab: [ 'right', 'left', 'bottom-right', 'bottom-left' ],
};
