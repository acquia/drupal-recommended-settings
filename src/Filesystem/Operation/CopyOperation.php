<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Config\ConfigResolver;
use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigAwareTrait;

/**
 * Copies a source file to the destination path.
 *
 * The payload can be:
 * - A string representing the local path to the source file. The
 *   destination will only be written if it does not already exist.
 * - An associative array with the following keys:
 *   - 'path': The local source file path (required).
 *   - 'overwrite': Optional boolean. When FALSE (default), the copy is
 *     skipped if the destination already exists. When TRUE, the destination
 *     is overwritten only when its content differs from the source.
 */
final class CopyOperation extends BaseOperation implements ConfigAwareInterface {
  use ConfigAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function executeOperation(): OperationResult {
    $destination = $this->getDestination();
    $payload = $this->getPayload();

    // Parse payload for source, overwrite, and placeholder flags.
    [$source, $overwrite, $placeholder] = $this->parsePayload($payload);
    $destination_exists = $this->getFilesystem()->exists($destination);

    // If placeholder resolution is needed, resolve content now.
    $resolved_content = NULL;
    if ($placeholder) {
      $resolved_content = $this->resolvePlaceholderContent($source);
    }

    // --- Non-overwrite mode ---
    if (!$overwrite) {
      if ($destination_exists) {
        return new OperationResult(
          OperationStatus::Skipped,
          sprintf('Destination already exists - %s', $destination),
          $this,
        );
      }
      if ($placeholder) {
        $this->copyWithPlaceholderResolution($source, $destination, $resolved_content);
        return new OperationResult(
          OperationStatus::Success,
          sprintf('Copied with placeholder resolution %s → %s', $source, $destination),
          $this,
        );
      }
      $this->getFilesystem()->copy($source, $destination);
      return new OperationResult(
        OperationStatus::Success,
        sprintf('Copied %s → %s', $source, $destination),
        $this,
      );
    }

    // --- Overwrite mode ---
    if ($destination_exists) {
      $source_content = $this->getFilesystem()->readFile($source);
      $destination_content = $this->getFilesystem()->readFile($destination);
      if ($placeholder && str_contains($destination_content, $resolved_content)) {
        return new OperationResult(
          OperationStatus::Skipped,
          sprintf('Resolved content contains the destination content - %s', $source),
          $this,
        );
      }
      if (!$placeholder && $source_content === $destination_content) {
        return new OperationResult(
          OperationStatus::Skipped,
          sprintf('Destination already matches source - %s', $destination),
          $this,
        );
      }
    }
    // If we reach here, we need to overwrite.
    if ($placeholder) {
      $this->copyWithPlaceholderResolution($source, $destination, $resolved_content);
      return new OperationResult(
        OperationStatus::Success,
        sprintf('Copied with placeholder resolution (overwrite) %s → %s', $source, $destination),
        $this,
      );
    }
    $this->getFilesystem()->copy($source, $destination, TRUE);
    return new OperationResult(
      OperationStatus::Success,
      sprintf('Copied (overwrite) %s → %s', $source, $destination),
      $this,
    );
  }

  /**
   * Parse the payload for source, overwrite, and placeholder flags.
   *
   * @param string|array $payload
   *   The operation payload.
   *
   * @return array{string, bool, bool}
   *   Array with source, overwrite, placeholder.
   */
  private function parsePayload(string|array $payload): array {
    $source = is_string($payload) ? $payload : $payload[OperationKey::Path->value];
    $overwrite = is_array($payload) && !empty($payload[OperationKey::Overwrite->value]);
    $placeholder = is_array($payload) && !empty($payload[OperationKey::Placeholder->value]);
    return [$source, $overwrite, $placeholder];
  }

  /**
   * Resolve the content of the source file with placeholder replacement.
   *
   * @param string $source
   *   The source file path.
   *
   * @return string
   *   The resolved file content.
   */
  private function resolvePlaceholderContent(string $source): string {
    $config = $this->getConfig();
    $config_resolver = new ConfigResolver($config);
    $source_content = $this->getFilesystem()->readFile($source);
    return $config_resolver->resolve($source_content);
  }

  /**
   * Copy the source file to the destination with resolved content.
   *
   * @param string $source
   *   The source file path.
   * @param string $destination
   *   The destination file path.
   * @param string $resolved_content
   *   The resolved content to write to the destination.
   */
  private function copyWithPlaceholderResolution(string $source, string $destination, string $resolved_content): void {
    $this->getFilesystem()->writeToFile($destination, $resolved_content);
    // This is done to ensure destination file preserve the executable
    // permissions and file modification time as the source file; when
    // placeholder resolution is enabled, since the copy method is not used
    // in this case.
    chmod($destination, fileperms($destination) | (fileperms($source) & 0o111));
    touch($destination, filemtime($source));
  }

}
