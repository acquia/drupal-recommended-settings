<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Represents the result of executing a file operation.
 *
 * Returned by every BaseOperation::execute() call. Callers can inspect
 * the status to decide what to do next — log a skip, report a failure,
 * or confirm a success — without catching exceptions for flow control.
 *
 * Example usage:
 * @code
 *   $result = $operation->execute();
 *   match ($result->getStatus()) {
 *     OperationStatus::Success => $logger->info($result->getMessage()),
 *     OperationStatus::Skipped => $logger->debug($result->getMessage()),
 *     OperationStatus::Failed  => $logger->error($result->getMessage()),
 *   };
 * @endcode
 */
final class OperationResult {

  /**
   * Constructs an OperationResult.
   *
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationStatus $status
   *   The outcome status of the operation.
   * @param string $message
   *   A human-readable message describing what happened or why it was skipped
   *   or failed.
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationInterface $operation
   *   The operation instance that produced this result, for context.
   */
  public function __construct(
    private readonly OperationStatus $status,
    private readonly string $message,
    private readonly FileOperationInterface $operation,
  ) {}

  /**
   * Returns the outcome status of the operation.
   *
   * @return \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationStatus
   *   The status enum case.
   */
  public function getStatus(): OperationStatus {
    return $this->status;
  }

  /**
   * Returns the human-readable message describing the result.
   *
   * @return string
   *   The result message.
   */
  public function getMessage(): string {
    return $this->message;
  }

  /**
   * Returns the operation instance that produced this result.
   *
   * @return \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationInterface
   *   The operation.
   */
  public function getOperation(): FileOperationInterface {
    return $this->operation;
  }

  /**
   * Returns TRUE if the operation was successful.
   *
   * @return bool
   *   TRUE for OperationStatus::Success.
   */
  public function isSuccess(): bool {
    return $this->status === OperationStatus::Success;
  }

  /**
   * Returns TRUE if the operation was skipped.
   *
   * @return bool
   *   TRUE for OperationStatus::Skipped.
   */
  public function isSkipped(): bool {
    return $this->status === OperationStatus::Skipped;
  }

  /**
   * Returns TRUE if the operation failed.
   *
   * @return bool
   *   TRUE for OperationStatus::Failed.
   */
  public function isFailed(): bool {
    return $this->status === OperationStatus::Failed;
  }

}
