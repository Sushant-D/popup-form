<?php

// src/Plugin/Block/PopupFormBlock.php

namespace Drupal\popup_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\popup_form\PopupFormManager;

/**
 * Provides a 'Popup Form Trigger' Block.
 *
 * @Block(
 *   id = "popup_form_trigger",
 *   admin_label = @Translation("Popup Form Trigger"),
 *   category = @Translation("Forms")
 * )
 */
class PopupFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The popup form manager.
   *
   * @var \Drupal\popup_form\PopupFormManager
   */
  protected $popupFormManager;

  /**
   * Constructs a PopupFormBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\popup_form\PopupFormManager $popup_form_manager
   *   The popup form manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PopupFormManager $popup_form_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->popupFormManager = $popup_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('popup_form.popup_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'popup_form' => '',
      'button_text' => 'Open Form',
      'button_classes' => 'btn btn-primary',
      'custom_trigger_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    // Get available popup forms
    $popup_forms = $this->entityTypeManager->getStorage('popup_form')->loadMultiple();
    $options = [];
    foreach ($popup_forms as $popup_form) {
      $options[$popup_form->id()] = $popup_form->label();
    }

    $form['popup_form'] = [
      '#type' => 'select',
      '#title' => $this->t('Popup Form'),
      '#options' => $options,
      '#default_value' => $config['popup_form'],
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a popup form -'),
      '#description' => $this->t('Choose which popup form this block should trigger.'),
    ];

    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $config['button_text'],
      '#required' => TRUE,
      '#description' => $this->t('The text displayed on the trigger button.'),
    ];

    $form['button_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button CSS Classes'),
      '#default_value' => $config['button_classes'],
      '#description' => $this->t('CSS classes to apply to the button (e.g., "btn btn-primary").'),
    ];

    $form['custom_trigger_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Trigger ID'),
      '#default_value' => $config['custom_trigger_id'],
      '#description' => $this->t('Optional: Custom ID for the trigger element. If empty, a unique ID will be generated.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['popup_form'] = $values['popup_form'];
    $this->configuration['button_text'] = $values['button_text'];
    $this->configuration['button_classes'] = $values['button_classes'];
    $this->configuration['custom_trigger_id'] = $values['custom_trigger_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    if (empty($config['popup_form'])) {
      return [];
    }

    // Load the popup form
    $popup_form = $this->entityTypeManager->getStorage('popup_form')->load($config['popup_form']);
    if (!$popup_form || !$popup_form->status()) {
      return [];
    }

    // Generate unique ID for this block instance
    $trigger_id = !empty($config['custom_trigger_id']) 
      ? $config['custom_trigger_id']
      : 'popup-trigger-' . $this->getPluginId() . '-' . substr(md5(serialize($config)), 0, 8);

    // Update popup form's trigger selector to include this block's trigger
    $current_selector = $popup_form->getTriggerSelector();
    $block_selector = '#' . $trigger_id;
    
    // Add this block's selector to the popup form's triggers
    if (strpos($current_selector, $block_selector) === FALSE) {
      $updated_selector = $current_selector . ', ' . $block_selector;
      $popup_form->setTriggerSelector($updated_selector);
      // Note: We don't save this change permanently, just for this request
    }

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['popup-form-block-wrapper'],
      ],
    ];

    $build['trigger'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $config['button_text'],
      '#attributes' => [
        'id' => $trigger_id,
        'type' => 'button',
        'class' => explode(' ', $config['button_classes']),
        'data-popup-form' => $config['popup_form'],
      ],
    ];

    // Attach popup form library and settings
    $build['#attached']['library'][] = 'popup_form/popup_form';
    $build['#attached']['drupalSettings']['popup_form'][$popup_form->id()] = [
      'trigger_selector' => $block_selector,
      'title' => $popup_form->getPopupTitle(),
      'description' => $popup_form->getPopupDescription(),
      'content' => $this->popupFormManager->renderPopupContent($popup_form),
      'settings' => $popup_form->getPopupSettings(),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0; // Disable caching for dynamic behavior
  }

}
