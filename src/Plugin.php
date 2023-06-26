<?php

namespace Acquia\Drupal\RecommendedSettings;

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
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * Composer's I/O service.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * The Composer Scaffold handler.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\Handler
   */
  protected $handler;

  /**
   * Record whether the 'require' command was called.
   *
   * @var bool
   */
  protected $drsIncluded;

  /**
   * Stores this plugin package object.
   *
   * @var mixed|null
   */
  protected $settingsPackage;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    $this->drsIncluded = FALSE;
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
   * Marks Acquia Drupal Recommended Settings to be processed after an install or update command.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Event.
   */
  public function onPostPackageEvent(PackageEvent $event) {
    $package = $this->getSettingsPackage($event->getOperation());
    if ($package) {
      // By explicitly setting the Acquia Drupal Recommended Settings package,
      // the onPostCmdEvent() will process the update automatically.
      $this->settingsPackage = $package;
    }
  }

  /**
   * Execute Acquia Drupal Recommended Settings drs:update after update command has been executed.
   *
   * @throws \Exception
   */
  public function onPostCmdEvent() {
    // Only install the template files if acquia/drupal-recommended-settings was installed.
    if ($this->settingsPackage) {
      $settings = new Settings($this->composer, $this->io, $this->settingsPackage);
      $settings->hashSalt();
      $settings->generateSettings();
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
  protected function getSettingsPackage(OperationInterface $operation) {
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
   * Hook for pre-package install.
   */
  public function prePackageInstall(PackageEvent $event) {
    if (!$this->drsIncluded) {
      $operations = $event->getOperations();
      foreach ($operations as $operation) {
        if ($operation instanceof InstallOperation) {
          $package = $operation->getPackage();
        }
        elseif ($operation instanceof UpdateOperation) {
          $package = $operation->getTargetPackage();
        }
        if (isset($package) && $package instanceof PackageInterface && $package->getName() == 'acquia/drupal-recommended-settings') {
          $this->drsIncluded = TRUE;
          break;
        }
      }
    }
  }

}
