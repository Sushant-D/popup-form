<?php

namespace Drupal\popup_form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

/**
 * Enhanced Popup Form Manager service with multiple content support.
 */
class PopupFormManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a PopupFormManager object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    RendererInterface $renderer,
    BlockManagerInterface $block_manager,
    ModuleHandlerInterface $module_handler,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->blockManager = $block_manager;
    $this->moduleHandler = $module_handler;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * Get all active popup forms.
   *
   * @return \Drupal\popup_form\PopupFormInterface[]
   *   Array of active popup forms.
   */
  public function getActivePopupForms() {
    return $this->entityTypeManager
      ->getStorage('popup_form')
      ->loadByProperties(['status' => TRUE]);
  }

  /**
   * Render a popup form's content with all content items.
   *
   * @param \Drupal\popup_form\PopupFormInterface $popup_form
   *   The popup form entity.
   *
   * @return string
   *   The rendered content.
   */
  public function renderPopupContent(PopupFormInterface $popup_form) {
    $content = [
      '#type' => 'container',
      '#attributes' => ['class' => ['popup-form-content-wrapper']],
    ];

    // Get content items sorted by weight
    $content_items = $popup_form->getContentItems();

    foreach ($content_items as $delta => $item) {
      $rendered_item = $this->renderContentItem($item, $delta);
      if ($rendered_item) {
        $content['item_' . $delta] = $rendered_item;
      }
    }

    // Backward compatibility: If no content items but has old webform_id
    if (empty($content_items) && $popup_form->getWebformId()) {
      $webform_id = $popup_form->getWebformId();
      if ($webform_id && Webform::load($webform_id)) {
        $content['webform'] = [
          '#type' => 'webform',
          '#webform' => $webform_id,
        ];
      }
    }

    return $this->renderer->renderPlain($content);
  }

  /**
   * Render a single content item.
   *
   * @param array $item
   *   The content item configuration.
   * @param int $delta
   *   The item delta/index.
   *
   * @return array|null
   *   The render array for the item or NULL if invalid.
   */
  protected function renderContentItem(array $item, $delta) {
    $content_type = $item['content_type'] ?? '';
    $config = $item['config'] ?? [];

    $wrapper = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'popup-content-item',
          'popup-content-item--' . str_replace('_', '-', $content_type),
        ],
        'data-content-type' => $content_type,
        'data-delta' => $delta,
      ],
    ];

    switch ($content_type) {
      case 'webform':
        $content = $this->renderWebform($config);
        break;

      case 'block':
        $content = $this->renderBlock($config);
        break;

      case 'paragraph':
        $content = $this->renderParagraph($config);
        break;

      case 'custom_text':
        $content = $this->renderCustomText($config);
        break;

      case 'custom_html':
        $content = $this->renderCustomHtml($config);
        break;

      default:
        $content = NULL;
    }

    if ($content) {
      $wrapper['content'] = $content;
      return $wrapper;
    }

    return NULL;
  }

  /**
   * Render a webform content item.
   *
   * @param array $config
   *   The webform configuration.
   *
   * @return array|null
   *   The render array or NULL.
   */
  protected function renderWebform(array $config) {
    $webform_id = $config['webform_id'] ?? '';
    
    if ($webform_id && $webform_id !== '_none') {
      $webform = Webform::load($webform_id);
      if ($webform) {
        return [
          '#type' => 'webform',
          '#webform' => $webform_id,
          '#attributes' => ['class' => ['popup-webform']],
        ];
      }
    }
    
    return NULL;
  }

  /**
   * Render a block content item.
   *
   * @param array $config
   *   The block configuration.
   *
   * @return array|null
   *   The render array or NULL.
   */
  protected function renderBlock(array $config) {
    $block_id = $config['block_id'] ?? '';
    
    if ($block_id && $block_id !== '_none') {
      try {
        // Check if it's a placed block
        if (strpos($block_id, 'block:') === 0) {
          $block_entity_id = substr($block_id, 6);
          $block = $this->entityTypeManager->getStorage('block')->load($block_entity_id);
          
          if ($block && $block->status()) {
            $block_plugin = $block->getPlugin();
            if ($block_plugin) {
              return [
                '#theme' => 'block',
                '#attributes' => ['class' => ['popup-block']],
                '#configuration' => $block_plugin->getConfiguration(),
                '#plugin_id' => $block_plugin->getPluginId(),
                '#base_plugin_id' => $block_plugin->getBaseId(),
                '#derivative_plugin_id' => $block_plugin->getDerivativeId(),
                '#weight' => $block->getWeight(),
                'content' => $block_plugin->build(),
              ];
            }
          }
        } else {
          // It's a block plugin ID
          $block_plugin = $this->blockManager->createInstance($block_id);
          if ($block_plugin) {
            return [
              '#type' => 'container',
              '#attributes' => ['class' => ['popup-block', 'popup-block-plugin']],
              'content' => $block_plugin->build(),
            ];
          }
        }
      } catch (\Exception $e) {
        \Drupal::logger('popup_form')->error('Error rendering block @block_id: @message', [
          '@block_id' => $block_id,
          '@message' => $e->getMessage(),
        ]);
      }
    }
    
    return NULL;
  }

  /**
   * Render a paragraph content item.
   *
   * @param array $config
   *   The paragraph configuration.
   *
   * @return array|null
   *   The render array or NULL.
   */
  protected function renderParagraph(array $config) {
    if (!$this->moduleHandler->moduleExists('paragraphs')) {
      return NULL;
    }

    $paragraph_type = $config['paragraph_type'] ?? '';
    $view_mode = $config['view_mode'] ?? 'default';
    $use_previewer = $config['use_previewer'] ?? FALSE;
    $paragraph_data = $config['paragraph_data'] ?? [];

    if ($paragraph_type && $paragraph_type !== '_none') {
      try {
        // Create or load paragraph entity
        if (!empty($config['paragraph_id'])) {
          $paragraph = $this->entityTypeManager->getStorage('paragraph')->load($config['paragraph_id']);
        } else {
          // Create new paragraph with saved data
          $paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
            'type' => $paragraph_type,
          ]);

          // Set field values from saved data
          if (!empty($paragraph_data)) {
            foreach ($paragraph_data as $field_name => $field_value) {
              if ($paragraph->hasField($field_name)) {
                // Handle different field value formats
                if (is_array($field_value)) {
                  // Handle text format fields
                  if (isset($field_value['value']) && isset($field_value['format'])) {
                    $paragraph->set($field_name, [
                      'value' => $field_value['value'],
                      'format' => $field_value['format'],
                    ]);
                  }
                  // Handle link fields
                  elseif (isset($field_value['uri'])) {
                    $paragraph->set($field_name, [
                      'uri' => $field_value['uri'],
                      'title' => $field_value['title'] ?? '',
                    ]);
                  }
                  // Handle other array values
                  else {
                    $paragraph->set($field_name, $field_value);
                  }
                } else {
                  // Simple scalar values
                  $paragraph->set($field_name, $field_value);
                }
              }
            }
          }

          // Save the paragraph to get an ID
          $paragraph->save();
        }

        if ($paragraph) {
          // Check if paragraphs_previewer is enabled and should be used
          if ($use_previewer && $this->moduleHandler->moduleExists('paragraphs_previewer')) {
            // Use paragraphs_previewer view mode
            $view_builder = $this->entityTypeManager->getViewBuilder('paragraph');
            $build = $view_builder->view($paragraph, 'preview');
          } else {
            // Use standard view mode
            $view_builder = $this->entityTypeManager->getViewBuilder('paragraph');
            $build = $view_builder->view($paragraph, $view_mode);
          }

          return [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'popup-paragraph',
                'popup-paragraph--' . $paragraph_type,
              ],
            ],
            'content' => $build,
          ];
        }
      } catch (\Exception $e) {
        \Drupal::logger('popup_form')->error('Error rendering paragraph: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
    }

    return NULL;
  }

  /**
   * Render custom text content item.
   *
   * @param array $config
   *   The text configuration.
   *
   * @return array|null
   *   The render array or NULL.
   */
  protected function renderCustomText(array $config) {
    $text_content = $config['text_content'] ?? [];
    
    if (!empty($text_content['value'])) {
      return [
        '#type' => 'processed_text',
        '#text' => $text_content['value'],
        '#format' => $text_content['format'] ?? 'basic_html',
        '#attributes' => ['class' => ['popup-custom-text']],
      ];
    }
    
    return NULL;
  }

  /**
   * Render custom HTML content item.
   *
   * @param array $config
   *   The HTML configuration.
   *
   * @return array|null
   *   The render array or NULL.
   */
  protected function renderCustomHtml(array $config) {
    $html_content = $config['html_content'] ?? '';
    
    if (!empty($html_content)) {
      return [
        '#type' => 'markup',
        '#markup' => $html_content,
        '#allowed_tags' => [], // Allow all HTML tags - be careful!
        '#attributes' => ['class' => ['popup-custom-html']],
      ];
    }
    
    return NULL;
  }

  /**
   * Get JavaScript settings for all active popup forms.
   *
   * @return array
   *   JavaScript settings array.
   */
  public function getJavaScriptSettings() {
    $settings = [];
    $popup_forms = $this->getActivePopupForms();

    foreach ($popup_forms as $popup_form) {
      $settings[$popup_form->id()] = [
        'trigger_selector' => $popup_form->getTriggerSelector(),
        'title' => $popup_form->getPopupTitle(),
        'description' => $popup_form->getPopupDescription(),
        'content' => $this->renderPopupContent($popup_form),
        'settings' => $popup_form->getPopupSettings(),
        'has_multiple_items' => $popup_form->hasMultipleContentItems(),
      ];
    }

    return $settings;
  }

  /**
   * Check if popup forms should be loaded on current page.
   *
   * @return bool
   *   TRUE if popup forms should be loaded.
   */
  public function shouldLoadPopupForms() {
    $config = $this->configFactory->get('popup_form.settings');
    
    // If load on all pages is enabled, always load
    if ($config->get('load_on_all_pages')) {
      return TRUE;
    }

    // Check if there are active popup forms with triggers on current page
    $popup_forms = $this->getActivePopupForms();
    return !empty($popup_forms);
  }

  /**
   * Validate content items configuration.
   *
   * @param array $content_items
   *   The content items to validate.
   *
   * @return array
   *   Array of validation errors.
   */
  public function validateContentItems(array $content_items) {
    $errors = [];

    foreach ($content_items as $delta => $item) {
      $content_type = $item['content_type'] ?? '';
      $config = $item['config'] ?? [];

      switch ($content_type) {
        case 'webform':
          if (empty($config['webform_id']) || $config['webform_id'] === '_none') {
            $webform = Webform::load($config['webform_id']);
            if (!$webform) {
              $errors[$delta] = t('Invalid webform selected.');
            }
          }
          break;

        case 'block':
          if (empty($config['block_id']) || $config['block_id'] === '_none') {
            $errors[$delta] = t('Invalid block selected.');
          }
          break;

        case 'paragraph':
          if (!$this->moduleHandler->moduleExists('paragraphs')) {
            $errors[$delta] = t('Paragraphs module is not installed.');
          } elseif (empty($config['paragraph_type']) || $config['paragraph_type'] === '_none') {
            $errors[$delta] = t('Invalid paragraph type selected.');
          }
          break;
      }
    }

    return $errors;
  }
}