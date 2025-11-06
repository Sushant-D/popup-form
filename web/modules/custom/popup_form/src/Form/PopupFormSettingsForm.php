<?php

namespace Drupal\popup_form\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PopupFormSettingsForm.
 */
class PopupFormSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'popup_form.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'popup_form_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('popup_form.settings');

    $form['global_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global Settings'),
    ];

    $form['global_settings']['enable_jquery'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include jQuery UI'),
      '#default_value' => $config->get('enable_jquery') ?? TRUE,
      '#description' => $this->t('Enable if you need jQuery UI for drag and drop functionality.'),
    ];

    $form['global_settings']['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug Mode'),
      '#default_value' => $config->get('debug_mode') ?? FALSE,
      '#description' => $this->t('Enable debug logging for popup forms.'),
    ];

    $form['global_settings']['load_on_all_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load popup JavaScript on all pages'),
      '#default_value' => $config->get('load_on_all_pages') ?? TRUE,
      '#description' => $this->t('If unchecked, you will need to manually attach popup libraries where needed.'),
    ];

    $form['default_styling'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default Styling'),
    ];

    $form['default_styling']['include_default_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include default CSS'),
      '#default_value' => $config->get('include_default_css') ?? TRUE,
      '#description' => $this->t('Include the default popup styling. Disable if you want to provide your own CSS.'),
    ];

    $form['default_styling']['default_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Theme'),
      '#options' => [
        'default' => $this->t('Default'),
        'minimal' => $this->t('Minimal'),
        'modern' => $this->t('Modern'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('default_theme') ?? 'default',
      '#states' => [
        'visible' => [
          ':input[name="include_default_css"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('popup_form.settings')
      ->set('enable_jquery', $form_state->getValue('enable_jquery'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->set('load_on_all_pages', $form_state->getValue('load_on_all_pages'))
      ->set('include_default_css', $form_state->getValue('include_default_css'))
      ->set('default_theme', $form_state->getValue('default_theme'))
      ->save();
  }

}
