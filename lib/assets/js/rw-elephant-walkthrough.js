/**
 * RW Elephant Rental Inventory Setup Walkthrough
 *
 * @author RW Elephant <info@rwelephant.com>
 * @since 2.0.0
 */
( function( $ ) {

  var transitionEvent = rweWhichTransitionEvent();

  var rweWalkthrough = {

    /**
     * Initialize the Walkthrough
     */
    init: function() {

      $.featherlight( $( '.rwe-walkthrough' ), {
        variant: 'rw-elephant-walkthrough',
        afterOpen: function() {
          setTimeout( function() {
            $( '.rw-elephant-walkthrough > .featherlight-content' ).css( 'opacity', 0 ).addClass( 'animated fadeInDown' );
          }, 5 );
          // Set the apropriate tabindex on input fields
          // featherlight bug: https://github.com/noelboss/featherlight/issues/285
          var count = 1;
          $( '.rw-elephant-walkthrough > .featherlight-content' ).find( 'input[type="text"], select, a.js-go-to-step' ).each( function() {
            $( this ).attr( 'tabindex', count++ );
          } );
        }
      } );

    },

    /**
     * Go to a setup step.
     *
     * @param {integer} step The step to go to.
     * @param {string}  type The action to take. next|prev|finish
     */
    goToStep: function( step, type ) {

      var currentStep       = ( 'next' === type ) ? step - 1 : step + 1,
          currStepAnimation = ( 'next' === type ) ? 'fadeOutLeft' : 'fadeOutRight',
          nextStepAnimation = ( 'next' === type ) ? 'fadeInRight' : 'fadeInLeft';

      if ( rweWalkthrough.checkRequiredFields( '.step-' + currentStep ) ) {

        return;

      }

      if ( 'finish' === type ) {

        // AJAX request to update options, generate pages etc.
        rweWalkthrough.completeSetup( {
          tenantID:     $( '.featherlight-content #tenant-id' ).val().trim(),
          apiKey:       $( '.featherlight-content #api-key' ).val().trim(),
          galleryPage:  $( '.featherlight-content #gallery-page' ).val(),
          wishlistPage: $( '.featherlight-content #wishlist-page' ).val(),
        } );

        return;

      }

      // Fade in preloader
      $( '.rwe-preloader' ).fadeTo( 1000, 1 );

      // Fade out current step
      $( '.step-' + currentStep ).addClass( 'animated ' + currStepAnimation ).one( transitionEvent, function( event ) {

        var data = {
          'action': 'next_step',
          'step': step,
        };

        // Current step 2, next step 3
        if ( 2 === currentStep && 3 === step ) {

          data.tenantID = $( '.featherlight-content #tenant-id' ).val().trim();
          data.apiKey   = $( '.featherlight-content #api-key' ).val().trim();

        }

        jQuery.post( ajaxurl, data, function( response ) {

          $( '.step-2' ).find( '.connection-error' ).html( '' ).addClass( 'hidden' );

          if ( ! response.success ) {

            $( '.rwe-preloader' ).fadeTo( 'fast', 0 );

            $( '.step-2' ).find( '.connection-error' ).html( response.data.error ).removeClass( 'hidden' );

            $( '.step-2' ).addClass( 'animated fadeInLeft' ).one( transitionEvent , function() {
              $( this ).removeClass( 'animated fadeInLeft fadeOutLeft' );
            } );

            return;

          }

          // Add hidden class to current step
          $( '.step-' + currentStep ).addClass( 'hidden' ).removeClass( 'animated ' + currStepAnimation );

          // Hide the preloader
          $( '.rwe-preloader' ).fadeTo( 'fast', 0 );

          // Slide in next step and remove animation classes
          $( '.step-' + step ).removeClass( 'hidden' ).addClass( 'animated ' + nextStepAnimation ).one( transitionEvent, function() {
            $( this ).removeClass( 'animated ' + nextStepAnimation );
          } );

        } );

      } );

    },

    /**
     * Check a required field contains a value.
     * @param  {[type]} currentStep [description]
     * @return {[type]}             [description]
     */
    checkRequiredFields: function( currentStep ) {

      var error = false;

      $( currentStep + ' input' ).filter( '[required]:visible' ).each( function() {

        if ( ! $( this ).val() || '' === $( this ).val().trim() ) {

          error = true;

          $( this ).addClass( 'required-field' );

          return true;

        }

        $( this ).removeClass( 'required-field' );

      } );

      return error;

    },

    /**
     * Complete the plugin setup.
     * Submits the data to our AJAX handler to update the plugin options
     * and generate the gallery and wishlist pages, if none were set.
     *
     * @param {object} formFields Walkthrough form fields.
     *
     * @return {boolean}           True if setup was successfuly, else false.
     */
    completeSetup: function( formFields ) {

      $( '.step-4' ).addClass( 'animated fadeOutLeft' ).one( transitionEvent, function( event ) {
        // Hide current step
        $( '.step-4' ).addClass( 'hidden' ).removeClass( 'animated fadeOutLeft' );
        // Toggle preloader
        $( '.final-step > .rwe-preloader' ).fadeTo( 'fast', 0 );
        // Slide in next step and remove animation classes
        $( '.final-step' ).removeClass( 'hidden' ).addClass( 'animated fadeInRight' ).one( transitionEvent, function() {
          $( this ).removeClass( 'animated fadeInRight' );

          var data = {
            'action': 'complete_plugin_setup',
            'rw_plugin_data': formFields
          };

          // Run the AJAX request
          jQuery.post( ajaxurl, data, function( response ) {

            $( '.rwe-preloader-final' ).hide();
            $( '.final-step .current-step' ).html( rweWalkthroughData.complete );

            if ( ! response.success ) {

              $( '.walkthrough-results' ).addClass( 'danger' ).html( response.data );

              return;

            }

            $( '.walkthrough-results' ).addClass( 'success' ).html( response.data );

          } );

        } );
      } );

    }

  };

  $( document ).on( 'ready', rweWalkthrough.init );

    $( document ).on( 'keyup', '#tenant-id, #api-key', function( e ) {
      $( this ).removeClass( 'required-field' );
    } );

  $( document ).on( 'click', '.js-go-to-step', function( e ) {
    e.preventDefault();
    rweWalkthrough.goToStep( $( this ).data( 'step' ), $( this ).data( 'type' ) );
  } );

} )( jQuery );

/**
 * Helper to prevent multiple events fired in browser
 *
 * @return {string} Transition type, dependant on browser.
 */
function rweWhichTransitionEvent() {

  var t,
      el = document.createElement( 'fakeelement' );

  var transitions = {
    'animation': 'animationend',
    'OAnimation': 'oAnimationEnd',
    'MozAnimation': 'animationend',
    'WebkitAnimation': 'webkitAnimationEnd'
  };

  for ( t in transitions ) {

    if ( el.style[ t ] !== undefined ) {

      return transitions[ t ];

    }

  }

}
