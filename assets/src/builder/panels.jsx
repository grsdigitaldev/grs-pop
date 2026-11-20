/**
 * Pannelli di modifica del builder.
 *
 * @package GRSDigital\Pop
 */

import { DATA, clone, newSectionId, POSITIONS, INTRUSIVE, resolveHex, contrastRatio, cssColor } from './utils';
import { Icon } from './preview';
import { TextInput, NumberInput, Range, Select, Toggle, Segmented, ColorField, MediaField, ItemsPicker, Notice, Group } from './controls';
import { formatLabels, positionLabels, deviceLabels, sectionLabels, iconLabels, ruleLabels, dayLabels } from './labels';
import { __, sprintf } from './i18n';
import { useForms, formLabel } from './forms';

const { useState } = wp.element;

const sizeOptions = () => [
	{ value: 'sm', label: __( 'Small', 'grs-pop' ) },
	{ value: 'md', label: __( 'Medium', 'grs-pop' ) },
	{ value: 'lg', label: __( 'Large', 'grs-pop' ) },
];

const alignOptions = () => [
	{ value: 'inherit', label: __( 'Popup default', 'grs-pop' ) },
	{ value: 'left', label: __( 'Left', 'grs-pop' ) },
	{ value: 'center', label: __( 'Centre', 'grs-pop' ) },
	{ value: 'right', label: __( 'Right', 'grs-pop' ) },
];

/* ---------------------------------------------------------------------- */
/* Contenuto                                                                */
/* ---------------------------------------------------------------------- */

function summary( s ) {
	const strip = ( t ) => String( t || '' ).replace( /<[^>]+>/g, '' );
	switch ( s.type ) {
		case 'heading':
		case 'text':
		case 'dismiss':
			return strip( s.text );
		case 'button':
			return s.label;
		case 'coupon':
			return s.code;
		case 'form': {
			if ( ! s.form_id ) {
				return __( 'No form selected', 'grs-pop' );
			}
			return formLabel( s.form_id ) || '#' + s.form_id;
		}
		case 'list':
			return strip( s.items[ 0 ] || '' );
		case 'shortcode':
			return s.code;
		case 'countdown':
			return s.mode === 'evergreen' ? sprintf( /* translators: %d: minutes. */ __( '%d minutes per visitor', 'grs-pop' ), s.minutes ) : s.end.replace( 'T', ' ' );
	}
	return '';
}

function SectionEditor( { s, onChange } ) {
	const set = ( key ) => ( value ) => onChange( Object.assign( {}, s, { [ key ]: value } ) );
	const formsData = useForms();
	const fields = [];
	switch ( s.type ) {
		case 'heading':
			fields.push(
				<TextInput key="text" label={ __( 'Title', 'grs-pop' ) } value={ s.text } onChange={ set( 'text' ) } multiline rows={ 2 } help={ __( 'You can use <strong>, <em> and <br>.', 'grs-pop' ) } />,
				<Segmented key="size" label={ __( 'Size', 'grs-pop' ) } value={ s.size } onChange={ set( 'size' ) } options={ [ { value: 'sm', label: 'S' }, { value: 'md', label: 'M' }, { value: 'lg', label: 'L' }, { value: 'xl', label: 'XL' } ] } />,
				<Select key="tag" label={ __( 'HTML tag', 'grs-pop' ) } value={ s.tag } onChange={ set( 'tag' ) } options={ [ { value: 'h2', label: 'H2' }, { value: 'h3', label: 'H3' }, { value: 'p', label: __( 'Paragraph', 'grs-pop' ) } ] } help={ __( 'H2 is best for accessibility; use a paragraph for small labels.', 'grs-pop' ) } />,
				<ColorField key="color" label={ __( 'Colour', 'grs-pop' ) } value={ s.color } onChange={ set( 'color' ) } allowEmpty />
			);
			break;
		case 'text':
			fields.push(
				<TextInput key="text" label={ __( 'Text', 'grs-pop' ) } value={ s.text } onChange={ set( 'text' ) } multiline rows={ 4 } help={ __( 'You can use <strong>, <em>, <a href="…"> and line breaks.', 'grs-pop' ) } />,
				<Segmented key="size" label={ __( 'Size', 'grs-pop' ) } value={ s.size } onChange={ set( 'size' ) } options={ sizeOptions() } />,
				<ColorField key="color" label={ __( 'Colour', 'grs-pop' ) } value={ s.color } onChange={ set( 'color' ) } allowEmpty />
			);
			break;
		case 'button':
			fields.push(
				<TextInput key="label" label={ __( 'Label', 'grs-pop' ) } value={ s.label } onChange={ set( 'label' ) } maxLength={ 120 } />,
				<Segmented key="action" label={ __( 'On click', 'grs-pop' ) } value={ s.action } onChange={ set( 'action' ) } options={ [ { value: 'url', label: __( 'Open a link', 'grs-pop' ) }, { value: 'close', label: __( 'Close the popup', 'grs-pop' ) } ] } />
			);
			if ( s.action === 'url' ) {
				fields.push(
					<TextInput key="url" label={ __( 'Link', 'grs-pop' ) } value={ s.url } onChange={ set( 'url' ) } placeholder="https://" help={ __( 'Also tel:, mailto:, https://wa.me/… or #grs-pop-ID to open another popup.', 'grs-pop' ) } />,
					<Toggle key="tab" label={ __( 'Open in a new tab', 'grs-pop' ) } checked={ s.new_tab } onChange={ set( 'new_tab' ) } />,
					<Toggle key="conv" label={ __( 'Count a click as a conversion', 'grs-pop' ) } checked={ s.convert } onChange={ set( 'convert' ) } />
				);
			}
			fields.push(
				<Select key="style" label={ __( 'Style', 'grs-pop' ) } value={ s.style } onChange={ set( 'style' ) } options={ [ { value: 'primary', label: __( 'Primary', 'grs-pop' ) }, { value: 'secondary', label: __( 'Secondary', 'grs-pop' ) }, { value: 'outline', label: __( 'Outline', 'grs-pop' ) }, { value: 'link', label: __( 'Text link', 'grs-pop' ) } ] } />,
				<Select key="icon" label={ __( 'Icon', 'grs-pop' ) } value={ s.icon } onChange={ set( 'icon' ) } options={ Object.entries( iconLabels() ).map( ( [ value, label ] ) => ( { value, label } ) ) } />,
				<Toggle key="full" label={ __( 'Full width', 'grs-pop' ) } checked={ s.full } onChange={ set( 'full' ) } />
			);
			break;
		case 'form': {
			const forms = formsData.list;
			if ( ! DATA.formsActive ) {
				fields.push(
					<Notice key="nf" type="warn">
						{ __( 'GRS Forms is not active. Install and activate it to collect leads from popups.', 'grs-pop' ) }
					</Notice>
				);
			} else if ( formsData.status === 'loading' ) {
				fields.push( <p key="nf" className="gpb-hint">{ __( 'Loading forms…', 'grs-pop' ) }</p> );
			} else if ( formsData.status === 'error' ) {
				fields.push(
					<Notice key="nf" type="warn">
						{ __( 'The list of forms could not be loaded from GRS Forms. Update GRS Forms or reload the page.', 'grs-pop' ) }
						{ s.form_id > 0 && ' ' + sprintf( /* translators: %d: form ID. */ __( 'Form chosen: #%d.', 'grs-pop' ), s.form_id ) }
					</Notice>
				);
			} else if ( ! forms.length ) {
				fields.push(
					<Notice key="nf" type="warn">
						{ __( 'No forms yet: create one in GRS DEVs → Forms, then choose it here.', 'grs-pop' ) }{ ' ' }
						<a href={ DATA.formsUrl } target="_blank" rel="noopener noreferrer">
							{ __( 'Open GRS Forms', 'grs-pop' ) }
						</a>
					</Notice>
				);
			} else {
				fields.push(
					<Select
						key="form"
						label={ __( 'Form', 'grs-pop' ) }
						value={ String( s.form_id ) }
						onChange={ ( v ) => onChange( Object.assign( {}, s, { form_id: parseInt( v, 10 ) || 0 } ) ) }
						options={ [ { value: '0', label: __( 'Choose a form…', 'grs-pop' ) } ].concat(
							forms.map( ( f ) => ( { value: String( f.value ), label: f.label + ( f.active ? '' : ' (' + __( 'inactive', 'grs-pop' ) + ')' ) } ) )
						) }
						help={ __( 'Leads, anti-spam, notifications and conversion tracking are handled by GRS Forms. The form style follows the form settings.', 'grs-pop' ) }
					/>
				);
			}
			break;
		}
		case 'coupon':
			fields.push(
				<TextInput key="code" label={ __( 'Code', 'grs-pop' ) } value={ s.code } onChange={ ( v ) => onChange( Object.assign( {}, s, { code: v.toUpperCase().replace( /\s+/g, '' ) } ) ) } maxLength={ 60 } />,
				<TextInput key="label" label={ __( 'Button label', 'grs-pop' ) } value={ s.label } onChange={ set( 'label' ) } placeholder={ __( 'Copy', 'grs-pop' ) } />,
				<TextInput key="copied" label={ __( 'Text after copying', 'grs-pop' ) } value={ s.copied } onChange={ set( 'copied' ) } placeholder={ __( 'Copied!', 'grs-pop' ) } />
			);
			break;
		case 'countdown':
			fields.push(
				<Segmented key="mode" label={ __( 'Type', 'grs-pop' ) } value={ s.mode } onChange={ set( 'mode' ) } options={ [ { value: 'date', label: __( 'Until a date', 'grs-pop' ) }, { value: 'evergreen', label: __( 'Per visitor', 'grs-pop' ) } ] } />
			);
			if ( s.mode === 'date' ) {
				fields.push( <TextInput key="end" type="datetime-local" label={ __( 'Ends on (site time zone)', 'grs-pop' ) } value={ s.end } onChange={ set( 'end' ) } /> );
			} else {
				fields.push( <NumberInput key="min" label={ __( 'Duration from the first view', 'grs-pop' ) } value={ s.minutes } onChange={ set( 'minutes' ) } min={ 1 } max={ 100000 } suffix={ __( 'min', 'grs-pop' ) } /> );
			}
			fields.push(
				<Select key="exp" label={ __( 'When it ends', 'grs-pop' ) } value={ s.expire } onChange={ set( 'expire' ) } options={ [ { value: 'hide_section', label: __( 'Hide the countdown', 'grs-pop' ) }, { value: 'hide_popup', label: __( 'Hide the whole popup', 'grs-pop' ) }, { value: 'zero', label: __( 'Show 00:00', 'grs-pop' ) } ] } />,
				<div key="labels" className="gpb-grid4">
					<TextInput label={ __( 'Days', 'grs-pop' ) } value={ s.l_days } onChange={ set( 'l_days' ) } placeholder={ __( 'Days', 'grs-pop' ) } />
					<TextInput label={ __( 'Hours', 'grs-pop' ) } value={ s.l_hours } onChange={ set( 'l_hours' ) } placeholder={ __( 'Hours', 'grs-pop' ) } />
					<TextInput label={ __( 'Minutes', 'grs-pop' ) } value={ s.l_min } onChange={ set( 'l_min' ) } placeholder={ __( 'Minutes', 'grs-pop' ) } />
					<TextInput label={ __( 'Seconds', 'grs-pop' ) } value={ s.l_sec } onChange={ set( 'l_sec' ) } placeholder={ __( 'Seconds', 'grs-pop' ) } />
				</div>
			);
			break;
		case 'list':
			fields.push(
				<TextInput key="items" label={ __( 'Items (one per line)', 'grs-pop' ) } value={ s.items.join( '\n' ) } onChange={ ( v ) => onChange( Object.assign( {}, s, { items: v.split( '\n' ).slice( 0, 12 ) } ) ) } multiline rows={ 4 } />,
				<Segmented key="icon" label={ __( 'Bullet', 'grs-pop' ) } value={ s.icon } onChange={ set( 'icon' ) } options={ [ { value: 'check', label: '✓' }, { value: 'star', label: '★' }, { value: 'arrow', label: '→' }, { value: 'dot', label: '•' } ] } />
			);
			break;
		case 'spacer':
			fields.push(
				<Segmented key="size" label={ __( 'Height', 'grs-pop' ) } value={ s.size } onChange={ set( 'size' ) } options={ sizeOptions() } />,
				<Toggle key="line" label={ __( 'Show a line', 'grs-pop' ) } checked={ s.line } onChange={ set( 'line' ) } />
			);
			break;
		case 'dismiss':
			fields.push( <TextInput key="text" label={ __( 'Text', 'grs-pop' ) } value={ s.text } onChange={ set( 'text' ) } placeholder={ __( 'No thanks', 'grs-pop' ) } help={ __( 'Closes the popup. Keep it neutral and respectful: avoid guilt-tripping wording.', 'grs-pop' ) } /> );
			break;
		case 'shortcode':
			fields.push( <TextInput key="code" label={ __( 'Shortcode', 'grs-pop' ) } value={ s.code } onChange={ set( 'code' ) } placeholder="[shortcode]" help={ __( 'One shortcode from another plugin (for example a map or a booking widget).', 'grs-pop' ) } /> );
			break;
	}
	if ( s.type !== 'spacer' ) {
		fields.push( <Select key="align" label={ __( 'Alignment', 'grs-pop' ) } value={ s.align } onChange={ set( 'align' ) } options={ alignOptions() } /> );
	}
	return <div className="gpb-section-editor">{ fields }</div>;
}

export function ContentPanel( { config, setConfig, selected, setSelected } ) {
	const sections = config.sections;
	const labels = sectionLabels();
	const update = ( list ) => setConfig( Object.assign( {}, config, { sections: list } ) );
	const add = ( type ) => {
		const s = clone( DATA.sectionDefaults[ type ] );
		s.id = newSectionId( sections );
		if ( type === 'heading' ) {
			s.text = __( 'New title', 'grs-pop' );
		} else if ( type === 'text' ) {
			s.text = __( 'Write your message here.', 'grs-pop' );
		} else if ( type === 'button' ) {
			s.label = __( 'Find out more', 'grs-pop' );
		} else if ( type === 'coupon' ) {
			s.code = 'SAVE10';
		} else if ( type === 'list' ) {
			s.items = [ __( 'First benefit', 'grs-pop' ), __( 'Second benefit', 'grs-pop' ) ];
		}
		update( sections.concat( [ s ] ) );
		setSelected( s.id );
	};
	const move = ( index, dir ) => {
		const list = sections.slice();
		const to = index + dir;
		if ( to < 0 || to >= list.length ) {
			return;
		}
		const tmp = list[ index ];
		list[ index ] = list[ to ];
		list[ to ] = tmp;
		update( list );
	};
	const duplicate = ( index ) => {
		const copy = clone( sections[ index ] );
		copy.id = newSectionId( sections );
		const list = sections.slice();
		list.splice( index + 1, 0, copy );
		update( list );
		setSelected( copy.id );
	};
	const remove = ( index ) => {
		const list = sections.slice();
		list.splice( index, 1 );
		update( list );
	};
	const types = Object.keys( labels ).filter( ( t ) => t !== 'shortcode' || DATA.canShortcode );

	return (
		<div className="gpb-panel">
			<ul className="gpb-sections">
				{ sections.map( ( s, i ) => {
					const open = selected === s.id;
					return (
						<li key={ s.id } className={ 'gpb-section' + ( open ? ' is-open' : '' ) }>
							<div className="gpb-section__head">
								<button type="button" className="gpb-section__toggle" aria-expanded={ open } onClick={ () => setSelected( open ? null : s.id ) }>
									<span className="gpb-section__type">{ labels[ s.type ] }</span>
									<span className="gpb-section__summary">{ summary( s ) }</span>
								</button>
								<span className="gpb-section__tools">
									<button type="button" onClick={ () => move( i, -1 ) } disabled={ i === 0 } aria-label={ __( 'Move up', 'grs-pop' ) } title={ __( 'Move up', 'grs-pop' ) }>
										↑
									</button>
									<button type="button" onClick={ () => move( i, 1 ) } disabled={ i === sections.length - 1 } aria-label={ __( 'Move down', 'grs-pop' ) } title={ __( 'Move down', 'grs-pop' ) }>
										↓
									</button>
									<button type="button" onClick={ () => duplicate( i ) } aria-label={ __( 'Duplicate', 'grs-pop' ) } title={ __( 'Duplicate', 'grs-pop' ) }>
										+
									</button>
									<button type="button" className="gpb-danger" onClick={ () => remove( i ) } aria-label={ __( 'Delete', 'grs-pop' ) } title={ __( 'Delete', 'grs-pop' ) }>
										✕
									</button>
								</span>
							</div>
							{ open && (
								<SectionEditor
									s={ s }
									onChange={ ( ns ) => {
										const list = sections.slice();
										list[ i ] = ns;
										update( list );
									} }
								/>
							) }
						</li>
					);
				} ) }
			</ul>
			<div className="gpb-add">
				<span className="gpb-add__label">{ __( 'Add', 'grs-pop' ) }</span>
				<div className="gpb-add__grid">
					{ types.map( ( type ) => (
						<button type="button" key={ type } className="gpb-add__btn" onClick={ () => add( type ) }>
							{ labels[ type ] }
						</button>
					) ) }
				</div>
			</div>
		</div>
	);
}

/* ---------------------------------------------------------------------- */
/* Aspetto                                                                  */
/* ---------------------------------------------------------------------- */

export function DesignPanel( { config, setConfig } ) {
	const d = config.design;
	const set = ( key ) => ( value ) => setConfig( Object.assign( {}, config, { design: Object.assign( {}, d, { [ key ]: value } ) } ) );
	return (
		<div className="gpb-panel">
			<Group title={ __( 'Image and layout', 'grs-pop' ) }>
				<MediaField label={ __( 'Image', 'grs-pop' ) } value={ d.image } onChange={ set( 'image' ) } />
				{ d.image.url && (
					<>
						<Segmented
							label={ __( 'Layout', 'grs-pop' ) }
							value={ d.layout }
							onChange={ set( 'layout' ) }
							options={ [
								{ value: 'stack', label: __( 'Top', 'grs-pop' ) },
								{ value: 'left', label: __( 'Left', 'grs-pop' ) },
								{ value: 'right', label: __( 'Right', 'grs-pop' ) },
								{ value: 'background', label: __( 'Background', 'grs-pop' ) },
								{ value: 'none', label: __( 'Hidden', 'grs-pop' ) },
							] }
						/>
						<TextInput label={ __( 'Alternative text', 'grs-pop' ) } value={ d.image.alt } onChange={ ( v ) => set( 'image' )( Object.assign( {}, d.image, { alt: v } ) ) } help={ __( 'Describe the image for screen readers. Leave empty if it is only decorative.', 'grs-pop' ) } />
						<Toggle label={ __( 'Show the image on small screens', 'grs-pop' ) } checked={ d.image_mobile } onChange={ set( 'image_mobile' ) } />
						{ d.layout === 'background' && <Range label={ __( 'Darken the image', 'grs-pop' ) } value={ d.bg_dim } onChange={ set( 'bg_dim' ) } min={ 0 } max={ 90 } suffix="%" /> }
					</>
				) }
			</Group>
			<Group title={ __( 'Size and spacing', 'grs-pop' ) }>
				<Range label={ __( 'Maximum width', 'grs-pop' ) } value={ d.width } onChange={ set( 'width' ) } min={ 240 } max={ 1200 } step={ 10 } suffix="px" />
				<Segmented label={ __( 'Inner spacing', 'grs-pop' ) } value={ d.padding } onChange={ set( 'padding' ) } options={ sizeOptions() } />
				<Range label={ __( 'Rounded corners', 'grs-pop' ) } value={ d.radius } onChange={ set( 'radius' ) } min={ 0 } max={ 40 } suffix="px" />
				<Range label={ __( 'Text size', 'grs-pop' ) } value={ d.font_scale } onChange={ set( 'font_scale' ) } min={ 70 } max={ 150 } step={ 5 } suffix="%" />
				<Segmented label={ __( 'Text alignment', 'grs-pop' ) } value={ d.align } onChange={ set( 'align' ) } options={ [ { value: 'left', label: __( 'Left', 'grs-pop' ) }, { value: 'center', label: __( 'Centre', 'grs-pop' ) } ] } />
			</Group>
			<Group title={ __( 'Colours', 'grs-pop' ) }>
				<p className="gpb-hint">{ __( 'Round swatches are your palette (GRS DEVs → Pop → Settings): change it once and every popup follows.', 'grs-pop' ) }</p>
				<ColorField label={ __( 'Background', 'grs-pop' ) } value={ d.bg } onChange={ set( 'bg' ) } />
				<ColorField label={ __( 'Title', 'grs-pop' ) } value={ d.heading } onChange={ set( 'heading' ) } />
				<ColorField label={ __( 'Text', 'grs-pop' ) } value={ d.text } onChange={ set( 'text' ) } />
				<ColorField label={ __( 'Button', 'grs-pop' ) } value={ d.btn } onChange={ set( 'btn' ) } />
				<ColorField label={ __( 'Button text', 'grs-pop' ) } value={ d.btn_text } onChange={ set( 'btn_text' ) } />
				<ColorField label={ __( 'Secondary elements', 'grs-pop' ) } value={ d.accent } onChange={ set( 'accent' ) } />
				<ColorField label={ __( 'Close icon', 'grs-pop' ) } value={ d.close_color } onChange={ set( 'close_color' ) } />
			</Group>
			<Group title={ __( 'Border, shadow and background', 'grs-pop' ) } open={ false }>
				<Range label={ __( 'Border', 'grs-pop' ) } value={ d.stroke } onChange={ set( 'stroke' ) } min={ 0 } max={ 8 } suffix="px" />
				{ d.stroke > 0 && <ColorField label={ __( 'Border colour', 'grs-pop' ) } value={ d.stroke_color } onChange={ set( 'stroke_color' ) } /> }
				<Segmented label={ __( 'Shadow', 'grs-pop' ) } value={ d.shadow } onChange={ set( 'shadow' ) } options={ [ { value: 'none', label: __( 'None', 'grs-pop' ) }, { value: 'soft', label: __( 'Soft', 'grs-pop' ) }, { value: 'strong', label: __( 'Strong', 'grs-pop' ) } ] } />
				<ColorField label={ __( 'Page overlay', 'grs-pop' ) } value={ d.overlay } onChange={ set( 'overlay' ) } />
				<Range label={ __( 'Overlay opacity', 'grs-pop' ) } value={ d.overlay_opacity } onChange={ set( 'overlay_opacity' ) } min={ 0 } max={ 95 } suffix="%" />
			</Group>
			<Group title={ __( 'Animation', 'grs-pop' ) } open={ false }>
				<Segmented label={ __( 'Entrance', 'grs-pop' ) } value={ d.anim } onChange={ set( 'anim' ) } options={ [ { value: 'zoom', label: __( 'Zoom', 'grs-pop' ) }, { value: 'slide', label: __( 'Slide', 'grs-pop' ) }, { value: 'fade', label: __( 'Fade', 'grs-pop' ) }, { value: 'none', label: __( 'None', 'grs-pop' ) } ] } />
				<p className="gpb-hint">{ __( 'Visitors who ask their device for reduced motion always see the popup without animation.', 'grs-pop' ) }</p>
			</Group>
		</div>
	);
}

/* ---------------------------------------------------------------------- */
/* Formato e posizione                                                      */
/* ---------------------------------------------------------------------- */

export function DisplayPanel( { config, setConfig, setDevice } ) {
	const fl = formatLabels();
	const pl = positionLabels();
	const dl = deviceLabels();
	const display = config.display;
	const setDisplay = ( value ) => setConfig( Object.assign( {}, config, { display: value } ) );
	const setDev = ( device, key, value ) => {
		const dev = Object.assign( {}, display[ device ], { [ key ]: value } );
		if ( key === 'format' && POSITIONS[ value ].indexOf( dev.position ) === -1 ) {
			dev.position = POSITIONS[ value ][ 0 ];
		}
		setDisplay( Object.assign( {}, display, { [ device ]: dev } ) );
	};
	const copyDesktop = () => {
		setDisplay( Object.assign( {}, display, { tablet: clone( display.desktop ), mobile: clone( display.desktop ) } ) );
	};
	const usesTab = [ 'desktop', 'tablet', 'mobile' ].some( ( k ) => display[ k ].enabled && display[ k ].format === 'tab' );
	const usesBar = [ 'desktop', 'tablet', 'mobile' ].some( ( k ) => display[ k ].enabled && display[ k ].format === 'bar' );
	const usesTopBar = [ 'desktop', 'tablet', 'mobile' ].some( ( k ) => display[ k ].enabled && display[ k ].format === 'bar' && display[ k ].position === 'top' );
	const bo = display.bar_offset;
	const setBo = ( key ) => ( value ) => setDisplay( Object.assign( {}, display, { bar_offset: Object.assign( {}, bo, { [ key ]: value } ) } ) );
	const mobile = display.mobile;
	const intrusiveMobile = config.placement === 'floating' && mobile.enabled && INTRUSIVE[ mobile.format ] && ( config.triggers.load.on || config.triggers.scroll.on );

	return (
		<div className="gpb-panel">
			<Segmented
				label={ __( 'Where', 'grs-pop' ) }
				value={ config.placement }
				onChange={ ( v ) => setConfig( Object.assign( {}, config, { placement: v } ) ) }
				options={ [ { value: 'floating', label: __( 'Over the page', 'grs-pop' ) }, { value: 'inline', label: __( 'Inside the content', 'grs-pop' ) } ] }
			/>
			{ config.placement === 'inline' ? (
				<Group title={ __( 'Position in the content', 'grs-pop' ) }>
					<Select
						label={ __( 'Insert', 'grs-pop' ) }
						value={ config.inline.position }
						onChange={ ( v ) => setConfig( Object.assign( {}, config, { inline: Object.assign( {}, config.inline, { position: v } ) } ) ) }
						options={ [
							{ value: 'manual', label: __( 'Only where I place the shortcode or block', 'grs-pop' ) },
							{ value: 'before_content', label: __( 'Before the content', 'grs-pop' ) },
							{ value: 'after_content', label: __( 'After the content', 'grs-pop' ) },
							{ value: 'after_paragraph', label: __( 'After a paragraph', 'grs-pop' ) },
						] }
						help={ __( 'Automatic positions apply to the single pages and posts chosen in Audience.', 'grs-pop' ) }
					/>
					{ config.inline.position === 'after_paragraph' && (
						<NumberInput label={ __( 'After paragraph number', 'grs-pop' ) } value={ config.inline.paragraph } min={ 1 } max={ 50 } onChange={ ( v ) => setConfig( Object.assign( {}, config, { inline: Object.assign( {}, config.inline, { paragraph: v } ) } ) ) } />
					) }
					{ config.inline.position === 'manual' && <Notice>{ DATA.popup ? sprintf( /* translators: %d: popup ID. */ __( 'Shortcode: [grs_pop id="%d"] or the "GRS Pop inline" block.', 'grs-pop' ), DATA.popup.id ) : __( 'Save the popup to get its shortcode.', 'grs-pop' ) }</Notice> }
				</Group>
			) : (
				<>
					{ [ 'desktop', 'tablet', 'mobile' ].map( ( device ) => {
						const dev = display[ device ];
						return (
							<Group key={ device } title={ dl[ device ] }>
								<Toggle
									label={ sprintf( /* translators: %s: device name. */ __( 'Show on %s', 'grs-pop' ), dl[ device ].toLowerCase() ) }
									checked={ dev.enabled }
									onChange={ ( v ) => {
										setDev( device, 'enabled', v );
										setDevice( device );
									} }
								/>
								{ dev.enabled && (
									<>
										<Select
											label={ __( 'Format', 'grs-pop' ) }
											value={ dev.format }
											onChange={ ( v ) => {
												setDev( device, 'format', v );
												setDevice( device );
											} }
											options={ [ 'modal', 'slidein', 'bar', 'fullscreen', 'tab' ].map( ( f ) => ( { value: f, label: fl[ f ] } ) ) }
										/>
										{ POSITIONS[ dev.format ].length > 1 && (
											<Select
												label={ __( 'Position', 'grs-pop' ) }
												value={ dev.position }
												onChange={ ( v ) => {
													setDev( device, 'position', v );
													setDevice( device );
												} }
												options={ POSITIONS[ dev.format ].map( ( p ) => ( { value: p, label: pl[ p ] } ) ) }
											/>
										) }
									</>
								) }
								{ device === 'desktop' && (
									<button type="button" className="button gpb-link-btn" onClick={ copyDesktop }>
										{ __( 'Use the same settings on tablet and mobile', 'grs-pop' ) }
									</button>
								) }
							</Group>
						);
					} ) }
					{ intrusiveMobile && (
						<Notice type="warn">
							{ __( 'On mobile this popup covers the page and opens automatically. Google may penalise pages that hide the content to visitors coming from search: for mobile prefer a slide-in from the bottom or a bar, or open it on exit, after scrolling or on click.', 'grs-pop' ) }
						</Notice>
					) }
					{ usesBar && (
						<Segmented
							label={ __( 'Bar behaviour', 'grs-pop' ) }
							value={ display.bar_mode }
							onChange={ ( v ) => setDisplay( Object.assign( {}, display, { bar_mode: v } ) ) }
							options={ [ { value: 'fixed', label: __( 'Over the page', 'grs-pop' ) }, { value: 'push', label: __( 'Pushes the page down', 'grs-pop' ) } ] }
						/>
					) }
					{ usesTopBar && display.bar_mode === 'fixed' && (
						<Group title={ __( 'Fixed header', 'grs-pop' ) }>
							<Toggle
								label={ __( 'Move the fixed elements of the site down (header, menu)', 'grs-pop' ) }
								checked={ bo.enabled }
								onChange={ setBo( 'enabled' ) }
								help={ __( 'While the bar at the top is open, the page and the fixed header slide down by the height of the bar, so the bar does not cover logo and menu.', 'grs-pop' ) }
							/>
							{ bo.enabled && (
								<>
									<Toggle label={ __( 'Find them automatically', 'grs-pop' ) } checked={ bo.auto } onChange={ setBo( 'auto' ) } help={ __( 'Looks for fixed or sticky elements at the top of the page when the bar opens.', 'grs-pop' ) } />
									<TextInput label={ __( 'Elements to move (CSS selectors)', 'grs-pop' ) } value={ bo.selectors } onChange={ setBo( 'selectors' ) } placeholder=".site-header, .primary-nav" help={ __( 'Separated by commas. Recommended when the automatic search misses the header or the mobile menu panel.', 'grs-pop' ) } />
									<p className="gpb-hint">{ __( 'Check it on the site: the preview here does not include the header of the theme.', 'grs-pop' ) }</p>
								</>
							) }
						</Group>
					) }
					{ usesTab && <TextInput label={ __( 'Tab label', 'grs-pop' ) } value={ display.tab_label } onChange={ ( v ) => setDisplay( Object.assign( {}, display, { tab_label: v } ) ) } maxLength={ 60 } placeholder={ __( 'Open', 'grs-pop' ) } /> }
				</>
			) }
		</div>
	);
}

/* ---------------------------------------------------------------------- */
/* Comparsa e chiusura                                                      */
/* ---------------------------------------------------------------------- */

export function TriggersPanel( { config, setConfig } ) {
	const t = config.triggers;
	const c = config.close;
	const setT = ( key, prop, value ) => setConfig( Object.assign( {}, config, { triggers: Object.assign( {}, t, { [ key ]: Object.assign( {}, t[ key ], { [ prop ]: value } ) } ) } ) );
	const setC = ( key ) => ( value ) => setConfig( Object.assign( {}, config, { close: Object.assign( {}, c, { [ key ]: value } ) } ) );
	const id = DATA.popup ? DATA.popup.id : 'ID';
	const ro = c.reopen;
	const setRo = ( key ) => ( value ) => setConfig( Object.assign( {}, config, { close: Object.assign( {}, c, { reopen: Object.assign( {}, ro, { [ key ]: value } ) } ) } ) );
	const usesReopenFormat = [ 'desktop', 'tablet', 'mobile' ].some( ( k ) => config.display[ k ].enabled && ( config.display[ k ].format === 'bar' || config.display[ k ].format === 'slidein' ) );
	const roBg = ro.bg || config.design.bg;
	const roText = ro.text || config.design.text;
	const roContrast = contrastRatio( resolveHex( roBg ), resolveHex( roText ) );
	const il = iconLabels();

	if ( config.placement === 'inline' ) {
		return (
			<div className="gpb-panel">
				<Notice>{ __( 'Inline boxes are part of the page: they have no triggers. You can still use Audience and Frequency.', 'grs-pop' ) }</Notice>
				<Toggle label={ __( 'Show a close button', 'grs-pop' ) } checked={ c.button } onChange={ setC( 'button' ) } />
			</div>
		);
	}
	const none = ! t.load.on && ! t.scroll.on && ! t.exit.on && ! t.inactivity.on && ! t.element.on;

	return (
		<div className="gpb-panel">
			<p className="gpb-hint">{ __( 'The popup opens at the first trigger that happens.', 'grs-pop' ) }</p>
			<Group title={ __( 'Automatic', 'grs-pop' ) }>
				<Toggle label={ __( 'After a few seconds', 'grs-pop' ) } checked={ t.load.on } onChange={ ( v ) => setT( 'load', 'on', v ) } />
				{ t.load.on && <NumberInput label={ __( 'Delay', 'grs-pop' ) } value={ t.load.delay } min={ 0 } max={ 600 } suffix="s" onChange={ ( v ) => setT( 'load', 'delay', v ) } /> }
				<Toggle label={ __( 'After scrolling the page', 'grs-pop' ) } checked={ t.scroll.on } onChange={ ( v ) => setT( 'scroll', 'on', v ) } />
				{ t.scroll.on && <Range label={ __( 'Page scrolled', 'grs-pop' ) } value={ t.scroll.percent } min={ 5 } max={ 100 } step={ 5 } suffix="%" onChange={ ( v ) => setT( 'scroll', 'percent', v ) } /> }
				<Toggle label={ __( 'When the visitor is about to leave (exit intent)', 'grs-pop' ) } checked={ t.exit.on } onChange={ ( v ) => setT( 'exit', 'on', v ) } help={ __( 'On computers: when the mouse moves towards the tabs or the address bar.', 'grs-pop' ) } />
				{ t.exit.on && (
					<Select
						label={ __( 'On touch screens', 'grs-pop' ) }
						value={ t.exit.mobile }
						onChange={ ( v ) => setT( 'exit', 'mobile', v ) }
						options={ [ { value: 'scroll_up', label: __( 'Open on a quick scroll back up', 'grs-pop' ) }, { value: 'none', label: __( 'Do not open', 'grs-pop' ) } ] }
					/>
				) }
				<Toggle label={ __( 'After a period of inactivity', 'grs-pop' ) } checked={ t.inactivity.on } onChange={ ( v ) => setT( 'inactivity', 'on', v ) } />
				{ t.inactivity.on && <NumberInput label={ __( 'Inactive for', 'grs-pop' ) } value={ t.inactivity.seconds } min={ 5 } max={ 3600 } suffix="s" onChange={ ( v ) => setT( 'inactivity', 'seconds', v ) } /> }
				<Toggle label={ __( 'When an element becomes visible', 'grs-pop' ) } checked={ t.element.on } onChange={ ( v ) => setT( 'element', 'on', v ) } />
				{ t.element.on && <TextInput label={ __( 'CSS selector', 'grs-pop' ) } value={ t.element.selector } onChange={ ( v ) => setT( 'element', 'selector', v ) } placeholder="#comments, .price-table" /> }
			</Group>
			<Group title={ __( 'On click', 'grs-pop' ) }>
				<p className="gpb-hint">
					{ sprintf(
						/* translators: 1: link address, 2: CSS class. */
						__( 'Always available: any link to %1$s or element with the class %2$s opens this popup.', 'grs-pop' ),
						'#grs-pop-' + id,
						'grs-pop-open-' + id
					) }
				</p>
				<TextInput label={ __( 'Also open when clicking (CSS selector)', 'grs-pop' ) } value={ t.click.selector } onChange={ ( v ) => setT( 'click', 'selector', v ) } placeholder=".menu-item-offer > a" />
				{ none && <Notice>{ __( 'No automatic trigger: this popup opens only on click.', 'grs-pop' ) }</Notice> }
			</Group>
			<Group title={ __( 'Closing', 'grs-pop' ) }>
				<Toggle label={ __( 'Close button (×)', 'grs-pop' ) } checked={ c.button } onChange={ setC( 'button' ) } />
				<Toggle label={ __( 'Close by clicking outside', 'grs-pop' ) } checked={ c.overlay } onChange={ setC( 'overlay' ) } />
				<Toggle label={ __( 'Close with the Esc key', 'grs-pop' ) } checked={ c.esc } onChange={ setC( 'esc' ) } />
				{ ! c.button && ! c.overlay && ! c.esc && <Notice type="warn">{ __( 'At least one way to close is required: the close button will be shown.', 'grs-pop' ) }</Notice> }
				<Select
					label={ __( 'After a form is sent', 'grs-pop' ) }
					value={ c.after_form }
					onChange={ setC( 'after_form' ) }
					options={ [ { value: 'close', label: __( 'Close the popup after a few seconds', 'grs-pop' ) }, { value: 'keep', label: __( 'Keep it open', 'grs-pop' ) } ] }
				/>
				{ c.after_form === 'close' && <NumberInput label={ __( 'Close after', 'grs-pop' ) } value={ c.after_form_delay } min={ 0 } max={ 60 } suffix="s" onChange={ setC( 'after_form_delay' ) } /> }
				<NumberInput label={ __( 'Close automatically after (0 = never)', 'grs-pop' ) } value={ c.auto_close } min={ 0 } max={ 600 } suffix="s" onChange={ setC( 'auto_close' ) } />
			</Group>
			{ usesReopenFormat && (
				<Group title={ __( 'Reopen after closing', 'grs-pop' ) }>
					<Toggle
						label={ __( 'Show a tab to reopen it after closing', 'grs-pop' ) }
						checked={ ro.enabled }
						onChange={ setRo( 'enabled' ) }
						help={ __( 'For bars and slide-ins. The tab stays available even when Frequency stops the popup from opening by itself; it disappears after a conversion and when the schedule ends.', 'grs-pop' ) }
					/>
					{ ro.enabled && (
						<>
							<TextInput label={ __( 'Tab text', 'grs-pop' ) } value={ ro.label } onChange={ setRo( 'label' ) } maxLength={ 40 } placeholder={ __( 'Reopen', 'grs-pop' ) } />
							<Select label={ __( 'Icon', 'grs-pop' ) } value={ ro.icon } onChange={ setRo( 'icon' ) } options={ Object.keys( il ).map( ( k ) => ( { value: k, label: il[ k ] } ) ) } />
							<Select
								label={ __( 'Position', 'grs-pop' ) }
								value={ ro.position }
								onChange={ setRo( 'position' ) }
								options={ [
									{ value: 'auto', label: __( 'Automatic (based on the format)', 'grs-pop' ) },
									{ value: 'top-right', label: __( 'Top right', 'grs-pop' ) },
									{ value: 'top-left', label: __( 'Top left', 'grs-pop' ) },
									{ value: 'bottom-right', label: __( 'Bottom right', 'grs-pop' ) },
									{ value: 'bottom-left', label: __( 'Bottom left', 'grs-pop' ) },
								] }
							/>
							{ ro.position.indexOf( 'bottom' ) !== 0 && (
								<NumberInput
									label={ __( 'Distance from the top', 'grs-pop' ) }
									value={ ro.top }
									min={ 0 }
									max={ 400 }
									suffix="px"
									onChange={ setRo( 'top' ) }
									help={ config.display.bar_offset.enabled ? __( 'Used only when no fixed header is found: otherwise the tab hangs right below the header.', 'grs-pop' ) : __( 'With a fixed header, set its height here (or turn on "Move the fixed elements of the site down" in Format: the tab then hangs below the header by itself).', 'grs-pop' ) }
								/>
							) }
							<NumberInput label={ __( 'Align to the site content (width, 0 = window edge)', 'grs-pop' ) } value={ ro.container } min={ 0 } max={ 3000 } suffix="px" onChange={ setRo( 'container' ) } help={ __( 'On wide screens the tab lines up with the edge of the content, for example 1200.', 'grs-pop' ) } />
							<ColorField label={ __( 'Tab background', 'grs-pop' ) } value={ ro.bg } onChange={ setRo( 'bg' ) } allowEmpty />
							<ColorField label={ __( 'Tab text colour', 'grs-pop' ) } value={ ro.text } onChange={ setRo( 'text' ) } allowEmpty />
							<p className="gpb-hint">{ __( 'Empty colours use the background and text of the popup.', 'grs-pop' ) }</p>
							{ roContrast < 4.5 && <Notice type="warn">{ sprintf( /* translators: %s: contrast ratio, e.g. 3.2. */ __( 'Low contrast between background and text (%s:1): at least 4.5:1 is recommended for readability.', 'grs-pop' ), roContrast.toFixed( 1 ) ) }</Notice> }
							<div className="gpb-reopen-sample">
								<span className="gpb-field__label">{ __( 'Example', 'grs-pop' ) }</span>
								<span className={ 'grs-pop-reopen grs-pop-reopen--sample grs-pop-reopen--pos-' + ( ro.position === 'auto' || ro.position.indexOf( 'top' ) === 0 ? 'top-right' : 'bottom-right' ) } style={ { '--gp-ro-bg': cssColor( roBg ), '--gp-ro-txt': cssColor( roText ) } }>
									{ ro.icon !== 'none' && <Icon name={ ro.icon } className="grs-pop-reopen__icon" /> }
									<span className="grs-pop-reopen__label">{ ro.label || __( 'Reopen', 'grs-pop' ) }</span>
									<Icon name="chevron" className="grs-pop-reopen__chev" />
								</span>
							</div>
						</>
					) }
				</Group>
			) }
		</div>
	);
}


/* ---------------------------------------------------------------------- */
/* Pubblico (regole)                                                        */
/* ---------------------------------------------------------------------- */

function RulesEditor( { rules, onChange } ) {
	const [ type, setType ] = useState( 'front_page' );
	const rl = ruleLabels();
	const postTypes = DATA.postTypes || [];
	const taxonomies = DATA.taxonomies || [];
	const add = () => {
		const rule = { type };
		if ( type === 'singular' || type === 'archive' ) {
			rule.post_type = ( postTypes[ 0 ] || {} ).value || 'post';
		} else if ( type === 'posts' ) {
			rule.items = [];
		} else if ( type === 'terms' ) {
			rule.taxonomy = ( taxonomies[ 0 ] || {} ).value || 'category';
			rule.items = [];
		} else if ( type === 'url' ) {
			rule.op = 'contains';
			rule.value = '';
		}
		onChange( rules.concat( [ rule ] ) );
	};
	const set = ( i, patch ) => {
		const list = rules.slice();
		list[ i ] = Object.assign( {}, list[ i ], patch );
		onChange( list );
	};
	const remove = ( i ) => onChange( rules.filter( ( r, n ) => n !== i ) );
	return (
		<div className="gpb-rules">
			{ rules.map( ( r, i ) => (
				<div className="gpb-rule" key={ i }>
					<div className="gpb-rule__head">
						<strong>{ rl[ r.type ] }</strong>
						<button type="button" className="gpb-danger" onClick={ () => remove( i ) } aria-label={ __( 'Remove rule', 'grs-pop' ) }>
							✕
						</button>
					</div>
					{ ( r.type === 'singular' || r.type === 'archive' ) && (
						<Select label={ __( 'Type', 'grs-pop' ) } value={ r.post_type } onChange={ ( v ) => set( i, { post_type: v } ) } options={ postTypes.filter( ( p ) => r.type === 'singular' || p.archive ).map( ( p ) => ( { value: p.value, label: p.label } ) ) } />
					) }
					{ r.type === 'posts' && <ItemsPicker kind="posts" items={ r.items } onChange={ ( items ) => set( i, { items } ) } /> }
					{ r.type === 'terms' && (
						<>
							<Select label={ __( 'Taxonomy', 'grs-pop' ) } value={ r.taxonomy } onChange={ ( v ) => set( i, { taxonomy: v, items: [] } ) } options={ taxonomies.map( ( t ) => ( { value: t.value, label: t.label } ) ) } />
							<ItemsPicker kind="terms" subtype={ r.taxonomy } items={ r.items } onChange={ ( items ) => set( i, { items } ) } />
						</>
					) }
					{ r.type === 'url' && (
						<div className="gpb-row">
							<Select value={ r.op } onChange={ ( v ) => set( i, { op: v } ) } options={ [ { value: 'contains', label: __( 'contains', 'grs-pop' ) }, { value: 'starts', label: __( 'starts with', 'grs-pop' ) }, { value: 'equals', label: __( 'equals', 'grs-pop' ) } ] } />
							<TextInput value={ r.value } onChange={ ( v ) => set( i, { value: v } ) } placeholder="/shop/" />
						</div>
					) }
				</div>
			) ) }
			<div className="gpb-row gpb-rules__add">
				<Select value={ type } onChange={ setType } options={ Object.entries( rl ).map( ( [ value, label ] ) => ( { value, label } ) ) } />
				<button type="button" className="button" onClick={ add }>
					{ __( 'Add rule', 'grs-pop' ) }
				</button>
			</div>
		</div>
	);
}

/**
 * Regola "ha / non ha compilato il form" (contratto dev/contratto-grs-pop-forms.md).
 */
function FormsRule( { fm, setFm, toggleIn } ) {
	const formsData = useForms();
	const on = fm.mode !== 'any';
	const known = formsData.list.map( ( f ) => f.value );
	const missing = fm.ids.filter( ( id ) => known.indexOf( id ) === -1 );
	return (
		<Group title={ __( 'Forms already filled in', 'grs-pop' ) } open={ on }>
			{ ! DATA.formsActive && (
				<Notice type="warn">{ __( 'GRS Forms is not active: this rule is ignored and the popup is shown as if it were not set.', 'grs-pop' ) }</Notice>
			) }
			<Segmented
				label={ __( 'Show to', 'grs-pop' ) }
				value={ fm.mode }
				onChange={ setFm( 'mode' ) }
				options={ [
					{ value: 'any', label: __( 'Everyone', 'grs-pop' ) },
					{ value: 'not_done', label: __( 'Who has not filled in', 'grs-pop' ) },
					{ value: 'done', label: __( 'Who has filled in', 'grs-pop' ) },
				] }
			/>
			{ on && DATA.formsActive && formsData.status === 'loading' && <p className="gpb-hint">{ __( 'Loading forms…', 'grs-pop' ) }</p> }
			{ on && DATA.formsActive && formsData.status === 'error' && (
				<Notice type="warn">{ __( 'The list of forms could not be loaded from GRS Forms. Update GRS Forms or reload the page.', 'grs-pop' ) }</Notice>
			) }
			{ on && formsData.status === 'ok' && ! formsData.list.length && (
				<Notice type="warn">
					{ __( 'No forms yet: create one in GRS DEVs → Forms, then choose it here.', 'grs-pop' ) }{ ' ' }
					<a href={ DATA.formsUrl } target="_blank" rel="noopener noreferrer">
						{ __( 'Open GRS Forms', 'grs-pop' ) }
					</a>
				</Notice>
			) }
			{ on && ( formsData.list.length > 0 || missing.length > 0 ) && (
				<div className="gpb-checks" role="group" aria-label={ __( 'Forms', 'grs-pop' ) }>
					<span className="gpb-field__label">{ __( 'At least one of these forms', 'grs-pop' ) }</span>
					{ formsData.list.map( ( f ) => (
						<label key={ f.value }>
							<input type="checkbox" checked={ fm.ids.indexOf( f.value ) !== -1 } onChange={ () => setFm( 'ids' )( toggleIn( fm.ids, f.value ) ) } /> { f.label }
							{ ! f.active && ' (' + __( 'inactive', 'grs-pop' ) + ')' }
						</label>
					) ) }
					{ formsData.status !== 'loading' &&
						missing.map( ( id ) => (
							<label key={ 'm' + id }>
								<input type="checkbox" checked onChange={ () => setFm( 'ids' )( fm.ids.filter( ( x ) => x !== id ) ) } />{ ' ' }
								{ formsData.status === 'ok' ? sprintf( /* translators: %d: form ID. */ __( 'Form #%d (not found)', 'grs-pop' ), id ) : sprintf( /* translators: %d: form ID. */ __( 'Form #%d', 'grs-pop' ), id ) }
							</label>
						) ) }
				</div>
			) }
			{ on && (
				<NumberInput label={ __( 'Filled in during the last N days', 'grs-pop' ) } value={ fm.days } min={ 0 } max={ 3650 } onChange={ setFm( 'days' ) } help={ __( '0 = at any time.', 'grs-pop' ) } />
			) }
			{ on && fm.ids.length === 0 && formsData.list.length > 0 && <p className="gpb-hint">{ __( 'Choose at least one form, otherwise the rule is not applied.', 'grs-pop' ) }</p> }
			<p className="gpb-hint">
				{ __( 'The visitor\'s browser remembers which GRS Forms forms were sent and when (no data entered in the form), from the moment a popup with this rule is active. If the browser does not keep data, the popup is treated as if no form had been filled in.', 'grs-pop' ) }
			</p>
		</Group>
	);
}

export function TargetingPanel( { config, setConfig } ) {
	const tg = config.targeting;
	const co = config.conditions;
	const setTg = ( key ) => ( value ) => setConfig( Object.assign( {}, config, { targeting: Object.assign( {}, tg, { [ key ]: value } ) } ) );
	const setCo = ( key ) => ( value ) => setConfig( Object.assign( {}, config, { conditions: Object.assign( {}, co, { [ key ]: value } ) } ) );
	const sched = co.schedule;
	const setSched = ( key ) => ( value ) => setCo( 'schedule' )( Object.assign( {}, sched, { [ key ]: value } ) );
	const days = dayLabels();
	const languages = DATA.languages || [];
	const fm = co.forms || { mode: 'any', ids: [], days: 0 };
	const setFm = ( key ) => ( value ) => setCo( 'forms' )( Object.assign( {}, fm, { [ key ]: value } ) );

	const toggleIn = ( list, value ) => ( list.indexOf( value ) === -1 ? list.concat( [ value ] ) : list.filter( ( v ) => v !== value ) );

	return (
		<div className="gpb-panel">
			<Group title={ __( 'Pages', 'grs-pop' ) }>
				<Segmented label={ __( 'Show on', 'grs-pop' ) } value={ tg.pages } onChange={ setTg( 'pages' ) } options={ [ { value: 'all', label: __( 'The whole site', 'grs-pop' ) }, { value: 'include', label: __( 'Only on…', 'grs-pop' ) } ] } />
				{ tg.pages === 'include' && <RulesEditor rules={ tg.include } onChange={ setTg( 'include' ) } /> }
				<h4 className="gpb-subtitle">{ __( 'Never show on', 'grs-pop' ) }</h4>
				<RulesEditor rules={ tg.exclude } onChange={ setTg( 'exclude' ) } />
			</Group>
			<Group title={ __( 'Visitors', 'grs-pop' ) }>
				<Segmented
					label={ __( 'Logged-in users', 'grs-pop' ) }
					value={ tg.users }
					onChange={ setTg( 'users' ) }
					options={ [ { value: 'all', label: __( 'Everyone', 'grs-pop' ) }, { value: 'out', label: __( 'Only logged out', 'grs-pop' ) }, { value: 'in', label: __( 'Only logged in', 'grs-pop' ) } ] }
				/>
				{ tg.users === 'in' && (
					<div className="gpb-checks" role="group" aria-label={ __( 'Roles', 'grs-pop' ) }>
						{ ( DATA.roles || [] ).map( ( r ) => (
							<label key={ r.value }>
								<input type="checkbox" checked={ tg.roles.indexOf( r.value ) !== -1 } onChange={ () => setTg( 'roles' )( toggleIn( tg.roles, r.value ) ) } /> { r.label }
							</label>
						) ) }
						<p className="gpb-hint">{ __( 'No role selected = all logged-in users.', 'grs-pop' ) }</p>
					</div>
				) }
				<Segmented label={ __( 'Visit', 'grs-pop' ) } value={ co.visitor } onChange={ setCo( 'visitor' ) } options={ [ { value: 'all', label: __( 'All', 'grs-pop' ) }, { value: 'new', label: __( 'First visit', 'grs-pop' ) }, { value: 'returning', label: __( 'Returning', 'grs-pop' ) } ] } />
				<NumberInput label={ __( 'After at least N pages viewed', 'grs-pop' ) } value={ co.pageviews } min={ 0 } max={ 100 } onChange={ setCo( 'pageviews' ) } help={ __( '0 = from the first page.', 'grs-pop' ) } />
				<Select
					label={ __( 'Coming from', 'grs-pop' ) }
					value={ co.referrer.type }
					onChange={ ( v ) => setCo( 'referrer' )( Object.assign( {}, co.referrer, { type: v } ) ) }
					options={ [
						{ value: 'any', label: __( 'Anywhere', 'grs-pop' ) },
						{ value: 'search', label: __( 'Search engines', 'grs-pop' ) },
						{ value: 'social', label: __( 'Social networks', 'grs-pop' ) },
						{ value: 'external', label: __( 'Any other site', 'grs-pop' ) },
						{ value: 'direct', label: __( 'Direct visits', 'grs-pop' ) },
						{ value: 'contains', label: __( 'A site whose address contains…', 'grs-pop' ) },
					] }
					help={ __( 'The source of the first page of the visit.', 'grs-pop' ) }
				/>
				{ co.referrer.type === 'contains' && <TextInput value={ co.referrer.value } onChange={ ( v ) => setCo( 'referrer' )( Object.assign( {}, co.referrer, { value: v } ) ) } placeholder="partner-site.com" /> }
				<h4 className="gpb-subtitle">{ __( 'Link parameters (UTM and others)', 'grs-pop' ) }</h4>
				{ co.params.map( ( p, i ) => (
					<div className="gpb-row" key={ i }>
						<TextInput value={ p.name } onChange={ ( v ) => setCo( 'params' )( co.params.map( ( x, n ) => ( n === i ? Object.assign( {}, x, { name: v } ) : x ) ) ) } placeholder="utm_source" />
						<TextInput value={ p.value } onChange={ ( v ) => setCo( 'params' )( co.params.map( ( x, n ) => ( n === i ? Object.assign( {}, x, { value: v } ) : x ) ) ) } placeholder={ __( 'any value', 'grs-pop' ) } />
						<button type="button" className="gpb-danger" onClick={ () => setCo( 'params' )( co.params.filter( ( x, n ) => n !== i ) ) } aria-label={ __( 'Remove', 'grs-pop' ) }>
							✕
						</button>
					</div>
				) ) }
				{ co.params.length < 5 && (
					<button type="button" className="button" onClick={ () => setCo( 'params' )( co.params.concat( [ { name: 'utm_campaign', value: '' } ] ) ) }>
						{ __( 'Add parameter', 'grs-pop' ) }
					</button>
				) }
				<p className="gpb-hint">{ __( 'Checked on the landing page and kept for the rest of the visit.', 'grs-pop' ) }</p>
				{ languages.length > 0 && (
					<div className="gpb-checks" role="group" aria-label={ __( 'Languages', 'grs-pop' ) }>
						<span className="gpb-field__label">{ __( 'Languages (none = all)', 'grs-pop' ) }</span>
						{ languages.map( ( l ) => (
							<label key={ l.value }>
								<input type="checkbox" checked={ tg.languages.indexOf( l.value ) !== -1 } onChange={ () => setTg( 'languages' )( toggleIn( tg.languages, l.value ) ) } /> { l.label }
							</label>
						) ) }
					</div>
				) }
			</Group>
			{ ( DATA.formsActive || fm.mode !== 'any' ) && <FormsRule fm={ fm } setFm={ setFm } toggleIn={ toggleIn } /> }
			<Group title={ __( 'Schedule', 'grs-pop' ) } open={ !! ( sched.start || sched.end || sched.days.length || sched.from ) }>
				<div className="gpb-row">
					<TextInput type="datetime-local" label={ __( 'From', 'grs-pop' ) } value={ sched.start } onChange={ setSched( 'start' ) } />
					<TextInput type="datetime-local" label={ __( 'To', 'grs-pop' ) } value={ sched.end } onChange={ setSched( 'end' ) } />
				</div>
				<div className="gpb-days" role="group" aria-label={ __( 'Days of the week', 'grs-pop' ) }>
					{ [ 1, 2, 3, 4, 5, 6, 0 ].map( ( d ) => (
						<button type="button" key={ d } className={ 'gpb-day' + ( sched.days.indexOf( d ) !== -1 ? ' is-active' : '' ) } aria-pressed={ sched.days.indexOf( d ) !== -1 } onClick={ () => setSched( 'days' )( toggleIn( sched.days, d ).sort() ) }>
							{ days[ d ] }
						</button>
					) ) }
				</div>
				<div className="gpb-row">
					<TextInput type="time" label={ __( 'From hour', 'grs-pop' ) } value={ sched.from } onChange={ setSched( 'from' ) } />
					<TextInput type="time" label={ __( 'To hour', 'grs-pop' ) } value={ sched.to } onChange={ setSched( 'to' ) } />
				</div>
				<p className="gpb-hint">{ __( 'Site time zone. Leave empty for no limits. Checked in the browser, so it works with page caching.', 'grs-pop' ) }</p>
			</Group>
		</div>
	);
}

/* ---------------------------------------------------------------------- */
/* Frequenza                                                                */
/* ---------------------------------------------------------------------- */

export function FrequencyPanel( { config, setConfig } ) {
	const f = config.frequency;
	const set = ( key ) => ( value ) => setConfig( Object.assign( {}, config, { frequency: Object.assign( {}, f, { [ key ]: value } ) } ) );
	const adv = config.advanced;
	const setAdv = ( key ) => ( value ) => setConfig( Object.assign( {}, config, { advanced: Object.assign( {}, adv, { [ key ]: value } ) } ) );
	return (
		<div className="gpb-panel">
			<Group title={ __( 'How often', 'grs-pop' ) }>
				<Select
					label={ __( 'Show', 'grs-pop' ) }
					value={ f.mode }
					onChange={ set( 'mode' ) }
					options={ [
						{ value: 'days', label: __( 'Once every few days', 'grs-pop' ) },
						{ value: 'visit', label: __( 'Once per visit', 'grs-pop' ) },
						{ value: 'session', label: __( 'Once per browser session (until the tab is closed)', 'grs-pop' ) },
						{ value: 'once', label: __( 'Only once', 'grs-pop' ) },
						{ value: 'always', label: __( 'On every page', 'grs-pop' ) },
					] }
				/>
				{ f.mode === 'days' && <NumberInput label={ __( 'Every', 'grs-pop' ) } value={ f.days } min={ 1 } max={ 365 } suffix={ __( 'days', 'grs-pop' ) } onChange={ set( 'days' ) } /> }
				{ f.mode === 'visit' && <NumberInput label={ __( 'A visit ends after', 'grs-pop' ) } value={ f.visit_minutes } min={ 5 } max={ 240 } suffix={ __( 'minutes without activity', 'grs-pop' ) } onChange={ set( 'visit_minutes' ) } help={ __( 'As in Google Analytics: 30 minutes. Activity is measured on the pages where GRS Pop is loaded.', 'grs-pop' ) } /> }
				{ f.mode === 'session' && <p className="gpb-hint">{ __( 'The browser session lasts until the tab is closed: it survives reloads and, on smartphones, it can last for days. For a popup that comes back at the next visit choose "Once per visit".', 'grs-pop' ) }</p> }
				{ f.mode === 'visit' && f.after_close_days > 0 && <Notice>{ sprintf( /* translators: %d: number of days. */ __( 'When the visitor closes it, it stays hidden for %d days (setting below): set 0 to show it again at the next visit.', 'grs-pop' ), f.after_close_days ) }</Notice> }
				<NumberInput label={ __( 'After it is closed, hide it for', 'grs-pop' ) } value={ f.after_close_days } min={ 0 } max={ 365 } suffix={ __( 'days', 'grs-pop' ) } onChange={ set( 'after_close_days' ) } help={ f.mode === 'visit' ? __( '0 = only until the end of the visit.', 'grs-pop' ) : __( '0 = only until the end of the browser session.', 'grs-pop' ) } />
				<Select
					label={ __( 'After a conversion', 'grs-pop' ) }
					value={ f.after_convert }
					onChange={ set( 'after_convert' ) }
					options={ [ { value: 'never', label: __( 'Never show it again', 'grs-pop' ) }, { value: 'days', label: __( 'Hide it for some days', 'grs-pop' ) }, { value: 'keep', label: __( 'Keep showing it', 'grs-pop' ) } ] }
				/>
				{ f.after_convert === 'days' && <NumberInput label={ __( 'Hide for', 'grs-pop' ) } value={ f.after_convert_days } min={ 1 } max={ 3650 } suffix={ __( 'days', 'grs-pop' ) } onChange={ set( 'after_convert_days' ) } /> }
				<NumberInput label={ __( 'Maximum views per visitor (0 = no limit)', 'grs-pop' ) } value={ f.max_views } min={ 0 } max={ 1000 } onChange={ set( 'max_views' ) } />
				<p className="gpb-hint">{ __( 'Remembered only in the visitor\'s browser, with technical data and no personal information.', 'grs-pop' ) }</p>
			</Group>
			<Group title={ __( 'Advanced', 'grs-pop' ) } open={ false }>
				<NumberInput label={ __( 'Priority', 'grs-pop' ) } value={ adv.priority } min={ 1 } max={ 100 } onChange={ setAdv( 'priority' ) } help={ __( 'When several popups could open on the same page, the one with the highest priority goes first.', 'grs-pop' ) } />
				<TextInput label={ __( 'Extra CSS classes', 'grs-pop' ) } value={ adv.css_class } onChange={ setAdv( 'css_class' ) } placeholder="my-popup" />
			</Group>
		</div>
	);
}
