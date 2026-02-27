<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem;

/**
 * Manages filesystem permissions and directory creation with rollback support.
 *
 * This class is designed to be used in conjunction with a separate worker
 * that performs file copying or appending.
 */
final class StatefulDirectoryCreator {

  /**
   * Stack to track original permissions for rollback.
   *
   * Each entry is an associative array:
   * - 'perms': The original octal permissions before modification.
   * - 'created': TRUE if this path was newly created and should be removed on
   *   rollback, FALSE if it already existed and only had its permissions
   *   changed.
   */
  protected array $rollbackStack = [];

  /**
   * Constructs a StatefulDirectoryCreator.
   *
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface|null $fileSystem
   *   An optional filesystem implementation. Defaults to Filesystem if NULL.
   */
  public function __construct(private readonly FilesystemInterface $fileSystem = new Filesystem()) {}

  /**
   * Prepares a path by ensuring directories exist and permissions are writable.
   *
   * Case 1: If path exists and isn't writable, it is made writable.
   * Case 2: If path doesn't exist, it recurses up to find an existing parent,
   * ensures that parent is writable, and creates the missing tree.
   *
   * @param array $paths
   *   An array of file paths to prepare. Each path is processed independently,
   *   allowing for a mix of existing and non-existing paths.
   */
  public function preparePaths(array $paths): void {
    foreach ($paths as $path) {
      if (file_exists($path)) {
        $dir = dirname($path);
        if (!is_writable($dir)) {
          $this->trackState($dir);
          $this->fileSystem->chmod($dir, 0755);
        }
        if (!is_writable($path)) {
          $this->trackState($path);
          $this->fileSystem->chmod($path, 0755);
        }
        continue;
      }

      $dir = dirname($path);
      $this->ensureDirectoryRecursive($dir);
    }
  }

  /**
   * Recursively ensures that a directory exists and is writable.
   *
   * @param string $dir
   *   The directory path to ensure.
   */
  protected function ensureDirectoryRecursive(string $dir): void {
    if (is_dir($dir)) {
      if (!is_writable($dir)) {
        $this->trackState($dir);
        $this->fileSystem->chmod($dir, 0755);
      }
      return;
    }

    // Recurse up to find a parent that exists.
    $this->ensureDirectoryRecursive(dirname($dir));

    // Create the directory now that the parent is confirmed to exist and
    // be writable.
    if (!@mkdir($dir, 0755, TRUE)) {
      throw new \RuntimeException(
        sprintf("Failed to create directory '%s'.", $dir)
      );
    }

    // Track newly created directory so we can remove it on rollback.
    $this->trackState($dir, created: TRUE);
  }

  /**
   * Captures original octal permissions before modification.
   *
   * @param string $path
   *   The path to track.
   * @param bool $created
   *   Whether this path was newly created (TRUE) or already existed (FALSE).
   */
  protected function trackState(string $path, bool $created = FALSE): void {
    // Do not overwrite an already-recorded entry — the first recorded state
    // represents the true original, and we must not lose it on repeated calls.
    if (isset($this->rollbackStack[$path])) {
      return;
    }
    $this->rollbackStack[$path] = [
      'perms'   => file_exists($path) ? (fileperms($path) & 0777) : NULL,
      'created' => $created,
    ];
  }

  /**
   * Final step on success: Make paths and their immediate parents readonly.
   *
   * @param string|array<string> $paths
   *   A single file path or an array of file paths that were processed.
   */
  public function lockPath(string|array $paths): void {
    foreach ((array) $paths as $path) {
      if (file_exists($path)) {
        $this->fileSystem->chmod(dirname($path), 0555);
        $this->fileSystem->chmod($path, 0444);
      }
    }
  }

  /**
   * Restores the original permissions for any paths that were chmod'd.
   *
   * Unlike rollbackPath(), this method does NOT remove newly created
   * directories — it only undoes permission changes on paths that already
   * existed before preparePaths() was called. Use this after a successful
   * operation when you want to return the filesystem to its original
   * permission state without discarding created directories.
   *
   * This restores ALL tracked paths, including intermediate parent directories
   * that were made writable internally by ensureDirectoryRecursive() — not
   * just the file paths originally passed to preparePaths(). Callers do not
   * need to know which parent directories were modified.
   *
   * Paths that have no recorded state (i.e. were already writable and never
   * tracked) are silently skipped.
   */
  public function restorePath(): void {
    foreach ($this->rollbackStack as $path => $state) {
      // Only act on pre-existing paths whose permissions were modified.
      if ($state['created'] || $state['perms'] === NULL) {
        continue;
      }
      if (file_exists($path)) {
        $this->fileSystem->chmod($path, $state['perms']);
      }
    }
  }

  /**
   * Rollback changes made during preparation for the given paths.
   *
   * For paths that were newly created, the directory is removed. For paths
   * that already existed and only had their permissions changed, the original
   * permissions are restored.
   *
   * Only the paths explicitly provided are rolled back, allowing callers to
   * selectively undo changes to specific paths without affecting others that
   * are still tracked in the stack.
   *
   * Paths that were not tracked (i.e. never modified by preparePaths()) are
   * silently skipped.
   *
   * Paths are processed in reverse insertion order so child directories are
   * removed before their parents.
   *
   * @param string|array<string> $paths
   *   A single file path or an array of file paths to roll back.
   */
  public function rollbackPath(string|array $paths): void {
    // Filter the stack to only the requested paths, preserving insertion order
    // so that reversing it correctly removes children before parents.
    $entries = array_intersect_key(
      $this->rollbackStack,
      array_flip((array) $paths)
    );
    foreach (array_reverse($entries, preserve_keys: TRUE) as $path => $state) {
      if ($state['created']) {
        // Remove the directory that was newly created.
        if (is_dir($path)) {
          @rmdir($path);
        }
      }
      elseif ($state['perms'] !== NULL && file_exists($path)) {
        // Restore the original permissions.
        @chmod($path, $state['perms']);
      }
    }
  }

}
