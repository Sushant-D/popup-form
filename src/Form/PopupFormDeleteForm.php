<?php


namespace Drupal\popup_form\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Popup Form entities.
 */
class PopupFormDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the popup form %name?', [
      '%name' => $this->entity->label()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('popup_form.admin.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone. The popup form and all its configuration will be permanently deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $label = $this->entity->label();
    
    $this->entity->delete();

    $this->messenger()->addMessage(
      $this->t('Popup form %label has been deleted.', [
        '%label' => $label,
      ])
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}