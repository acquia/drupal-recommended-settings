<?php

namespace Acquia\Drupal\RecommendedSettings;

use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Config\DefaultConfig;
use Acquia\Drupal\RecommendedSettings\Config\SettingsConfig;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem;
use Consolidation\Config\ConfigInterface;

/**
 * Core class of the plugin.
 *
 * Contains the primary logic to copy acquia-recommended-settings files.
 *
 * @internal
 */
class Settings {

  /**
   * Config.
   */
  protected ConfigInterface $config;

  /**
   * Settings warning.
   *
   * @var string
   * Warning text added to the end of settings.php to point people
   * to the Acquia Drupal Recommended Settings
   * docs on how to include settings.
   */
  private string $settingsWarning = <<<WARNING
/**
 * IMPORTANT.
 *
 * Do not include additional settings here. Instead, add them to settings
 * included by `acquia-recommended.settings.php`. See Acquia's documentation for more detail.
 *
 * @link https://docs.acquia.com/
 */
WARNING;

  /**
   * The symfony file-system object.
   */
  protected Filesystem $fileSystem;

  /**
   * The path to drupal webroot directory.
   */
  protected string $drupalRoot;

  /**
   * The drupal site machine_name. Ex: site1, site2 etc.
   */
  protected string $site;

  /**
   * Constructs the plugin object.
   */
  public function __construct(string $drupal_root, string $site = "default") {
    $this->config = new DefaultConfig($drupal_root);
    $this->fileSystem = new Filesystem();
    $this->config->set("site", $site);
  }

  /**
   * Returns the acquia/drupal-recommended-plugin path.
   */
  public static function getPluginPath(): string {
    return dirname(__DIR__);
  }

  /**
   * Copies the Global default setting files.
   */
  protected function copyGlobalSettings(): bool {
    return $this->fileSystem->copyFiles(
      self::getPluginPath() . "/settings/global",
      $this->config->get("docroot") . "/sites/settings"
    );
  }

  /**
   * Copies the default site specific setting files.
   */
  protected function copySiteSettings(): bool {
    return $this->fileSystem->copyFiles(
      self::getPluginPath() . "/settings/site",
      $this->config->get("docroot") . "/sites/" . $this->config->get("site") . "/settings"
    );
  }

  /**
   * Ensures that the settings files & directories are writable.
   *
   * @param array<string> $files
   *   An array of files or directories.
   */
  protected function ensureFileWritable(array $files): bool {
    return $this->fileSystem->chmod($files, 0777);
  }

  /**
   * Generate/Copy settings files.
   *
   * @param string[] $overrideData
   *   An array of data to override.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  public function generate(array $overrideData = []): void {
    try {
      $site = $this->config->get("site");
      // Replace variables in local.settings.php file.
      $config = new ConfigInitializer($this->config);
      $config->setSite($site);
      $config = $config->initialize()->loadAllConfig();
      if ($overrideData) {
        $config->addConfig($overrideData);
      }
      $config = $config->processConfig();

      $docroot = $config->get("docroot");

      $this->ensureFileWritable([
        $docroot . "/sites/$site",
        $docroot . "/sites/$site/settings.php",
      ]);

      $this->copyGlobalSettings();
      $this->copySiteSettings();

      // Create settings.php file from default.settings.php.
      $this->fileSystem->copyFile(
        $docroot . "/sites/default/default.settings.php",
        $docroot . "/sites/$site/settings.php"
      );

      // Append `require acquia-recommended.settings.php` code block in
      // site specific settings.php file (if it does not exist).
      $this->appendIfMatchesCollect(
        $docroot . "/sites/$site/settings.php",
       '#vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php#', PHP_EOL . 'require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";' . PHP_EOL
      );
      $this->appendIfMatchesCollect(
        $docroot . "/sites/$site/settings.php",
       '#Do not include additional settings here#', $this->settingsWarning . PHP_EOL
      );

      // Create local.settings.php file from default.local.settings.php.
      $this->fileSystem->copyFile(
        $docroot . "/sites/$site/settings/default.local.settings.php",
        $docroot . "/sites/$site/settings/local.settings.php"
      );

      $settings = new SettingsConfig($config->export());
      $settings->replaceFileVariables($docroot . "/sites/$site/settings/local.settings.php");

      // The config directory for given site must exists, otherwise Drupal will
      // add database credentials to settings.php.
      $this->fileSystem->ensureDirectoryExists($docroot . "/../config/$site");
    }
    catch (\Exception $e) {
      throw new SettingsException($e->getMessage());
    }

  }

  /**
   * Append the string to file, if matches.
   *
   * @param string $file
   *   The path to file.
   * @param string $pattern
   *   The regex patten.
   * @param string $text
   *   Text to append.
   * @param bool $shouldMatch
   *   Decides when to append if match found.
   */
  protected function appendIfMatchesCollect(string $file, string $pattern, string $text, bool $shouldMatch = FALSE): void {
    $contents = file_get_contents($file);
    if (preg_match($pattern, $contents) == $shouldMatch) {
      $contents .= $text;
    }
    $this->fileSystem->dumpFile($file, $contents);
  }

}
