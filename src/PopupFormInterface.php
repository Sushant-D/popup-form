<?php

// src/PopupFormInterface.php

namespace Drupal\popup_form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Popup Form entities.
 */
interface PopupFormInterface extends ConfigEntityInterface {

  /**
   * Gets the trigger selector.
   *
   * @return string
   *   The CSS selector for elements that trigger the popup.
   */
  public function getTriggerSelector();

  /**
   * Sets the trigger selector.
   *
   * @param string $selector
   *   The CSS selector.
   *
   * @return $this
   */
  public function setTriggerSelector($selector);

  /**
   * Gets the webform ID.
   *
   * @return string
   *   The webform ID.
   */
  public function getWebformId();

  /**
   * Sets the webform ID.
   *
   * @param string $webform_id
   *   The webform ID.
   *
   * @return $this
   */
  public function setWebformId($webform_id);

  /**
   * Gets the popup title.
   *
   * @return string
   *   The popup title.
   */
  public function getPopupTitle();

  /**
   * Sets the popup title.
   *
   * @param string $title
   *   The popup title.
   *
   * @return $this
   */
  public function setPopupTitle($title);

  /**
   * Gets the popup description.
   *
   * @return string
   *   The popup description.
   */
  public function getPopupDescription();

  /**
   * Sets the popup description.
   *
   * @param string $description
   *   The popup description.
   *
   * @return $this
   */
  public function setPopupDescription($description);

  /**
   * Gets the fields configuration.
   *
   * @return array
   *   The fields configuration.
   */
  public function getFields();

  /**
   * Sets the fields configuration.
   *
   * @param array $fields
   *   The fields configuration.
   *
   * @return $this
   */
  public function setFields(array $fields);

  /**
   * Gets the field order.
   *
   * @return array
   *   The field order.
   */
  public function getFieldOrder();

  /**
   * Sets the field order.
   *
   * @param array $order
   *   The field order.
   *
   * @return $this
   */
  public function setFieldOrder(array $order);

  /**
   * Gets the popup settings.
   *
   * @return array
   *   The popup settings.
   */
  public function getPopupSettings();

  /**
   * Sets the popup settings.
   *
   * @param array $settings
   *   The popup settings.
   *
   * @return $this
   */
  public function setPopupSettings(array $settings);

}
