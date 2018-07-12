<?php
/**
* @file providing the service that used in not in database functionality.
*
*/
namespace  Drupal\auditfiles;

use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;

class AuditFilesBatchProcess {

  /**
   * The function that is called when the batch is completed.
   */
  public static function auditfiles_not_in_database_batch_finish_batch($success, $results, $operations) {
    if ($success) {
      //success action.
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(
      t('An error occurred while processing @operation with arguments : @args',
        [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0], TRUE),
         ]
        )
      );
    }
  }

  /**
   * The batch process for adding the file.
   *
   * @param string $filename
   *   The full pathname to the file to add to the database.
   * @param array $context
   *   Used by the Batch API to keep track of data and pass it from one operation
   *   to the next.
   */
  public static function auditfiles_not_in_database_batch_add_process_batch($filename, array &$context) {
    \Drupal::service('auditfiles.not_in_database')->auditfiles_not_in_database_batch_add_process_file($filename);
    $context['results'][] = $filename;
    $context['message'] = t('Processed %filename.', ['%filename' => $filename]);
  }

  /**
   * The batch process for deleting the file.
   *
   * @param string $filename
   *   The filename to delete.
   * @param array $context
   *   Used by the Batch API to keep track of data and pass it from one operation
   *   to the next.
   */
  public static function auditfiles_not_in_database_batch_delete_process_batch($filename, array &$context) {
    \Drupal::service('auditfiles.not_in_database')->_auditfiles_not_in_database_batch_delete_process_file($filename);
    $context['results'][] = Html::escape($filename);
    $context['message'] = t('Processed %filename.', ['%filename' => $filename]);
  }

  /**
   * Escapes any possible regular expression characters in the specified string.
   *
   * @param string $element
   *   The string to escape.
   * @param mixed $key
   *   The key or index for the array item passed into $element.
   * @param bool $makefilepath
   *   Set to TRUE to change elements to file paths at the same time.
   */
  public static function _auditfiles_make_preg(&$element, $key='', $makefilepath = FALSE) {
    if ($makefilepath) {
      $realpath = drupal_realpath(file_build_uri($element));
      if ($realpath) {
        $element = $realpath;
      }
    }
    $element = preg_quote($element);
  }

  /**
   * The function that is called when the batch is complete.
   */
  public static function _auditfiles_not_on_server_batch_finish_batch($success, $results, $operations) {
    if ($success) {
    // Do tasks
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * The batch process for deleting the file.
   *
   * @param int $file_id
   *   The ID of a file to delete.
   * @param array $context
   *   Used by the Batch API to keep track of and pass data from one operation to
   *   the next.
   */
  public static function _auditfiles_not_on_server_batch_delete_process_batch($file_id, array &$context) {
    \Drupal::service('auditfiles.not_on_server')->_auditfiles_not_on_server_batch_delete_process_file($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

}