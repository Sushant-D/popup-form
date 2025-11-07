<?php

namespace Drupal\popup_form\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\popup_form\PopupFormInterface;

/**
 * Defines the Enhanced Popup Form entity with multiple content support.
 *
 * @ConfigEntityType(
 *   id = "popup_form",
 *   label = @Translation("Popup Form"),
 *   label_collection = @Translation("Popup Forms"),
 *   label_singular = @Translation("popup form"),
 *   label_plural = @Translation("popup forms"),
 *   label_count = @PluralTranslation(
 *     singular = "@count popup form",
 *     plural = "@count popup forms",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\popup_form\PopupFormListBuilder",
 *     "form" = {
 *       "add" = "Drupal\popup_form\Form\PopupFormEntityForm",
 *       "edit" = "Drupal\popup_form\Form\PopupFormEntityForm",
 *       "delete" = "Drupal\popup_form\Form\PopupFormDeleteForm"
 *     }
 *   },
 *   config_prefix = "popup_form",
 *   admin_permission = "administer popup forms",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/user-interface/popup-form/{popup_form}",
 *     "add-form" = "/admin/config/user-interface/popup-form/add",
 *     "edit-form" = "/admin/config/user-interface/popup-form/{popup_form}/edit",
 *     "delete-form" = "/admin/config/user-interface/popup-form/{popup_form}/delete",
 *     "collection" = "/admin/config/user-interface/popup-form/manage"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "trigger_selector",
 *     "webform_id",
 *     "popup_title",
 *     "popup_description",
 *     "fields",
 *     "field_order",
 *     "content_items",
 *     "popup_settings",
 *     "status"
 *   }
 * )
 */
class PopupForm extends ConfigEntityBase implements PopupFormInterface {

  /**
   * The Popup Form ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Popup Form label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Popup Form description.
   *
   * @var string
   */
  protected $description;

  /**
   * The trigger selector (CSS selector for elements that open popup).
   *
   * @var string
   */
  protected $trigger_selector = '#open-signUp-UcDavis';

  /**
   * @deprecated Use content_items instead
   * The webform ID to embed in the popup.
   *
   * @var string
   */
  protected $webform_id;

  /**
   * The popup title.
   *
   * @var string
   */
  protected $popup_title;

  /**
   * The popup description.
   *
   * @var string
   */
  protected $popup_description;

  /**
   * @deprecated Use content_items instead
   * Additional fields configuration.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * @deprecated Use content_items instead
   * Field order configuration.
   *
   * @var array
   */
  protected $field_order = [];

  /**
   * Content items configuration (webforms, blocks, paragraphs, etc.).
   *
   * @var array
   */
  protected $content_items = [];

  /**
   * Popup settings (width, height, animation, etc.).
   *
   * @var array
   */
  protected $popup_settings = [];

  /**
   * Static method for machine name validation.
   */
  public static function load($id) {
    return \Drupal::entityTypeManager()->getStorage('popup_form')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTriggerSelector() {
    return $this->trigger_selector ?: '#open-signUp-UcDavis';
  }

  /**
   * {@inheritdoc}
   */
  public function setTriggerSelector($selector) {
    $this->trigger_selector = $selector;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformId() {
    // Backward compatibility: Check content_items first
    if (!empty($this->content_items)) {
      foreach ($this->content_items as $item) {
        if ($item['content_type'] === 'webform' && !empty($item['config']['webform_id'])) {
          return $item['config']['webform_id'];
        }
      }
    }
    return $this->webform_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebformId($webform_id) {
    $this->webform_id = $webform_id;
    // Also update content_items for consistency
    if (!empty($webform_id)) {
      $found = FALSE;
      if (!empty($this->content_items)) {
        foreach ($this->content_items as &$item) {
          if ($item['content_type'] === 'webform') {
            $item['config']['webform_id'] = $webform_id;
            $found = TRUE;
            break;
          }
        }
      }
      if (!$found) {
        $this->content_items[] = [
          'content_type' => 'webform',
          'config' => ['webform_id' => $webform_id],
          'weight' => 0,
        ];
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPopupTitle() {
    return $this->popup_title;
  }

  /**
   * {@inheritdoc}
   */
  public function setPopupTitle($title) {
    $this->popup_title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPopupDescription() {
    return $this->popup_description;
  }

  /**
   * {@inheritdoc}
   */
  public function setPopupDescription($description) {
    $this->popup_description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return $this->fields ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    $this->fields = $fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldOrder() {
    return $this->field_order ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldOrder(array $order) {
    $this->field_order = $order;
    return $this;
  }

  /**
   * Get content items.
   *
   * @return array
   *   The content items configuration.
   */
  public function getContentItems() {
    // Migrate old configuration to new format if needed
    if (empty($this->content_items) && !empty($this->webform_id)) {
      $this->content_items = [
        [
          'content_type' => 'webform',
          'config' => ['webform_id' => $this->webform_id],
          'weight' => 0,
        ],
      ];
    }
    
    // Sort by weight
    if (!empty($this->content_items)) {
      usort($this->content_items, function($a, $b) {
        return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
      });
    }
    
    return $this->content_items ?: [];
  }

  /**
   * Set content items.
   *
   * @param array $content_items
   *   The content items configuration.
   *
   * @return $this
   */
  public function setContentItems(array $content_items) {
    $this->content_items = $content_items;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPopupSettings() {
    $defaults = [
      'width' => '600px',
      'height' => 'auto',
      'animation' => 'fadeIn',
      'overlay' => TRUE,
      'close_button' => TRUE,
      'escape_close' => TRUE,
      'click_outside_close' => TRUE,
      'auto_close' => FALSE,
      'auto_close_delay' => 5000,
    ];
    
    return ($this->popup_settings ?: []) + $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function setPopupSettings(array $settings) {
    $this->popup_settings = $settings + $this->getPopupSettings();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    // Handle nested properties
    if (isset($this->{$property_name})) {
      return $this->{$property_name};
    }
    
    return parent::get($property_name);
  }

  /**
   * Check if entity has multiple content items.
   *
   * @return bool
   *   TRUE if multiple content items exist.
   */
  public function hasMultipleContentItems() {
    return count($this->getContentItems()) > 1;
  }

  /**
   * Get all webforms from content items.
   *
   * @return array
   *   Array of webform IDs.
   */
  public function getWebformIds() {
    $webform_ids = [];
    foreach ($this->getContentItems() as $item) {
      if ($item['content_type'] === 'webform' && !empty($item['config']['webform_id'])) {
        $webform_ids[] = $item['config']['webform_id'];
      }
    }
    return $webform_ids;
  }

  /**
   * Get all blocks from content items.
   *
   * @return array
   *   Array of block IDs.
   */
  public function getBlockIds() {
    $block_ids = [];
    foreach ($this->getContentItems() as $item) {
      if ($item['content_type'] === 'block' && !empty($item['config']['block_id'])) {
        $block_ids[] = $item['config']['block_id'];
      }
    }
    return $block_ids;
  }

  /**
   * Get all paragraphs from content items.
   *
   * @return array
   *   Array of paragraph configurations.
   */
  public function getParagraphConfigs() {
    $paragraphs = [];
    foreach ($this->getContentItems() as $item) {
      if ($item['content_type'] === 'paragraph' && !empty($item['config']['paragraph_type'])) {
        $paragraphs[] = $item['config'];
      }
    }
    return $paragraphs;
  }
}