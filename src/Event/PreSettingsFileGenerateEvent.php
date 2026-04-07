<?php

namespace Acquia\Drupal\RecommendedSettings\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event before generating the settings file to modify operations.
 */
final class PreSettingsFileGenerateEvent extends Event {

  // Event name for dispatching and listening.
  public const NAME = 'pre-settings-file-generate';

  /**
   * Constructs the event with the given file operations.
   *
   * @param array $operations
   *   An array of file operations to be performed during settings file
   *   generation.
   */
  public function __construct(private array $operations) {}

  /**
   * Sets the file operations to be performed during settings file generation.
   *
   * @param array $operations
   *   An array of file operations.
   */
  public function setOperations(array $operations): void {
    $this->operations = $operations;
  }

  /**
   * Gets the file operations to be performed during settings file generation.
   *
   * @return array
   *   An array of file operations.
   */
  public function getOperations(): array {
    return $this->operations;
  }

}
