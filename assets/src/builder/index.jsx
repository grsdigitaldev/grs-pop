/**
 * GRS Pop - builder dei popup (React di WordPress, JSX classico per WP 6.0+).
 *
 * @package GRSDigital\Pop
 */

import { DATA, clone } from './utils';
import { Preview } from './preview';
import { Gallery } from './gallery';
import { ContentPanel, DesignPanel, DisplayPanel, TriggersPanel, TargetingPanel, FrequencyPanel } from './panels';
import { deviceLabels } from './labels';
import { __, sprintf } from './i18n';
import { useForms } from './forms';

const { useState, useEffect, useRef, useCallback } = wp.element;

function App() {
	const initial = DATA.popup;
	const [ id, setId ] = useState( initial ? initial.id : 0 );
	const [ title, setTitle ] = useState( initial ? initial.title : '' );
	const [ status, setStatus ] = useState( initial ? initial.status : 'draft' );
	const [ config, setConfigState ] = useState( initial ? initial.config : clone( DATA.defaults ) );
	const [ previewUrl, setPreviewUrl ] = useState( initial ? initial.preview_url : '' );
	const [ gallery, setGallery ] = useState( ! initial );
	const [ panel, setPanel ] = useState( 'content' );
	const [ device, setDevice ] = useState( 'desktop' );
	const [ selected, setSelected ] = useState( null );
	const [ dirty, setDirty ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ message, setMessage ] = useState( null );
	const titleRef = useRef( null );
	useForms(); // Elenco dei form di GRS Forms: al caricamento si aggiornano riepiloghi e anteprima.

	const setConfig = useCallback( ( next ) => {
		setConfigState( next );
		setDirty( true );
	}, [] );

	useEffect( () => {
		const warn = ( e ) => {
			if ( dirty ) {
				e.preventDefault();
				e.returnValue = '';
			}
		};
		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty ] );

	const save = useCallback(
		( nextStatus ) => {
			const st = nextStatus || status;
			setSaving( true );
			setMessage( null );
			wp.apiFetch( {
				path: '/grs-pop/v1/popups' + ( id ? '/' + id : '' ),
				method: 'POST',
				data: { title, status: st, config },
			} )
				.then( ( r ) => {
					setId( r.id );
					setTitle( r.title );
					setStatus( r.status );
					setConfigState( r.config );
					setPreviewUrl( r.preview_url );
					setDirty( false );
					DATA.popup = r;
					if ( ! id && window.history && window.history.replaceState ) {
						window.history.replaceState( null, '', DATA.editUrl + '&id=' + r.id );
					}
					setMessage( { type: 'ok', text: r.status === 'publish' ? __( 'Saved. The popup is active.', 'grs-pop' ) : __( 'Saved as inactive.', 'grs-pop' ) } );
				} )
				.catch( ( err ) => {
					setMessage( { type: 'error', text: ( err && err.message ) || __( 'Save failed.', 'grs-pop' ) } );
				} )
				.finally( () => setSaving( false ) );
		},
		[ id, title, status, config ]
	);

	useEffect( () => {
		const onKey = ( e ) => {
			if ( ( e.ctrlKey || e.metaKey ) && ( e.key === 's' || e.key === 'S' ) ) {
				e.preventDefault();
				if ( ! saving && ! gallery ) {
					save();
				}
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ save, saving, gallery ] );

	const pick = ( preset ) => {
		if ( id && dirty && ! window.confirm( __( 'Replace the current content and design with this template?', 'grs-pop' ) ) ) {
			return;
		}
		const next = clone( preset.config );
		if ( id ) {
			// Le regole di pubblico e frequenza già impostate restano.
			next.targeting = config.targeting;
		}
		setConfig( next );
		if ( ! title ) {
			setTitle( preset.name );
		}
		setGallery( false );
		setSelected( null );
		setPanel( 'content' );
		setTimeout( () => titleRef.current && titleRef.current.focus(), 50 );
	};

	if ( gallery ) {
		return (
			<div className="gpb">
				<div className="gpb-topbar">
					<a className="gpb-back" href={ DATA.listUrl }>
						← { __( 'All popups', 'grs-pop' ) }
					</a>
				</div>
				<Gallery onPick={ pick } onClose={ () => setGallery( false ) } canClose={ !! id || dirty } />
			</div>
		);
	}

	const panels = [
		{ key: 'content', label: __( 'Content', 'grs-pop' ) },
		{ key: 'design', label: __( 'Design', 'grs-pop' ) },
		{ key: 'display', label: __( 'Format', 'grs-pop' ) },
		{ key: 'triggers', label: __( 'Triggers', 'grs-pop' ) },
		{ key: 'targeting', label: __( 'Audience', 'grs-pop' ) },
		{ key: 'frequency', label: __( 'Frequency', 'grs-pop' ) },
	];
	const dl = deviceLabels();
	const props = { config, setConfig, selected, setSelected, setDevice };

	return (
		<div className="gpb">
			<div className="gpb-topbar">
				<a className="gpb-back" href={ DATA.listUrl }>
					← { __( 'All popups', 'grs-pop' ) }
				</a>
				<input
					ref={ titleRef }
					className="gpb-title"
					type="text"
					value={ title }
					placeholder={ __( 'Popup name (only for you)', 'grs-pop' ) }
					aria-label={ __( 'Popup name', 'grs-pop' ) }
					onChange={ ( e ) => {
						setTitle( e.target.value );
						setDirty( true );
					} }
				/>
				<label className="gpb-status">
					<input
						type="checkbox"
						checked={ status === 'publish' }
						onChange={ ( e ) => {
							setStatus( e.target.checked ? 'publish' : 'draft' );
							setDirty( true );
						} }
					/>
					<span className="gpb-toggle__track" aria-hidden="true"></span>
					<span>{ status === 'publish' ? __( 'Active', 'grs-pop' ) : __( 'Inactive', 'grs-pop' ) }</span>
				</label>
				<span className="gpb-topbar__spacer"></span>
				<button type="button" className="button" onClick={ () => setGallery( true ) }>
					{ __( 'Templates', 'grs-pop' ) }
				</button>
				{ previewUrl && (
					<a className="button" href={ previewUrl } target="_blank" rel="noopener noreferrer" title={ dirty ? __( 'Save first to see the latest changes', 'grs-pop' ) : '' }>
						{ __( 'Preview on site', 'grs-pop' ) }
					</a>
				) }
				<button type="button" className="button button-primary gpb-save" onClick={ () => save() } disabled={ saving }>
					{ saving ? __( 'Saving…', 'grs-pop' ) : dirty || ! id ? __( 'Save', 'grs-pop' ) : __( 'Saved', 'grs-pop' ) }
				</button>
			</div>
			{ message && (
				<div className={ 'gpb-message gpb-message--' + message.type } role={ message.type === 'error' ? 'alert' : 'status' }>
					{ message.text }
					{ message.type === 'ok' && id ? <span className="gpb-message__id">{ sprintf( /* translators: %d: popup ID. */ __( 'ID %d', 'grs-pop' ), id ) }</span> : null }
					<button type="button" onClick={ () => setMessage( null ) } aria-label={ __( 'Dismiss', 'grs-pop' ) }>
						✕
					</button>
				</div>
			) }
			<div className="gpb-main">
				<aside className="gpb-side">
					<nav className="gpb-tabs" aria-label={ __( 'Settings', 'grs-pop' ) }>
						{ panels.map( ( p ) => (
							<button type="button" key={ p.key } className={ 'gpb-tab' + ( panel === p.key ? ' is-active' : '' ) } aria-current={ panel === p.key ? 'true' : undefined } onClick={ () => setPanel( p.key ) }>
								{ p.label }
							</button>
						) ) }
					</nav>
					<div className="gpb-side__body">
						{ panel === 'content' && <ContentPanel { ...props } /> }
						{ panel === 'design' && <DesignPanel { ...props } /> }
						{ panel === 'display' && <DisplayPanel { ...props } /> }
						{ panel === 'triggers' && <TriggersPanel { ...props } /> }
						{ panel === 'targeting' && <TargetingPanel { ...props } /> }
						{ panel === 'frequency' && <FrequencyPanel { ...props } /> }
					</div>
				</aside>
				<section className="gpb-canvas" aria-label={ __( 'Preview', 'grs-pop' ) }>
					<div className="gpb-devices" role="group" aria-label={ __( 'Preview device', 'grs-pop' ) }>
						{ [ 'desktop', 'tablet', 'mobile' ].map( ( d ) => (
							<button type="button" key={ d } className={ 'gpb-device' + ( device === d ? ' is-active' : '' ) } aria-pressed={ device === d } onClick={ () => setDevice( d ) }>
								{ dl[ d ] }
								{ config.placement === 'floating' && ! config.display[ d ].enabled ? ' ·' + __( 'off', 'grs-pop' ) : '' }
							</button>
						) ) }
					</div>
					<Preview
						config={ config }
						device={ device }
						title={ title }
						selected={ selected }
						onSelect={ ( sid ) => {
							setSelected( sid );
							setPanel( 'content' );
						} }
					/>
					<p className="gpb-canvas__hint">{ __( 'Click an element of the preview to edit it.', 'grs-pop' ) }</p>
				</section>
			</div>
		</div>
	);
}

function mount() {
	const root = document.getElementById( 'grs-pop-builder' );
	if ( ! root ) {
		return;
	}
	const el = wp.element.createElement( App );
	if ( wp.element.createRoot ) {
		wp.element.createRoot( root ).render( el );
	} else {
		wp.element.render( el, root );
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mount );
} else {
	mount();
}
