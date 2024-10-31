( function( $ ) {

  /**
   * Watchlist functionality
   *
   * @type {Object}
   */
  var rweWishlists = {

    /**
     * Add item to wishlist.
     */
    addToWishlist: function( e ) {

      e.preventDefault();

      // Remove element focus
      $( this ).blur();

      // Bail if the button was recently clicked and is 'loading'
      if ( $( this ).hasClass( 'loading' ) ) {

        return;

      }

      var itemID     = $( this ).data( 'itemid' );
      var qty        = $( this ).data( 'wishlistquantity' );
      var removetext = $( this ).data( 'wishlistremovetext' );
      var addtext    = $( this ).data( 'wishlistaddtext' );

      if ( $( this ).hasClass( 'remove-from-wishlist' ) ) {

        rweWishlists.remove( this, itemID, qty, addtext );

        return;

      }

      rweWishlists.add( this, itemID, qty, removetext );

    },

    /**
     * Add item to wishlist.
     */
    add: function( button, itemID, qty, removetext ) {

      var cookie = rweWishlists.get(),
          qtyval = parseInt( qty ),
          quantity = ( typeof qty !== 'undefined') ? qty : ( 0 === qtyval ? 1 : qtyval );

      if ( cookie ) {

        cookie.push( { itemID: itemID, quantity: quantity } );

      } else {

        cookie = [ { itemID: itemID, quantity: quantity } ];

      }

      rweWishlists.togglePreloader( button );

      rweWishlists.set( cookie );

      setTimeout( function() {

        var itemName = $( button ).closest( '.rwe-item' ).find( '.rwe-item__link' ).attr( 'title' );

        if ( 'undefined' === typeof itemName ) {

          itemName = $( button ).closest( '.rwe-data' ).find( '.rwe-data__title' ).text();

        }

        rweWishlists.addToWidget( itemID, itemName );
        $( button ).find( 'span.text' ).html( removetext );
        $( button ).removeClass( 'add-to-wishlist' ).addClass( 'remove-from-wishlist' );

        rweWishlists.togglePreloader( button );

      }, 800 );

    },

    /**
     * Remove an item from the wishlist widget.
     */
    removeFromWishlistWidget: function( itemID ) {

      $( 'ul.wishlist' ).find( 'li.item[data-itemid="' + itemID + '"]' ).fadeOut( 'slow', function() {

        $( this ).remove();

        if ( ! $( 'ul.wishlist > li.item' ).length ) {

          $( 'ul.wishlist' ).append( '<li class="empty-wishlist-text"><p>' + rweWishlistData.emptyWishlistText + '</p></li>' );

        }

      } );

    },

    /**
     * Add an item to the wishlist widget.
     */
    addToWidget: function( itemID, itemName ) {

      $( 'li.empty-wishlist-text' ).remove();

      var wishlist = $( 'ul.wishlist' ),
          newItem  = '<li class="item" data-itemid="' + itemID + '">' +
                       '<a href="' + rweWishlistData.urlBase.replace( 'productID', itemID ) + '">' + itemName + '</a>' +
                       '<div class="js-remove-from-wishlist rwe-item__remove" data-itemid="' + itemID + '">' +
                          '<span class="rwe-icon rwe-icon--remove"><svg viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><path d="M5 3.586L2.173.759A.999.999 0 1 0 .76 2.173L3.586 5 .759 7.827A.999.999 0 1 0 2.173 9.24L5 6.414l2.827 2.827A.999.999 0 1 0 9.24 7.827L6.414 5l2.827-2.827A.999.999 0 1 0 7.827.76L5 3.586z" fill-rule="evenodd"></path></svg></span>' +
                       '</div>' +
                     '</li>';

      wishlist.append( newItem );

    },

    /**
     * Remove item from wishlist.
     */
    remove: function( button, itemID, qty, addtext ) {

      var cookie = rweWishlists.get();

      if ( ! cookie ) {

        console.error( 'RW Elephant wishlist cookie not set.' );

        return;

      }

      rweWishlists.togglePreloader( button );

      for ( var i = 0; i < cookie.length; i++ ) {

        if ( cookie[i].itemID === itemID ) {

          cookie.splice( i, 1 );

        }

      }

      rweWishlists.set( cookie );

      setTimeout( function() {

        $( '.js-add-to-wishlist[data-itemid="' + itemID + '"]' ).find( 'span.text' ).html( addtext );
        $( '.js-add-to-wishlist[data-itemid="' + itemID + '"]' ).removeClass( 'remove-from-wishlist' ).addClass( 'add-to-wishlist' );

        rweWishlists.removeFromWishlistWidget( itemID );
        rweWishlists.togglePreloader( button );

      }, 800 );

    },

    updateQuantity: function() {

      var itemID   = $( this ).data( 'inventory-id' ),
          quantity = $( this ).val();

      rweWishlists.remove( this, itemID );
      rweWishlists.add( this, itemID, quantity );

    },

    /**
     * Get the wishlist JSON array.
     */
    get: function() {

      var re    = new RegExp( "rw-elephant-wishlist=([^;]+)" ),
          value = re.exec( document.cookie );

      return ( value != null ) ? JSON.parse( value[1] ) : null;

    },

    /**
     * Set the cookie 'rw-elephant-wishlist' value with 7 day expiration.
     */
    set: function( value ) {

      var today  = new Date(),
          expire = new Date();

      expire.setTime( today.getTime() + 3600000 * 24 * 7 );

      document.cookie = "rw-elephant-wishlist=" + JSON.stringify( value ) + "; expires='" + expire.toGMTString() + "'; path=/";

    },

    /**
     * Toggle the preloader visibility and loading class.
     */
    togglePreloader: function( button ) {

      $( button ).toggleClass( 'loading' ).find( '.rwe-circle-preloader' ).toggle();

    },

    /**
     * Remove an item from the wishlist page.
     */
    removeFromWishlistPage: function() {

      var itemID = $( this ).data( 'itemid' );

      rweWishlists.remove( this, itemID );

      $( this ).closest( '.rwe-grid__item, li.item' ).fadeOut( 'slow', function() {

        $( this ).remove();

        if ( ! $( 'ul.wishlist > li.item' ).length ) {

          $( 'ul.wishlist' ).append( '<li class="empty-wishlist-text"><p>' + rweWishlistData.emptyWishlistText + '</p></li>' );

        }

        if ( $( '.rwe-item' ).length >= 1 ) {

          return;

        }

        // If no items on the list, remove the "Submit Wishlist" button & display the empty wishlist notice.
        $( '.rwe-submit-wishlist-btn' ).remove();
        $( '.rwe-wishlist-empty' ).fadeIn();

      } );

    },

    datePickerInit: function() {

      if ( ! $( '.js-date').length ) {

        return;

      }

      var picker = new Pikaday( {
        field: $( '.js-date' )[0],
        format: 'MM-DD-YYYY'
      } );

    },

    /**
     * Prevent users from submitting the wishlist if the quantity is greater than the inventory.
     */
    validateWishlist: function( e ) {
      $( '.rwe-item__quantity-input .text-input' ).each( function() {

        var max = $( this ).attr( 'max' );
        var value = $( this ).val();

        if ( parseInt( max ) < parseInt( value ) ) {
          e.preventDefault();
          $( this ).after( '<p class="error">' + rweWishlistData.wishlistQuantityError  + max + '</p>' );
        }

        // if value is 0, replace it with 1
        if ( value == 0 ) {
          $( this ).val( 1 );
        }

      } );

    },

  };

  // Wishlist
  $( 'body' ).on( 'click', '.js-add-to-wishlist', rweWishlists.addToWishlist );
  $( 'body' ).on( 'click', '.js-remove-from-wishlist', rweWishlists.removeFromWishlistPage );
  $( 'body' ).on( 'click', '.rwe-submit-wishlist-btn', rweWishlists.validateWishlist );
  $( 'body' ).on( 'change', '.rwe-item__quantity-input input[type="number"]', rweWishlists.updateQuantity );
  $( document ).ready( rweWishlists.datePickerInit );

} )( jQuery );
