(function ($, Drupal, drupalSettings, once) {
    'use strict';
  
    /**
     * Enhanced Popup Form behavior with form submission handling.
     */
    Drupal.behaviors.popupFormEnhanced = {
      attach: function (context, settings) {
        const popupForms = drupalSettings.popup_form || {};
        
        Object.keys(popupForms).forEach(function(popupId) {
          const popup = popupForms[popupId];
          const elements = once('popup-form-enhanced-' + popupId, popup.trigger_selector, context);
          
          elements.forEach(function(element) {
            $(element).on('click.popupFormEnhanced', function(e) {
              e.preventDefault();
              Drupal.popupForm.openEnhanced(popupId, popup);
            });
          });
        });
  
        // Handle form submissions within popups
        $(context).find('.popup-webform').each(function() {
          const $form = $(this);
          once('popup-webform-submit', $form).forEach(function() {
            $form.on('submit', function(e) {
              const $popup = $(this).closest('.popup-form-wrapper');
              const popupId = $popup.attr('id').replace('popup-form-', '');
              
              // Add loading state
              $popup.find('.popup-container').addClass('popup-loading');
              
              // Handle successful submission
              $form.on('webform:submit:complete', function() {
                $popup.find('.popup-container').removeClass('popup-loading');
                
                // Check if should close on submit
                if (drupalSettings.popup_form.close_on_submit) {
                  setTimeout(function() {
                    Drupal.popupForm.close(popupId);
                  }, 2000);
                }
              });
            });
          });
        });
      }
    };
  
    /**
     * Enhanced Popup Form API.
     */
    Drupal.popupForm = Drupal.popupForm || {};
    
    /**
     * Enhanced popup opening with better form handling.
     */
    Drupal.popupForm.openEnhanced = function(popupId, config) {
      const settings = config.settings || {};
      
      // Create popup HTML
      const popupHtml = this.createEnhancedPopupHtml(popupId, config);
      
      // Remove existing popup if present
      $('#popup-form-' + popupId).remove();
      
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
      
      // Add theme class
      if (settings.theme) {
        $container.addClass('popup-theme-' + settings.theme);
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
      this.attachEnhancedEventHandlers(popupId, settings);
      
      // Auto close
      if (settings.auto_close) {
        setTimeout(function() {
          Drupal.popupForm.close(popupId);
        }, settings.auto_close_delay || 5000);
      }
      
      // Focus management
      $container.attr('tabindex', '-1').focus();
      
      // Initialize any Drupal behaviors within the popup
      setTimeout(function() {
        Drupal.attachBehaviors($popup[0]);
      }, 100);
    };
  
    /**
     * Create enhanced popup HTML with loading states.
     */
    Drupal.popupForm.createEnhancedPopupHtml = function(popupId, config) {
      const settings = config.settings || {};
      const closeButton = settings.close_button !== false ? '<button class="popup-close" aria-label="Close popup">&times;</button>' : '';
      
      return `
        <div id="popup-form-${popupId}" class="popup-form-wrapper">
          ${settings.overlay !== false ? '<div class="popup-overlay"></div>' : ''}
          <div class="popup-container" role="dialog" aria-modal="true" aria-labelledby="popup-title-${popupId}">
            ${closeButton}
            <div class="popup-loading-indicator" style="display: none;">
              <div class="popup-spinner"></div>
              <p>Processing...</p>
            </div>
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
    };
  
    /**
     * Attach enhanced event handlers.
     */
    Drupal.popupForm.attachEnhancedEventHandlers = function(popupId, settings) {
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
      
      // Focus trap for accessibility
      $popup.on('keydown', function(e) {
        if (e.key === 'Tab') {
          const focusableElements = $popup.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible');
          const firstElement = focusableElements.first();
          const lastElement = focusableElements.last();
          
          if (e.shiftKey && $(document.activeElement)[0] === firstElement[0]) {
            e.preventDefault();
            lastElement.focus();
          } else if (!e.shiftKey && $(document.activeElement)[0] === lastElement[0]) {
            e.preventDefault();
            firstElement.focus();
          }
        }
      });
    };
  
    /**
     * Show loading state.
     */
    Drupal.popupForm.showLoading = function(popupId) {
      const $popup = $('#popup-form-' + popupId);
      $popup.find('.popup-container').addClass('popup-loading');
      $popup.find('.popup-loading-indicator').show();
      $popup.find('.popup-content').css('opacity', '0.5');
    };
  
    /**
     * Hide loading state.
     */
    Drupal.popupForm.hideLoading = function(popupId) {
      const $popup = $('#popup-form-' + popupId);
      $popup.find('.popup-container').removeClass('popup-loading');
      $popup.find('.popup-loading-indicator').hide();
      $popup.find('.popup-content').css('opacity', '1');
    };
  
  })(jQuery, Drupal, drupalSettings, once);
  