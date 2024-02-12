<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\unit;

use Acquia\Drupal\RecommendedSettings\Plugin;
use Composer\Composer;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\IO\IOInterface;

class PluginUnitTest extends DrsPhpUnitBase {

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
    $this->plugin = new Plugin();
    $this->setClass($this->plugin);
    $this->composer = $this->createMock(Composer::class);
    $this->io = $this->createMock(IOInterface::class);
    $this->getClassMethod("activate")->invokeArgs($this->plugin, [$this->composer, $this->io]);
  }

  /**
   * Test to get project root.
   */
  public function testGetProjectRoot(): void {
    $getProjectRootMethod = $this->getClassMethod("getProjectRoot");
    $projectRootPath = $getProjectRootMethod->invoke($this->plugin);
    $this->assertIsString($projectRootPath);
  }

  /**
   * Test to get drupal root.
   */
  public function testGetDrupalRoot(): void {
    $getDrupalRootMethod = $this->getClassMethod("getDrupalRoot");
    $drupalRootPath = $getDrupalRootMethod->invoke($this->plugin);
    $this->assertIsString($drupalRootPath);
  }

  /**
   * Test to get settings package.
   */
  public function testGetSettingsPackage(): void {
    $getSettingsPackageMethod = $this->getClassMethod("getSettingsPackage");
    $operationInterfaceParam = $this->createMock(OperationInterface::class);
    $getSettingsPackage = $getSettingsPackageMethod->invokeArgs($this->plugin, [$operationInterfaceParam]);
    // @todo to perform other assertions as this method
    // invoked on the basis of operation i.e update or install.
    $this->assertNull($getSettingsPackage);
  }

}
