<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException;
use Acquia\Drupal\RecommendedSettings\Filesystem\Filesystem;
use Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface;
use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigInterface;

/**
 * FileOperationHandler handles and validates file operation configurations.
 *
 * Transforms raw file operation mappings into normalized FileOperationInterface
 * objects, ensuring all operation types and destinations are valid before
 * any file system changes are made.
 */
final class FileOperationHandler {

  /**
   * Constructs a FileOperationHandler.
   *
   * @param \Consolidation\Config\ConfigInterface $config
   *   The configuration interface for accessing global settings.
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationFactoryInterface $operationFactory
   *   The factory resolve operation types and create operation instances.
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface $fileSystem
   *   The filesystem interface for validating file paths and existence.
   */
  public function __construct(
    private readonly ConfigInterface $config,
    private readonly OperationFactoryInterface $operationFactory = new OperationFactory(),
    private readonly FilesystemInterface $fileSystem = new Filesystem(),
  ) {
  }

  /**
   * Creates an array of raw operation mappings into operation objects.
   *
   * Each key is a destination path; each value is either:
   * - FALSE (skip the file)
   * - A string (source path → implicit copy)
   * - An associative array of operation_type => payload entries.
   *
   * @param array $operation_mappings
   *   Raw mapping of destination => operation config.
   *
   * @return array<\Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationInterface>
   *   Array of normalized file operation objects.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException
   *   If any destination is invalid, if any operation type is unrecognized, or
   *   if destructive operations are added after others.
   */
  public function handle(array $operation_mappings): array {
    $normalized_operations = [];
    foreach ($operation_mappings as $destination => $operation_config) {
      if ($this->isSkippedOperation($operation_config, $destination)) {
        continue;
      }
      $this->validateDestination($destination);
      array_push(
        $normalized_operations,
        ...$this->normalizeSingleOperation($destination, $operation_config)
      );
    }
    return $normalized_operations;
  }

  /**
   * Determines if the operation should be skipped.
   *
   * A boolean FALSE value explicitly signals that the destination file
   * should be skipped. A boolean TRUE is invalid and will trigger an assertion.
   *
   * @param mixed $operation_config
   *   The raw operation config value for this destination.
   * @param string $destination
   *   The destination file path, used in the assertion message.
   *
   * @return bool
   *   TRUE if the operation should be skipped, FALSE otherwise.
   *
   * @throws \AssertionError
   *   If the boolean value is TRUE instead of FALSE.
   */
  protected function isSkippedOperation(mixed $operation_config, string $destination): bool {
    if (is_bool($operation_config)) {
      assert(
        $operation_config === FALSE,
        "Boolean source value must be `false` to skip a file. Found `true` for destination '$destination'."
      );
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validates that the destination is a non-empty string.
   *
   * @param mixed $destination
   *   The destination value to validate.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException
   *   If the destination is not a string.
   */
  protected function validateDestination(mixed $destination): void {
    if (!is_string($destination) || empty($destination) || !$this->fileSystem->isPathLocal($destination)) {
      throw new InvalidMappingException(
        "Invalid destination file path. It must be a non-empty string, got " . get_debug_type($destination)
      );
    }
  }

  /**
   * Normalizes the operation config for a single destination into objects.
   *
   * @param string $destination
   *   The local destination file path.
   * @param mixed $operation_config
   *   Either a string (source file path) or an associative array
   *   of operation_type => payload entries.
   *
   * @return array<\Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationInterface>
   *   Array of file operation objects for this destination.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException
   */
  protected function normalizeSingleOperation(string $destination, mixed $operation_config): array {
    $normalized = [];
    if (is_string($operation_config)) {
      assert(
        !empty($operation_config) && $this->fileSystem->exists($operation_config) && $this->fileSystem->isPathLocal($operation_config),
        sprintf(
          "Source path must be a non-empty string representing a local file path for destination '%s'.",
          $destination,
        ),
      );
      // A bare string is treated as an implicit copy: source => destination.
      $operation = $this->operationFactory->create(OperationType::Copy, $destination, $operation_config);
      if ($operation instanceof ConfigAwareInterface) {
        $operation->setConfig($this->config);
      }
      $normalized[] = $operation;
    }
    elseif (is_array($operation_config)) {
      $seen_types = [];
      foreach ($operation_config as $operation_type => $payload) {
        $resolved_type = $this->resolveOperationType($operation_type);
        $this->validateOperationOrder($seen_types, $resolved_type);
        $this->validateOperationKeys($resolved_type, $destination, $payload);
        $operation = $this->operationFactory->create($resolved_type, $destination, $payload);
        if ($operation instanceof ConfigAwareInterface) {
          $operation->setConfig($this->config);
        }
        $normalized[] = $operation;
        $seen_types[] = $resolved_type;
      }
    }
    return $normalized;
  }

  /**
   * Validates that the payload for the given operation type has supported keys.
   *
   * For Append and Prepend operations, the payload can be either a string or
   * an array of operation entries. For other operations, the payload must be
   * an associative array of operation_keys => payload.
   *
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType $operation_type
   *   The type of the operation being validated.
   * @param string $destination
   *   The destination file path, used in the exception message.
   * @param string|array $payload
   *   The payload to validate, which can be a string or an array depending
   *   on the operation type.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException
   *   If the payload structure is invalid or if it contains unsupported keys
   *   for the given operation type.
   */
  private function validateOperationKeys(OperationType $operation_type, string $destination, string | array $payload): void {
    if (is_string($payload)) {
      return;
    }
    if ($operation_type === OperationType::Append || $operation_type === OperationType::Prepend) {
      if (array_is_list($payload)) {
        foreach ($payload as $operation) {
          assert(
            is_array($operation) && !empty($operation),
            sprintf(
              "Each entry in the payload for '%s' operation must be an array of operation_keys => payload. Found '%s' for destination '%s'.",
              $operation_type->value,
              get_debug_type($operation),
              $destination
            )
          );
          $this->validateKeys($operation_type, $destination, $operation);
        }
        return;
      }
    }
    assert(
      is_array($payload) && !empty($payload),
      sprintf(
        "Payload for '%s' operations must be a non-empty array. Found '%s' for destination '%s'.",
        $operation_type->value,
        get_debug_type($payload),
        $destination,
      ),
    );
    $this->validateKeys($operation_type, $destination, $payload);
  }

  /**
   * Validates that the payload keys for the given operation type are supported.
   *
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType $operation_type
   *   The type of the operation being validated.
   * @param string $destination
   *   The destination file path, used in the exception message.
   * @param array $payload
   *   The payload to validate, which must be an array at this point.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException
   *   If the payload contains unsupported keys for the given operation type.
   */
  private function validateKeys(OperationType $operation_type, string $destination, array $payload): void {
    $operation_keys = array_keys($payload);
    $supported_keys = $operation_type->supportedKeys();
    foreach ($operation_keys as $operation_key) {
      assert(
        is_string($operation_key),
         sprintf(
           "Each operation key must be a string. Found '%s' for '%s' operation destination '%s'.",
           get_debug_type($operation_key),
           $operation_type->value,
           $destination,
         ),
      );
      if (!in_array($operation_key, $supported_keys, TRUE)) {
        throw new InvalidMappingException(
          sprintf(
            "Unsupported operation key '%s' for '%s' operation on destination '%s'. Supported keys are: %s.",
            $operation_key,
            $operation_type->value,
            $destination,
            implode(', ', $supported_keys)
          )
        );
      }
      $schema_definition = OperationKey::from($operation_key)->getSchemaDefinition();
      $value_type = $schema_definition['type'] ?? NULL;
      assert($value_type !== NULL, "Schema definition for operation key '$operation_key' must define a 'type'.");
      $value = $payload[$operation_key];
      if ($value_type === 'file') {
        assert(
          is_string($value) && !empty($value) && $this->fileSystem->exists($value) && $this->fileSystem->isPathLocal($value),
          sprintf(
            "The value for operation key '%s' must be a non-empty string representing a local file path. Found '%s' for destination '%s'.",
            $operation_key,
            get_debug_type($value),
            $destination,
          )
        );
      }
      elseif ($value_type === 'string') {
        assert(
          is_string($value) && !empty($value),
          sprintf(
            "The value for operation key '%s' must be a non-empty string. Found '%s' for destination '%s'.",
            $operation_key,
            get_debug_type($value),
            $destination,
          )
        );
      }
      elseif ($value_type === 'bool') {
        assert(
          is_bool($value),
          sprintf(
            "The value for operation key '%s' must be a boolean. Found '%s' for destination '%s'.",
            $operation_key,
            get_debug_type($value),
            $destination,
          )
        );
      }
    }
  }

  /**
   * Resolves a raw string operation type key into an OperationType enum case.
   *
   * @param string $operation_type
   *   The raw operation type string (e.g. 'copy', 'append').
   *
   * @return \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType
   *   The resolved OperationType enum case.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException
   *   If the string does not match any known OperationType.
   */
  protected function resolveOperationType(string $operation_type): OperationType {
    try {
      return OperationType::from($operation_type);
    }
    catch (\ValueError $e) {
      throw new InvalidMappingException(
        sprintf(
          "Invalid operation type '%s'. Allowed types are: %s.",
          $operation_type,
          implode(', ', array_column(OperationType::cases(), 'value'))
        )
      );
    }
  }

  /**
   * Validates that destructive operations are not added after others.
   *
   * @param OperationType[] $seen_types
   *   The list of OperationType cases already registered for this destination.
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType $current_type
   *   The current operation type being validated.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException
   *   If a destructive operation is added after others.
   */
  protected function validateOperationOrder(array $seen_types, OperationType $current_type): void {
    if (empty($seen_types)) {
      return;
    }
    // A destructive operation (e.g. copy) cannot come after any other.
    if ($current_type->isDestructive()) {
      throw new InvalidMappingException(
        sprintf(
          "'%s' is a destructive operation and cannot follow other operations for the same destination. Found after: %s.",
          $current_type->value,
          implode(', ', array_map(fn(OperationType $t) => $t->value, $seen_types))
        )
      );
    }
  }

}
