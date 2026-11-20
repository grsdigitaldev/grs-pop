/**
 * GRS Pop - scheda "Coming soon": scelta del logo, copia del link segreto, anteprima per dispositivo.
 *
 * @package GRSDigital\Pop
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $media = $( '.grs-pop-ms-media' );
		var frame = null;

		$media.on( 'click', '.grs-pop-ms-media__pick', function ( e ) {
			e.preventDefault();
			if ( ! window.wp || ! wp.media ) {
				return;
			}
			if ( ! frame ) {
				frame = wp.media( {
					title: $media.data( 'title' ),
					button: { text: $media.data( 'button' ) },
					library: { type: 'image' },
					multiple: false,
				} );
				frame.on( 'select', function () {
					var item = frame.state().get( 'selection' ).first().toJSON();
					var url = item.sizes && item.sizes.medium ? item.sizes.medium.url : item.url;
					$media.find( '.grs-pop-ms-media__id' ).val( item.id );
					$media.find( '.grs-pop-ms-media__preview' ).empty().append( $( '<img alt="">' ).attr( 'src', url ) );
					$media.find( '.grs-pop-ms-media__remove' ).prop( 'hidden', false );
					$( 'input[name="grs_pop_maintenance[logo]"][value="custom"]' ).prop( 'checked', true );
				} );
			}
			frame.open();
		} );

		$media.on( 'click', '.grs-pop-ms-media__remove', function ( e ) {
			e.preventDefault();
			$media.find( '.grs-pop-ms-media__id' ).val( '0' );
			$media.find( '.grs-pop-ms-media__preview' ).empty();
			$( this ).prop( 'hidden', true );
		} );

		$( '.grs-pop-ms-preset' ).on( 'click', function ( e ) {
			var message = $( this ).data( 'confirm' );
			if ( message && ! window.confirm( message ) ) {
				e.preventDefault();
			}
		} );

		$( '.grs-pop-ms-copy' ).on( 'click', function () {
			var $btn = $( this );
			var input = document.getElementById( $btn.data( 'target' ) );
			if ( ! input ) {
				return;
			}
			var done = function () {
				var label = $btn.text();
				$btn.text( $btn.data( 'done' ) );
				setTimeout( function () {
					$btn.text( label );
				}, 1500 );
			};
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( input.value ).then( done, function () {
					input.select();
				} );
			} else {
				input.select();
				try {
					document.execCommand( 'copy' );
					done();
				} catch ( err ) {}
			}
		} );

		$( '.grs-pop-ms-device' ).on( 'click', function () {
			var $btn = $( this );
			$( '.grs-pop-ms-device' ).removeClass( 'is-active' ).attr( 'aria-pressed', 'false' );
			$btn.addClass( 'is-active' ).attr( 'aria-pressed', 'true' );
			$( '.grs-pop-ms-preview__frame' ).css( 'width', $btn.data( 'width' ) );
		} );
	} );
} )( jQuery );
