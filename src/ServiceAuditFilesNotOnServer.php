<?php
/**
 * @file providing the service that used in not in database functionality.
 *
 */
namespace  Drupal\auditfiles;

use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;

class ServiceAuditFilesNotOnServer {
  /**
   * Retrieves the file IDs to operate on.
   *
   * @return array
   *   The file IDs.
   */
  function _auditfiles_not_on_server_get_file_list() {
    $config = \Drupal::config('auditfiles_config.settings');
    $file_ids = [];
    $maximum_records = $config->get('auditfiles_report_options_maximum_records') ? $config->get('auditfiles_report_options_maximum_records') : 250;
    $connection = Database::getConnection();
    $query = $connection->select('file_managed', 'fm');
    $query->range(0, $maximum_records);
    $query->fields('fm', ['fid','uri']);
    $results = $query->execute()->fetchAll();
    foreach ($results as $result) {
      $target = drupal_realpath($result->uri);
      if (!file_exists($target)) {
        $file_ids[] = $result->fid;
      }
    }
    return $file_ids;
  }

  /**
   * Retrieves information about an individual file from the database.
   *
   * @param int $file_id
   *   The ID of the file to prepare for display.
   *
   * @return array
   *   The row for the table on the report, with the file's information formatted
   *   for display.
   */
  function _auditfiles_not_on_server_get_file_data($file_id, $date_format) {
    $connection = Database::getConnection();
    $query = $connection->select('file_managed', 'fm');
    $query->condition('fm.fid', $file_id);
    $query->fields('fm', ['fid','uid','filename','uri','filemime','filesize','created','status']);
    $results = $query->execute()->fetchAll();
    $file = $results[0];
    return [
      'fid' => $file->fid,
      'uid' => $file->uid,
      'filename' => $file->filename,
      'uri' => $file->uri,
      'path' => drupal_realpath($file->uri),
      'filemime' => $file->filemime,
      'filesize' => number_format($file->filesize),
      'datetime' => \Drupal::service('date.formatter')->format($file->created, $date_format),
      'status' => ($file->status = 1) ? 'Permanent' : 'Temporary',
    ];
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  function _auditfiles_not_on_server_get_header() {
    return [
      'fid' => [
        'data' => t('File ID'),
      ],
      'uid' => [
        'data' => t('User ID'),
      ],
      'filename' => [
        'data' => t('Name'),
      ],
      'uri' => [
        'data' => t('URI'),
      ],
      'path' => [
        'data' => t('Path'),
      ],
      'filemime' => [
        'data' => t('MIME'),
      ],
      'filesize' => [
        'data' => t('Size'),
      ],
      'datetime' => [
        'data' => t('When added'),
      ],
      'status' => [
        'data' => t('Status'),
      ],
    ];
  }

  /**
   * Creates the batch for deleting files from the database.
   *
   * @param array $fileids
   *   The list of file IDs to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  function _auditfiles_not_on_server_batch_delete_create_batch(array $fileids) {
    $batch['error_message'] = t('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::_auditfiles_not_on_server_batch_finish_batch';
    $batch['progress_message'] = t('Completed @current of @total operations.');
    $batch['title'] = t('Deleting files from the database');
    $operations = $file_ids = [];
    foreach ($fileids as $file_id) {
      if ($file_id != 0) {
        $file_ids[] = $file_id;
      }
    }
    foreach ($file_ids as $file_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::_auditfiles_not_on_server_batch_delete_process_batch',
        [$file_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the database.
   *
   * @param int $file_id
   *   The ID of the file to delete from the database.
   */
  function _auditfiles_not_on_server_batch_delete_process_file($file_id) {
    $connection = Database::getConnection();
    $num_rows = $connection->delete('file_managed')
      ->condition('fid', $file_id)
      ->execute();
    if (empty($num_rows)) {
      drupal_set_message(
        t(
          'There was a problem deleting the record with file ID %fid from the file_managed table. Check the logs for more information.',
          ['%fid' => $file_id]
        ),
        'warning'
      );
    } else {
      drupal_set_message(
        t(  
          'Sucessfully deleted File ID : %fid from the file_managed table.',
          ['%fid' => $file_id]
        )
      );
    }
  }

}