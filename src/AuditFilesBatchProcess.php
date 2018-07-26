<?php
/**
 * @file providing the service that used in not in 
 * database batch processing functionality.
 */
namespace  Drupal\auditfiles;

use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;

class AuditFilesBatchProcess {

  /**
   * Called when the batch is completed in 'not in database fumctionality'.
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
        ),
        'error'
      );
    }
  }

  /**
   * The batch process for adding the file.
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
   * Called when the batch is complete in 'Not on server'.
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
        ),
        'error'
      );
    }
  }

  /**
   * The batch process for deleting the file.
   *
   *   Used by the Batch API to keep track of and pass data from one operation to
   *   the next.
   */
  public static function _auditfiles_not_on_server_batch_delete_process_batch($file_id, array &$context) {
    \Drupal::service('auditfiles.not_on_server')->_auditfiles_not_on_server_batch_delete_process_file($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

  /**
   * The batch process for deleting the file of Managed not used functionality.
   *
   *   Used by the Batch API to keep track of and pass data from one operation to
   *   the next.
   */
  public static function _auditfiles_managed_not_used_batch_delete_process_batch($file_id, array &$context) {
    \Drupal::service('auditfiles.managed_not_used')->_auditfiles_managed_not_used_batch_delete_process_file($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

  /**
   * The function that is called when the batch is complete.
   */
  public static function _auditfiles_managed_not_used_batch_finish_batch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      drupal_set_message(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        ),
        'error'
      );
    }
  }

  /**
   * The batch process for deleting the file feature 'used not managed'.
   *
   */
  public static function _auditfiles_used_not_managed_batch_delete_process_batch($file_id, array &$context) {
    \Drupal::service('auditfiles.used_not_managed')->_auditfiles_used_not_managed_batch_delete_process_file($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

  /**
   * Called when the batch is complete : functionality 'used not managed'.
   */
  public static function _auditfiles_used_not_managed_batch_finish_batch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      drupal_set_message(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        ),
        'error'
      );
    }
  }

  /**
   * The batch process for deleting the file.
   *
   */
  public static function _auditfiles_used_not_referenced_batch_delete_process_batch($file_id, array &$context) {
    \Drupal::service('auditfiles.used_not_referenced')->_auditfiles_used_not_referenced_batch_delete_process_file($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

  /**
   * The function that is called when the batch is complete.
   */
  public static function _auditfiles_used_not_referenced_batch_finish_batch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      drupal_set_message(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        ),
        'error'
      );
    }
  }

  /**
   * The function that is called when the batch is complete.
   */
  public static function _auditfiles_referenced_not_used_batch_finish_batch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      drupal_set_message(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        ),
        'error'
      );
    }
  }

  /**
   * The batch process for adding the file.
   *   Used by the Batch API to keep track of and pass data from one operation to
   *   the next.
   */
  public static function _auditfiles_referenced_not_used_batch_add_process_batch($reference_id, array &$context) {
    \Drupal::service('auditfiles.referenced_not_used')->_auditfiles_referenced_not_used_batch_add_process_file($reference_id);
    $context['results'][] = $reference_id;
    $context['message'] = t('Processed reference ID %file_id.', ['%file_id' => $reference_id]);
  }

  /**
   * The batch process for deleting the file.
   *   Used by the Batch API to keep track of and pass data from one operation to
   *   the next.
   */
  public static function _auditfiles_referenced_not_used_batch_delete_process_batch($reference_id, array &$context) {
    \Drupal::service('auditfiles.referenced_not_used')->_auditfiles_referenced_not_used_batch_delete_process_file($reference_id);
    $context['results'][] = $reference_id;
    $context['message'] = t('Processed reference ID %file_id.', ['%file_id' => $reference_id]);
  }


  /**
   * The function that is called when the batch is complete.
   */
  public static function _auditfiles_merge_file_references_batch_finish_batch($success, $results, $operations) {
  if (!$success) {
    $error_operation = reset($operations);
    drupal_set_message(
      t('An error occurred while processing @operation with arguments : @args',
        [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0], TRUE),
        ]
      ),
        'error'
    );
    }
  }

  /**
   * The batch process for deleting the file.
   *
   * @param int $file_being_kept
   *   The file ID of the file to merge the other into.
   * @param int $file_being_merged
   *   The file ID of the file to merge.
   * @param array $context
   *   Used by the Batch API to keep track of and pass data from one operation to
   *   the next.
   */
  public static function _auditfiles_merge_file_references_batch_merge_process_batch($file_being_kept, $file_being_merged, array &$context) {
    \Drupal::service('auditfiles.merge_file_references')->_auditfiles_merge_file_references_batch_merge_process_file($file_being_kept, $file_being_merged);
    $context['results'][] = $file_being_merged;
    $context['message'] = t(
      'Merged file ID %file_being_merged into file ID %file_being_kept.',
      [
        '%file_being_kept' => $file_being_kept,
        '%file_being_merged' => $file_being_merged,
      ]
    );
  }

}