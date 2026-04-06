<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Represents the outcome status of a file operation.
 *
 * Used within OperationResult to communicate what actually happened
 * when an operation was executed — without relying on exceptions for
 * non-error outcomes such as skipping an already up-to-date file.
 */
enum OperationStatus {

  /* The operation completed successfully and the file was modified. */
  case Success;

  /*
   * The operation was intentionally skipped.
   *
   * Example: the destination file already exists and is up to date,
   * so no write was necessary.
   */
  case Skipped;

  /*
   * The operation failed due to an unexpected error.
   *
   * The OperationResult message will contain the failure reason.
   */
  case Failed;

}
