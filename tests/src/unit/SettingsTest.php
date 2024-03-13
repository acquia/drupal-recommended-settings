<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit;

use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem as DrsFilesystem;
use Acquia\Drupal\RecommendedSettings\Settings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class SettingsTest extends TestCase {

  /**
   * The recommended settings object.
   */
  protected Settings $settings;

  /**
   * The path to drupal webroot directory.
   */
  protected string $drupalRoot;

  /**
   * The symfony file-system object.
   */
  protected Filesystem $fileSystem;

  /**
   * The symfony file-system object.
   */
  protected DrsFilesystem $drsFileSystem;

  /**
   * Set up test environment.
   */
  public function setUp(): void {
    $this->drupalRoot = dirname(__FILE__);
    $docroot = $this->drupalRoot . '/docroot';
    $this->drsFileSystem = new DrsFilesystem();
    $this->drsFileSystem->ensureDirectoryExists($docroot . '/sites/default');
    $this->fileSystem = new Filesystem();
    $this->fileSystem->touch($docroot . '/sites/default/default.settings.php');
    $this->settings = new Settings($docroot, "default");
    $this->settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);
  }

  /**
   * Test that the file is created.
   */
  public function testFileIsCreated(): void {
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
    // Get the local.settings.php file content.
    $localSettings = file_get_contents($this->drupalRoot . '/docroot/sites/default/settings/local.settings.php');
    // Verify database credentials.
    $this->assertStringContainsString("db_name = 'drs'", $localSettings, "The local.settings.php doesn't contains the 'drs' database.");
    $this->assertStringContainsString("'username' => 'drupal'", $localSettings, "The local.settings.php doesn't contains the 'drupal' username.");
    $this->assertStringContainsString("'password' => 'drupal'", $localSettings, "The local.settings.php doesn't contains the 'drupal' password.");
    $this->assertStringContainsString("'host' => 'localhost'", $localSettings, "The local.settings.php doesn't contains the 'localhost' host.");
    $this->assertStringContainsString("'port' => '3306'", $localSettings, "The local.settings.php doesn't contains the '3306' port.");
  }

  public function tearDown(): void {
    $this->fileSystem->remove($this->drupalRoot . '/docroot');
    $this->fileSystem->remove($this->drupalRoot . '/config');
  }

}
