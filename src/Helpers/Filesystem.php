<?php

namespace Acquia\Drupal\RecommendedSettings\Helpers;

/**
 * Helper class to perform file operations.
 */
class Filesystem {

  /**
   * Return all files for given directory.
   *
   * @param string $directory
   *   Given directory to load all files.
   *
   * @return string[]
   *   Returns an array of files.
   */
  public function loadFilesFromDirectory(string $directory): array {
    // Check if the directory exists.
    if (!is_dir($directory)) {
      return [];
    }

    // Get the list of files in the directory.
    $files = scandir($directory);

    // Remove . and .. from the list.
    $files = array_diff($files, ['.', '..']);

    // Initialize an array to store the file paths.
    $filePaths = [];

    // Iterate through the file list and add file paths to the array.
    foreach ($files as $file) {
      $filePath = $directory . DIRECTORY_SEPARATOR . $file;
      if (is_file($filePath)) {
        $filePaths[] = $filePath;
      }
    }

    return $filePaths;
  }

  /**
   * Check and create directory (if it doesn't exist).
   *
   * @param string $directory
   *   Given directory to check & create.
   */
  public function ensureDirectoryExists(string $directory): void {
    if (!is_dir($directory)) {
      if (file_exists($directory)) {
        throw new \RuntimeException(
          sprintf("The file at path '%s' already exists.", $directory)
        );
      }
      if (!@mkdir($directory, 0777, TRUE)) {
        throw new \RuntimeException(
          sprintf("The directory '%s' does not exist and could not be created.", $directory)
        );
      }
    }
    else {
      if (!is_writable($directory)) {
        throw new \RuntimeException(
          sprintf("The directory '%s' exist and is not writable.", $directory)
        );
      }
    }
  }

  /**
   * Copy all files from source to destination.
   *
   * @param string $sourceDir
   *   Given source directory.
   * @param string $destDir
   *   Given destination directory.
   * @param bool $overwrite
   *   Flag to determine if files to overwrite.
   */
  public function copyFiles(string $sourceDir, string $destDir, bool $overwrite = FALSE): bool {
    $this->ensureDirectoryExists($destDir);
    $sourceFiles = $this->loadFilesFromDirectory($sourceDir);
    foreach ($sourceFiles as $sourceFile) {
      $sourceFileName = basename($sourceFile);
      $destFile = $destDir . DIRECTORY_SEPARATOR . $sourceFileName;
      $this->copyFile($sourceFile, $destFile, $overwrite);
    }

    return TRUE;
  }

  /**
   * Copy file from source to destination.
   *
   * @param string $source
   *   Given source file.
   * @param string $destination
   *   Given destination file.
   * @param bool $overwrite
   *   Flag to determine if files to overwrite.
   */
  public function copyFile(string $source, string $destination, bool $overwrite = FALSE): bool {
    // Check if the file should be overwritten.
    if (!$overwrite && is_file($destination)) {
      // Skip copying as it should not be overwritten.
      return TRUE;
    }

    // Copy the file from source to destination.
    if (is_file($source)) {
      if (!@copy($source, $destination)) {
        throw new \RuntimeException("Failed to copy file: $destination.");
      }
    }
    else {
      throw new \RuntimeException(sprintf("Source file `%s` doesn't exist.", $source));
    }
    return TRUE;
  }

  /**
   * Append the contents to the file.
   *
   * @param string $filePath
   *   Given filepath to append content.
   * @param string $content
   *   Content to append on file.
   */
  public function appendToFile(string $filePath, string $content): bool {
    $fileHandle = @fopen($filePath, 'a');
    if (!$fileHandle) {
      throw new \RuntimeException("Unable to open the file for writing: " . $filePath);
    }

    // Append the content to the file.
    $status = fwrite($fileHandle, $content);

    fclose($fileHandle);
    return $status;
  }

  /**
   * Writes the content to the file.
   *
   * @param string $filePath
   *   Given filepath to write content.
   * @param string $content
   *   Content to write on file.
   */
  public function dumpFile(string $filePath, string $content): bool {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
      $this->ensureDirectoryExists($dir);
    }
    $fileHandle = @fopen($filePath, 'w');
    if (!$fileHandle) {
      throw new \RuntimeException("Unable to open the file for writing: " . $filePath);
    }

    return fwrite($fileHandle, $content);
  }

  /**
   * Checks & convert to iterable.
   *
   * @param string|iterable<string> $files
   *   A file path or an array of files.
   *
   * @return iterable<string>
   *   Returns an array of files or directories.
   */
  private function toIterable(string|iterable $files): iterable {
    return is_iterable($files) ? $files : [$files];
  }

  /**
   * Change permissions for directory, file or an array of files, directories.
   *
   * @param string|iterable<string> $files
   *   A file path or an array of files.
   * @param int $mode
   *   Given file mode.
   * @param int $umask
   *   Given umask for file.
   */
  public function chmod(string|iterable $files, int $mode, int $umask = 0000): bool {
    foreach ($this->toIterable($files) as $file) {
      if (file_exists($file) && !@chmod($file, $mode & ~$umask)) {
        $file_or_directory = is_dir($file) ? "directory" : "file";
        throw new \RuntimeException(
          sprintf("Failed to change permissions for %s: '%s'.", $file_or_directory, $file)
        );
      }
    }
    return TRUE;
  }

}
