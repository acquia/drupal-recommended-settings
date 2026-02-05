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
use Composer\Util\ProcessExecutor;

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
   * Process executor.
   */
  protected ProcessExecutor $executor;

  /**
   * Stores this plugin package object.
   */
  protected mixed $settingsPackage = NULL;

  /**
   * Checks if acquia/blt is updated.
   */
  protected bool $bltUpdated = FALSE;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    ProcessExecutor::setTimeout(3600);
    $this->executor = new ProcessExecutor($this->io);
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
   */
  public function onPostCmdEvent(): void {
    // Only install the template files, if the drupal-recommended-settings
    // plugin is installed, with drupal project.
    if ($this->settingsPackage && $this->getDrupalRoot() && !$this->bltUpdated) {
      // Check if settings generation should be skipped.
      if ($this->shouldSkipSettingsGeneration()) {
        return;
      }
      $vendor_dir = $this->composer->getConfig()->get('vendor-dir');
      $this->executeCommand(
        $vendor_dir . "/bin/drush drs:init:settings", [],
        TRUE
      );
    }
  }

  /**
   * Determines if settings generation should be skipped.
   *
   * Checks multiple sources in order of priority:
   * 1. Environment variable (DRS_GENERATE_SETTINGS)
   * 2. Composer configuration (extra.drupal-recommended-settings)
   * 3. Automatic environment detection.
   *
   * @return bool
   *   TRUE if settings generation should be skipped, FALSE otherwise.
   */
  protected function shouldSkipSettingsGeneration(): bool {
    // Priority 1: Check environment variable (explicit override).
    $envVar = getenv('DRS_GENERATE_SETTINGS');
    if ($envVar !== FALSE) {
      if ($envVar === 'false' || $envVar === '0') {
        $this->io->write(
          '<info>Skipping settings generation (DRS_GENERATE_SETTINGS=false)</info>'
        );
        return TRUE;
      }
      // If set to 'true' or '1', don't skip.
      return FALSE;
    }

    // Priority 2: Check composer.json configuration.
    $extra = $this->composer->getPackage()->getExtra();
    $drsConfig = $extra['drupal-recommended-settings'] ?? [];

    // Check auto-generate-on-install setting.
    if (isset($drsConfig['auto-generate-on-install'])) {
      if ($drsConfig['auto-generate-on-install'] === FALSE) {
        $this->io->write(
          '<info>Skipping settings generation (auto-generate-on-install is disabled)</info>'
        );
        return TRUE;
      }
      // If explicitly set to TRUE, don't skip.
      return FALSE;
    }

    // Priority 3: Automatic environment detection.
    if ($this->isNonLocalEnvironment()) {
      $this->io->write(
        '<info>Skipping settings generation (non-local environment detected)</info>'
      );
      return TRUE;
    }

    // Default: proceed with generation.
    return FALSE;
  }

  /**
   * Detects if current environment is non-local (CI/Production).
   *
   * @return bool
   *   TRUE if in CI or production environment, FALSE otherwise.
   */
  protected function isNonLocalEnvironment(): bool {
    // Check for CI environments.
    $ciEnvVars = [
      'CI',
      'CONTINUOUS_INTEGRATION',
      'GITHUB_ACTIONS',
      'GITLAB_CI',
      'CIRCLECI',
      'JENKINS_HOME',
      'TRAVIS',
      'PIPELINE_ENV',
    ];

    foreach ($ciEnvVars as $var) {
      if (getenv($var) !== FALSE) {
        return TRUE;
      }
    }

    // Check for Acquia production environments.
    $ahEnv = getenv('AH_SITE_ENVIRONMENT');
    if ($ahEnv !== FALSE && in_array($ahEnv, ['prod', 'test', 'ode'], TRUE)) {
      return TRUE;
    }

    // Check for other production indicators.
    $prodEnvVars = [
      'ACQUIA_ENVIRONMENT' => ['prod', 'test'],
      'PLATFORM_BRANCH' => ['production', 'master', 'main'],
    ];

    foreach ($prodEnvVars as $var => $prodValues) {
      $value = getenv($var);
      if ($value !== FALSE && in_array($value, $prodValues, TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
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
    elseif ($operation instanceof UpdateOperation && $operation->getTargetPackage() instanceof PackageInterface) {
      $this->bltUpdated = ($operation->getTargetPackage()->getName() == "acquia/blt");
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
  protected function getDrupalRoot(): ?string {
    $extra = $this->composer->getPackage()->getExtra();
    $docroot = $extra['drupal-scaffold']['locations']['web-root'] ?? NULL;
    if ($docroot) {
      $docroot = realpath($this->getProjectRoot() . DIRECTORY_SEPARATOR . $docroot);
      return $docroot ?: NULL;
    }
    return NULL;
  }

  /**
   * Executes a shell command with escaping.
   *
   * Example usage: $this->executeCommand("test command %s", [ $value ]).
   *
   * @param string $cmd
   *   Cmd.
   * @param array<string> $args
   *   Args.
   * @param bool $display_output
   *   Optional. Defaults to FALSE. If TRUE, command output will be displayed
   *   on screen.
   *
   * @return bool
   *   TRUE if command returns successfully with a 0 exit code.
   */
  protected function executeCommand(string $cmd, array $args = [], bool $display_output = FALSE): bool {
    // Shell-escape all arguments.
    foreach ($args as $index => $arg) {
      $args[$index] = escapeshellarg($arg);
    }
    // Add command as first arg.
    array_unshift($args, $cmd);
    // And replace the arguments.
    $command = call_user_func_array('sprintf', $args);
    if ($this->io->isVerbose() || $display_output) {
      $this->io->write('<comment> > ' . $command . '</comment>');
      $io = $this->io;
      function ($type, $buffer) use ($io): void {
        $io->write($buffer, FALSE);
      };
    }
    return ($this->executor->executeTty($command) == 0);
  }

}
