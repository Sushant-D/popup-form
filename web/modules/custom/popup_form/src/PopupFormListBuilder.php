<?php

// src/PopupFormListBuilder.php - FINAL FIXED VERSION

namespace Drupal\popup_form;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Popup Form entities.
 */
class PopupFormListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Popup Form');
    $header['id'] = $this->t('Machine name');
    $header['trigger_selector'] = $this->t('Trigger Selector');
    $header['webform'] = $this->t('Webform');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\popup_form\PopupFormInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['trigger_selector'] = $entity->getTriggerSelector() ?: $this->t('- Not set -');
    
    // Load webform label safely
    $webform_id = $entity->getWebformId();
    $webform_label = $this->t('- None -');
    if ($webform_id) {
      try {
        $webform_storage = \Drupal::entityTypeManager()->getStorage('webform');
        $webform = $webform_storage->load($webform_id);
        if ($webform) {
          $webform_label = $webform->label();
        } else {
          $webform_label = $this->t('- Not found -');
        }
      } catch (\Exception $e) {
        $webform_label = $this->t('- Webform module not available -');
      }
    }
    $row['webform'] = $webform_label;
    
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Remove the view operation since canonical route may not exist
    // Only keep edit and delete operations
    unset($operations['view']);

    // Ensure edit operation exists
    if ($entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl('edit-form'),
      ];
    }

    // Ensure delete operation exists
    if ($entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form'),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    
    $build['description'] = [
      '#markup' => $this->t('Manage popup forms that can be triggered by CSS selectors on your site. <a href="@add-url">Add a new popup form</a>.', [
        '@add-url' => Url::fromRoute('popup_form.admin.add')->toString(),
      ]),
      '#weight' => -10,
    ];

    return $build;
  }

}