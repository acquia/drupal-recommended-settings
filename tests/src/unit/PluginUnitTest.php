<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\unit;

use Acquia\Drupal\RecommendedSettings\Plugin;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;
use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\RootPackage;
use Composer\Repository\RepositoryInterface;

class PluginUnitTest extends FunctionalBaseTest {

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
   * Set up test environmemt.
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
    $method = $this->getReflectionMethod(Plugin::class, "activate");
    $method->invokeArgs($this->plugin, [$this->composer, $this->io]);
  }

  /**
   * Test to get project root.
   */
  public function testGetProjectRoot(): void {
    $getProjectRootMethod = $this->getReflectionMethod(Plugin::class, "getProjectRoot");
    $projectRootPath = $getProjectRootMethod->invoke($this->plugin);
    // Assertion to check project root path.
    $this->assertEquals($this->getProjectRoot(), $projectRootPath);
  }

  /**
   * Test to get drupal root.
   */
  public function testGetDrupalRoot(): void {
    $getDrupalRootMethod = $this->getReflectionMethod(Plugin::class, "getDrupalRoot");
    $drupalRootPath = $getDrupalRootMethod->invoke($this->plugin);
    // Assertion to check project docroot path.
    $this->assertEquals(realpath($this->getProjectRoot() . "/docroot"), $drupalRootPath);
  }

  /**
   * Test to get settings package.
   */
  public function testGetSettingsPackage(): void {
    $repository = $this->createMock(RepositoryInterface::class);
    $operation = new InstallOperation($this->composer->getPackage());
    $packageEvent = new PackageEvent("install", $this->composer, $this->io, FALSE, $repository, [], $operation);
    $this->plugin->onPostPackageEvent($packageEvent);
    $getSettingsPackageMethod = $this->getReflectionMethod(Plugin::class, "getSettingsPackage");
    $getSettingsPackage = $getSettingsPackageMethod->invokeArgs($this->plugin, [$operation]);
    // Assertion to check package name.
    $this->assertEquals($getSettingsPackage, "acquia/drupal-recommended-settings-1.0");
  }

}
