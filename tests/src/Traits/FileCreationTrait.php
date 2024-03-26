<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Traits;

/**
 * Test traits to perform file operations.
 */
trait FileCreationTrait {

  /**
   * Creates a file and write/append contents to the file.
   *
   * @param string $file_name
   *   Given file name.
   * @param string $content
   *   Content to write.
   * @param string $mode
   *   Given file mode. Ex: 'w' for write, 'a' for append etc.
   */
  protected function createFile(string $file_name, string $content, string $mode = "w"): void {
    $config = fopen($file_name, $mode) or die("Unable to open file!");
    fwrite($config, $content);
    fclose($config);
  }

}
