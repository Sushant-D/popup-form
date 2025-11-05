(function ($, Drupal, drupalSettings, once) {
    'use strict';
  
    /**
     * Popup Form behavior.
     */
    Drupal.behaviors.popupForm = {
      attach: function (context, settings) {
        const popupForms = drupalSettings.popup_form || {};
        
        Object.keys(popupForms).forEach(function(popupId) {
          const popup = popupForms[popupId];
          const elements = once('popup-form-' + popupId, popup.trigger_selector, context);
          
          elements.forEach(function(element) {
            $(element).on('click.popupForm', function(e) {
              e.preventDefault();
              Drupal.popupForm.open(popupId, popup);
            });
          });
        });
      }
    };
  
    /**
     * Popup Form API.
     */
    Drupal.popupForm = {
      
      /**
       * Open a popup form.
       */
      open: function(popupId, config) {
        const settings = config.settings || {};
        
        // Create popup HTML
        const popupHtml = this.createPopupHtml(popupId, config);
        
        // Add to DOM
        $('body').append(popupHtml);
        
        const $popup = $('#popup-form-' + popupId);
        const $overlay = $popup.find('.popup-overlay');
        const $container = $popup.find('.popup-container');
        
        // Apply settings
        if (settings.width) {
          $container.css('width', settings.width);
        }
        if (settings.height && settings.height !== 'auto') {
          $container.css('height', settings.height);
        }
        
        // Show popup with animation
        $popup.addClass('popup-active');
        
        if (settings.animation === 'fadeIn') {
          $container.addClass('popup-fade-in');
        } else if (settings.animation === 'slideDown') {
          $container.addClass('popup-slide-down');
        } else if (settings.animation === 'slideUp') {
          $container.addClass('popup-slide-up');
        } else if (settings.animation === 'zoomIn') {
          $container.addClass('popup-zoom-in');
        }
        
        // Event handlers
        this.attachEventHandlers(popupId, settings);
        
        // Auto close
        if (settings.auto_close) {
          setTimeout(function() {
            Drupal.popupForm.close(popupId);
          }, settings.auto_close_delay || 5000);
        }
        
        // Focus management
        $container.attr('tabindex', '-1').focus();
      },
      
      /**
       * Close a popup form.
       */
      close: function(popupId) {
        const $popup = $('#popup-form-' + popupId);
        $popup.removeClass('popup-active');
        
        setTimeout(function() {
          $popup.remove();
        }, 300);
      },
      
      /**
       * Create popup HTML.
       */
      createPopupHtml: function(popupId, config) {
        const settings = config.settings || {};
        const closeButton = settings.close_button !== false ? '<button class="popup-close" aria-label="Close popup">&times;</button>' : '';
        
        return `
          <div id="popup-form-${popupId}" class="popup-form-wrapper">
            ${settings.overlay !== false ? '<div class="popup-overlay"></div>' : ''}
            <div class="popup-container" role="dialog" aria-modal="true" aria-labelledby="popup-title-${popupId}">
              ${closeButton}
              <div class="popup-content">
                ${config.title ? `<h2 id="popup-title-${popupId}" class="popup-title">${config.title}</h2>` : ''}
                ${config.description ? `<div class="popup-description">${config.description}</div>` : ''}
                <div class="popup-form-content">
                  ${config.content || ''}
                </div>
              </div>
            </div>
          </div>
        `;
      },
      
      /**
       * Attach event handlers.
       */
      attachEventHandlers: function(popupId, settings) {
        const $popup = $('#popup-form-' + popupId);
        
        // Close button
        if (settings.close_button !== false) {
          $popup.find('.popup-close').on('click', function() {
            Drupal.popupForm.close(popupId);
          });
        }
        
        // Overlay click
        if (settings.click_outside_close !== false) {
          $popup.find('.popup-overlay').on('click', function() {
            Drupal.popupForm.close(popupId);
          });
        }
        
        // Escape key
        if (settings.escape_close !== false) {
          $(document).on('keydown.popup-' + popupId, function(e) {
            if (e.key === 'Escape') {
              Drupal.popupForm.close(popupId);
              $(document).off('keydown.popup-' + popupId);
            }
          });
        }
      }
    };
  
  })(jQuery, Drupal, drupalSettings, once);