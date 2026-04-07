<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Filesystem;

use Acquia\Drupal\RecommendedSettings\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Functional test for the Filesystem adapter.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Filesystem
 */
class FilesystemTest extends TestCase {

  /**
   * The filesystem adapter under test.
   */
  private Filesystem $fs;

  /**
   * A temporary directory for test fixtures.
   */
  private string $tmpDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->fs = new Filesystem();
    $this->tmpDir = sys_get_temp_dir() . '/drs_fs_test_' . uniqid('', TRUE);
    mkdir($this->tmpDir, 0777, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    (new SymfonyFilesystem())->remove($this->tmpDir);
  }

  /**
   * Tests that copy() creates the destination file with correct content.
   */
  public function testCopyCreatesDestination(): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, 'hello world');
    $this->fs->copy($source, $dest);
    $this->assertFileExists($dest);
    $this->assertSame('hello world', file_get_contents($dest));
  }

  /**
   * Tests that copy() with overwrite=TRUE replaces existing destination.
   */
  public function testCopyWithOverwriteReplacesDestination(): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, 'new content');
    file_put_contents($dest, 'old content');
    $this->fs->copy($source, $dest, TRUE);
    $this->assertSame('new content', file_get_contents($dest));
  }

  /**
   * Tests that copy() throws for a non-existent source file.
   */
  public function testCopyThrowsForMissingSource(): void {
    $this->expectException(\Exception::class);
    $this->fs->copy($this->tmpDir . '/nonexistent.txt', $this->tmpDir . '/dest.txt');
  }

  /**
   * Tests that append() adds content to the end of an existing file.
   */
  public function testAppendAddsContentToEnd(): void {
    $file = $this->tmpDir . '/file.txt';
    file_put_contents($file, 'initial');
    $this->fs->append($file, ' appended');
    $this->assertSame('initial appended', file_get_contents($file));
  }

  /**
   * Tests that append() creates the file if it does not exist.
   */
  public function testAppendCreatesFileIfMissing(): void {
    $file = $this->tmpDir . '/new.txt';
    $this->fs->append($file, 'brand new');
    $this->assertFileExists($file);
    $this->assertSame('brand new', file_get_contents($file));
  }

  /**
   * Tests that prepend() inserts content before existing content.
   */
  public function testPrependInsertsContentAtBeginning(): void {
    $file = $this->tmpDir . '/file.txt';
    file_put_contents($file, 'world');
    $this->fs->prepend($file, 'hello ');
    $this->assertSame('hello world', file_get_contents($file));
  }

  /**
   * Tests that exists() returns TRUE for a file that exists.
   */
  public function testExistsReturnsTrueForExistingFile(): void {
    $file = $this->tmpDir . '/exists.txt';
    file_put_contents($file, '');
    $this->assertTrue($this->fs->exists($file));
  }

  /**
   * Tests that exists() returns FALSE for a missing file.
   */
  public function testExistsReturnsFalseForMissingFile(): void {
    $this->assertFalse($this->fs->exists($this->tmpDir . '/missing.txt'));
  }

  /**
   * Tests that exists() accepts an iterable of paths.
   */
  public function testExistsWithMultiplePaths(): void {
    $a = $this->tmpDir . '/a.txt';
    $b = $this->tmpDir . '/b.txt';
    file_put_contents($a, '');
    file_put_contents($b, '');
    $this->assertTrue($this->fs->exists([$a, $b]));
    $this->assertFalse($this->fs->exists([$a, $this->tmpDir . '/missing.txt']));
  }

  /**
   * Tests that readFile() returns the file content.
   */
  public function testReadFileReturnsContent(): void {
    $file = $this->tmpDir . '/read.txt';
    file_put_contents($file, 'read this');
    $this->assertSame('read this', $this->fs->readFile($file));
  }

  /**
   * Tests that readFile() throws for a missing file.
   */
  public function testReadFileThrowsForMissingFile(): void {
    $this->expectException(\Exception::class);
    $this->fs->readFile($this->tmpDir . '/missing.txt');
  }

  /**
   * Tests that isPathLocal() returns TRUE for a local path.
   */
  public function testIsPathLocalReturnsTrueForLocalPath(): void {
    $this->assertTrue($this->fs->isPathLocal($this->tmpDir . '/foo.txt'));
  }

  /**
   * Tests that isPathLocal() returns FALSE for a remote URL.
   */
  public function testIsPathLocalReturnsFalseForRemotePath(): void {
    $this->assertFalse($this->fs->isPathLocal('ftp://example.com/file.txt'));
  }

  /**
   * Tests that writeToFile() creates a new file with given content.
   */
  public function testWriteToFileCreatesNewFile(): void {
    $file = $this->tmpDir . '/sub/write.txt';
    $this->fs->writeToFile($file, 'written content');
    $this->assertFileExists($file);
    $this->assertSame('written content', file_get_contents($file));
  }

  /**
   * Tests that writeToFile() overwrites an existing file.
   */
  public function testWriteToFileOverwritesExistingFile(): void {
    $file = $this->tmpDir . '/overwrite.txt';
    file_put_contents($file, 'old');
    $this->fs->writeToFile($file, 'new');
    $this->assertSame('new', file_get_contents($file));
  }

  /**
   * Tests that chmod() changes the file permissions.
   */
  public function testChmodChangesPermissions(): void {
    $file = $this->tmpDir . '/chmod.txt';
    file_put_contents($file, '');
    $this->fs->chmod($file, 0644);
    $this->assertSame(0644, fileperms($file) & 0777);
  }

  /**
   * Tests that prepend() throws when the file does not exist.
   *
   * Prepend() calls readFile() internally; a missing file must propagate as an
   * exception rather than silently creating a truncated file.
   */
  public function testPrependThrowsForMissingFile(): void {
    $this->expectException(\Exception::class);
    $this->fs->prepend($this->tmpDir . '/missing.txt', 'prefix');
  }

  /**
   * Tests that chmod() accepts an iterable of paths.
   */
  public function testChmodAcceptsMultiplePaths(): void {
    $a = $this->tmpDir . '/a.txt';
    $b = $this->tmpDir . '/b.txt';
    file_put_contents($a, '');
    file_put_contents($b, '');
    $this->fs->chmod([$a, $b], 0600);
    $this->assertSame(0600, fileperms($a) & 0777);
    $this->assertSame(0600, fileperms($b) & 0777);
  }

}
