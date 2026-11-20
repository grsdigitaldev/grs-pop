/**
 * Blocco "GRS Pop inline" nell'editor.
 *
 * @package GRSDigital\Pop
 */

const { __ } = wp.i18n;
const { useState, useEffect } = wp.element;
const { registerBlockType } = wp.blocks;
const { Placeholder, SelectControl, Spinner } = wp.components;
const { useBlockProps } = wp.blockEditor;

function Edit( { attributes, setAttributes } ) {
	const [ popups, setPopups ] = useState( null );
	const blockProps = useBlockProps();
	useEffect( () => {
		wp.apiFetch( { path: '/grs-pop/v1/inline' } )
			.then( ( list ) => setPopups( list || [] ) )
			.catch( () => setPopups( [] ) );
	}, [] );

	let body;
	if ( popups === null ) {
		body = <Spinner />;
	} else if ( ! popups.length ) {
		body = <p>{ __( 'No active inline popups. Create one in GRS DEVs → Pop choosing "Inside the content" in the Format panel.', 'grs-pop' ) }</p>;
	} else {
		const chosen = popups.find( ( p ) => p.id === attributes.popupId );
		body = (
			<>
				<SelectControl
					label={ __( 'Inline popup', 'grs-pop' ) }
					value={ String( attributes.popupId || 0 ) }
					options={ [ { value: '0', label: __( 'Choose…', 'grs-pop' ) } ].concat( popups.map( ( p ) => ( { value: String( p.id ), label: p.title } ) ) ) }
					onChange={ ( v ) => setAttributes( { popupId: parseInt( v, 10 ) || 0 } ) }
				/>
				{ chosen && <p className="components-placeholder__instructions">{ __( 'The box is shown here on the site, with its design and rules.', 'grs-pop' ) }</p> }
			</>
		);
	}
	return (
		<div { ...blockProps }>
			<Placeholder icon="megaphone" label={ __( 'GRS Pop inline', 'grs-pop' ) }>
				{ body }
			</Placeholder>
		</div>
	);
}

// Impostazioni complete anche nel browser: su WP 6.0 le definizioni del server possono arrivare dopo lo script.
registerBlockType( 'grs-pop/inline', {
	apiVersion: 2,
	title: __( 'GRS Pop inline', 'grs-pop' ),
	description: __( 'Shows a GRS Pop inline box (newsletter, offer, call to action) inside the content.', 'grs-pop' ),
	category: 'widgets',
	icon: 'megaphone',
	keywords: [ 'popup', 'cta', 'newsletter' ],
	attributes: {
		popupId: { type: 'integer', default: 0 },
	},
	supports: { html: false, align: [ 'wide', 'full' ] },
	edit: Edit,
	save: () => null,
} );
