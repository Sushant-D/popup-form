<?php

/**
 * @file
 * Provides an additional config form for theme settings.
 */

use Drupal\node\Entity\Node;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function cpeucdavis_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  
  // Header description textarea
  $form['header_settings'] = [
    '#type' => 'details',
    '#title' => t('Header Settings'),
    '#open' => TRUE,
  ];

  // Autocomplete for node
  $form['header_settings']['start_conversation_link'] = [
    '#type' => 'textfield',
    '#title' => t('Start a Conversation Link'),
    '#default_value' => theme_get_setting('start_conversation_link'),
    '#description' => t('Enter the URL for Start a Conversation Link.'),
  ];

  // Footer description textarea
  $form['footer_settings'] = [
    '#type' => 'details',
    '#title' => t('Footer Settings'),
    '#open' => TRUE,
  ];

  $form['footer_settings']['footer_background_image'] = [
    '#type' => 'media_library_widget',
    '#title' => t('Footer Background Image'),
    '#default_value' => theme_get_setting('footer_background_image') ? theme_get_setting('footer_background_image') : NULL,
    '#allowed_bundles' => ['image'],
    '#cardinality' => 1,
    '#description' => t('Select or upload a background image for the footer section. Recommended size: 1920x800px or larger.'),
  ];

  // Footer Description Container
  $form['footer_settings']['footer_description_container'] = [
    '#type' => 'fieldset',
    '#title' => t('Footer Description Section'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  ];

  $form['footer_settings']['footer_description_container']['footer_description_title'] = [
    '#type' => 'textfield',
    '#title' => t('Footer Description Title'),
    '#default_value' => theme_get_setting('footer_description_title'),
    '#description' => t('Enter the title for the footer description section.'),
  ];

  $form['footer_settings']['footer_description_container']['footer_description'] = [
    '#type' => 'text_format',
    '#title' => t('Footer Description'),
    '#default_value' => theme_get_setting('footer_description.value') ?: '',
    '#format' => theme_get_setting('footer_description.format') ?: 'full_html',
    '#description' => t('Enter the footer description text that will appear in the footer. HTML is allowed.'),
  ];

  $form['footer_settings']['footer_description_container']['footer_description_tagline'] = [
    '#type' => 'textfield',
    '#title' => t('Footer Description Tagline'),
    '#default_value' => theme_get_setting('footer_description_tagline'),
    '#description' => t('Enter a tagline for the footer description section.'),
  ];

  $form['footer_settings']['copyright_text'] = [
    '#type' => 'text_format',
    '#title' => t('Copyright Text'),
    '#default_value' => theme_get_setting('copyright_text.value') ?: '',
    '#format' => theme_get_setting('copyright_text.format') ?: 'full_html',
    '#description' => t('Enter the footer copyright text that will appear in the footer. HTML is allowed.'),
  ];

  // Youtube link
  $form['footer_settings']['youtube_link'] = [
    '#type' => 'textfield',
    '#title' => t('Youtube Link'),
    '#default_value' => theme_get_setting('youtube_link'),
    '#description' => t('Enter the URL of your youtube page.'),
  ];

  // Twitter link
  $form['footer_settings']['twitter_link'] = [
    '#type' => 'textfield',
    '#title' => t('Twitter Link'),
    '#default_value' => theme_get_setting('twitter_link'),
    '#description' => t('Enter the URL of your Twitter page.'),
  ];

  // Facebook link
  $form['footer_settings']['facebook_link'] = [
    '#type' => 'textfield',
    '#title' => t('Facebook Link'),
    '#default_value' => theme_get_setting('facebook_link'),
    '#description' => t('Enter the URL of your Facebook page.'),
  ];

  // Linkedin link
  $form['footer_settings']['linkedin_link'] = [
    '#type' => 'textfield',
    '#title' => t('Linkedin Link'),
    '#default_value' => theme_get_setting('linkedin_link'),
    '#description' => t('Enter the URL of your Linkedin profile.'),
  ];

  // Instagram link
  $form['footer_settings']['instagram_link'] = [
    '#type' => 'textfield',
    '#title' => t('Instagram Link'),
    '#default_value' => theme_get_setting('instagram_link'),
    '#description' => t('Enter the URL of your Instagram profile.'),
  ];

  // Admin Container
  $form['footer_settings']['admin_container'] = [
    '#type' => 'fieldset',
    '#title' => t('Admin Container Section'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  ];

  $form['footer_settings']['admin_container']['admin_container_title'] = [
    '#type' => 'textfield',
    '#title' => t('Admin Container Title'),
    '#default_value' => theme_get_setting('admin_container_title'),
    '#description' => t('Enter the title for the admin container section.'),
  ];

  $form['footer_settings']['admin_container']['admin_container_description'] = [
    '#type' => 'text_format',
    '#title' => t('Admin Container Description'),
    '#default_value' => theme_get_setting('admin_container_description.value') ?: '',
    '#format' => theme_get_setting('admin_container_description.format') ?: 'full_html',
    '#description' => t('Enter the description for the admin container section. HTML is allowed.'),
  ];

  $form['footer_settings']['admin_container']['admin_mail_address'] = [
    '#type' => 'email',
    '#title' => t('Admin Mail Address'),
    '#default_value' => theme_get_setting('admin_mail_address'),
    '#description' => t('Enter the email address for the admin section.'),
  ];

  // Classroom Container
  $form['footer_settings']['classroom_container'] = [
    '#type' => 'fieldset',
    '#title' => t('Classroom Container Section'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  ];

  $form['footer_settings']['classroom_container']['classroom_container_title'] = [
    '#type' => 'textfield',
    '#title' => t('Classroom Container Title'),
    '#default_value' => theme_get_setting('classroom_container_title'),
    '#description' => t('Enter the title for the classroom container section.'),
  ];

  $form['footer_settings']['classroom_container']['classroom_container_description'] = [
    '#type' => 'text_format',
    '#title' => t('Classroom Container Description'),
    '#default_value' => theme_get_setting('classroom_container_description.value') ?: '',
    '#format' => theme_get_setting('classroom_container_description.format') ?: 'full_html',
    '#description' => t('Enter the description for the classroom container section. HTML is allowed.'),
  ];

  $form['footer_settings']['classroom_container']['classroom_mail_address'] = [
    '#type' => 'email',
    '#title' => t('Classroom Mail Address'),
    '#default_value' => theme_get_setting('classroom_mail_address'),
    '#description' => t('Enter the email address for the classroom section.'),
  ];

  // Add custom submit handler to save file permanently
  $form['#submit'][] = 'cpeucdavis_theme_settings_submit';
}


/**
 * Custom submit handler for theme settings form.
 */
function cpeucdavis_theme_settings_submit($form, \Drupal\Core\Form\FormStateInterface $form_state) {
  // Handle footer background image media
  $footer_bg_media_id = $form_state->getValue('footer_background_image');
  if (!empty($footer_bg_media_id)) {
    // Media ID is already in the correct format for storage
    $form_state->setValue('footer_background_image', $footer_bg_media_id);
  }
}
