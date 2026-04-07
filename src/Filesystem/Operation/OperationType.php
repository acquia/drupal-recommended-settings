<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Represents the built-in file operation types.
 *
 * Each case declares its own destructivity via isDestructive(), meaning
 * FileOperationHandler does not need to maintain a hardcoded list of
 * destructive types.
 *
 * Adding a new built-in operation type requires:
 * 1. Adding a new case here with the correct isDestructive() return value.
 * 2. Creating the concrete operation class extending BaseOperation.
 * 3. Adding a corresponding match in OperationFactory::create().
 *
 * @see \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationHandler
 * @see \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationFactory
 * @see \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\BaseOperation
 */
enum OperationType: string {

  case Copy = 'copy';
  case Append = 'append';
  case Prepend = 'prepend';

  /**
   * Returns whether this operation type is destructive.
   *
   * A destructive operation overwrites the destination entirely (e.g. copy)
   * and therefore cannot be added after any other operation for the same
   * destination. Non-destructive operations (append, prepend) modify
   * only specific parts of the file and can be combined for the same
   * destination.
   *
   * @return bool
   *   TRUE if this operation is destructive, FALSE otherwise.
   */
  public function isDestructive(): bool {
    // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext
    return match ($this) {
      self::Copy => TRUE,
      self::Append, self::Prepend => FALSE,
    };
  }

  /**
   * Returns the supported payload keys for this operation type.
   *
   * Copy supports the 'path', 'overwrite' and 'with-placeholder' keys. Append
   * and Prepend both support the 'path' and 'content' keys.
   *
   * This allows payload validation to ensure only allowed keys are used per
   * operation type, preventing misconfiguration and runtime errors.
   *
   * @return string[]
   *   An array of supported OperationKey values for this operation type.
   */
  public function supportedKeys(): array {
    // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext
    return match ($this) {
      self::Copy => [
        OperationKey::Path->value,
        OperationKey::Overwrite->value,
        OperationKey::Placeholder->value,
      ],
      self::Append, self::Prepend => [
        OperationKey::Path->value,
        OperationKey::Content->value,
      ],
    };
  }

}
