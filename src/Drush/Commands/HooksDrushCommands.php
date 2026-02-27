<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Drush\Traits\SiteUriTrait;
use Acquia\Drupal\RecommendedSettings\Filesystem\Filesystem;
use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Base hook commands.
 */
class HooksDrushCommands extends DrushCommands {

  use SiteUriTrait;

  /**
   * Generate settings for multisite.
   */
  #[CLI\Hook(type: HookManager::ON_EVENT, target: MultisiteDrushCommands::VALIDATE_GENERATE_SETTINGS)]
  public function doGenerateSettings(CommandData $commandData): bool {
    // First check environment return FALSE if it is ACSF.
    $envCheck = !EnvironmentDetector::isAcsfEnv() && (EnvironmentDetector::isLocalEnv() || EnvironmentDetector::isAhIdeEnv());
    if (!$envCheck) {
      return FALSE;
    }
    // Get --uri from CLI user input.
    $uri = $commandData->input()->getOption('uri') ?: 'default';
    // Get sub directory if present in sites.php file.
    $sitesSubdir = $this->getSitesSubdirFromUri(DRUPAL_ROOT, $uri);
    // Check settings.php file exists in the sub directory.
    $settingsFileExist = DRUPAL_ROOT . "/sites/$sitesSubdir/settings.php";

    // Return FALSE if settings.php exists;.
    if (file_exists($settingsFileExist)) {
      return FALSE;
    }

    // If user pass --sites-subdir from cli.
    $inputSubDir = $commandData->input()->getOption('sites-subdir');
    // Site Sub-dir and user input sub-dir are same but settings.php exists
    // then return FALSE otherwise TRUE.
    if (($sitesSubdir == $inputSubDir) && file_exists($settingsFileExist)) {
      return FALSE;
    }

    // Site sub directory is equal to site uri and uri is not default.
    if (($sitesSubdir == $uri || !empty($sitesSubdir)) && $sitesSubdir != "default") {
      // Setting the sites sub directory to the command data object.
      $commandData->input()->setOption('sites-subdir', $sitesSubdir);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Adds write permission to the site directory and its contents.
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: SettingsDrushCommands::SETTINGS_COMMAND)]
  public function writePermissionToDir(): void {
    // When ORCA_FIXTURE_DIR environment variable is set, it means we are
    // running tests in CI. Hence, we need to provide write permission to the
    // site directory and its contents as ORCA updates the settings.php file
    // during tests, and it needs write permission to do so.
    if (getenv("ORCA_FIXTURE_DIR")) {
      $uri = $this->input()->getOption("uri") ?? "default";
      $root = $this->input()->getOption("root");
      $path = $root . DIRECTORY_SEPARATOR . "sites" . DIRECTORY_SEPARATOR . $uri;
      $file_system = new Filesystem();
      $currentPerms = fileperms($path) & 0777;
      $file_system->chmod($path, $currentPerms | 0222, 0o000, TRUE);
    }
  }

}
