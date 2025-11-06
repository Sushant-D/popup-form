(function ($, Drupal, once) {
    'use strict';
  
    /**
     * Admin interface behaviors.
     */
    Drupal.behaviors.popupFormAdmin = {
      attach: function (context, settings) {
        const elements = once('popup-form-admin', '.popup-form-admin', context);
        
        elements.forEach(function(element) {
          // Initialize sortable for field ordering
          if ($.fn.sortable) {
            $(element).find('.field-order-list').sortable({
              placeholder: 'field-placeholder',
              handle: '.field-handle',
              update: function(event, ui) {
                Drupal.popupFormAdmin.updateFieldOrder(this);
              }
            });
          }
          
          // Preview functionality
          $(element).find('.popup-preview-btn').on('click', function(e) {
            e.preventDefault();
            Drupal.popupFormAdmin.preview();
          });
        });
      }
    };
  
    /**
     * Admin API.
     */
    Drupal.popupFormAdmin = {
      
      /**
       * Update field order.
       */
      updateFieldOrder: function(sortableElement) {
        const order = [];
        $(sortableElement).find('.field-item').each(function() {
          order.push($(this).data('field-id'));
        });
        
        // Update hidden form field
        $('input[name="field_order"]').val(JSON.stringify(order));
      },
      
      /**
       * Preview popup.
       */
      preview: function() {
        // Collect form data and show preview
        const title = $('input[name="popup_title"]').val();
        const description = $('textarea[name="popup_description"]').val();
        
        const previewConfig = {
          title: title,
          description: description,
          content: '<p>This is a preview of your popup form.</p>',
          settings: {
            width: $('input[name="popup_settings[width]"]').val(),
            animation: $('select[name="popup_settings[animation]"]').val()
          }
        };
        
        Drupal.popupForm.open('preview', previewConfig);
      }
    };
  
  })(jQuery, Drupal, once);