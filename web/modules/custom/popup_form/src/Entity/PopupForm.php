<?php

// src/Entity/PopupForm.php - FIXED VERSION

namespace Drupal\popup_form\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\popup_form\PopupFormInterface;

/**
 * Defines the Popup Form entity.
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
   * Additional fields configuration.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * Field order configuration.
   *
   * @var array
   */
  protected $field_order = [];

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
    return $this->webform_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebformId($webform_id) {
    $this->webform_id = $webform_id;
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

}
