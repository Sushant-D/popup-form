<?php

// src/Form/PopupFormEntityForm.php - SIMPLE WORKING VERSION

namespace Drupal\popup_form\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PopupFormEntityForm.
 */
class PopupFormEntityForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PopupFormEntityForm object.
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
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\popup_form\Entity\PopupForm $popup_form */
    $popup_form = $this->entity;

    // Basic fields - NO NESTED STRUCTURE
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $popup_form->label(),
      '#description' => $this->t('Label for the Popup Form.'),
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

    // Trigger selector - FLAT, NOT NESTED
    $form['trigger_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger Selector'),
      '#default_value' => $popup_form->getTriggerSelector() ?: '#open-signUp-UcDavis',
      '#description' => $this->t('CSS selector for elements that will trigger this popup (e.g., #open-signUp-UcDavis, .popup-trigger).'),
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

    // Webform Selection
    $webform_options = [];
    if ($this->moduleHandler->moduleExists('webform')) {
      $webforms = $this->entityTypeManager->getStorage('webform')->loadMultiple();
      foreach ($webforms as $webform) {
        if ($webform instanceof WebformInterface) {
          $webform_options[$webform->id()] = $webform->label();
        }
      }
    }

    $form['webform_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Webform'),
      '#options' => $webform_options,
      '#default_value' => $popup_form->getWebformId() ?: '',
      '#description' => $this->t('Select the webform to embed in this popup.'),
      '#empty_option' => $this->t('- Select a webform -'),
    ];

    // Popup Settings - FLAT STRUCTURE
    $popup_settings = $popup_form->getPopupSettings();
    
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Popup Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['settings']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $popup_settings['width'] ?? '600px',
      '#description' => $this->t('Popup width (e.g., 600px, 50%, auto).'),
    ];

    $form['settings']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $popup_settings['height'] ?? 'auto',
      '#description' => $this->t('Popup height (e.g., 400px, 50%, auto).'),
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

    $form['settings']['escape_close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close on Escape key'),
      '#default_value' => $popup_settings['escape_close'] ?? TRUE,
    ];

    $form['settings']['click_outside_close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close when clicking outside'),
      '#default_value' => $popup_settings['click_outside_close'] ?? TRUE,
    ];

    $form['settings']['auto_close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto close after delay'),
      '#default_value' => $popup_settings['auto_close'] ?? FALSE,
    ];

    $form['settings']['auto_close_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Auto close delay (ms)'),
      '#default_value' => $popup_settings['auto_close_delay'] ?? 5000,
      '#states' => [
        'visible' => [
          ':input[name="settings[auto_close]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Simple, direct validation
    $trigger_selector = $form_state->getValue('trigger_selector');
    if (empty(trim($trigger_selector))) {
      $form_state->setErrorByName('trigger_selector', $this->t('Trigger selector is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get settings values and structure them
    $settings = $form_state->getValue('settings', []);
    
    $popup_settings = [
      'width' => $settings['width'] ?? '600px',
      'height' => $settings['height'] ?? 'auto',
      'animation' => $settings['animation'] ?? 'fadeIn',
      'overlay' => $settings['overlay'] ?? TRUE,
      'close_button' => $settings['close_button'] ?? TRUE,
      'escape_close' => $settings['escape_close'] ?? TRUE,
      'click_outside_close' => $settings['click_outside_close'] ?? TRUE,
      'auto_close' => $settings['auto_close'] ?? FALSE,
      'auto_close_delay' => $settings['auto_close_delay'] ?? 5000,
    ];
    
    // Set the structured popup settings
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

    // Redirect to the collection page using the correct route
    $form_state->setRedirect('popup_form.admin.collection');
  }

}