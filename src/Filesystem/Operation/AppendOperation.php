<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Appends content to the destination file.
 *
 * The payload can be:
 * - A string representing the local path to a source file whose content
 *   will be appended to the destination.
 * - An array of operations to perform, where each operation is an associative
 *   array with either:
 *   - 'path': A local path to a source file whose content will be appended.
 *   - 'content': A string of content to append directly.
 */
final class AppendOperation extends BaseOperation {

  /**
   * {@inheritdoc}
   *
   * Skips if the content to append is already present in the destination.
   */
  protected function executeOperation(): OperationResult {
    $destination = $this->getDestination();
    $contents = $this->resolveContent($this->getPayload());
    $existing = $this->getFilesystem()->readFile($destination);
    $content_added = FALSE;
    foreach ($contents as $content) {
      // Trim the content to compare to avoid false negatives due to trailing
      // newlines.
      $content_to_compare = trim($content, PHP_EOL);
      // Skip if the content is already present in the destination.
      if (str_contains($existing, $content_to_compare)) {
        continue;
      }
      $content_added = TRUE;
      $this->getFilesystem()->append($destination, $content);
    }
    if (!$content_added) {
      return new OperationResult(
        OperationStatus::Skipped,
        sprintf('Content already present in — %s', $destination),
        $this,
      );
    }
    return new OperationResult(
      OperationStatus::Success,
      sprintf('Appended content to %s', $destination),
      $this,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): void {
    parent::validate();
    $destination = $this->getDestination();
    // Not checking for not empty here as FileOperationHandler already checks
    // for that.
    // @see \Acquia\Drupal\RecommendedSettings\Filesystem\FileOperationHandler::validateDestination()
    assert(
      $this->getFilesystem()->exists($destination) && is_readable($destination),
      "Destination file must exist and be readable: $destination"
    );
  }

}
