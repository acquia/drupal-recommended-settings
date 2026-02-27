<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Creates concrete FileOperationInterface instances from an OperationType.
 *
 * This class is the single place responsible for mapping an OperationType
 * enum case to its concrete operation class and constructing it.
 *
 * It intentionally uses an exhaustive match expression rather than dynamic
 * instantiation (e.g. new $class()) so that static analysis tools and IDEs
 * can resolve all possible return types, and so that adding a new operation
 * type produces a compile-time exhaustive match error rather than a silent
 * runtime failure.
 *
 * Adding a new operation type requires:
 * 1. Adding a case to OperationType.
 * 2. Creating the concrete operation class extending BaseOperation.
 * 3. Adding a corresponding arm to the match expression in create().
 */
final class OperationFactory implements OperationFactoryInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \UnhandledMatchError
   *   If the given OperationType has no corresponding match arm. This is a
   *   development-time guard to catch missing arms after adding a new case
   *   to OperationType without updating this factory.
   */
  public function create(OperationType $operation_type, string $destination, array|string $payload): FileOperationInterface {
    return match ($operation_type) {
      OperationType::Copy => new CopyOperation($destination, $payload),
      OperationType::Append => new AppendOperation($destination, $payload),
      OperationType::Prepend => new PrependOperation($destination, $payload),
    };
  }

}
