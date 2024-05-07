<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Acquia\Drupal\RecommendedSettings\Settings;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\Core\Database\Database;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Drush;

/**
 * A Drush command to generate settings.php for Multisite.
 */
class MultisiteDrushCommands extends DrushCommands implements CustomEventAwareInterface {

  use CustomEventAwareTrait;

  const VALIDATE_GENERATE_SETTINGS = 'validate-generate-settings';
  const POST_GENERATE_SETTINGS = 'post-generate-settings';

  /**
   * Execute code before pre-validate site:install.
   */
  #[CLI\Hook(type: HookManager::PRE_ARGUMENT_VALIDATOR, target: 'site-install')]
  public function preValidateSiteInstall(CommandData $commandData): void {
    $bootstrapManager = Drush::bootstrapManager();
    if ($this->validateGenerateSettings($commandData)) {
      // Get sites subdir which we set in the hook doGenerateSettings.
      $sitesSubdir = $commandData->input()->getOption('sites-subdir');
      // Try to get any already configured database information.
      $bootstrapManager->bootstrapMax(DrupalBootLevels::CONFIGURATION, $commandData->annotationData());
      // By default, bootstrap manager boot site from default/setting.php
      // hence remove the database connection if site is other than default.
      if (($sitesSubdir && "sites/$sitesSubdir" !== $bootstrapManager->bootstrap()->confpath())) {
        Database::removeConnection('default');
        $db = [
          'database' => 'drupal',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ];
        $dbSpec = [
          "drupal" => ["db" => $db],
        ];

        $options = $commandData->options();
        // Db url is not present then ask for db credentials.
        if (!($options['db-url'])) {
          if (EnvironmentDetector::isLocalEnv()) {
            $db = $this->askDbCredentials($sitesSubdir, $db);
            $dbSpec["drupal"]["db"] = $db;
          }
          $commandData->input()->setOption("db-url",
            "mysql://" . $db['username'] . ":" . $db['password'] . "@" . $db['host'] . ":" . $db['port'] . "/" . $db['database']
          );
        }
        $settings = new Settings(DRUPAL_ROOT, $sitesSubdir);
        try {
          // Generate settings files with db specs.
          $settings->generate($dbSpec);
          $this->postGenerateSettings($commandData);
        }
        catch (SettingsException $e) {
          $this->io()->error($e->getMessage());
        }
      }
    }
  }

  /**
   * Function to check if multisite should be setup or not.
   */
  protected function validateGenerateSettings(CommandData $commandData): bool {
    $handlers = $this->getCustomEventHandlers(self::VALIDATE_GENERATE_SETTINGS);
    $status = TRUE;
    foreach ($handlers as $handler) {
      $status = $handler($commandData);
      $this->debugCommand($handler);
      if (!$status) {
        return FALSE;
      }
    }

    return $status;
  }

  /**
   * Function to run if post generation of settings.php.
   */
  protected function postGenerateSettings(CommandData $commandData): void {
    $handlers = $this->getCustomEventHandlers(self::POST_GENERATE_SETTINGS);
    foreach ($handlers as $handler) {
      $handler($commandData);
      $this->debugCommand($handler);
    }
  }

  /**
   * Get local database specs.
   *
   * @param string $site_name
   *   The site name.
   * @param string[] $default_credentials
   *   The default db credentials.
   *
   * @return string[]
   *   The database specs.
   */
  private function askDbCredentials(string $site_name, array $default_credentials): array {
    $shouldAsk = $this->io()->confirm(dt("Would you like to configure the local database credentials?"));
    $credentials = $default_credentials;
    if ($shouldAsk) {
      $credentials['database'] = $this->io()->ask("Local database name", $site_name);
      $credentials['username'] = $this->io()->ask("Local database user", $credentials['username']);
      $credentials['password'] = $this->io()->ask("Local database password", $credentials['password']);
      $credentials['host'] = $this->io()->ask("Local database host", $credentials['host']);
      $credentials['port'] = $this->io()->ask("Local database port", $credentials['port']);
    }
    return $credentials;
  }

  /**
   * Method to print command in terminal.
   *
   * @param object[] $handler
   *   An array of command handler.
   */
  private function debugCommand(array $handler): void {
    $object = $handler[0] ?? NULL;
    $method = $handler[1] ?? NULL;
    $className = is_object($object) ? $object::class : "";
    $commandInvoked = ($className && $method) ? "$className::$method" : "";
    if ($commandInvoked) {
      $this->logger()->debug(
        "Invoked command: <info>$commandInvoked</info>."
      );
    }
  }

}
