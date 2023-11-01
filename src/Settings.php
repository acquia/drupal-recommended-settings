<?php

namespace Acquia\Drupal\RecommendedSettings;

use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Config\SettingsConfig;
use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem;

/**
 * Core class of the plugin.
 *
 * Contains the primary logic to copy acquia-recommended-settings files.
 *
 * @internal
 */
class Settings {

  /**
   * Settings warning.
   *
   * @var string
   * Warning text added to the end of settings.php to point people
   * to the Acquia Drupal Recommended Settings
   * docs on how to include settings.
   */
  private $settingsWarning = <<<WARNING
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
   *
   * @var \Acquia\Drupal\RecommendedSettings\Helpers\Filesystem
   */
  protected Filesystem $fileSystem;

  /**
   * The path to drupal webroot directory.
   *
   * @var string
   */
  protected $drupalRoot;

  /**
   * The drupal site machine_name. Ex: site1, site2 etc.
   *
   * @var string
   */
  protected $site;

  /**
   * Constructs the plugin object.
   */
  public function __construct(string $drupalRoot, string $site = "default") {
    $this->fileSystem = new Filesystem();
    $this->drupalRoot = $drupalRoot;
    $this->site = $site;
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
      $this->drupalRoot . "/sites/settings"
    );
  }

  /**
   * Copies the default site specific setting files.
   */
  protected function copySiteSettings(): bool {
    return $this->fileSystem->copyFiles(
      self::getPluginPath() . "/settings/site",
      $this->drupalRoot . "/sites/" . $this->site . "/settings"
    );
  }

  /**
   * Generate/Copy settings files.
   *
   * @param array $overrideData
   *   An array of data to override.
   */
  public function generate(array $overrideData = []): void {
    $site = $this->site;
    $this->copyGlobalSettings();
    $this->copySiteSettings();

    // Create settings.php file from default.settings.php.
    $this->fileSystem->copyFile(
      $this->drupalRoot . "/sites/default/default.settings.php",
      $this->drupalRoot . "/sites/$site/settings.php"
    );

    // Append `require acquia-recommended.settings.php` code block in
    // site specific settings.php file (if it does not exist).
    $this->appendIfMatchesCollect(
      $this->drupalRoot . "/sites/$site/settings.php",
      '#vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php#', PHP_EOL . 'require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";' . PHP_EOL
    );
    $this->appendIfMatchesCollect(
      $this->drupalRoot . "/sites/$site/settings.php",
      '#Do not include additional settings here#', $this->settingsWarning . PHP_EOL
    );

    // Create local.settings.php file from default.local.settings.php.
    $this->fileSystem->copyFile(
      $this->drupalRoot . "/sites/$site/settings/default.local.settings.php",
      $this->drupalRoot . "/sites/$site/settings/local.settings.php"
    );

    // Replace variables in local.settings.php file.
    $config = new ConfigInitializer();
    $config = $config->loadAllConfig();
    if ($overrideData) {
      $config->addConfig($overrideData);
    }
    $settings = new SettingsConfig($config->processConfig()->export());
    $settings->replaceFileVariables($this->drupalRoot . "/sites/$site/settings/local.settings.php");
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
