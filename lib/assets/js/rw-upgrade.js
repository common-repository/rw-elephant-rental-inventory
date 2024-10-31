( function( $ ) {

  var upgradeRoutines = rwUpgrades.upgrades,
      count = 0,
      interval;

  var rweUpgrade = {

    init: function() {

      if ( ! upgradeRoutines.length ) {

        return;

      }

      interval = setInterval( rweUpgrade.doAjax, 1500 );

    },

    doAjax: function() {

      if ( count >= upgradeRoutines.length ) {

        clearInterval( interval );

        // Redirect the user after 5 seconds when final ajax request finishes
        setTimeout( function() {

          window.location.href = rwUpgrades.settingsURL;

        }, 2000 );

        return;

      }

      $.ajax( {
        type: "POST",
        url: ajaxurl,
        data: {
          'action': upgradeRoutines[ count ]
        },
        error: rweUpgrade.errorHandler,
        success: rweUpgrade.successHandler
      } );

      count = count + 1;

    },

    errorHandler: function() {

      console.error( rwUpgrades.errorResponse );

    },

    successHandler: function( response ) {

      // Tweak progress bar width
      rweUpgrade.increaseProgressBar();

      if ( ! response.success ) {

        rweUpgrade.errorHandler();

        return;

      }

    },

    increaseProgressBar: function() {

      $( '.rwe-progress .progress-bar' ).css( 'width', parseInt( ( count / upgradeRoutines.length ) * 100 ) + '%' );

    }

  };

  $( document ).ready( function() {
    setTimeout( function() {
      rweUpgrade.init();
    }, 5000 );
  } );

} )( jQuery );
