<?php

namespace Acquia\Drupal\RecommendedSettings;

use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Helpers\HashGenerator;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin for handling drupal scaffold.
 *
 * @internal
 */
class Plugin implements PluginInterface, EventSubscriberInterface {

  /**
   * The Composer service.
   */
  protected Composer $composer;

  /**
   * Composer's I/O service.
   */
  protected IOInterface $io;

  /**
   * Stores this plugin package object.
   */
  protected mixed $settingsPackage = NULL;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  /**
   * {@inheritdoc}
   */
  public function deactivate(Composer $composer, IOInterface $io) {
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(Composer $composer, IOInterface $io) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PackageEvents::POST_PACKAGE_INSTALL => "onPostPackageEvent",
      PackageEvents::POST_PACKAGE_UPDATE => "onPostPackageEvent",
      ScriptEvents::POST_UPDATE_CMD => "onPostCmdEvent",
      ScriptEvents::POST_INSTALL_CMD => "onPostCmdEvent",
    ];
  }

  /**
   * Marks this plugin to be processed after package install or update event.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Event.
   */
  public function onPostPackageEvent(PackageEvent $event): void {
    $package = $this->getSettingsPackage($event->getOperation());
    if ($package) {
      // By explicitly setting the Acquia Drupal Recommended Settings package,
      // the onPostCmdEvent() will process the update automatically.
      $this->settingsPackage = $package;
    }
  }

  /**
   * Includes Acquia recommended settings post composer update/install command.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  public function onPostCmdEvent(): void {
    // Only install the template files, if the drupal-recommended-settings
    // plugin is installed.
    if ($this->settingsPackage) {
      try {
        HashGenerator::generate($this->getProjectRoot(), $this->io);
        $settings = new Settings($this->getDrupalRoot());
        $settings->generate();
      }
      catch (SettingsException $e) {
        $this->io->write("<fg=white;bg=red;options=bold>[error]</> " . $e->getMessage());
      }
    }
  }

  /**
   * Gets the acquia/drupal-recommended-settings package.
   *
   * @param \Composer\DependencyResolver\Operation\OperationInterface $operation
   *   Op.
   *
   * @return mixed|null
   *   Returns mixed or NULL.
   */
  protected function getSettingsPackage(OperationInterface $operation): mixed {
    if ($operation instanceof InstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }
    if (isset($package) && $package instanceof PackageInterface && $package->getName() == "acquia/drupal-recommended-settings") {
      return $package;
    }
    return NULL;
  }

  /**
   * Returns the project directory path.
   */
  protected function getProjectRoot(): string {
    return dirname($this->composer->getConfig()->get('vendor-dir'));
  }

  /**
   * Returns the drupal root directory path.
   */
  protected function getDrupalRoot(): string {
    $extra = $this->composer->getPackage()->getExtra();
    $docroot = $extra['drupal-scaffold']['locations']['web-root'] ?? "";
    return realpath($this->getProjectRoot() . "/" . $docroot);
  }

}
