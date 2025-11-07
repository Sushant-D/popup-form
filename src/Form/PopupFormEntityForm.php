<?php

namespace Drupal\popup_form\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Field\WidgetBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Enhanced Popup Form Entity Form with multiple content types.
 */
class PopupFormEntityForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a PopupFormEntityForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\popup_form\Entity\PopupForm $popup_form */
    $popup_form = $this->entity;

    // Add library for drag-and-drop
    $form['#attached']['library'][] = 'popup_form/admin';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Basic fields
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $popup_form->label(),
      '#description' => $this->t('Administrative label for the Popup Form.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $popup_form->id(),
      '#machine_name' => [
        'exists' => '\Drupal\popup_form\Entity\PopupForm::load',
      ],
      '#disabled' => !$popup_form->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $popup_form->get('description') ?: '',
      '#description' => $this->t('Administrative description for this popup form.'),
    ];

    $form['trigger_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger Selector'),
      '#default_value' => $popup_form->getTriggerSelector() ?: '#open-signUp-UcDavis',
      '#description' => $this->t('CSS selector for elements that will trigger this popup.'),
      '#required' => TRUE,
    ];

    $form['popup_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Popup Title'),
      '#default_value' => $popup_form->getPopupTitle() ?: '',
      '#description' => $this->t('Title displayed in the popup header.'),
    ];

    $form['popup_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Popup Description'),
      '#default_value' => $popup_form->getPopupDescription() ?: '',
      '#description' => $this->t('Description text displayed in the popup.'),
    ];

    // Content Items Section
    $form['content_items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Popup Content Items'),
      '#tree' => TRUE,
      '#prefix' => '<div id="content-items-wrapper">',
      '#suffix' => '</div>',
    ];

    // Initialize content items from form state or entity
    $content_items = $form_state->get('content_items');
    if ($content_items === NULL) {
      $content_items = $popup_form->get('content_items') ?: [];
      if (empty($content_items)) {
        // Add an empty item if none exist
        $content_items = [];
      }
      $form_state->set('content_items', $content_items);
    }

    // Store the number of items for AJAX
    $num_items = count($content_items);
    $form_state->set('num_items', $num_items);

    // Build content items fields
    $form['content_items']['items'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'content-items-container',
        'class' => ['content-items-sortable'],
      ],
    ];

    foreach ($content_items as $delta => $item) {
      $form['content_items']['items'][$delta] = $this->buildContentItemForm($delta, $item, $form_state);
    }

    // Add more button
    $form['content_items']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Content Item'),
      '#submit' => ['::addContentItem'],
      '#ajax' => [
        'callback' => '::contentItemsAjaxCallback',
        'wrapper' => 'content-items-wrapper',
        'effect' => 'fade',
      ],
      '#limit_validation_errors' => [
        ['content_items'],
      ],
    ];

    // Popup Settings
    $popup_settings = $popup_form->getPopupSettings();
    
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Popup Settings'),
      '#open' => FALSE,
    ];

    $form['settings']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $popup_settings['width'] ?? '600px',
    ];

    $form['settings']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $popup_settings['height'] ?? 'auto',
    ];

    $form['settings']['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation'),
      '#options' => [
        'fadeIn' => $this->t('Fade In'),
        'slideDown' => $this->t('Slide Down'),
        'slideUp' => $this->t('Slide Up'),
        'zoomIn' => $this->t('Zoom In'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $popup_settings['animation'] ?? 'fadeIn',
    ];

    $form['settings']['overlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show overlay'),
      '#default_value' => $popup_settings['overlay'] ?? TRUE,
    ];

    $form['settings']['close_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show close button'),
      '#default_value' => $popup_settings['close_button'] ?? TRUE,
    ];

    return $form;
  }

  /**
   * Build individual content item form.
   */
  protected function buildContentItemForm($delta, $item, FormStateInterface $form_state) {
    $element = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['content-item', 'draggable'],
        'data-delta' => $delta,
      ],
    ];

    // Drag handle
    $element['handle'] = [
      '#markup' => '<div class="content-item-handle"></div>',
    ];

    // Get the current content type from form state values if available
    $parents = ['content_items', 'items', $delta];
    $current_values = NestedArray::getValue($form_state->getUserInput(), $parents);
    $content_type = $current_values['content_type'] ?? $item['content_type'] ?? '';

    // Content type selector
    $element['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => [
        '' => $this->t('- Select -'),
        'webform' => $this->t('Webform'),
        'block' => $this->t('Block'),
        'paragraph' => $this->t('Paragraph'),
        'custom_text' => $this->t('Custom Text'),
        'custom_html' => $this->t('Custom HTML'),
      ],
      '#default_value' => $content_type,
      '#ajax' => [
        'callback' => '::updateContentItemCallback',
        'wrapper' => 'content-item-config-' . $delta,
        'event' => 'change',
      ],
    ];

    // Configuration container
    $element['config'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-item-config-' . $delta],
      '#tree' => TRUE,
    ];

    // Build configuration based on content type
    if ($content_type) {
      $config = $current_values['config'] ?? $item['config'] ?? [];
      switch ($content_type) {
        case 'webform':
          $element['config'] = $this->buildWebformConfig($delta, $config);
          break;
        case 'block':
          $element['config'] = $this->buildBlockConfig($delta, $config);
          break;
        case 'paragraph':
          $element['config'] = $this->buildParagraphConfig($delta, $config, $form_state);
          break;
        case 'custom_text':
          $element['config'] = $this->buildCustomTextConfig($delta, $config);
          break;
        case 'custom_html':
          $element['config'] = $this->buildCustomHtmlConfig($delta, $config);
          break;
      }
    }

    // Weight field for sorting
    $element['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $item['weight'] ?? $delta,
      '#delta' => 50,
      '#attributes' => ['class' => ['content-item-weight']],
    ];

    // Remove button
    $element['remove'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove'),
      '#name' => 'remove_' . $delta,
      '#submit' => ['::removeContentItem'],
      '#ajax' => [
        'callback' => '::contentItemsAjaxCallback',
        'wrapper' => 'content-items-wrapper',
      ],
      '#attributes' => [
        'data-delta' => $delta,
      ],
      '#limit_validation_errors' => [],
    ];

    return $element;
  }

  /**
   * Build webform configuration.
   */
  protected function buildWebformConfig($delta, $config) {
    $container = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-item-config-' . $delta],
      '#tree' => TRUE,
    ];

    $webform_options = ['_none' => $this->t('- Select -')];
    
    if ($this->moduleHandler->moduleExists('webform')) {
      $webforms = $this->entityTypeManager->getStorage('webform')->loadMultiple();
      foreach ($webforms as $webform) {
        if ($webform instanceof WebformInterface) {
          $webform_options[$webform->id()] = $webform->label();
        }
      }
    }

    $container['webform_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Webform'),
      '#options' => $webform_options,
      '#default_value' => $config['webform_id'] ?? '_none',
    ];

    return $container;
  }

  /**
   * Build block configuration.
   */
  protected function buildBlockConfig($delta, $config) {
    $container = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-item-config-' . $delta],
      '#tree' => TRUE,
    ];

    $block_options = ['_none' => $this->t('- Select -')];
    
    // Get all block plugins
    $block_manager = \Drupal::service('plugin.manager.block');
    $definitions = $block_manager->getDefinitions();
    
    foreach ($definitions as $plugin_id => $definition) {
      $block_options[$plugin_id] = (string) $definition['admin_label'];
    }

    // Also include placed blocks
    $blocks = $this->entityTypeManager->getStorage('block')->loadMultiple();
    foreach ($blocks as $block) {
      $block_options['block:' . $block->id()] = $this->t('Placed: @label', ['@label' => $block->label()]);
    }

    $container['block_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Block'),
      '#options' => $block_options,
      '#default_value' => $config['block_id'] ?? '_none',
    ];

    return $container;
  }

  /**
   * Build paragraph configuration.
   */
  protected function buildParagraphConfig($delta, $config, FormStateInterface $form_state) {
    $container = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-item-config-' . $delta],
      '#tree' => TRUE,
    ];

    // Check if paragraphs module exists
    if (!$this->moduleHandler->moduleExists('paragraphs')) {
      $container['message'] = [
        '#markup' => '<p>' . $this->t('Paragraphs module is not installed.') . '</p>',
      ];
      return $container;
    }

    try {
      // Get paragraph types
      $paragraph_types = [];
      $paragraph_type_storage = $this->entityTypeManager->getStorage('paragraphs_type');
      $paragraph_type_entities = $paragraph_type_storage->loadMultiple();

      foreach ($paragraph_type_entities as $type) {
        $paragraph_types[$type->id()] = $type->label();
      }

      // Get current paragraph type from form state
      $parents = ['content_items', 'items', $delta, 'config', 'paragraph_type'];
      $current_paragraph_type = NestedArray::getValue($form_state->getUserInput(), $parents)
        ?? $config['paragraph_type'] ?? '';

      $container['paragraph_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Paragraph Type'),
        '#options' => ['_none' => $this->t('- Select -')] + $paragraph_types,
        '#default_value' => $current_paragraph_type,
        '#ajax' => [
          'callback' => '::updateParagraphFieldsCallback',
          'wrapper' => 'paragraph-fields-' . $delta,
          'event' => 'change',
        ],
      ];

      // Container for paragraph fields
      $container['paragraph_fields'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'paragraph-fields-' . $delta],
        '#tree' => TRUE,
      ];

      // If paragraph type is selected, show fields
      if ($current_paragraph_type && $current_paragraph_type !== '_none') {
        // Get available view modes
        $view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle('paragraph', $current_paragraph_type);
        
        $container['paragraph_fields']['view_mode'] = [
          '#type' => 'select',
          '#title' => $this->t('View Mode'),
          '#options' => $view_modes,
          '#default_value' => $config['view_mode'] ?? 'default',
        ];

        // Check if paragraphs_previewer module is enabled
        if ($this->moduleHandler->moduleExists('paragraphs_previewer')) {
          $container['paragraph_fields']['use_previewer'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Use Paragraphs Previewer'),
            '#default_value' => $config['use_previewer'] ?? FALSE,
            '#description' => $this->t('Use the Paragraphs Previewer module view mode if available.'),
          ];
        }

        // Add paragraph entity form fields
        $container['paragraph_fields']['paragraph_form'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Paragraph Content'),
          '#description' => $this->t('Configure the paragraph content that will be displayed in the popup.'),
        ];

        // Create or load paragraph entity
        $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
        
        if (!empty($config['paragraph_id'])) {
          $paragraph = $paragraph_storage->load($config['paragraph_id']);
        } else {
          $paragraph = $paragraph_storage->create(['type' => $current_paragraph_type]);
        }

        if ($paragraph) {
          // Get the field definitions for this paragraph type
          $field_definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $current_paragraph_type);
          
          // Get the form display
          $form_display = $this->entityDisplayRepository->getFormDisplay('paragraph', $current_paragraph_type, 'default');
          
          // Build a simple form for paragraph fields
          $this->buildParagraphFields($container, $field_definitions, $form_display, $config, $current_paragraph_type);
          
          // Store paragraph ID if it exists
          if (!$paragraph->isNew()) {
            $container['paragraph_fields']['paragraph_id'] = [
              '#type' => 'value',
              '#value' => $paragraph->id(),
            ];
          }
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('popup_form')->error('Error building paragraph config: @message', [
        '@message' => $e->getMessage(),
      ]);
      $container['error'] = [
        '#markup' => '<div class="messages messages--error">' . 
                     $this->t('Error loading paragraph configuration. Please check the logs.') . 
                     '</div>',
      ];
    }

    return $container;
  }

  /**
   * Build paragraph fields.
   */
  protected function buildParagraphFields(&$container, $field_definitions, $form_display, $config, $current_paragraph_type) {
    // List of known base fields to skip
    $base_fields = [
      'id', 'uuid', 'revision_id', 'langcode', 'type', 'status',
      'created', 'parent_id', 'parent_type', 'parent_field_name',
      'behavior_settings', 'uid', 'revision_uid', 'revision_log',
      'revision_created', 'revision_translation_affected', 'default_langcode'
    ];

    foreach ($field_definitions as $field_name => $field_definition) {
      // Skip base fields
      if (in_array($field_name, $base_fields)) {
        continue;
      }

      // Check if field should be shown in form display
      $widget = $form_display->getRenderer($field_name);
      if (!$widget) {
        continue;
      }

      try {
        // Get field properties
        $field_label = $field_definition->getLabel();
        $field_description = $field_definition->getDescription();
        $field_type = $field_definition->getType();
        $field_required = $field_definition->isRequired();
        
        // Get field storage definition safely
        $field_storage_definition = NULL;
        $cardinality = 1;
        
        if (method_exists($field_definition, 'getFieldStorageDefinition')) {
          $field_storage_definition = $field_definition->getFieldStorageDefinition();
          $cardinality = $field_storage_definition->getCardinality();
        }
        
        // Default values
        $default_value = '';
        if (isset($config['paragraph_data'][$field_name])) {
          $default_value = $config['paragraph_data'][$field_name];
        }
        
        // Build form element based on field type
        $element = $this->buildFieldElement($field_type, $field_label, $field_description, $field_required, $default_value, $field_storage_definition);
        
        if ($element) {
          // Handle multiple values
          if ($cardinality !== 1) {
            $element['#description'] = ($element['#description'] ?? '') . ' ' . 
              $this->t('(This field supports multiple values - only first value can be set here)');
          }
          
          $container['paragraph_fields']['paragraph_form'][$field_name] = $element;
        }
      } catch (\Exception $e) {
        \Drupal::logger('popup_form')->warning('Error processing field @field: @message', [
          '@field' => $field_name,
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Build a field element based on field type.
   */
  protected function buildFieldElement($field_type, $field_label, $field_description, $field_required, $default_value, $field_storage_definition = NULL) {
    $element = [];

    switch ($field_type) {
      case 'text_long':
      case 'text_with_summary':
        $element = [
          '#type' => 'text_format',
          '#title' => $field_label,
          '#default_value' => is_array($default_value) ? ($default_value['value'] ?? '') : $default_value,
          '#format' => is_array($default_value) ? ($default_value['format'] ?? 'basic_html') : 'basic_html',
          '#description' => $field_description,
          '#required' => $field_required,
        ];
        break;
        
      case 'string_long':
        $element = [
          '#type' => 'textarea',
          '#title' => $field_label,
          '#default_value' => $default_value,
          '#description' => $field_description,
          '#required' => $field_required,
          '#rows' => 5,
        ];
        break;
        
      case 'boolean':
        $element = [
          '#type' => 'checkbox',
          '#title' => $field_label,
          '#default_value' => (bool) $default_value,
          '#description' => $field_description,
        ];
        break;
        
      case 'list_string':
      case 'list_integer':
      case 'list_float':
        if ($field_storage_definition) {
          $allowed_values = $field_storage_definition->getSetting('allowed_values');
          if ($allowed_values) {
            $element = [
              '#type' => 'select',
              '#title' => $field_label,
              '#options' => $allowed_values,
              '#default_value' => $default_value,
              '#description' => $field_description,
              '#required' => $field_required,
              '#empty_option' => $field_required ? NULL : $this->t('- Select -'),
            ];
          }
        }
        break;
        
      case 'integer':
      case 'decimal':
      case 'float':
        $element = [
          '#type' => 'number',
          '#title' => $field_label,
          '#default_value' => $default_value,
          '#description' => $field_description,
          '#required' => $field_required,
        ];
        if ($field_type === 'decimal' || $field_type === 'float') {
          $element['#step'] = 0.01;
        }
        break;
        
      case 'email':
        $element = [
          '#type' => 'email',
          '#title' => $field_label,
          '#default_value' => $default_value,
          '#description' => $field_description,
          '#required' => $field_required,
        ];
        break;
        
      case 'link':
        $element = [
          '#type' => 'fieldset',
          '#title' => $field_label,
          '#description' => $field_description,
        ];
        $element['uri'] = [
          '#type' => 'url',
          '#title' => $this->t('URL'),
          '#default_value' => is_array($default_value) ? ($default_value['uri'] ?? '') : '',
          '#required' => $field_required,
        ];
        $element['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Link text'),
          '#default_value' => is_array($default_value) ? ($default_value['title'] ?? '') : '',
        ];
        break;
        
      case 'entity_reference':
      case 'entity_reference_revisions':
        $target_type = '';
        if ($field_storage_definition) {
          $target_type = $field_storage_definition->getSetting('target_type');
        }
        $element = [
          '#type' => 'textfield',
          '#title' => $field_label,
          '#default_value' => $default_value,
          '#description' => $field_description . ' ' . $this->t('(Entity reference to @type - enter entity ID)', ['@type' => $target_type ?: 'entity']),
          '#required' => $field_required,
        ];
        break;
        
      case 'image':
      case 'file':
        $element = [
          '#type' => 'textfield',
          '#title' => $field_label,
          '#default_value' => $default_value,
          '#description' => $field_description . ' ' . $this->t('(Enter file/image ID - file upload not implemented in popup form)'),
          '#required' => $field_required,
        ];
        break;
        
      case 'datetime':
        $element = [
          '#type' => 'datetime',
          '#title' => $field_label,
          '#default_value' => $default_value,
          '#description' => $field_description,
          '#required' => $field_required,
        ];
        break;
        
      case 'string':
      case 'text':
      default:
        $element = [
          '#type' => 'textfield',
          '#title' => $field_label,
          '#default_value' => $default_value,
          '#description' => $field_description,
          '#required' => $field_required,
          '#maxlength' => $field_type === 'string' ? 255 : NULL,
        ];
        break;
    }

    return $element;
  }

  /**
   * Build custom text configuration.
   */
  protected function buildCustomTextConfig($delta, $config) {
    $container = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-item-config-' . $delta],
      '#tree' => TRUE,
    ];

    $container['text_content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Custom Text'),
      '#default_value' => $config['text_content']['value'] ?? '',
      '#format' => $config['text_content']['format'] ?? 'basic_html',
    ];

    return $container;
  }

  /**
   * Build custom HTML configuration.
   */
  protected function buildCustomHtmlConfig($delta, $config) {
    $container = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-item-config-' . $delta],
      '#tree' => TRUE,
    ];

    $container['html_content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom HTML'),
      '#default_value' => $config['html_content'] ?? '',
      '#description' => $this->t('Enter custom HTML code. Be careful with user input!'),
      '#rows' => 10,
    ];

    return $container;
  }

  /**
   * Ajax callback for content items.
   */
  public function contentItemsAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['content_items'];
  }

  /**
   * Ajax callback for updating content item configuration.
   */
  public function updateContentItemCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $delta = $parents[2];
    
    return $form['content_items']['items'][$delta]['config'];
  }

  /**
   * Ajax callback for updating paragraph fields.
   */
  public function updateParagraphFieldsCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $delta = $parents[2];
    
    return $form['content_items']['items'][$delta]['config']['paragraph_fields'];
  }

  /**
   * Submit handler for adding content item.
   */
  public function addContentItem(array &$form, FormStateInterface $form_state) {
    $content_items = $form_state->get('content_items');
    $content_items[] = [
      'content_type' => '',
      'config' => [],
      'weight' => count($content_items),
    ];
    $form_state->set('content_items', $content_items);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for removing content item.
   */
  public function removeContentItem(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $delta = $triggering_element['#attributes']['data-delta'];
    
    $content_items = $form_state->get('content_items');
    unset($content_items[$delta]);
    $content_items = array_values($content_items); // Re-index
    
    $form_state->set('content_items', $content_items);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $trigger_selector = $form_state->getValue('trigger_selector');
    if (empty(trim($trigger_selector))) {
      $form_state->setErrorByName('trigger_selector', $this->t('Trigger selector is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process content items
    $content_items = $form_state->getValue(['content_items', 'items']) ?? [];
    
    // Sort by weight
    usort($content_items, function($a, $b) {
      return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
    });

    // Clean up and structure content items
    $processed_items = [];
    foreach ($content_items as $item) {
      if (!empty($item['content_type'])) {
        $processed_item = [
          'content_type' => $item['content_type'],
          'weight' => $item['weight'] ?? 0,
          'config' => [],
        ];

        // Process configuration based on content type
        switch ($item['content_type']) {
          case 'webform':
            $processed_item['config']['webform_id'] = $item['config']['webform_id'] ?? '';
            break;
            
          case 'block':
            $processed_item['config']['block_id'] = $item['config']['block_id'] ?? '';
            break;
            
          case 'paragraph':
            $processed_item['config']['paragraph_type'] = $item['config']['paragraph_type'] ?? '';
            if (isset($item['config']['paragraph_fields'])) {
              $processed_item['config']['view_mode'] = $item['config']['paragraph_fields']['view_mode'] ?? 'default';
              $processed_item['config']['use_previewer'] = $item['config']['paragraph_fields']['use_previewer'] ?? FALSE;
              $processed_item['config']['paragraph_id'] = $item['config']['paragraph_fields']['paragraph_id'] ?? NULL;
              
              // Store paragraph field data
              $processed_item['config']['paragraph_data'] = [];
              if (isset($item['config']['paragraph_fields']['paragraph_form'])) {
                foreach ($item['config']['paragraph_fields']['paragraph_form'] as $field_name => $field_value) {
                  $processed_item['config']['paragraph_data'][$field_name] = $field_value;
                }
              }
            }
            break;
            
          case 'custom_text':
            $processed_item['config']['text_content'] = $item['config']['text_content'] ?? [];
            break;
            
          case 'custom_html':
            $processed_item['config']['html_content'] = $item['config']['html_content'] ?? '';
            break;
        }

        $processed_items[] = $processed_item;
      }
    }

    $form_state->setValue('content_items', $processed_items);

    // Process settings
    $settings = $form_state->getValue('settings', []);
    $popup_settings = [
      'width' => $settings['width'] ?? '600px',
      'height' => $settings['height'] ?? 'auto',
      'animation' => $settings['animation'] ?? 'fadeIn',
      'overlay' => $settings['overlay'] ?? TRUE,
      'close_button' => $settings['close_button'] ?? TRUE,
    ];
    
    $form_state->setValue('popup_settings', $popup_settings);
    
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\popup_form\Entity\PopupForm $popup_form */
    $popup_form = $this->entity;

    $status = $popup_form->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Popup Form.', [
          '%label' => $popup_form->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Popup Form.', [
          '%label' => $popup_form->label(),
        ]));
    }

    $form_state->setRedirect('popup_form.admin.collection');
  }
}