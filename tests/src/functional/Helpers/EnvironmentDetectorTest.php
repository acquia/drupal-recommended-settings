<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Helpers;

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem as DrsFilesystem;
use Acquia\Drupal\RecommendedSettings\Plugin;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;
use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\RootPackage;

/**
 * Functional test for the EnvironmentDetectorTest class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector
 */
class EnvironmentDetectorTest extends FunctionalBaseTest {

  /**
   * The recommended setting's plugin object.
   */
  protected Plugin $plugin;

  /**
   * The Composer service.
   */
  protected Composer $composer;

  /**
   * Composer's I/O service.
   */
  protected IOInterface $io;

  /**
   * The path to drupal webroot directory.
   */
  protected string $drupalRoot;

  /**
   * Set up test environment.
   * @throws \ReflectionException
   */
  public function setUp(): void {
    $config = new Config(TRUE, $this->getProjectRoot());
    $this->composer = new Composer();
    $package = new RootPackage("acquia/drupal-recommended-settings", "1.0", "1.0.0");
    $package->setExtra([
      "drupal-scaffold" => [
        "locations" => [
          "web-root" => "docroot"
        ],
      ],
    ]);
    $this->composer->setPackage($package);
    $this->composer->setConfig($config);
    $this->plugin = new Plugin();
    $this->io = $this->createMock(IOInterface::class);
    $activateMethod = $this->getReflectionMethod(Plugin::class, "activate");
    $activateMethod->invokeArgs($this->plugin, [$this->composer, $this->io]);

    $getDrupalRootMethod = $this->getReflectionMethod(Plugin::class, "getDrupalRoot");
    $this->drupalRoot = $drupalRootDir = $getDrupalRootMethod->invoke($this->plugin);
    // Define DRUPAL_ROOT once.
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $drupalRootDir);
    }
  }

  /**
   * Tests EnvironmentDetector::getCiEnv().
   *
   * @throws \ReflectionException
   */
  public function testGetCiEnv(): void {

    putenv("PIPELINE_ENV=TRUE");
    $this->assertSame('pipelines', EnvironmentDetector::getCiEnv());

    putenv("PIPELINE_ENV=");
    $this->assertFalse(EnvironmentDetector::getCiEnv());
    putenv("GITLAB_CI_TOKEN=TRUE");
    $this->assertSame('codestudio', EnvironmentDetector::getCiEnv());

    putenv("PIPELINE_ENV=");
    putenv("GITLAB_CI_TOKEN=");
    $this->assertFalse(EnvironmentDetector::isCiEnv());
    putenv("CI=TRUE");
    $this->assertTrue(EnvironmentDetector::isCiEnv());
  }

  /**
   * Verify Ci settings file suggestion exists.
   *
   * @throws \ReflectionException
   */
  public function testGetCiSettingsFile(): void {
    putenv("PIPELINE_ENV=TRUE");
    $this->assertStringEndsWith('/acquia/drupal-recommended-settings/settings/pipelines.settings.php', EnvironmentDetector::getCiSettingsFile());
  }

  /**
   * Verify multiple environment.
   *
   * @throws \ReflectionException
   */
  public function testMultipleEnv(): void {
    putenv("PIPELINE_ENV=");
    putenv("CI=");
    $this->assertTrue(EnvironmentDetector::isLocalEnv());

    putenv("AH_SITE_ENVIRONMENT=dev");
    $this->assertTrue(EnvironmentDetector::isDevEnv());

    putenv("AH_SITE_ENVIRONMENT=test");
    $this->assertTrue(EnvironmentDetector::isStageEnv());

    putenv("AH_SITE_ENVIRONMENT=prod");
    $this->assertTrue(EnvironmentDetector::isProdEnv());
  }

  /**
   * Test EnvironmentDetector::isAcsfInited().
   */
  public function testIsAcsfInited(): void {
    // Generate folder/files for ACSF.
    $drsFileSystem = new DrsFilesystem();
    $drsFileSystem->ensureDirectoryExists($this->drupalRoot . '/sites/g');
    $drsFileSystem->dumpFile($this->drupalRoot . '/sites/g/random.php', "<?php echo 'hello';");
    $this->assertTrue(EnvironmentDetector::isAcsfInited());
  }

  /**
   * Test EnvironmentDetector::getSiteName().
   * @throws \ReflectionException
   */
  public function testGetSiteName(): void {
    $sitePath = "sites/site1";
    $this->assertSame('site1', EnvironmentDetector::getSiteName($sitePath));
  }

  /**
   * Test EnvironmentDetector::getRepoRoot().
   */
  public function testGetRepoRoot(): void {
    $this->assertSame(EnvironmentDetector::getRepoRoot(), $this->getProjectRoot());
  }

  /**
   * Test EnvironmentDetector::getEnvironments().
   * @throws \ReflectionException
   */
  public function testGetEnvironments(): void {
    $this->assertSame([
      'local' => FALSE,
      'dev' => FALSE,
      'stage' => FALSE,
      'prod' => TRUE,
      'ci' => FALSE,
      'ode' => FALSE,
      'ah_other' => FALSE
    ], EnvironmentDetector::getEnvironments());
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    @unlink($this->drupalRoot . '/sites/g/random.php');
    @rmdir($this->drupalRoot . '/sites/g');
  }

}
