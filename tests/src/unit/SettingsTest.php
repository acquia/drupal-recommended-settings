<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use Acquia\Drupal\RecommendedSettings\Config\DefaultConfig;
use Acquia\Drupal\RecommendedSettings\Settings;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
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
   * Set up test environment.
   */
  public function setUp(): void {
    $this->drupalRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . RandomString::string(5, TRUE, NULL, 'abcdefghijklmnopqrstuvwxyz');
    $docroot = $this->drupalRoot . '/docroot';
    mkdir($docroot . '/sites/default', 0777, TRUE);
    $this->fileSystem = new Filesystem();
    $this->fileSystem->dumpFile($docroot . '/sites/default/default.settings.php', "<?php");
    $this->fileSystem->dumpFile($this->drupalRoot . '/composer.json', '{}');
  }

  /**
   * Test that the file is created.
   */
  public function testFileIsCreated(): void {
    $docroot = $this->drupalRoot . '/docroot';
    $config = new DefaultConfig($docroot);
    $settings = new Settings();
    $settings->setConfig($config);
    $settings->generate([
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
    // Assert that settings/default.global.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->drupalRoot . '/docroot/sites/settings/default.global.settings.php'));
    // Assert that settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->drupalRoot . '/docroot/sites/default/settings.php'));
    // Assert that settings.php file has content.
    $content = <<<CONTENT
<?php
require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";
/**
 * IMPORTANT.
 *
 * Do not include additional settings here. Instead, add them to settings
 * included by `acquia-recommended.settings.php`. See Acquia's documentation for more detail.
 *
 * @link https://docs.acquia.com/
 */

CONTENT;
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

  /**
   * Test that the deprecation message is triggered.
   *
   * @ignoreDeprecations
   */
  #[IgnoreDeprecations]
  public function testTriggerDeprecationMessage() {
    set_error_handler(function ($errno, $errstr) {
      $this->assertSame("Since acquia/drupal-recommended-settings:1.1.3: Creating an object by passing (\$drupal_root, \$site) arguments is deprecated and will cause an error in 1.2.0.", $errstr);
    }, \E_USER_DEPRECATED);
    $docroot = $this->drupalRoot . '/docroot';
    new Settings($docroot);
    restore_error_handler();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $this->fileSystem->chmod($this->drupalRoot, 0777, 0o000, TRUE);
    $this->fileSystem->remove($this->drupalRoot . '/docroot');
  }

}
