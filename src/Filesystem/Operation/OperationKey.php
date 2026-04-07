<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Represents the allowed payload keys for file operations.
 *
 * Each key is used by specific operation types:
 * - Overwrite: Used by Copy to control whether an existing destination file
 *   should be overwritten. When TRUE, the destination is overwritten only if
 *   its content differs from the source.
 * - Path: Used by Copy, Append, and Prepend operations to specify the
 *   local source file path.
 * - Content: Used by Append and Prepend operations to provide direct content
 *   to write instead of reading from a source file.
 * - Placeholder: Used by Copy to enable config-based placeholder
 *   resolution within the destination file.
 *
 * @see \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType::supportedKeys()
 */
enum OperationKey: string {
  case Overwrite = 'overwrite';
  case Path = 'path';
  case Content = 'content';
  case Placeholder = 'with-placeholder';

  /**
   * Returns the schema definition for this operation key.
   *
   * This schema definition can be used for validating the payload of file
   * operations to ensure that the correct keys and value types are used,
   * preventing misconfiguration and runtime errors.
   *
   * @return array
   *   An array defining the expected type and default value (if any)
   *   for this operation key.
   */
  public function getSchemaDefinition(): array {
    // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext
    return match ($this) {
      self::Overwrite, self::Placeholder => [
        'type' => 'bool',
      ],
      self::Path => [
        'type' => 'file',
      ],
      self::Content => [
        'type' => 'string',
      ],
    };
  }

}
