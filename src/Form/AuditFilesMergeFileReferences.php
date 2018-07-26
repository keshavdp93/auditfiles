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
use Drupal\Core\Database\Database;

class AuditFilesMergeFileReferences extends FormBase implements ConfirmFormInterface {

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'mergefilereferences';
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
    return 'mergeFileReferences';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('auditfiles.audit_files_mergefilereferences');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t("Do you wan't to merge following record");
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('auditfiles_config.settings');
    $connection = Database::getConnection();
    $storage = $form_state->getStorage();
    $date_format = $config->get('auditfiles_report_options_date_format') ? $config->get('auditfiles_report_options_date_format') : 'long';
    if(isset($storage['confirm'])) {
      if ($storage['stage'] == 'confirm') {
        $header = [
          'origname' => [
            'data' => $this->t('Filename'),
          ],
          'fileid' => [
            'data' => $this->t('File ID'),
          ],
          'fileuri' => [
            'data' => $this->t('URI'),
          ],
          'filesize' => [
            'data' => $this->t('Size'),
          ],
          'timestamp' => [
            'data' => $this->t('Time uploaded'),
          ],
        ];
        $files = [];
        $values = $form_state->getValue('files_being_merged');
        foreach ( $values as $file_id) {
          if (!empty($file_id)) {
            $query = $connection->select('file_managed', 'fm');
            $query->condition('fm.fid', $file_id);
            $query->fields('fm', ['fid','uid','filename','uri','filemime','filesize','created','status']);
            $results = $query->execute()->fetchAll();
            $file = $results[0];
            if (!empty($file)) {
              $files[$file_id] = [
                'origname' => $file->filename,
                'fileid' => $file_id,
                'fileuri' => $file->uri,
                'filesize' => number_format($file->filesize),
                'timestamp' => format_date($file->created, $date_format),
              ];
            }
            else {
              drupal_set_message(t('A file object was not found for file ID @fid.', ['@fid' => $file_id]));
            }
          }
          else {
            unset($form_state->getValue('files_being_merged')[$file_id]);
          }
        }
        // Save the selected items for later use.
        $storage['files_being_merged'] = $files;
        $form_state->setStorage($storage);
        $form['file_being_kept'] = [
          '#type' => 'tableselect',
          '#header' => $header,
          '#options' => $files,
          '#default_value' => key($files),
          '#empty' => $this->t('No items found.'),
          '#multiple' => FALSE,
        ];
        $form['#title'] = $this->t('Which file will be the one the others are merged into?');
        $form['#attributes']['class'][] = 'confirmation';
        $form['actions'] = [
          '#type' => 'actions',
        ];
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->getConfirmText(),
          '#button_type' => 'primary',
          '#submit' => ['::confirmSubmissionHandlerFileMerge']
        ];
        $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
        if (!isset($form['#theme'])) {
          $form['#theme'] = 'confirm_form';
        }
        return $form;
      }
      else if ($storage['stage'] == 'preconfirm') {
        $header = [
          'filename' => [
            'data' => $this->t('Filename'),
          ],
          'fileid' => [
            'data' => $this->t('File ID'),
          ],
          'fileuri' => [
            'data' => $this->t('URI'),
          ],
          'filesize' => [
            'data' => $this->t('Size'),
          ],
          'timestamp' => [
            'data' => $this->t('Time uploaded'),
          ],
        ];
        $files = [];
        $values = $form_state->getValue('files');
        foreach ($values as $file_name) {
          if (!empty($file_name)) {
            $query = 'SELECT fid FROM {file_managed} WHERE filename = :file_name ORDER BY uri ASC';
            $results = $connection->query($query, [':file_name' => $file_name])->fetchAll();
            if (!empty($results)) {
              foreach ($results as $result) {
                $query = $connection->select('file_managed', 'fm');
                $query->condition('fm.fid', $result->fid);
                $query->fields('fm', ['fid','uid','filename','uri','filemime','filesize','created','status']);
                $results = $query->execute()->fetchAll();
                $file = $results[0];
                if (!empty($file)) {
                  $files[$result->fid] = [
                    'filename' => $file->filename,
                    'fileid' => $file->fid,
                    'fileuri' => $file->uri,
                    'filesize' => number_format($file->filesize),
                    'timestamp' => format_date($file->created, $date_format),
                  ];
                }
                else {
                  drupal_set_message(t('A file object was not found for file ID @fid.', ['@fid' => $result->fid]));
                }
              }
            }
          }
          else {
            unset($form_state->getValue('files')[$file_name]);
          }
        }
        $form['files_being_merged'] = [
          '#type' => 'tableselect',
          '#header' => $header,
          '#options' => $files,
          '#empty' => $this->t('No items found.'),
        ];
        if (!empty($files)) {
          $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
              '#type' => 'submit',
              '#value' => $this->t('Next step'),
              '#submit' => ['::submissionHandlerMergeRecordPreConfirm'],
            ],
          ];
        }
        return $form;
      }
    }
    $file_names = \Drupal::service('auditfiles.merge_file_references')->_auditfiles_merge_file_references_get_file_list();
    if (!empty($file_names)) {
      $date_format = $config->get('auditfiles_report_options_date_format') ? $config->get('auditfiles_report_options_date_format') : 'long';
      foreach ($file_names as $file_name) {
        $rows[$file_name] = \Drupal::service('auditfiles.merge_file_references')->_auditfiles_merge_file_references_get_file_data($file_name, $date_format);
      }
    }
    // Set up the pager.
    if (!empty($rows)) {
      $items_per_page =  $config->get('auditfiles_report_options_items_per_page')?$config->get('auditfiles_report_options_items_per_page'):50;
      if (!empty($items_per_page)) {
        $current_page = pager_default_initialize(count($rows), $items_per_page);
        // Break the total data set into page sized chunks.
        $pages = array_chunk($rows, $items_per_page, TRUE);
      }
    }
    // Setup the record count and related messages.
    $maximum_records = $config->get('auditfiles_report_options_maximum_records') ? $config->get('auditfiles_report_options_maximum_records'):250;
    if (!empty($rows)) {
      if ($maximum_records > 0) {
        $file_count_message = 'Found at least @count files in the file_managed table with duplicate file names.';
      }
      else {
        $file_count_message = 'Found @count files in the file_managed table with duplicate file names.';
      }
      $form_count = $this->formatPlural(count($rows), 'Found 1 file in the file_managed table with a duplicate file name.', $file_count_message);
    }
    else {
      $form_count = $this->t('Found no files in the file_managed table with duplicate file names.');
    }
    $form['filter']['single_file_names']['auditfiles_merge_file_references_show_single_file_names'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show files without duplicate names'),
      '#default_value' => $config->get('auditfiles_merge_file_references_show_single_file_names')?$config->get('auditfiles_merge_file_references_show_single_file_names'):0,
      '#suffix' => '<div>' . $this->t("Use this button to reset this report's variables and load the page anew.") . '</div>',
    ];
    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => \Drupal::service('auditfiles.merge_file_references')->_auditfiles_merge_file_references_get_header(),
      '#empty' => $this->t('No items found.'),
      '#prefix' => '<div><em>' . $form_count . '</em></div>',
    ];
    if (!empty($rows) && !empty($pages)) {
      $form['files']['#options'] = $pages[$current_page];
    }
    elseif (!empty($rows)) {
      $form['files']['#options'] = $rows;
    }
    else {
      $form['files']['#options'] = [];
    }
    if (!empty($rows)) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Merge selected items'),
        '#submit' => ['::submissionHandlerMergeRecord'],
      ];
      $form['pager'] = ['#type' => 'pager'];
    }
    return $form;
  }

  /**
   * Form validation.
   */
  public function validateForm (array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if(isset($storage['confirm'])) {
      if ($storage['stage'] == 'preconfirm') {
        $counter = 0;
        foreach ($form_state->getValue('files_being_merged') as $file) {
          if (!empty($file)) {
            $counter++;
          }
        }
        if ($counter < 2) {
          $form_state->setErrorByName('files_being_merged', $this->t('At least two file IDs must be selected in order to merge them.'));
        }
      }
    }
  }

  /**
   * Submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submit form.
   */
  public function submissionHandlerMergeRecord(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('auditfiles_config.settings')
      ->set('auditfiles_merge_file_references_show_single_file_names', $form_state->getValue('auditfiles_merge_file_references_show_single_file_names'))->save();
    if (!empty($form_state->getValue('files'))) {
      foreach ($form_state->getValue('files') as $file_id) {
        if (!empty($file_id)) {
          $storage = [
            'files' => $form_state->getValue('files'),
            'confirm' => TRUE,
            'stage' => 'preconfirm',
          ];
          $form_state->setStorage($storage);      
          $form_state->setRebuild();
        }
      }
      if(!isset($storage)) {
        drupal_set_message($this->t('At least one file name must be selected in order to merge the file IDs. No changes were made.'),'error');  
      }
    }
  }

  /**
   * Preconfirm form submission.
   */
  public function submissionHandlerMergeRecordPreConfirm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('files_being_merged'))) {
      foreach ($form_state->getValue('files_being_merged') as $file_id) {
        if (!empty($file_id)) {
          $storage = [
            'files_being_merged' => $form_state->getValue('files_being_merged'),
            'confirm' => TRUE,
            'stage' => 'confirm',
          ];
          $form_state->setStorage($storage);      
          $form_state->setRebuild();
        }
      }
    }
  }

  /**
   * Confirm form submission.
   */
  public function confirmSubmissionHandlerFileMerge(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    batch_set(\Drupal::service('auditfiles.merge_file_references')->_auditfiles_merge_file_references_batch_merge_create_batch($form_state->getValue('file_being_kept'),$storage['files_being_merged']));
    unset($storage['stage']);
  }

}
