<?php
namespace Drupal\popup_form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Block\BlockManagerInterface;

/**
 * Popup Form Manager service.
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
   * Constructs a PopupFormManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Block\BlockManagerInterface|null $block_manager
   *   The block manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, RendererInterface $renderer, ?BlockManagerInterface $block_manager = null) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->blockManager = $block_manager ?: \Drupal::service('plugin.manager.block');
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
   * Render a popup form's content.
   *
   * @param \Drupal\popup_form\PopupFormInterface $popup_form
   *   The popup form entity.
   *
   * @return string
   *   The rendered content.
   */
  public function renderPopupContent(PopupFormInterface $popup_form) {
    $content = [];

    // Get additional fields
    $fields = $popup_form->getFields();
    
    // Sort fields by weight
    if (!empty($fields)) {
      usort($fields, function ($a, $b) {
        return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
      });
    }

    // Add fields that should appear before webform
    foreach ($fields as $field) {
      if (($field['position'] ?? 'before') === 'before') {
        $content[] = $this->renderField($field);
      }
    }

    // Add webform if specified
    $webform_id = $popup_form->getWebformId();
    if ($webform_id && Webform::load($webform_id)) {
      $content['webform'] = [
        '#type' => 'webform',
        '#webform' => $webform_id,
      ];
    }

    // Add fields that should appear after webform
    foreach ($fields as $field) {
      if (($field['position'] ?? 'before') === 'after') {
        $content[] = $this->renderField($field);
      }
    }

    return $this->renderer->renderPlain($content);
  }

  /**
   * Render an individual field.
   *
   * @param array $field
   *   The field configuration.
   *
   * @return array
   *   The render array for the field.
   */
  protected function renderField(array $field) {
    $type = $field['type'] ?? 'text';
    $label = $field['label'] ?? '';
    
    switch ($type) {
      case 'text':
        return [
          '#type' => 'markup',
          '#markup' => '<div class="popup-field popup-field-text">' . 
                      ($label ? '<label>' . $label . '</label>' : '') .
                      '<p>' . $this->sanitizeContent($field['content'] ?? '') . '</p>' .
                      '</div>',
        ];

      case 'textarea':
        return [
          '#type' => 'markup',
          '#markup' => '<div class="popup-field popup-field-textarea">' . 
                      ($label ? '<label>' . $label . '</label>' : '') .
                      '<div>' . nl2br($this->sanitizeContent($field['content'] ?? '')) . '</div>' .
                      '</div>',
        ];

      case 'html':
        return [
          '#type' => 'markup',
          '#markup' => '<div class="popup-field popup-field-html">' . 
                      ($label ? '<label>' . $label . '</label>' : '') .
                      '<div>' . ($field['content'] ?? '') . '</div>' .
                      '</div>',
        ];

      case 'heading':
        $heading_level = $field['heading_level'] ?? 'h3';
        return [
          '#type' => 'markup',
          '#markup' => '<div class="popup-field popup-field-heading">' . 
                      '<' . $heading_level . ' class="popup-heading">' . 
                      $this->sanitizeContent($field['content'] ?? '') . 
                      '</' . $heading_level . '>' .
                      '</div>',
        ];

      case 'image':
        $image_url = $field['image_url'] ?? '';
        $alt_text = $field['alt_text'] ?? '';
        if ($image_url) {
          return [
            '#type' => 'markup',
            '#markup' => '<div class="popup-field popup-field-image">' . 
                        ($label ? '<label>' . $label . '</label>' : '') .
                        '<img src="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt_text, ENT_QUOTES, 'UTF-8') . '" class="popup-image" />' .
                        '</div>',
          ];
        }
        break;

      case 'link':
        $link_url = $field['link_url'] ?? '';
        $link_text = $field['link_text'] ?? $link_url;
        if ($link_url) {
          return [
            '#type' => 'markup',
            '#markup' => '<div class="popup-field popup-field-link">' . 
                        ($label ? '<label>' . $label . '</label>' : '') .
                        '<a href="' . htmlspecialchars($link_url, ENT_QUOTES, 'UTF-8') . '" class="popup-link" target="_blank" rel="noopener">' . 
                        $this->sanitizeContent($link_text) . '</a>' .
                        '</div>',
          ];
        }
        break;

      case 'block':
        $block_id = $field['block_id'] ?? '';
        if ($block_id) {
          try {
            $block = $this->entityTypeManager->getStorage('block')->load($block_id);
            if ($block && $block->status()) {
              $block_plugin = $block->getPlugin();
              if ($block_plugin) {
                $build = $block_plugin->build();
                
                return [
                  '#type' => 'container',
                  '#attributes' => ['class' => ['popup-field', 'popup-field-block']],
                  'label' => $label ? [
                    '#type' => 'markup',
                    '#markup' => '<label>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>',
                  ] : [],
                  'content' => $build,
                ];
              }
            }
          } catch (\Exception $e) {
            // Log error and continue
            \Drupal::logger('popup_form')->warning('Error rendering block @block_id: @message', [
              '@block_id' => $block_id,
              '@message' => $e->getMessage(),
            ]);
          }
        }
        break;
    }

    // Default fallback
    return [
      '#type' => 'markup',
      '#markup' => '<div class="popup-field popup-field-default">' . 
                  ($label ? '<label>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>' : '') .
                  '<p>' . $this->sanitizeContent($field['content'] ?? '') . '</p>' .
                  '</div>',
    ];
  }

  /**
   * Sanitize content for safe display.
   *
   * @param string $content
   *   The content to sanitize.
   *
   * @return string
   *   The sanitized content.
   */
  protected function sanitizeContent($content) {
    return htmlspecialchars($content ?? '', ENT_QUOTES, 'UTF-8');
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

}