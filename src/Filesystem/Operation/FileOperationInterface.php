<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Contract for a fully instantiated file operation command.
 *
 * Implementations must execute the file operation and return an OperationResult
 * describing the outcome — success, skipped, or failed — so callers can react
 * without relying on exceptions for flow control.
 */
interface FileOperationInterface {

  /**
   * Executes the file operation and returns its outcome.
   *
   * Implementations must internally call validate() before performing
   * any file system changes. The result communicates what actually happened:
   * - OperationStatus::Success — the file was written/copied/modified.
   * - OperationStatus::Skipped — the operation was intentionally not performed
   *   (e.g. destination already up to date).
   * - OperationStatus::Failed — an unexpected error occurred.
   *
   * @return \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationResult
   *   The result of the operation.
   */
  public function execute(): OperationResult;

  /**
   * Returns the destination file path.
   *
   * @return string
   *   The local destination file path.
   */
  public function getDestination(): string;

  /**
   * Returns the payload for the operation.
   *
   * The payload is either a source file path (string) or an array of
   * sub-operations to be executed on the destination.
   *
   * @return array|string
   *   The operation payload.
   */
  public function getPayload(): array|string;

  /**
   * Validates the operation parameters before execution.
   *
   * Can be called independently for pre-flight checks.
   * Always called automatically by execute() before any file system changes.
   *
   * @throws \InvalidArgumentException
   *   If the operation parameters are invalid.
   */
  public function validate(): void;

}
