<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit;

use Acquia\Drupal\RecommendedSettings\Plugin;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;
use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\RootPackage;
use Composer\Repository\RepositoryInterface;

class PluginUnitTest extends FunctionalTestBase {

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
   * Stores the message.
   *
   */
  protected string $message = '';

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
          "web-root" => "docroot",
        ],
      ],
    ]);
    $this->composer->setPackage($package);
    $this->composer->setConfig($config);
    $this->plugin = new Plugin();
    $this->io = $this->createMock(IOInterface::class);
    $this->io->method('write')->withAnyParameters()->willReturnCallback(fn ($message) => $this->message = $message);
    $method = $this->getReflectionMethod(Plugin::class, "activate");
    $method->invokeArgs($this->plugin, [$this->composer, $this->io]);

    // This is done to verify that these methods exists, and we just invoke
    // these methods.
    $method = $this->getReflectionMethod(Plugin::class, "deactivate");
    $method->invokeArgs($this->plugin, [$this->composer, $this->io]);
    $method = $this->getReflectionMethod(Plugin::class, "uninstall");
    $method->invokeArgs($this->plugin, [$this->composer, $this->io]);
  }

  /**
   * Test to get project root.
   */
  public function testGetProjectRoot(): void {
    $method = $this->getReflectionMethod(Plugin::class, "getProjectRoot");
    $project_root_dir = $method->invoke($this->plugin);
    // Assertion to check project root path.
    $this->assertEquals($this->getProjectRoot(), $project_root_dir);
  }

  /**
   * Tests the getSubscribedEvents method.
   */
  public function testGetSubscribedEvents(): void {
    $this->assertEquals([
     "post-package-install" => "onPostPackageEvent",
     "post-package-update" => "onPostPackageEvent",
     "post-update-cmd" => "onPostCmdEvent",
     "post-install-cmd" => "onPostCmdEvent",
   ], Plugin::getSubscribedEvents());
  }

  /**
   * Tests the onPostCmdEvent method.
   */
  public function testOnPostCmdEvent(): void {
    $method = $this->getReflectionMethod(Plugin::class, "onPostCmdEvent");
    $this->assertNull($method->invoke($this->plugin));
  }

  /**
   * Test to get drupal root.
   */
  public function testGetDrupalRoot(): void {
    $method = $this->getReflectionMethod(Plugin::class, "getDrupalRoot");
    $drupal_root_dir = $method->invoke($this->plugin);
    // Assertion to check project docroot path.
    $this->assertEquals($this->getProjectRoot() . "/docroot", $drupal_root_dir);

    // Assert when drupal root is not present.
    $extra = $this->composer->getPackage()->getExtra();
    unset($extra['drupal-scaffold']);
    $this->composer->getPackage()->setExtra($extra);

    // Re-initialize plugin.
    $method = $this->getReflectionMethod(Plugin::class, "activate");
    $method->invokeArgs($this->plugin, [$this->composer, $this->io]);

    $method = $this->getReflectionMethod(Plugin::class, "getDrupalRoot");
    $drupal_root_dir = $method->invoke($this->plugin);
    $this->assertNull($drupal_root_dir);
  }

  /**
   * Test to get settings package.
   */
  public function testGetSettingsPackage(): void {

    // Assert when acquia/drupal-recommended-settings plugin is installed.
    $repository = $this->createMock(RepositoryInterface::class);
    $operation = new InstallOperation($this->composer->getPackage());
    $package_event = new PackageEvent("install", $this->composer, $this->io, FALSE, $repository, [], $operation);
    $this->plugin->onPostPackageEvent($package_event);
    $method = $this->getReflectionMethod(Plugin::class, "getSettingsPackage");
    $package_name = $method->invokeArgs($this->plugin, [$operation]);
    // Assertion to check package name.
    $this->assertEquals("acquia/drupal-recommended-settings-1.0", $package_name);

    // Assert when acquia/drupal-recommended-settings plugin updated.
    $operation = new UpdateOperation($this->composer->getPackage(), $this->composer->getPackage());
    $package_event = new PackageEvent("update", $this->composer, $this->io, FALSE, $repository, [], $operation);
    $this->plugin->onPostPackageEvent($package_event);
    $method = $this->getReflectionMethod(Plugin::class, "getSettingsPackage");
    $package_name = $method->invokeArgs($this->plugin, [$operation]);
    $this->assertNull($package_name);

    // Assert when any other package is installed.
    $package = new RootPackage("acquia/blt", "14.0", "14.0.0");
    $operation = new InstallOperation($package);
    $package_event = new PackageEvent("install", $this->composer, $this->io, FALSE, $repository, [], $operation);
    $this->plugin->onPostPackageEvent($package_event);
    $method = $this->getReflectionMethod(Plugin::class, "getSettingsPackage");
    $package_name = $method->invokeArgs($this->plugin, [$operation]);
    $this->assertNull($package_name);
  }

  /**
   * Tests the executeCommand method.
   */
  public function testExecuteCommand(): void {
    $method = $this->getReflectionMethod(Plugin::class, "executeCommand");

    // Command should not be displayed as display_output parameter is FALSE.
    $exit_code = $method->invokeArgs($this->plugin, ['echo', []]);
    $this->assertEmpty($this->message);
    $this->assertTrue($exit_code);

    // Command should be displayed as display_output parameter is TRUE.
    $method->invokeArgs($this->plugin, ['echo', [], TRUE]);
    $this->assertEquals('<comment> > echo</comment>', $this->message);

    $method = $this->getReflectionMethod(Plugin::class, "executeCommand");
    $exit_code = $method->invokeArgs($this->plugin, ['echo', ['|', 'echo'], TRUE]);
    $this->assertTrue($exit_code);
  }

}
