<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Filesystem;
use Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface;

/**
 * Abstract base class for all file operations.
 *
 * Provides common constructor, accessors, and validation logic
 * shared across all file operations.
 */
abstract class BaseOperation implements FileOperationInterface {

  /**
   * The filesystem instance used for file operations.
   *
   * @var \Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface
   */
  private FilesystemInterface $filesystem;

  /**
   * Constructs a BaseOperation.
   *
   * @param string $destination
   *   The local destination file path.
   * @param array|string $payload
   *   The source file path (string) or array of operations to perform.
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface|null $filesystem
   *   An optional filesystem implementation. Defaults to Filesystem if NULL.
   */
  public function __construct(
    private readonly string $destination,
    private readonly array|string $payload,
    ?FilesystemInterface $filesystem = NULL,
  ) {
    $this->filesystem = $filesystem ?? new Filesystem();
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination(): string {
    return $this->destination;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayload(): array|string {
    return $this->payload;
  }

  /**
   * Gets the filesystem instance for file operations.
   *
   * @return \Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface
   *   The filesystem instance.
   */
  protected function getFilesystem(): FilesystemInterface {
    return $this->filesystem;
  }

  /**
   * Resolves the content to write based on the payload.
   *
   * If the payload is a string, it is treated as a source file path and
   * its content is read and returned as a single-element array. If the
   * payload is an array, each entry is processed to extract content either
   * from a source file or directly from a 'content' key.
   *
   * @param array|string $operation_payload
   *   The operation payload, either a string or an array of operations.
   *
   * @return array
   *   An array of content lines to write to the destination.
   *
   * @throws \InvalidArgumentException
   *   If payload is invalid or if any content entry is empty after resolution.
   * @throws \Exception
   *   If any source file does not exist or if any content entry is empty.
   */
  protected function resolveContent(array|string $operation_payload): array {
    if (is_string($operation_payload)) {
      return [
        $this->getFilesystem()->readFile($operation_payload),
      ];
    }
    $content_to_add = [];
    if (array_is_list($operation_payload)) {
      foreach ($operation_payload as $operation) {
        $content_to_add[] = $this->getContentFromPayload($operation);
      }
      return $content_to_add;
    }
    $content_to_add[] = $this->getContentFromPayload($operation_payload);
    return $content_to_add;
  }

  /**
   * Gets the content to write based on the payload.
   *
   * @param array|string $operation_payload
   *   The operation payload, either a string or an array of operations.
   *
   * @return string
   *   The content to write to the destination.
   *
   * @throws \InvalidArgumentException
   *   If payload is invalid or if the content entry is empty after resolution.
   * @throws \Exception
   *   If the source file does not exist or if the content entry is empty.
   */
  private function getContentFromPayload(array|string $operation_payload): string {
    if (array_key_exists(OperationKey::Path->value, $operation_payload)) {
      return $this->getFilesystem()->readFile($operation_payload[OperationKey::Path->value]);
    }
    elseif (array_key_exists(OperationKey::Content->value, $operation_payload)) {
      return $operation_payload[OperationKey::Content->value];
    }
    throw new \Exception("Invalid operation payload: must contain either 'path' or 'content' key.");
  }

  /**
   * Executes the file operation and returns its outcome.
   *
   * Always calls validate() first. If validation or the operation itself
   * throws, a Failed result is returned rather than propagating the exception,
   * so callers can handle all outcomes uniformly via the result object.
   *
   * Subclasses cannot override this method and must implement
   * executeOperation() instead. This ensures validation is never bypassed.
   *
   * @return \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationResult
   *   The outcome of the operation.
   */
  final public function execute(): OperationResult {
    try {
      $this->validate();
      return $this->executeOperation();
    }
    catch (\Throwable $e) {
      return new OperationResult(
        OperationStatus::Failed,
        $e->getMessage(),
        $this,
      );
    }
  }

  /**
   * Performs the actual file system operation and returns its result.
   *
   * Subclasses implement this method instead of execute(). At this point,
   * validation has already been guaranteed by execute(). Implementations
   * should return OperationResult object with either OperationStatus::Success,
   * OperationStatus::Skipped, or OperationStatus::Failed as appropriate.
   *
   * @return \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationResult
   *   The outcome of the operation.
   */
  abstract protected function executeOperation(): OperationResult;

  /**
   * {@inheritdoc}
   */
  public function validate(): void {}

}
