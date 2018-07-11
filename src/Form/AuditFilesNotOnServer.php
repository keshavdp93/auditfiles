<?php

namespace Drupal\auditfiles\Form;

use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Url;
use Drupal\Core\Link;

class AuditFilesNotOnServer extends FormBase implements ConfirmFormInterface {
  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'audit_files_not_on_server';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'auditFilesNotOnServer';
  }
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('auditfiles.audit_files_notonserver');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t("Do you wan't to delete following record");
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  return $form;
}
 /**
   * Submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
drupal_set_message('123');
  }
}