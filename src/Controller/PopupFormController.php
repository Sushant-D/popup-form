<?php

// src/Controller/PopupFormController.php - FIXED VERSION

namespace Drupal\popup_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\popup_form\PopupFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PopupFormController.
 */
class PopupFormController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PopupFormController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays a list of popup forms.
   *
   * @return array
   *   A render array.
   */
  public function managePopups() {
    $list_builder = $this->entityTypeManager->getListBuilder('popup_form');
    
    $build['table'] = $list_builder->render();
    
    return $build;
  }

  /**
   * Displays a popup form entity.
   *
   * @param \Drupal\popup_form\PopupFormInterface $popup_form
   *   The popup form entity.
   *
   * @return array
   *   A render array.
   */
  public function view(PopupFormInterface $popup_form) {
    $build = [];

    $build['details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Popup Form Details'),
    ];

    $build['details']['label'] = [
      '#type' => 'item',
      '#title' => $this->t('Label'),
      '#markup' => $popup_form->label(),
    ];

    $build['details']['id'] = [
      '#type' => 'item',
      '#title' => $this->t('ID'),
      '#markup' => $popup_form->id(),
    ];

    $build['details']['trigger_selector'] = [
      '#type' => 'item',
      '#title' => $this->t('Trigger Selector'),
      '#markup' => $popup_form->getTriggerSelector(),
    ];

    $build['details']['webform_id'] = [
      '#type' => 'item',
      '#title' => $this->t('Webform'),
      '#markup' => $popup_form->getWebformId() ?: $this->t('- None -'),
    ];

    $build['details']['popup_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Popup Title'),
      '#markup' => $popup_form->getPopupTitle() ?: $this->t('- None -'),
    ];

    $build['details']['status'] = [
      '#type' => 'item',
      '#title' => $this->t('Status'),
      '#markup' => $popup_form->status() ? $this->t('Enabled') : $this->t('Disabled'),
    ];

    return $build;
  }

  /**
   * The title callback for the entity view page.
   *
   * @param \Drupal\popup_form\PopupFormInterface $popup_form
   *   The popup form entity.
   *
   * @return string
   *   The page title.
   */
  public function title(PopupFormInterface $popup_form) {
    return $this->t('Popup Form: @label', ['@label' => $popup_form->label()]);
  }

}