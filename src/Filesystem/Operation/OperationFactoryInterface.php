<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Defines the interface for file operation factories.
 *
 * Implementations are responsible for creating concrete file operation
 * instances based on the provided destination and payload.
 */
interface OperationFactoryInterface {

  /**
   * Creates a file operation instance for given type, destination and payload.
   *
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType $operation_type
   *   The operation type enum case.
   * @param string $destination
   *   The local destination file path.
   * @param array|string $payload
   *   The source file path (string) or content lines (array).
   *
   * @return \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationInterface
   *   The concrete file operation instance.
   */
  public function create(OperationType $operation_type, string $destination, array|string $payload): FileOperationInterface;

}
