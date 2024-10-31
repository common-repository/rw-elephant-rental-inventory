/**
 * RW Elephant Rental Inventory Settings Scripts
 *
 * @author RW Elephant <info@rwelephant.com>
 * @since 2.0.0
 */
( function( $ ) {

  /**
   * Initialize the color pickers.
   *
   * @type {Object}
   */
  var rwColorPicker = {

    init: function() {

      $( '.rwe-color-picker' ).each( function() {

        $( this ).wpColorPicker();

      } );

    },

  };

  /**
   * Initialize the sortable fields.
   *
   * @type {Object}
   */
  var rwSortable = {

    init: function() {

      $( '.js-sortable' ).sortable( {
        items: '.item',
        axis: 'y',
        placeholder: {
          element: function( e ) {
            var isTable         = e.is( 'tr' ),
                height          = $( e ).height() + 'px',
                width           = $( e ).width() + 'px',
                placeholderElem = isTable ? '<tr class="placeholder" style="height: ' + height + '; width: ' + width + ';"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>' : '<li class="placeholder" style="height: ' + height + '; width: ' + width + ';">&nbsp;</li>';

            return $( placeholderElem )[0];
          },
          update: function() {
            return;
          },
        },
        helper: function( e, tr ) {
          if ( ! tr.is( 'tr' ) ) {
            return tr;
          }
          var $originals = tr.children(),
              $helper    = tr.clone();
          $helper.children().each( function( index ) {
            $( this ).width( $originals.eq( index ).outerWidth() );
          } );
          return $helper;
        }
      } ).disableSelection();

    },

  };

  /**
   * Image selector fields.
   * Thumbnail Image Replacement
   *
   * @type {Object}
   */
  var rwImageSelector = {

    addImage: function() {

      var clickedButton = $( this );

      media_uploader = wp.media( {
        frame:    "post",
        state:    "insert",
        multiple: false
      } );

      media_uploader.on( 'insert', function() {

        var json       = media_uploader.state().get( 'selection' ).first().toJSON(),
            image_url  = json.url,
            image_id   = json.id,
            parent     = clickedButton.closest( 'td' ),
            image_wrap = parent.find( '.image-wrap' );

        clickedButton.addClass( 'hidden' );
        parent.find( '.js-image-url' ).val( image_url );
        parent.find( '.js-image-id' ).val( image_id );
        image_wrap.find( 'img.image-preview' ).attr( 'src', image_url );
        image_wrap.removeClass( 'hidden' );

      } );

      media_uploader.open();

    },

    removeImage: function( e ) {

      e.preventDefault();

      var clickedButton = $( this ),
          parent        = clickedButton.closest( 'td' );

      parent.find( '.image-wrap' ).addClass( 'hidden' );
      parent.find( '.js-image-url' ).val( '' );
      parent.find( '.js-image-id' ).val( '' );
      parent.find( '.image-preview' ).attr( 'src', '' );
      parent.find( '.js-add-image' ).removeClass( 'hidden' );

    },

  };

  /**
   * Item notes clone
   *
   * @type {Object}
   */
  rwItemNotes = {

    /**
     * Add an item t the item notes list.
     */
    add: function( e ) {

      e.preventDefault();

      var cloneTarget = $( '.item-note.cloneable' ),
          $clone      = cloneTarget.clone();

      $clone.removeClass( 'cloneable hidden' );
      $clone.appendTo( '.item-notes' );

      $( '.item-notes' ).children( 'li' ).each( function() {

        var index = $( this ).index();

        $clone.find( 'input[type="text"]' ).attr( 'name', 'rw-elephant-rental-inventory[item-notes][' + index + '][title]' );
        $clone.find( 'textarea' ).attr( 'name', 'rw-elephant-rental-inventory[item-notes][' + index + '][text]' );

      } );


    },

    /**
     * Remove an item from the item notes list.
     */
    remove: function( e ) {

      e.preventDefault();

      $( this ).closest( 'li.item-note' ).fadeOut( 'fast', function() {
        $.when( $( this ).remove() ).then( function() {
          $( '.item-notes' ).children( 'li' ).each( function() {

            var index = $( this ).index();

            $( this ).find( 'input[type="text"]' ).attr( 'name', 'rw-elephant-rental-inventory[item-notes][' + index + '][title]' );
            $( this ).find( 'textarea' ).attr( 'name', 'rw-elephant-rental-inventory[item-notes][' + index + '][text]' );

          } );

        } );
      } );

    }

  };

  /**
   * Additional Form Fields
   *
   * @type {Object}
   */
  rwFormFields = {

    // Add a field to the wishlist form.
    add: function( e ) {

      e.preventDefault();

      var btn = $( this );

      $.featherlight( $( '#edit-field' ), {
        variant: 'rw-elephant-edit-wishlist-field',
        afterOpen: function() {

          // Ajax request
          var data = {
            'action': 'get_wishlist_form_field_data',
            'key': $( '.wishlist-form-field' ).length,
            'type': btn.data( 'type' )
          };

          $.post( ajaxurl, data, function( response ) {

            $( '#edit-field > .preloader' ).hide();

            // Error
            if ( ! response.success ) {

              $( '#edit-field > div.js-edit-field-form' ).html( '<div class="rwe-alert danger">' + rwElephant.editFieldError + '</div>' ).removeClass( 'hidden' );

              return;

            }

            $( '#edit-field > div.js-edit-field-form' ).html( response.data ).removeClass( 'hidden' ).find( 'input[type="submit"]' ).addClass( 'add-field' ).val( rwElephant.addFieldBtnText );

          } );

        },
        afterClose: function() {

          // Reset the modal
          $( '#edit-field > .preloader' ).removeClass( 'updating' ).show();
          $( '#edit-field > .js-update-wishlist-field' ).find( 'input' ).attr( 'disabled', 'disabled' );
          $( '#edit-field > div.js-edit-field-form' ).html( '' ).addClass( 'hidden' );

        }
      } );

    },

    // Remove a field from the wishlist form.
    remove: function( e ) {

      e.preventDefault();

      if ( ! confirm( rwElephant.removeFieldConfirmation ) ) {

        return;

      }

      var btn = $( this );

      // Ajax request
      var data = {
        'action': 'delete_wishlist_form_field',
        'key': btn.data( 'key' )
      };

      $.post( ajaxurl, data, function( response ) {

        if ( ! response.success ) {

          $( '.wishlist-form-fields' ).before( '<div class="rwe-alert danger add-field-error"><p>' + response.data.message + '</p></div>' );

          return;

        }

        btn.parent( 'li' ).remove();

      } );

    },

    // Edit an existing field.
    edit: function( e ) {

      var btn = $( this );

      $.featherlight( $( '#edit-field' ), {
        variant: 'rw-elephant-edit-wishlist-field',
        afterOpen: function() {

          // Ajax request
          var data = {
            'action': 'get_wishlist_form_field_data',
            'key': btn.data( 'key' ),
            'type': btn.closest( 'li' ).find( '.field-type' ).val()
          };

          $.post( ajaxurl, data, function( response ) {

            $( '#edit-field > .preloader' ).hide();

            // Error
            if ( ! response.success ) {

              $( '#edit-field > div.js-edit-field-form' ).html( '<div class="rwe-alert danger">' + rwElephant.editFieldError + '</div>' ).removeClass( 'hidden' );

              return;

            }

            $( '#edit-field > div.js-edit-field-form' ).html( response.data ).removeClass( 'hidden' );

          } );

        },
        afterClose: function() {

          // Reset the modal
          $( '#edit-field > .preloader' ).removeClass( 'updating' ).show();
          $( '#edit-field > .js-update-wishlist-field' ).find( 'input' ).attr( 'disabled', 'disabled' );
          $( '#edit-field > div.js-edit-field-form' ).html( '' ).addClass( 'hidden' );

        }
      } );

    },

    // Add an option to a field
    addOption: function( e ) {

      e.preventDefault();

      var form         = $( '.rw-elephant-edit-wishlist-field' ).find( '.js-update-wishlist-field, .js-add-wishlist-field' ),
          blankOptions = form.find( 'li.blank-options' ).clone(),
          optionsText  = form.find( 'li.no-options-message' ),
          options      = form.find( 'ul.options' );

      optionsText.hide();

      options.append( blankOptions );
      options.find( 'li.blank-options' ).removeAttr( 'class' );

    },

    // Add a field
    addField: function( e ) {

      e.preventDefault();

      var modal    = $( '.rw-elephant-edit-wishlist-field' ),
          formData = $( this ).serialize();

      modal.find( 'h2' ).fadeTo(  'fast', 0.5 );
      modal.find( '.js-edit-field-form' ).fadeTo( 'fast', 0.5, function() {

        modal.find( 'input' ).attr( 'disabled', 'disabled' );
        modal.find( '.preloader' ).addClass( 'updating' ).show();

        // Ajax request to add the field to the options array
        var data = {
          'action': 'add_wishlist_form_field',
          'data': formData
        };

        $.post( ajaxurl, data, function( response ) {

          $.featherlight.close();

          if ( ! response.success ) {

            $( '.wishlist-form-fields' ).before( '<div class="rwe-alert danger add-field-error"><p>' + response.data.message + '</p></div>' );

            return;

          }

          $( '.wishlist-form-fields > ul' ).append( response.data );

        } );

      } );

    },

    // Update a field
    updateField: function( e ) {

      e.preventDefault();

      var modal       = $( '.rw-elephant-edit-wishlist-field' ),
          originalKey = modal.find( '.js-field-key' ).val(),
          formData    = $( this ).serialize();

      modal.find( 'h2' ).fadeTo(  'fast', 0.5 );
      modal.find( '.js-edit-field-form' ).fadeTo( 'fast', 0.5, function() {

        modal.find( 'input' ).attr( 'disabled', 'disabled' );
        modal.find( '.preloader' ).addClass( 'updating' ).show();

        // Ajax request
        var data = {
          'action': 'update_wishlist_form_field',
          'key': originalKey,
          'data': formData
        };

        $.post( ajaxurl, data, function( response ) {

          $.featherlight.close();

          if ( ! response.success ) {

            $( '.wishlist-form-fields' ).before( '<div class="rwe-alert danger add-field-error"><p>' + response.data.message + '</p></div>' );

            return;

          }

          var originalFormField = $( 'span[data-key="' + originalKey + '"]' ).closest( 'li' );

          if ( originalFormField.length ) {

            originalFormField.replaceWith( response.data );

            return;

          }

        } );

      } );

    },

  };

  /**
   * Toggle the active cache setting.
   */
  var toggleCache = {

    init: function() {

      var labelText = this.checked ? rwElephant.siwtchLabels.toggleCache.enabled : rwElephant.siwtchLabels.toggleCache.disabled,
          $label    = $( this ).closest( '.display' ).find( 'label.toggle-label' );

      $label.text( labelText );

      toggleCache.toggleCache( this.checked );

    },

    toggleCache: function( cacheEnabled ) {

      var data = {
        'action': 'toggle_cache',
        'cacheEnabled': cacheEnabled,
      };

      $.post( ajaxurl, data, function( response ) {

        if ( cacheEnabled ) {

          $( '#flush-cache' ).removeAttr( 'disabled' );

          return;

        }

        $( '#flush-cache' ).attr( 'disabled', 'disabled' );

      } );

    },

  };

  // Color Pickers
  $( document ).ready( rwColorPicker.init );

  // Sortable Fields
  $( document ).ready( rwSortable.init );

  // Image Selection
  $( document ).on( 'click', '.js-add-image', rwImageSelector.addImage );
  $( document ).on( 'click', '.js-remove-image', rwImageSelector.removeImage );

  // Item Notes
  $( document ).on( 'click', '.js-add-item-note', rwItemNotes.add );
  $( document ).on( 'click', '.js-remove-item-note', rwItemNotes.remove );

  // Wishlist Additional Form Fields
  $( document ).on( 'click', '.js-add-field', rwFormFields.add );
  $( document ).on( 'click', '.js-remove-field', rwFormFields.remove );
  $( document ).on( 'click', '.js-edit-field', rwFormFields.edit );
  $( document ).on( 'click', '.js-add-option', rwFormFields.addOption );
  $( document ).on( 'submit', '.js-update-wishlist-field', rwFormFields.updateField );
  $( document ).on( 'submit', '.js-add-wishlist-field', rwFormFields.addField );

  // Toggle cache
  $( document ).on( 'change', '.rwe-toggle-cache', toggleCache.init );

} )( jQuery );
