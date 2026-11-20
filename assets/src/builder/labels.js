/**
 * Etichette del builder (traducibili).
 *
 * @package GRSDigital\Pop
 */

import { __ } from './i18n';


export const formatLabels = () => ( {
	modal: __( 'Popup', 'grs-pop' ),
	slidein: __( 'Slide-in', 'grs-pop' ),
	bar: __( 'Bar', 'grs-pop' ),
	fullscreen: __( 'Full screen', 'grs-pop' ),
	tab: __( 'Floating tab', 'grs-pop' ),
	inline: __( 'Inline', 'grs-pop' ),
} );

export const positionLabels = () => ( {
	center: __( 'Centre', 'grs-pop' ),
	top: __( 'Top', 'grs-pop' ),
	bottom: __( 'Bottom (full width)', 'grs-pop' ),
	'bottom-right': __( 'Bottom right', 'grs-pop' ),
	'bottom-left': __( 'Bottom left', 'grs-pop' ),
	'top-right': __( 'Top right', 'grs-pop' ),
	'top-left': __( 'Top left', 'grs-pop' ),
	right: __( 'Right side', 'grs-pop' ),
	left: __( 'Left side', 'grs-pop' ),
} );

export const deviceLabels = () => ( {
	desktop: __( 'Desktop', 'grs-pop' ),
	tablet: __( 'Tablet', 'grs-pop' ),
	mobile: __( 'Mobile', 'grs-pop' ),
} );

export const sectionLabels = () => ( {
	heading: __( 'Title', 'grs-pop' ),
	text: __( 'Text', 'grs-pop' ),
	button: __( 'Button', 'grs-pop' ),
	form: __( 'Form (GRS Forms)', 'grs-pop' ),
	coupon: __( 'Coupon code', 'grs-pop' ),
	countdown: __( 'Countdown', 'grs-pop' ),
	list: __( 'List', 'grs-pop' ),
	spacer: __( 'Spacer', 'grs-pop' ),
	dismiss: __( '"No thanks" link', 'grs-pop' ),
	shortcode: __( 'Shortcode', 'grs-pop' ),
} );

export const iconLabels = () => ( {
	none: __( 'No icon', 'grs-pop' ),
	arrow: __( 'Arrow', 'grs-pop' ),
	whatsapp: __( 'WhatsApp', 'grs-pop' ),
	phone: __( 'Phone', 'grs-pop' ),
	mail: __( 'Email', 'grs-pop' ),
	gift: __( 'Gift', 'grs-pop' ),
	download: __( 'Download', 'grs-pop' ),
	calendar: __( 'Calendar', 'grs-pop' ),
	cart: __( 'Cart', 'grs-pop' ),
} );

export const ruleLabels = () => ( {
	front_page: __( 'Home page', 'grs-pop' ),
	blog: __( 'Blog page', 'grs-pop' ),
	singular: __( 'All items of a type', 'grs-pop' ),
	posts: __( 'Specific pages or posts', 'grs-pop' ),
	terms: __( 'Category, tag or other term', 'grs-pop' ),
	archive: __( 'Archive of a type', 'grs-pop' ),
	search: __( 'Search results', 'grs-pop' ),
	404: __( 'Page not found (404)', 'grs-pop' ),
	url: __( 'Address contains / starts with / equals', 'grs-pop' ),
} );

export const dayLabels = () => [
	__( 'Sun', 'grs-pop' ),
	__( 'Mon', 'grs-pop' ),
	__( 'Tue', 'grs-pop' ),
	__( 'Wed', 'grs-pop' ),
	__( 'Thu', 'grs-pop' ),
	__( 'Fri', 'grs-pop' ),
	__( 'Sat', 'grs-pop' ),
];
