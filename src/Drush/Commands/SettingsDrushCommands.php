<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use Acquia\Drupal\RecommendedSettings\Drush\Traits\SiteUriTrait;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Settings;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as Cli;
use Robo\ResultData;

/**
 * The DRS Drush commands.
 */
class SettingsDrushCommands extends BaseDrushCommands {

  use SiteUriTrait;

  /**
   * Command name for settings.php generation.
   */
  const SETTINGS_COMMAND = "settings";

  /**
   * Command name for hash salt generation.
   */
  const HASH_SALT_COMMAND = "drupal:hash-salt:init";

  /**
   * Generates the settings.php for given site.
   *
   * @param array<string> $options
   *   An array of input options.
   */
  #[CLI\Command(name: self::SETTINGS_COMMAND, aliases: ["drs:init:settings", "dis", "init:settings"])]
  #[CLI\Help(description: 'Generates the acquia recommended settings template files for given site.')]
  #[CLI\Option(name: 'database', description: 'Local database name')]
  #[CLI\Option(name: 'username', description: 'Local database username')]
  #[CLI\Option(name: 'password', description: 'Local database password')]
  #[CLI\Option(name: 'host', description: 'Local database host')]
  #[CLI\Option(name: 'port', description: 'Local database port')]
  #[CLI\Usage(
    name: 'drush ' . self::SETTINGS_COMMAND . ' --database=mydb --username=myuser --password=mypass --host=127.0.0.1 --port=1234 --uri=site1',
    description: 'Generates the settings.php for site2 passing db credentials.',
  )]
  public function initSettings(array $options = [
    'database' => NULL,
    'username' => NULL,
    'password' => NULL,
    'host' => NULL,
    'port' => NULL,
  ]): int {
    $db = [];
    $db['drupal']['db'] = array_filter(
      $options,
      fn($value, $key) => in_array($key, ['database', 'username', 'password', 'host', 'port']) && $value !== NULL,
      ARRAY_FILTER_USE_BOTH
    );
    try {
      $site_directory = $this->getSitesSubdirFromUri($this->getConfigValue("docroot"), $this->getConfigValue("drush.uri"));
      $settings = new Settings($this->getConfigValue("docroot"), $site_directory);
      $settings->generate($db);
      if (!$this->output()->isQuiet()) {
        $this->print(
          sprintf("Settings generated successfully for site '%s'.", $this->getConfigValue("drush.uri"))
        );
      }
    }
    catch (SettingsException $e) {
      if (!$this->output()->isQuiet()) {
        $this->print($e->getMessage(), "error", "red");
        if ($this->output()->isVerbose()) {
          $this->io()->writeln($e->getTraceAsString());
        }
      }
      return ResultData::EXITCODE_ERROR;
    }
    return ResultData::EXITCODE_OK;
  }

  /**
   * Generates the hash salt.
   */
  #[CLI\Command(name: self::HASH_SALT_COMMAND, aliases: ["dhsi", "setup:hash-salt"])]
  public function hashSalt(): int {
    return $this->postInitSettings();
  }

  /**
   * Writes a hash salt to ${repo.root}/salt.txt if one does not exist.
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: self::SETTINGS_COMMAND)]
  public function postInitSettings(): int {
    $hash_salt_file = $this->getConfigValue('repo.root') . '/salt.txt';
    if (!file_exists($hash_salt_file)) {
      $this->say("Generating hash salt...");
      $result = $this->taskWriteToFile($hash_salt_file)
        ->line(RandomString::string(55))
        ->run();

      if (!$result->wasSuccessful()) {
        $this->print(
          sprintf("Unable to write hash salt at `%s`.", $hash_salt_file), "error",
        );
      }

      return $result->getExitCode();
    }
    else {
      $this->print("Hash salt already exists.", "notice");
    }
    return ResultData::EXITCODE_OK;
  }

}
