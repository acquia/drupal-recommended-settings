<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Filesystem;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Adapter for Symfony's Filesystem component to implement our interface.
 *
 * This adapter allows us to use Symfony's robust filesystem utilities while
 * adhering to our defined contract, ensuring that our code remains flexible and
 * decoupled from specific implementations.
 */
final class Filesystem implements FilesystemInterface {

  /**
   * The Symfony Filesystem instance used for file operations.
   *
   * @var \Symfony\Component\Filesystem\Filesystem|null
   *   The filesystem instance, initialized lazily when needed.
   */
  private ?SymfonyFilesystem $filesystem = NULL;

  /**
   * {@inheritdoc}
   */
  public function copy(string $source, string $destination, bool $overwrite = FALSE): void {
    $this->getFilesystem()->copy($source, $destination, $overwrite);
  }

  /**
   * {@inheritdoc}
   */
  public function append(string $filename, string $content): void {
    $this->getFilesystem()->appendToFile($filename, $content);
  }

  /**
   * {@inheritdoc}
   */
  public function prepend(string $filename, string $content): void {
    $existing_content = $this->readFile($filename);
    $this->getFilesystem()->dumpFile($filename, $content . $existing_content);
  }

  /**
   * {@inheritdoc}
   */
  public function exists(string | iterable $files): bool {
    return $this->getFilesystem()->exists($files);
  }

  /**
   * {@inheritdoc}
   */
  public function readFile(string $filename): string {
    $file_system = $this->getFilesystem();
    // The readFile() method was added in Symfony 7.1. If available, use it.
    // Otherwise, fall back to php native file_get_contents() method.
    if (method_exists($file_system, 'readFile')) {
      return $file_system->readFile($filename);
    }
    // @codeCoverageIgnoreStart
    // The @ suppresses the E_WARNING emitted by file_get_contents() when the
    // file does not exist. Older PHPUnit versions convert unhandled warnings
    // into errors, causing the test to fail before our IOException is thrown.
    $content = @file_get_contents($filename);
    if ($content === FALSE) {
      throw new IOException(\sprintf('Failed to read file "%s": ', $filename));
    }
    return $content;
    // @codeCoverageIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function isPathLocal(string $path): bool {
    return stream_is_local($path);
  }

  /**
   * {@inheritdoc}
   */
  public function writeToFile(string $filename, string $content): void {
    $this->getFilesystem()->dumpFile($filename, $content);
  }

  /**
   * {@inheritdoc}
   */
  public function chmod(iterable|string $files, int $mode, int $umask = 0o000, bool $recursive = FALSE): void {
    $this->getFilesystem()->chmod($files, $mode, $umask, $recursive);
  }

  /**
   * Returns the Symfony Filesystem instance, initializing it if necessary.
   *
   * @return \Symfony\Component\Filesystem\Filesystem
   *   The Symfony Filesystem instance.
   */
  private function getFilesystem(): SymfonyFilesystem {
    if (!$this->filesystem) {
      $this->filesystem = new SymfonyFilesystem();
    }
    return $this->filesystem;
  }

}
