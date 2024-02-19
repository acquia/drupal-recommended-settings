<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Helpers;

use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem as DrsFilesystem;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;

/**
 * Functional test for the Filesystem class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Helpers\Filesystem
 */
class FilesystemTest extends FunctionalBaseTest {

  /**
   * The path to drupal webroot directory.
   */
  protected string $drupalRoot;

  /**
   * The symfony file-system object.
   */
  protected DrsFilesystem $drsFileSystem;

  /**
   * The source file directory.
   * @var array<string>
   */
  protected  array $sourceFileDir;

  /**
   * The test directory.
   */
  protected string $testDir;

  /**
   * Set up test environment.
   */
  public function setUp(): void {
    $this->drupalRoot = $this->getDrupalRoot();
    $this->testDir = $this->drupalRoot . '/testDir';
    $this->drsFileSystem = new DrsFilesystem();

    $defaultSitePath = $this->drupalRoot . '/sites/site1';
    $this->sourceFileDir = [
      $defaultSitePath . '/default.services.yml',
      $defaultSitePath . '/default.settings.php',
      $defaultSitePath . '/local.settings.php',
      $defaultSitePath . '/settings.php',
    ];
    foreach ($this->sourceFileDir as $fileName) {
      $this->drsFileSystem->dumpFile($fileName, "<?php echo 'test content';");
    }
  }

  /**
   * Test Filesystem::loadFilesFromDirectory().
   */
  public function testLoadFilesFromDirectory(): void {

    // Check that file exits.
    $this->assertFileExists($this->sourceFileDir[0]);
    $this->assertFileExists($this->sourceFileDir[1]);
    $this->assertFileExists($this->sourceFileDir[2]);
    $this->assertFileExists($this->sourceFileDir[3]);

    // Assert that same number of file exits.
    $this->assertSame($this->sourceFileDir, $this->drsFileSystem->loadFilesFromDirectory($this->drupalRoot . '/sites/site1'));
  }

  /**
   * Test  Filesystem::ensureDirectoryExists()
   */
  public function testEnsureDirectoryExists(): void {
    $this->drsFileSystem->ensureDirectoryExists($this->testDir);
    $this->assertDirectoryExists($this->testDir);
  }

  /**
   * Test  Filesystem::copyFile() & testCopyFiles().
   */
  public function testCopyFiles(): void {
    // Create directory.
    $this->drsFileSystem->ensureDirectoryExists($this->testDir);

    // Assert exception throws during file copy.
    try {
      $this->drsFileSystem->copyFile('test.txt', $this->testDir);
    }
    catch (\RuntimeException $re) {
      $this->assertSame("Source file `test.txt` doesn't exist.", $re->getMessage());
    }

    // Copy single file.
    $this->assertTrue($this->drsFileSystem->copyFile($this->sourceFileDir[0], $this->testDir . '/default.services.yml'));

    // Check that copied single file exits in destination directory.
    $this->assertFileExists($this->testDir . '/default.services.yml');

    // Copy all files from directory.
    $this->assertTrue($this->drsFileSystem->copyFiles($this->drupalRoot . '/sites/site1', $this->testDir));

    // Check that copied files available in destination directory.
    $this->assertFileExists($this->testDir . '/default.services.yml');
    $this->assertFileExists($this->testDir . '/default.settings.php');
    $this->assertFileExists($this->testDir . '/local.settings.php');
    $this->assertFileExists($this->testDir . '/settings.php');

  }

  /**
   * Test  Filesystem::appendToFile()
   */
  public function testAppendToFile(): void {
    $contentOriginal = file_get_contents($this->sourceFileDir[0]);
    $this->drsFileSystem->appendToFile($this->sourceFileDir[0], PHP_EOL . 'New line.');
    $contentUpdated = file_get_contents($this->sourceFileDir[0]);
    $this->assertNotSame($contentOriginal, $contentUpdated);
    $this->assertStringContainsString($contentUpdated, "<?php echo 'test content';" . PHP_EOL . "New line.");
  }

  /**
   * Test  Filesystem::dumpFile()
   */
  public function testDumpFile(): void {
    $file = $this->drupalRoot . '/new-file.txt';
    $content = 'Hello there!';
    $this->assertTrue($this->drsFileSystem->dumpFile($file, $content));
    $this->assertFileExists($file);
    $this->assertStringContainsString($content, file_get_contents($file));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    // Delete all files from testDir directory.
    @unlink($this->testDir . '/default.services.yml');
    @unlink($this->testDir . '/default.settings.php');
    @unlink($this->testDir . '/local.settings.php');
    @unlink($this->testDir . '/settings.php');

    // Delete all files from site1 directory.
    @unlink($this->drupalRoot . '/sites/site1/default.services.yml');
    @unlink($this->drupalRoot . '/sites/site1/default.settings.php');
    @unlink($this->drupalRoot . '/sites/site1/local.settings.php');
    @unlink($this->drupalRoot . '/sites/site1/settings.php');
    @unlink($this->drupalRoot . '/new-file.txt');

    // Finally delete the tempDir.
    @rmdir($this->testDir);
  }

}
