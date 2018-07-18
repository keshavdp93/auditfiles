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
use Drupal\file\Entity\File;

class AuditFilesManagedNotUsed extends FormBase implements ConfirmFormInterface {

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'audit_files_managed_not_used';
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
    return 'AuditFilesManagedNotUsed';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('auditfiles.audit_files_managednotused');
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
    $storage = &$form_state->getStorage();
    if (isset($storage['confirm'])) {
      $values = $form_state->getValue('files');
      $form['changelist'] = [
        '#prefix' => '<ul>',
        '#suffix' => '</ul>',
        '#tree' => TRUE,
      ];
      // Prepare the list of items to present to the user.
      if (!empty($values)) {
        foreach ($values as $file_id) {
          if (!empty($file_id)) {
            $file = File::load($file_id);
            if (!empty($file)) {
              $form['changelist'][$file_id] = [
                '#type' => 'hidden',
                '#value' => $file_id,
                '#prefix' => '<li><strong>' . $file->getFilename() . '</strong> ' . $this->t('will be deleted from the file_managed table.'),
                '#suffix' => "</li>\n",
              ];
            }
          }
          else {
            unset($form_state->getValue('files')[$file_id]);
          }
        }
      }
      $form['#title'] = $this->t('Delete these items from the file_managed table?');
      $form['#attributes']['class'][] = 'confirmation';
      $form['actions'] = [
        '#type' => 'actions',
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#button_type' => 'primary',
        '#submit' => ['::confirmSubmissionHandlerFileDelete']
      ];
      $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
      if (!isset($form['#theme'])) {
        $form['#theme'] = 'confirm_form';
      }
      return $form;
    }
    $config = \Drupal::config('auditfiles_config.settings');
    $file_ids = \Drupal::service('auditfiles.managed_not_used')->_auditfiles_managed_not_used_get_file_list();
    if (!empty($file_ids)) {
      $date_format = $config->get('auditfiles_report_options_date_format') ? $config->get('auditfiles_report_options_date_format') : 'long';
      foreach ($file_ids as $file_id) {
        $rows[$file_id] = \Drupal::service('auditfiles.managed_not_used')->_auditfiles_managed_not_used_get_file_data($file_id, $date_format);
      }
    }
    if (!empty($rows)) {
      $items_per_page = $config->get('auditfiles_report_options_items_per_page') ? $config->get('auditfiles_report_options_items_per_page') : 50;
      if (!empty($items_per_page)) {
        $current_page = pager_default_initialize(count($rows), $items_per_page);
        $pages = array_chunk($rows, $items_per_page, TRUE);
      }
    }
    // Setup the record count and related messages.
    $maximum_records = $config->get('auditfiles_report_options_maximum_records') ? $config->get('auditfiles_report_options_maximum_records') : 50;
    if (!empty($rows)) {
      if ($maximum_records > 0) {
        $file_count_message = $this->t('Found at least @count files in the file_managed table that are not in the file_usage table.');
      }
      else {
        $file_count_message = $this->t('Found @count files in the file_managed table that are not in the file_usage table.');
      } 
      $form_count = $this->formatPlural(count($rows), $this->t('Found 1 file in the file_managed table that is not in the file_usage table.'), $file_count_message);
    }
    else {
      $form_count = $this->t('Found no files in the file_managed table that are not in the file_usage table.');
    }
    // Create the form table.
    $form['files'] = array(
      '#type' => 'tableselect',
      '#header' => \Drupal::service('auditfiles.managed_not_used')->_auditfiles_managed_not_used_get_header(),
      '#empty' => $this->t('No items found.'),
      '#prefix' => '<div><em>' . $form_count . '</em></div>',
    );
    // Add the data.
    if (!empty($rows) && !empty($pages)) {
      $form['files']['#options'] = $pages[$current_page];
    }
    elseif (!empty($rows)) {
      $form['files']['#options'] = $rows;
    }
    else {
      $form['files']['#options'] = [];
    }
    // Add any action buttons.
    if (!empty($rows)) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete selected items from the file_managed table'),
        '#submit' => ['::submissionHandlerDeleteFromFileManaged'],
      ];
      $form['pager'] = ['#type' => 'pager'];
    }
    return $form;
  }

  /**
   * Submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submit form delete file managed record.
   */
  public function submissionHandlerDeleteFromFileManaged(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('files'))) {
      foreach ($form_state->getValue('files') as $file_id) {
        if (!empty($file_id)) {
          $storage = [
            'files' => $form_state->getValue('files'),
            'op' => 'del',
            'confirm' => TRUE
          ];
          $form_state->setStorage($storage);      
          $form_state->setRebuild();
        }
      }
      if (!isset($storage)) {
        drupal_set_message($this->t('No items were selected to operate on.'));  
      }
    }
  }

  /**
   * Submit form confirm delete record.
   */
  public function confirmSubmissionHandlerFileDelete(array &$form, FormStateInterface $form_state) {
    batch_set(\Drupal::service('auditfiles.managed_not_used')->_auditfiles_managed_not_used_batch_delete_create_batch($form_state->getValue('changelist')));
  }

}
