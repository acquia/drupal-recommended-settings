<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem;

/**
 * Contract for filesystem interactions to decouple from specific frameworks.
 */
interface FilesystemInterface {

  /**
   * Copies a file from source to destination.
   *
   * @param string $source
   *   The file path of the source file to copy.
   * @param string $destination
   *   The file path of the destination where the file should be copied to.
   * @param bool $overwrite
   *   Whether to overwrite the destination if it already exists.
   *   Defaults to FALSE.
   *
   * @throws \Exception
   *   If the copy operation fails.
   */
  public function copy(string $source, string $destination, bool $overwrite = FALSE): void;

  /**
   * Appends content to an existing file.
   *
   * @param string $filename
   *   The file path of the file to append to.
   * @param string $content
   *   The content to append.
   *
   * @throws \Exception
   *   If the append operation fails.
   */
  public function append(string $filename, string $content): void;

  /**
   * Prepends content to an existing file.
   *
   * @param string $filename
   *   The file path of the file to prepend to.
   * @param string $content
   *   The content to prepend.
   *
   * @throws \Exception
   *   If the prepend operation fails.
   */
  public function prepend(string $filename, string $content): void;

  /**
   * Checks if a file exists at the given path.
   *
   * @param string|iterable $files
   *   A single file path as a string or an iterable of file paths to check
   *   for existence.
   *
   * @return bool
   *   TRUE if the file exists, FALSE otherwise.
   */
  public function exists(string|iterable $files): bool;

  /**
   * Reads the content of a file.
   *
   * @param string $filename
   *   The file path of the file to read.
   *
   * @return string
   *   The content of the file.
   *
   * @throws \Exception
   *   If the read operation fails.
   */
  public function readFile(string $filename): string;

  /**
   * Determines if a given path is local to the filesystem.
   *
   * @param string $path
   *   The file path to check.
   *
   * @return bool
   *   TRUE if the path is local, FALSE otherwise.
   */
  public function isPathLocal(string $path): bool;

  /**
   * Writes content to a file, creating it if it does not exist.
   *
   * @param string $filename
   *   The file path of the file to write to.
   * @param string $content
   *   The content to write to the file.
   *
   * @throws \Exception
   *   If the write operation fails.
   */
  public function writeToFile(string $filename, string $content): void;

  /**
   * Change mode for an array of files or directories.
   *
   * @param string|iterable $files
   *   A single path as a string or an iterable of paths to change the.
   * @param int $mode
   *   The new mode (octal)
   * @param int $umask
   *   The mode mask (octal)
   * @param bool $recursive
   *   Whether change the mod recursively or not.
   */
  public function chmod(string|iterable $files, int $mode, int $umask = 0o000, bool $recursive = FALSE): void;

}
