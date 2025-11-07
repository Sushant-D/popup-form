(function ($, Drupal, once) {
  'use strict';

  /**
   * Enhanced Admin interface behaviors for popup forms.
   */
  Drupal.behaviors.popupFormAdmin = {
    attach: function (context, settings) {
      // Initialize sortable for content items
      const contentContainers = once('popup-form-content-sortable', '.content-items-sortable', context);
      
      contentContainers.forEach(function(container) {
        if ($.fn.sortable) {
          $(container).sortable({
            items: '.content-item',
            handle: '.content-item-handle',
            placeholder: 'content-item-placeholder',
            axis: 'y',
            tolerance: 'pointer',
            update: function(event, ui) {
              Drupal.popupFormAdmin.updateContentItemsOrder(this);
            },
            start: function(event, ui) {
              ui.placeholder.height(ui.item.height());
            }
          });
        }
      });

      // Initialize old field ordering (backward compatibility)
      const fieldLists = once('popup-form-admin', '.field-order-list', context);
      
      fieldLists.forEach(function(element) {
        if ($.fn.sortable) {
          $(element).sortable({
            placeholder: 'field-placeholder',
            handle: '.field-handle',
            update: function(event, ui) {
              Drupal.popupFormAdmin.updateFieldOrder(this);
            }
          });
        }
      });
      
      // Preview functionality
      const previewButtons = once('popup-preview', '.popup-preview-btn', context);
      previewButtons.forEach(function(button) {
        $(button).on('click', function(e) {
          e.preventDefault();
          Drupal.popupFormAdmin.preview();
        });
      });

      // Re-initialize after AJAX updates
      if (context !== document) {
        Drupal.popupFormAdmin.updateContentItemNumbers();
        Drupal.popupFormAdmin.initializeTooltips();
      }

      // Handle content type changes
      const contentTypeSelects = once('content-type-change', '.content-item select[name*="[content_type]"]', context);
      contentTypeSelects.forEach(function(select) {
        $(select).on('change', function() {
          const $item = $(this).closest('.content-item');
          const delta = $item.data('delta');
          const contentType = $(this).val();
          
          // Update visual indicator
          $item.attr('data-content-type', contentType);
          
          // Handle special UI for different content types
          if (contentType === 'paragraph') {
            Drupal.popupFormAdmin.initializeParagraphUI(delta);
          } else if (contentType === 'block') {
            Drupal.popupFormAdmin.initializeBlockUI(delta);
          }
        });
      });

      // Handle paragraph type changes
      const paragraphTypeSelects = once('paragraph-type-change', 'select[name*="[paragraph_type]"]', context);
      paragraphTypeSelects.forEach(function(select) {
        $(select).on('change', function() {
          const $container = $(this).closest('.content-item');
          const paragraphType = $(this).val();
          
          if (paragraphType && paragraphType !== '_none') {
            // Add visual feedback
            $container.addClass('paragraph-type-selected');
          } else {
            $container.removeClass('paragraph-type-selected');
          }
        });
      });
    }
  };

  /**
   * Enhanced Admin API.
   */
  Drupal.popupFormAdmin = {
    
    /**
     * Update content items order after drag and drop.
     */
    updateContentItemsOrder: function(sortableElement) {
      $(sortableElement).find('.content-item').each(function(index) {
        const $item = $(this);
        
        // Update weight field
        $item.find('input[name*="[weight]"]').val(index);
        
        // Update visual order number
        const $number = $item.find('.content-item-number');
        if ($number.length === 0) {
          $item.prepend('<span class="content-item-number">' + (index + 1) + '</span>');
        } else {
          $number.text(index + 1);
        }
        
        // Update data-delta attribute
        $item.attr('data-delta', index);
      });
    },

    /**
     * Update field order (backward compatibility).
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
     * Initialize paragraph-specific UI.
     */
    initializeParagraphUI: function(delta) {
      const $container = $('[data-delta="' + delta + '"]');
      
      // Add helper text
      const $paragraphFields = $container.find('#paragraph-fields-' + delta);
      if ($paragraphFields.length && !$paragraphFields.hasClass('initialized')) {
        $paragraphFields.addClass('initialized');
        
        // Check if paragraphs_previewer is enabled
        if (typeof drupalSettings !== 'undefined' && 
            drupalSettings.popupFormAdmin && 
            drupalSettings.popupFormAdmin.paragraphsPreviewerEnabled) {
          this.addParagraphPreview($paragraphFields);
        }
      }
    },

    /**
     * Initialize block-specific UI.
     */
    initializeBlockUI: function(delta) {
      const $container = $('[data-delta="' + delta + '"]');
      const $blockSelect = $container.find('select[name*="[block_id]"]');
      
      if ($blockSelect.length && !$blockSelect.hasClass('initialized')) {
        $blockSelect.addClass('initialized');
        
        // Add change handler for block preview
        $blockSelect.on('change.blockPreview', function() {
          const blockId = $(this).val();
          if (blockId && blockId !== '_none') {
            Drupal.popupFormAdmin.previewBlock(blockId, delta);
          }
        });
      }
    },

    /**
     * Add paragraph preview button.
     */
    addParagraphPreview: function($container) {
      if ($container.find('.paragraph-preview-btn').length === 0) {
        const previewButton = $('<button type="button" class="button button--small paragraph-preview-btn">Preview Paragraph</button>');
        previewButton.on('click', function(e) {
          e.preventDefault();
          const delta = $(this).closest('.content-item').data('delta');
          Drupal.popupFormAdmin.previewParagraph(delta);
        });
        $container.append(previewButton);
      }
    },

    /**
     * Preview a paragraph.
     */
    previewParagraph: function(delta) {
      const $item = $('[data-delta="' + delta + '"]');
      const paragraphType = $item.find('select[name*="[paragraph_type]"]').val();
      
      if (paragraphType && paragraphType !== '_none') {
        // Collect paragraph field values
        const fieldData = {};
        $item.find('[name*="[paragraph_form]"]').each(function() {
          const fieldName = $(this).attr('name').match(/\[paragraph_form\]\[([^\]]+)\]/);
          if (fieldName) {
            fieldData[fieldName[1]] = $(this).val();
          }
        });
        
        console.log('Paragraph preview for type:', paragraphType, 'with data:', fieldData);
        // Here you would implement actual preview logic
      }
    },

    /**
     * Preview a block.
     */
    previewBlock: function(blockId, delta) {
      console.log('Block preview for:', blockId, 'at delta:', delta);
      // Here you would implement actual block preview logic
    },
    
    /**
     * Preview popup with all content items.
     */
    preview: function() {
      // Collect all content items
      const contentItems = [];
      $('.content-item').each(function() {
        const $item = $(this);
        const contentType = $item.find('select[name*="[content_type]"]').val();
        
        if (contentType) {
          const config = {};
          
          // Collect configuration based on content type
          switch (contentType) {
            case 'webform':
              config.webform_id = $item.find('select[name*="[webform_id]"]').val();
              break;
              
            case 'block':
              config.block_id = $item.find('select[name*="[block_id]"]').val();
              break;
              
            case 'paragraph':
              config.paragraph_type = $item.find('select[name*="[paragraph_type]"]').val();
              config.view_mode = $item.find('select[name*="[view_mode]"]').val();
              config.use_previewer = $item.find('input[name*="[use_previewer]"]').is(':checked');
              
              // Collect paragraph fields
              config.fields = {};
              $item.find('[name*="[paragraph_form]"]').each(function() {
                const fieldName = $(this).attr('name').match(/\[paragraph_form\]\[([^\]]+)\]/);
                if (fieldName) {
                  config.fields[fieldName[1]] = $(this).val();
                }
              });
              break;
              
            case 'custom_text':
              config.text = $item.find('textarea[name*="[value]"]').val();
              config.format = $item.find('select[name*="[format]"]').val();
              break;
              
            case 'custom_html':
              config.html = $item.find('textarea[name*="[html_content]"]').val();
              break;
          }
          
          contentItems.push({
            type: contentType,
            config: config
          });
        }
      });
      
      // Generate preview content
      let previewContent = '<div class="popup-preview-content">';
      
      if (contentItems.length === 0) {
        previewContent += '<p>' + Drupal.t('No content items to preview.') + '</p>';
      } else {
        contentItems.forEach(function(item, index) {
          previewContent += '<div class="preview-item preview-item--' + item.type + '">';
          previewContent += '<h4>' + Drupal.t('Item @num: @type', {'@num': index + 1, '@type': item.type}) + '</h4>';
          
          switch (item.type) {
            case 'webform':
              previewContent += '<p>' + Drupal.t('Webform: @id', {'@id': item.config.webform_id || 'Not selected'}) + '</p>';
              break;
              
            case 'block':
              previewContent += '<p>' + Drupal.t('Block: @id', {'@id': item.config.block_id || 'Not selected'}) + '</p>';
              break;
              
            case 'paragraph':
              previewContent += '<p>' + Drupal.t('Paragraph Type: @type', {'@type': item.config.paragraph_type || 'Not selected'}) + '</p>';
              if (item.config.view_mode) {
                previewContent += '<p>' + Drupal.t('View Mode: @mode', {'@mode': item.config.view_mode}) + '</p>';
              }
              break;
              
            case 'custom_text':
              previewContent += '<div class="preview-text">' + (item.config.text || Drupal.t('No text')) + '</div>';
              break;
              
            case 'custom_html':
              previewContent += '<div class="preview-html"><pre>' + Drupal.checkPlain(item.config.html || '') + '</pre></div>';
              break;
          }
          
          previewContent += '</div>';
        });
      }
      
      previewContent += '</div>';
      
      // Get other popup settings
      const title = $('input[name="popup_title"]').val() || Drupal.t('Popup Preview');
      const description = $('textarea[name="popup_description"]').val() || '';
      
      const previewConfig = {
        title: title,
        description: description,
        content: previewContent,
        settings: {
          width: $('input[name="settings[width]"]').val() || '600px',
          animation: $('select[name="settings[animation]"]').val() || 'fadeIn',
          overlay: $('input[name="settings[overlay]"]').is(':checked'),
          close_button: $('input[name="settings[close_button]"]').is(':checked')
        }
      };
      
      // Use the popup form API to show preview
      if (typeof Drupal.popupForm !== 'undefined' && Drupal.popupForm.open) {
        Drupal.popupForm.open('preview', previewConfig);
      } else {
        // Fallback: show preview in a dialog
        const $dialog = $('<div>').html(previewContent);
        $dialog.dialog({
          title: title,
          width: 600,
          modal: true,
          buttons: {
            Close: function() {
              $(this).dialog('close');
            }
          }
        });
      }
    },

    /**
     * Initialize content item tooltips.
     */
    initializeTooltips: function() {
      $('.content-item-handle').attr('title', Drupal.t('Drag to reorder'));
      $('.content-item .button--danger').attr('title', Drupal.t('Remove this content item'));
    },

    /**
     * Add content item number labels.
     */
    updateContentItemNumbers: function() {
      $('.content-item').each(function(index) {
        const $number = $(this).find('.content-item-number');
        if ($number.length === 0) {
          $(this).prepend('<span class="content-item-number">' + (index + 1) + '</span>');
        } else {
          $number.text(index + 1);
        }
      });
    },

    /**
     * Validate content items before save.
     */
    validateContentItems: function() {
      let isValid = true;
      const errors = [];

      $('.content-item').each(function(index) {
        const $item = $(this);
        const contentType = $item.find('select[name*="[content_type]"]').val();
        
        if (contentType) {
          let itemValid = true;
          let errorMessage = '';
          
          switch (contentType) {
            case 'webform':
              const webformId = $item.find('select[name*="[webform_id]"]').val();
              if (!webformId || webformId === '_none') {
                itemValid = false;
                errorMessage = Drupal.t('Please select a webform.');
              }
              break;
              
            case 'block':
              const blockId = $item.find('select[name*="[block_id]"]').val();
              if (!blockId || blockId === '_none') {
                itemValid = false;
                errorMessage = Drupal.t('Please select a block.');
              }
              break;
              
            case 'paragraph':
              const paragraphType = $item.find('select[name*="[paragraph_type]"]').val();
              if (!paragraphType || paragraphType === '_none') {
                itemValid = false;
                errorMessage = Drupal.t('Please select a paragraph type.');
              }
              break;
          }
          
          if (!itemValid) {
            $item.addClass('content-item--error');
            errors.push(Drupal.t('Item @num (@type): @error', {
              '@num': index + 1,
              '@type': contentType,
              '@error': errorMessage
            }));
            isValid = false;
          } else {
            $item.removeClass('content-item--error');
          }
        }
      });

      if (!isValid && errors.length > 0) {
        const errorMessage = Drupal.t('Please fix the following errors:') + '\n\n' + errors.join('\n');
        alert(errorMessage);
      }

      return isValid;
    }
  };

  // Initialize on document ready
  $(document).ready(function() {
    // Add CSS classes for better styling
    $('.content-items-sortable').addClass('ui-sortable-container');
    
    // Update content item numbers on load
    Drupal.popupFormAdmin.updateContentItemNumbers();
    
    // Initialize tooltips
    Drupal.popupFormAdmin.initializeTooltips();
    
    // Add form submit validation
    $('#popup-form-entity-form').on('submit', function(e) {
      if (!Drupal.popupFormAdmin.validateContentItems()) {
        e.preventDefault();
        return false;
      }
    });
  });

  // Handle AJAX complete events to reinitialize
  $(document).on('ajaxComplete', function(event, xhr, settings) {
    // Check if this is our form's AJAX request
    if (settings.extraData && settings.extraData._triggering_element_name) {
      const triggerName = settings.extraData._triggering_element_name;
      
      // If it's a content item related AJAX
      if (triggerName.includes('content_items') || 
          triggerName.includes('add_more') || 
          triggerName.includes('remove_')) {
        
        // Reinitialize sortable
        setTimeout(function() {
          const $container = $('.content-items-sortable');
          if ($container.length && $.fn.sortable) {
            $container.sortable('refresh');
          }
          
          // Update numbers and tooltips
          Drupal.popupFormAdmin.updateContentItemNumbers();
          Drupal.popupFormAdmin.initializeTooltips();
        }, 100);
      }
    }
  });

})(jQuery, Drupal, once);