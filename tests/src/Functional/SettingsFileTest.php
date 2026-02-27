<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use Acquia\Drupal\RecommendedSettings\Config\DefaultConfig;
use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Acquia\Drupal\RecommendedSettings\Settings;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;
use Acquia\Drupal\RecommendedSettings\Tests\Mock\Drupal;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Functional test for the acquia-recommended.settings.php.
 */
class SettingsFileTest extends FunctionalTestBase {

  /**
   * The path to drupal project root.
   */
  protected string $projectRoot;

  /**
   * The symfony file-system helper.
   */
  protected Filesystem $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->fileSystem = new Filesystem();
  }

  /**
   * Verifies settings.php generates expected values.
   *
   * Below DocBlock added for legacy fallback for PHPUnit < 10.
   *
   * @runInSeparateProcess
   */
  #[RunInSeparateProcess]
  public function testAcquiaRecommendedSettingsFile(): void {
    $this->createFixtureForLocal();
    $this->assertTrue(TRUE);
    $site_path = 'default';
    $settings = [];
    include_once "{$this->projectRoot}/vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";
    $this->assertNotEmpty($settings);
    $this->assertArrayHasKey('config_sync_directory', $settings);
    $this->assertArrayHasKey('site_studio_sync', $settings);
    $this->assertArrayHasKey('file_public_path', $settings);
    $this->assertArrayHasKey('hash_salt', $settings);
    $this->assertArrayHasKey('file_private_path', $settings);
    $this->assertSame("../config/$site_path", $settings['config_sync_directory']);
    $this->assertSame("../sitestudio/$site_path", $settings['site_studio_sync']);
    $this->assertSame("sites/$site_path/files", $settings['file_public_path']);
    $this->assertSame($this->projectRoot . "/files-private/$site_path", $settings['file_private_path']);
    $this->assertNotEmpty($settings['hash_salt']);
    $this->assertDirectoryExists("{$this->projectRoot}/config/$site_path");
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    if (EnvironmentDetector::isLocalEnv()) {
      $this->fileSystem->chmod($this->projectRoot, 0777, 0o000, TRUE);
      $this->fileSystem->remove($this->projectRoot);
    }
    parent::tearDown();
  }

  /**
   * Builds a temporary Drupal fixture for tests.
   */
  private function createFixtureForLocal(): void {
    if (defined('DRUPAL_ROOT')) {
      $this->projectRoot = dirname(DRUPAL_ROOT);
      return;
    }
    $this->projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . RandomString::string(5, TRUE, NULL, 'abcdefghijklmnopqrstuvwxyz');
    define('DRUPAL_ROOT', $this->projectRoot . '/docroot');
    $this->fileSystem->mirror($this->getProjectRoot(), $this->projectRoot);
    $destination_plugin_path = $this->projectRoot . "/vendor/acquia/drupal-recommended-settings";
    $is_success = mkdir($destination_plugin_path, 0755, TRUE);
    $this->assertTrue($is_success);
    $this->fileSystem->mirror(Settings::getPluginPath() . '/settings', $destination_plugin_path . "/settings");
    class_alias(Drupal::class, 'Drupal');
    $this->fileSystem->dumpFile("{$this->projectRoot}/salt.txt", RandomString::string(55));
    $this->fileSystem->dumpFile(DRUPAL_ROOT . "/sites/default/default.settings.php", "<?php\n");
    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($this->projectRoot . '/docroot'));
    $this->fileSystem->dumpFile("{$this->projectRoot}/composer.json", "{}");
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
  }

}
