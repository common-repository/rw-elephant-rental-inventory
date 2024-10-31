( function( $ ) {

  /**
   * Image preview and slider
   *
   * @type {Object}
   */
  var rweSlider = {

    init: function() {

      var $carousel = $( '.rwe-gallery__for' ),
          $nav      = $( '.rwe-gallery__nav' );

      if ( ! $carousel.length ) {

        return;

      }

      $carousel.slick( {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        centerPadding: '0',
        asNavFor: '.rwe-gallery__nav'
      } );

      $nav.slick( {
        slidesToShow: 4,
        slidesToScroll: 1,
        focusOnSelect: true,
        centerPadding: '0',
        asNavFor: '.rwe-gallery__for'
      } );

      $carousel.slickLightbox();

      // Prevent FOUC of the image slider
      setTimeout( function() {

        // Fade out the preloader
        $( '.rwe-single__gallery' ).find( '.rwe-circle-preloader' ).fadeOut( 'fast', function() {

          // Fade in the image slider
          $( '.rwe-gallery' ).removeAttr( 'style' );
          $( '.slick-track, .rwe-gallery' ).fadeTo( 800, 1 );

        } );

      }, 400 );

    },

  };

  /**
   * pikaday date selector.
   *
   * @type {Object}
   */
  var pikaday = {

    init: function() {

      if ( ! $( '.js-date' ).length ) {

        return;

      }

      $( '.js-date' ).each( function() {
        new Pikaday( {
          field: $( this )[0],
          format: 'MM-DD-YYYY'
        } );
      } );

    },

  };

	/**
   * Wishlist submission validation
   *
   * @type {Object}
   */
	var submitWishlist = {

		submit: function( e ) {

			var date = $( '#event_date' ).val();

			if ( ! date.length ) {

				e.preventDefault();

				return;

			}

			$( '#event_date' ).css( 'border-color', '' );
			$( '.rwe-wishlist-error' ).remove();
			jQuery( '.rwe-wishlist-form .rwe-circle-preloader' ).addClass( 'hidden' );

			jQuery( '.rwe-wishlist-form input' ).each( function() {

				jQuery( this ).removeAttr( 'disabled' ).css( 'opacity', 1 );

			} );

			if ( ! submitWishlist.validateDate( date ) ) {

				$( '#event_date' ).css( 'border-color', '#ffa5a5' ).after( '<p class="rwe-wishlist-error">' + rwe.i18n.invalidDateFormat + '</p>' );

				e.preventDefault();

				return;

			}

			jQuery( '.rwe-wishlist-form input' ).each( function() {

				jQuery( this ).css( 'opacity', 0.5 );

			} );

			jQuery( '.rwe-wishlist-form .rwe-circle-preloader' ).removeClass( 'hidden' );

		},

		validateDate: function( date ) {

			var dateRegex = /^(0[1-9]|1[0-2])-(0[1-9]|1\d|2\d|3[01])-(19|20)\d{2}$/;

			return dateRegex.test( date );

		},

	};

  // Video controls
  $( '.rwe-gallery__item video' ).on( 'click', function( e ) {
	  var video  = $( this );
	  var play   = $( this ).next( '.controls' ).find( '.play' );
	  var pause  = $( this ).next( '.controls' ).find( '.pause' );
	  var parent = $( this ).parent( '.rwe-gallery__item' );
	  if( video[0].paused || video[0].ended ) {
		  video[0].play();
		  play.hide();
		  pause.show();
		  parent.toggleClass( 'is_playing' );
	  } else {
		  video[0].pause();
		  play.show();
		  pause.hide();
		  parent.toggleClass( 'is_playing' );
	  }
	  return false;
  } );

	$( '.rwe-gallery__item video' ).on( 'ended', function( e ) {
		var play   = $( this ).next( '.controls' ).find( '.play' );
		var pause  = $( this ).next( '.controls' ).find( '.pause' );
		var parent = $( this ).parent( '.rwe-gallery__item' );

		play.show();
		pause.hide();
		parent.toggleClass( 'is_playing' );
		return false;
	} );

  // Image preview and slider
  $( window ).on( 'load', rweSlider.init );
  // Date picker
  $( window ).on( 'load', pikaday.init );

  // Clientside validatiion the wishlist fields as needed
  $( 'body' ).on( 'submit', '.rwe-wishlist-form', submitWishlist.submit );

} )( jQuery );
