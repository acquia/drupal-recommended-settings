<?php

namespace Acquia\Drupal\RecommendedSettings\Tests;

use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem as DrsFilesystem;
use Acquia\Drupal\RecommendedSettings\Settings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class SettingsTest extends TestCase {

  /**
   * The recommended settings object.
   *
   * @var string
   */
  protected $settings;

  /**
   * The path to drupal webroot directory.
   *
   * @var string
   */
  protected $drupalRoot;

  /**
   * The symfony file-system object.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected Filesystem $fileSystem;

  /**
   * The symfony file-system object.
   *
   * @var \Acquia\Drupal\RecommendedSettings\Helpers\Filesystem
   */
  protected DrsFilesystem $drsFileSystem;

  /**
   * Set up test environmemt.
   */
  public function setUp(): void {
    $this->drupalRoot = dirname(__FILE__);
    $docroot = $this->drupalRoot . '/docroot';
    $this->drsFileSystem = new DrsFilesystem();
    $this->drsFileSystem->ensureDirectoryExists($docroot . '/sites/default');
    $this->fileSystem = new Filesystem();
    $this->fileSystem->touch($docroot . '/sites/default/default.settings.php');
    $this->settings = new Settings($docroot, "default");
    $this->settings->generate();
  }

  /**
   * Test that the file is created.
   */
  public function testFileIsCreated() {
    // Assert that settings/default.global.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->drupalRoot . '/docroot/sites/settings/default.global.settings.php'));
    // Assert that settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->drupalRoot . '/docroot/sites/default/settings.php'));
    // Assert that settings.php file has content.
    $content = '
require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";
/**
 * IMPORTANT.
 *
 * Do not include additional settings here. Instead, add them to settings
 * included by `acquia-recommended.settings.php`. See Acquia\'s documentation for more detail.
 *
 * @link https://docs.acquia.com/
 */
';
    $this->assertEquals($content, file_get_contents($this->drupalRoot . '/docroot/sites/default/settings.php'));
    // Assert that default.includes.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->drupalRoot . '/docroot/sites/default/settings/default.includes.settings.php'));
    // Assert that default.local.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->drupalRoot . '/docroot/sites/default/settings/default.local.settings.php'));
    // Assert that local.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->drupalRoot . '/docroot/sites/default/settings/local.settings.php'));
  }

  public function tearDown(): void {
    $this->fileSystem->remove($this->drupalRoot . '/docroot');
    $this->fileSystem->remove($this->drupalRoot . '/config');
  }

}
