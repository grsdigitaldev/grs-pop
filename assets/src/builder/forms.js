/**
 * Elenco dei form di GRS Forms, letto dalla sua REST pubblica GET grs-forms/v1/forms
 * (contratto dev/contratto-grs-pop-forms.md): mai dalle sue tabelle o dai suoi post.
 *
 * @package GRSDigital\Pop
 */

import { DATA } from './utils';

const { useState, useEffect } = wp.element;

let state = { status: DATA.formsActive ? 'loading' : 'off', list: [] };
let request = null;

function decode( text ) {
	const str = String( text || '' );
	if ( str.indexOf( '&' ) === -1 && str.indexOf( '<' ) === -1 ) {
		return str;
	}
	try {
		return new window.DOMParser().parseFromString( str, 'text/html' ).documentElement.textContent || '';
	} catch ( e ) {
		return str;
	}
}

/**
 * Accetta [ { id, title } ] e le varianti comuni (title.rendered, name, label, status, active,
 * risposta dentro forms o items). Null se il formato non è riconosciuto.
 *
 * @param {*} res Risposta della REST.
 * @return {Array|null} Form normalizzati { value, label, active }.
 */
function normalize( res ) {
	let raw = null;
	if ( Array.isArray( res ) ) {
		raw = res;
	} else if ( res && Array.isArray( res.forms ) ) {
		raw = res.forms;
	} else if ( res && Array.isArray( res.items ) ) {
		raw = res.items;
	}
	if ( ! raw ) {
		return null;
	}
	const out = [];
	raw.forEach( ( f ) => {
		if ( ! f || typeof f !== 'object' ) {
			return;
		}
		let id = f.id;
		if ( id === undefined ) {
			id = f.ID !== undefined ? f.ID : f.value;
		}
		id = parseInt( id, 10 );
		if ( ! ( id > 0 ) || out.some( ( x ) => x.value === id ) ) {
			return;
		}
		let title = f.title;
		if ( title === undefined ) {
			title = f.name !== undefined ? f.name : f.label;
		}
		if ( title && typeof title === 'object' ) {
			title = title.rendered || title.raw || '';
		}
		const status = f.status || f.post_status || '';
		let active = true;
		if ( f.active !== undefined ) {
			active = !! f.active;
		} else if ( status ) {
			active = status === 'publish' || status === 'active';
		}
		out.push( { value: id, label: decode( title ) || '#' + id, active } );
	} );
	return out;
}

export function loadForms() {
	if ( ! DATA.formsActive ) {
		return Promise.resolve( state );
	}
	if ( ! request ) {
		request = wp
			.apiFetch( { path: '/grs-forms/v1/forms' } )
			.then(
				( res ) => {
					const list = normalize( res );
					state = list ? { status: 'ok', list } : { status: 'error', list: [] };
					return state;
				},
				() => {
					state = { status: 'error', list: [] };
					return state;
				}
			);
	}
	return request;
}

/**
 * Stato corrente (sincrono): { status: 'off'|'loading'|'ok'|'error', list }.
 *
 * @return {Object} Stato.
 */
export function formsState() {
	return state;
}

export function formLabel( id ) {
	const f = state.list.find( ( x ) => x.value === id );
	return f ? f.label : '';
}

/**
 * Hook: carica l'elenco una sola volta e aggiorna il componente quando arriva.
 *
 * @return {Object} Stato.
 */
export function useForms() {
	const [ current, setCurrent ] = useState( state );
	useEffect( () => {
		let alive = true;
		loadForms().then( ( s ) => alive && setCurrent( s ) );
		return () => {
			alive = false;
		};
	}, [] );
	return current;
}
