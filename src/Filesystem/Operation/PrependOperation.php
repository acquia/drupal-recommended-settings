<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem\Operation;

/**
 * Prepends content to the beginning of the destination file.
 *
 * The payload can be:
 * - A string representing the local path to a source file whose content
 *   will be prepended to the destination.
 * - An array of operations to perform, where each operation is an associative
 *   array with either:
 *    - 'path': A local source file path whose content will be prepended.
 *    - 'content': A string of content to prepended directly.
 */
final class PrependOperation extends BaseOperation {

  /**
   * {@inheritdoc}
   *
   * Skips if the content to prepend is already present in the destination.
   */
  protected function executeOperation(): OperationResult {
    $destination = $this->getDestination();
    $contents = $this->resolveContent($this->getPayload());
    $existing = $this->getFilesystem()->readFile($destination);
    // Collect only the content not already present in the destination,
    // preserving the declared order for a single prepend call.
    $new_contents = [];
    foreach ($contents as $content) {
      // Trim the content to compare to avoid false negatives due to trailing
      // newlines.
      $content_to_compare = trim($content, PHP_EOL);
      // Skip if the content is already present in the destination.
      if (str_contains($existing, $content_to_compare)) {
        continue;
      }
      $new_contents[] = $content;
    }
    if (empty($new_contents)) {
      return new OperationResult(
        OperationStatus::Skipped,
        sprintf('Content already present in — %s', $destination),
        $this,
      );
    }
    // Prepend all new content in a single write to preserve correct ordering.
    $this->getFilesystem()->prepend($destination, implode('', $new_contents));
    return new OperationResult(
      OperationStatus::Success,
      sprintf('Prepended content to %s', $destination),
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
