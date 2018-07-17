<?php
/**
 * @file providing the service that used in 'used not managed' functionality.
 *
 */
namespace  Drupal\auditfiles;

use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;
use Drupal\Core\Link;
use Drupal\Core\Url;

class ServiceAuditFilesUsedNotManaged {
  /**
   * Retrieves the file IDs to operate on.
   * @return array
   *   The file IDs.
   */
  function _auditfiles_used_not_managed_get_file_list() {
    // Get all the file IDs in the file_usage table that are not in the
    // file_managed table.
    $connection = Database::getConnection();
    $config = \Drupal::config('auditfiles_config.settings');
    $query = 'SELECT DISTINCT fid FROM {file_usage} fu WHERE fid NOT IN (SELECT fid FROM {file_managed})';
    $maximum_records = $config->get('auditfiles_report_options_maximum_records') ? $config->get('auditfiles_report_options_maximum_records') : 250;
    if ($maximum_records > 0) {
      $query .= ' LIMIT ' . $maximum_records;
    }
    return $connection->query($query)->fetchCol();
  }

  /**
   * Retrieves information about an individual file from the database.
   */
  function _auditfiles_used_not_managed_get_file_data($file_id) {
    // Get the file information for the specified file ID from the database.
    $connection = Database::getConnection();
    $query = 'SELECT * FROM {file_usage} WHERE fid = ' . $file_id;
    $file = $connection->query($query)->fetchObject();

    $url = Url::fromUri('internal:/'.$file->type . '/' . $file->id);
    $result = Link::fromTextAndUrl($file->type . '/' . $file->id, $url)->toString();
    return  [
      'fid' => $file->fid,
      'module' => $file->module . ' ' . t('module'),
      'id' => $result,
      'count' => $file->count,
    ];
  }

  /**
   * Returns the header to use for the display table.
   */
  function _auditfiles_used_not_managed_get_header() {
    return [
      'fid' => [
        'data' => t('File ID'),
      ],
      'module' => [
        'data' => t('Used by'),
      ],
      'id' => [
        'data' => t('Used in'),
      ],
      'count' => [
        'data' => t('Count'),
      ],
    ];
  }

  /**
   * Creates the batch for deleting files from the file_usage table.
   */
  function _auditfiles_used_not_managed_batch_delete_create_batch(array $fileids) {
    $batch['error_message'] = t('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::_auditfiles_used_not_managed_batch_finish_batch';
    $batch['progress_message'] = t('Completed @current of @total operations.');
    $batch['title'] = t('Deleting files from the file_usage table');
    $operations = $file_ids = [];
    foreach ($fileids as $file_id) {
      if ($file_id != 0) {
        $file_ids[] = $file_id;
      }
    }
    // Fill in the $operations variable.
    foreach ($file_ids as $file_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::_auditfiles_used_not_managed_batch_delete_process_batch',
        [$file_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the file_usage table.
   *
   * @param int $file_id
   *   The ID of the file to delete from the database.
   */
  function _auditfiles_used_not_managed_batch_delete_process_file($file_id) {
    $connection = Database::getConnection();
    $num_rows = $connection->delete('file_usage')->condition('fid', $file_id)->execute();
    if (empty($num_rows)) {
      drupal_set_message(
        t(
          'There was a problem deleting the record with file ID %fid from the file_usage table. Check the logs for more information.',
          ['%fid' => $file_id]
        ),
        'warning'
      );
    }
    else {
      drupal_set_message(
        t(  
          'Sucessfully deleted File ID : %fid from the file_usages table.',
          ['%fid' => $file_id]
        )
      );
    }
  }

}
