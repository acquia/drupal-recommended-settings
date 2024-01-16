<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Drush\Traits\SiteUriTrait;
use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Acquia\Drupal\RecommendedSettings\Settings;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\Core\Database\Database;
use Drush\Attributes as CLI;
use Drush\Boot\BootstrapManager;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Psr\Container\ContainerInterface as DrushContainer;
use Symfony\Component\Filesystem\Path;

/**
 * A Drush command to generate settings.php for Multisite.
 */
class MultisiteDrushCommands extends DrushCommands implements CustomEventAwareInterface {

  use CustomEventAwareTrait;
  use SiteUriTrait;

  const VALIDATE_GENERATE_SETTINGS = 'validate-generate-settings';
  const POST_GENERATE_SETTINGS = 'post-generate-settings';

  /**
   * Construct an object of Multisite commands.
   */
  public function __construct(private BootstrapManager $bootstrapManager) {
    parent::__construct();
  }

  /**
   * {@inheritDoc}
   */
  public static function createEarly(DrushContainer $drush_container): self {
    return new static(
      $drush_container->get('bootstrap.manager')
    );
  }

  /**
   * Execute code before pre-validate site:install.
   */
  #[CLI\Hook(type: HookManager::PRE_ARGUMENT_VALIDATOR, target: 'site-install')]
  public function preValidateSiteInstall(CommandData $commandData): void {
    if ($this->validateGenerateSettings($commandData)) {
      $uri = $commandData->input()->getOption('uri') ?? 'default';
      $sitesSubdir = $this->getSitesSubdirFromUri(DRUPAL_ROOT, $uri);
      $commandData->input()->setOption('sites-subdir', $sitesSubdir);
      $options = $commandData->options();
      $this->bootstrapManager->setUri('http://' . $sitesSubdir);

      // Try to get any already configured database information.
      $this->bootstrapManager->bootstrapMax(DrupalBootLevels::CONFIGURATION, $commandData->annotationData());

      // By default, bootstrap manager boot site from default/setting.php
      // hence remove the database connection if site is other than default.
      if (($sitesSubdir && "sites/$sitesSubdir" !== $this->bootstrapManager->bootstrap()->confpath())) {
        Database::removeConnection('default');
        $db = [
          'database' => 'drupal',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ];
        $dbSpec = [
          "drupal" => ["db" => $db]
        ];
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
          $settings->generate($dbSpec);
          $this->postGenerateSettings($commandData);
        }
        catch (SettingsException $e) {
          $this->io()->warning($e->getMessage());
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
    }
  }

  /**
   * Get local database specs.
   *
   * @param string $site_name
   *   The site name.
   *
   * @return array
   *   The database specs.
   */
  private function askDbCredentials(string $site_name, array $defaultCredentials): array {
    $shouldAsk = $this->io()->confirm(dt("Would you like to configure the local database credentials?"));
    $credentials = $defaultCredentials;
    if ($shouldAsk) {
      $credentials['database'] = $this->io()->ask("Local database name", $site_name);
      $credentials['username'] = $this->io()->ask("Local database user", $credentials['username']);
      $credentials['password'] = $this->io()->ask("Local database password", $credentials['password']);
      $credentials['host'] = $this->io()->ask("Local database host", $credentials['host']);
      $credentials['port'] = $this->io()->ask("Local database port", $credentials['port']);
    }
    return $credentials;
  }

}
