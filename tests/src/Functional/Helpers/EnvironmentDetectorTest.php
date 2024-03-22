<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Helpers;

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem as DrsFilesystem;
use Acquia\Drupal\RecommendedSettings\Plugin;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;
use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\RootPackage;

/**
 * Functional test for the EnvironmentDetectorTest class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector
 * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
 */
class EnvironmentDetectorTest extends FunctionalTestBase {

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

    putenv("AH_SITE_ENVIRONMENT=");
    putenv('PANTHEON_ENVIRONMENT=live');
    $this->assertTrue(EnvironmentDetector::isProdEnv());
    putenv("PANTHEON_ENVIRONMENT=");
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
   * Test EnvironmentDetector OS related methods.
   */
  public function testOS(): void {
    $os_name = EnvironmentDetector::getOsName();

    $this->assertIsString($os_name);
    $this->assertNotEmpty($os_name);

    $os_version = EnvironmentDetector::getOsVersion();
    $this->assertIsString($os_version);
    $this->assertNotEmpty($os_version);

    $this->assertIsBool(EnvironmentDetector::isDarwin());
  }

  /**
   * Test EnvironmentDetector::getSiteName().
   * @throws \ReflectionException
   */
  public function testGetSiteName(): void {
    $sitePath = "sites/site1";
    $this->assertSame('site1', EnvironmentDetector::getSiteName($sitePath));

    putenv('AH_SITE_GROUP=test');
    putenv('AH_SITE_ENVIRONMENT=prod');
    // Test definitely will be skipped in MacOS due to this, ensure to create
    // directory/file manually running commands:
    // sudo mkdir -p /var/www/site-php/test.prod/
    // sudo touch /var/www/site-php/test.prod/multisite-config.json,
    // This is important, or else we won't be able to test this functionality
    if (!is_dir("/var/www/site-php/test.prod/")) {
      if (!@mkdir("/var/www/site-php/test.prod/", "0777", TRUE)
        || !@touch("/var/www/site-php/test.prod/multisite-config.json")) {
        putenv('AH_SITE_GROUP=');
        putenv('AH_SITE_ENVIRONMENT=');
        $this->markTestSkipped("Not able to create directory. Please create manually.");
      }
    }
    $GLOBALS['gardens_site_settings']['conf']['acsf_db_name'] = "site1";
    $this->assertEquals("site1", EnvironmentDetector::getSiteName($sitePath));
    unset($GLOBALS['gardens_site_settings']);
    putenv('AH_SITE_GROUP=');
    putenv('AH_SITE_ENVIRONMENT=');
  }

  /**
   * Test EnvironmentDetector::getSiteName().
   */
  public function testGetSiteNameForLocalAcsf(): void {
    if (getenv("ORCA_FIXTURE_DIR")) {
      // Due to some reasons, we've to manually copy fixture directories to
      // project directory.
      // @todo: Revisit on why it's not working & fix it.
      $this->copyFixtureFiles($this->getFixtureDirectory(), $this->getProjectRoot());
    }
    $drsFileSystem = new DrsFilesystem();
    $drsFileSystem->ensureDirectoryExists($this->drupalRoot . '/sites/g');
    $drsFileSystem->dumpFile($this->drupalRoot . '/sites/g/random.php', "<?php echo 'hello';");
    $this->assertFileExists($this->drupalRoot . '/sites/g/random.php');
    $ci_updated = $host_updated = FALSE;
    if (getenv("CI")) {
      putenv("CI=");
      $ci_updated = TRUE;
    }
    if (!getenv('HTTP_HOST')) {
      putenv("HTTP_HOST=localhost.acms");
      $host_updated = TRUE;
    }
    $this->assertEquals("acms", EnvironmentDetector::getSiteName("sites/site1"));
    if ($ci_updated) {
      putenv("CI=true");
    }
    if ($host_updated) {
      putenv("HTTP_HOST=");
    }
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
    $ci_updated = FALSE;
    if (getenv("CI")) {
      putenv("CI=");
      $ci_updated = TRUE;
    }
    $this->assertSame([
      'local' => TRUE,
      'dev' => FALSE,
      'stage' => FALSE,
      'prod' => FALSE,
      'ci' => FALSE,
      'ode' => FALSE,
      'ah_other' => FALSE
    ], EnvironmentDetector::getEnvironments());
    if ($ci_updated) {
      putenv("CI=true");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    @unlink($this->drupalRoot . '/sites/g/random.php');
    @rmdir($this->drupalRoot . '/sites/g');
    @unlink('/var/www/site-php/test.prod/multisite-config.json');
    @rmdir('/var/www');
    parent::tearDown();
  }

}
