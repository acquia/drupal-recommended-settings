<?php

namespace Acquia\Drupal\RecommendedSettings;

use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Config\ConfigResolver;
use Acquia\Drupal\RecommendedSettings\Config\DefaultConfig;
use Acquia\Drupal\RecommendedSettings\Event\PreSettingsFileGenerateEvent;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Filesystem\Filesystem;
use Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationHandler;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Consolidation\Config\ConfigAwareTrait;
use Consolidation\Config\ConfigInterface;
use Robo\Config\Config;

/**
 * Core class of the plugin.
 *
 * Contains the primary logic to copy acquia-recommended-settings files.
 *
 * @internal
 */
class Settings implements CustomEventAwareInterface {
  use ConfigAwareTrait;
  use CustomEventAwareTrait;

  /**
   * The filesystem interface for file operations.
   *
   * @var \Acquia\Drupal\RecommendedSettings\Filesystem\FilesystemInterface
   */
  private FilesystemInterface $fileSystem;

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
   * The DRS require line.
   */
  private string $drsRequireLine = <<<DRS_REQUIRE
require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";
DRS_REQUIRE;

  /**
   * Constructs the plugin object.
   */
  public function __construct(?string $drupal_root = NULL, string $site = "default") {
    if (func_get_args()) {
      $this->initializeConfig($drupal_root, $site);
      trigger_error(
        sprintf(
          'Since acquia/drupal-recommended-settings:1.1.3: Creating an object by passing (%s, %s) arguments is deprecated and will cause an error in 1.2.0.',
          "\$drupal_root", "\$site",
        ),
        \E_USER_DEPRECATED,
      );
    }
    $this->fileSystem = new Filesystem();
  }

  /**
   * Returns the acquia/drupal-recommended-plugin path.
   */
  public static function getPluginPath(): string {
    return dirname(__DIR__);
  }

  /**
   * Ensures that the settings files & directories are writable.
   *
   * @param array<string> $files
   *   An array of files or directories.
   */
  protected function ensureFileWritable(array $files): void {
    foreach ($files as $file) {
      if (!file_exists($file)) {
        continue;
      }
      $needs_chmod = !is_writable($file) || (is_dir($file) && !is_executable($file));
      if ($needs_chmod) {
        $this->fileSystem->chmod($file, 0777);
      }
    }
  }

  /**
   * Generate/Copy settings files.
   *
   * @param array[] $overrideData
   *   An array of data to override.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  public function generate(array $overrideData = []): void {
    try {
      $config = $this->mergeConfigWithOverrides($overrideData);
      $site = $config->get('site');
      $docroot = $config->get('docroot');
      assert(
        is_string($docroot) && is_string($site) && !empty($docroot) && !empty($site),
        "The docroot and site must be non-empty strings."
      );
      $operations = $this->prepareOperations($config);
      $handler = new FileOperationHandler($config);
      $this->ensureFileWritable([
        $docroot . "/sites/$site",
        $docroot . "/sites/$site/settings.php",
      ]);
      $this->executeOperations($handler, $operations);
      // The config directory for given site must exist, otherwise Drupal will
      // add database credentials to settings.php.
      if (!is_dir($docroot . "/../config/$site")) {
        @mkdir($docroot . "/../config/$site", 0755, TRUE);
      }
    }
    catch (\Throwable $e) {
      throw new SettingsException($e->getMessage());
    }
  }

  /**
   * Merge config with any override data.
   *
   * @param array $overrideData
   *   Override data.
   *
   * @return \Consolidation\Config\ConfigInterface
   *   The merged config object.
   */
  private function mergeConfigWithOverrides(array $overrideData): ConfigInterface {
    $config = $this->getConfig();
    assert($config instanceof ConfigInterface);
    if ($overrideData) {
      $config = new Config($config->export());
      $config->combine($overrideData);
    }
    return $config;
  }

  /**
   * Prepare all file operations.
   *
   * Includes event handling and placeholder resolution.
   *
   * @param \Consolidation\Config\ConfigInterface $config
   *   The config object.
   *
   * @return array
   *   The resolved operations array.
   */
  private function prepareOperations(ConfigInterface $config): array {
    $resolver = new ConfigResolver($config);
    $operations = $this->loadDefaultOperations();
    $operations = $this->combineProjectOperations($operations);
    $event = new PreSettingsFileGenerateEvent($operations);
    $handlers = $this->getCustomEventHandlers($event::NAME);
    foreach ($handlers as $handle) {
      $handle($event);
      if ($event->isPropagationStopped()) {
        break;
      }
    }
    $operations = $this->addDrsSettings($event->getOperations());
    return $resolver->resolve($operations);
  }

  /**
   * Execute all file operations and collect results.
   *
   * @param \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationHandler $handler
   *   The file operation handler.
   * @param array $operations
   *   The operations to execute.
   *
   * @throws \Exception
   */
  private function executeOperations(FileOperationHandler $handler, array $operations): void {
    $operations = $handler->handle($operations);
    foreach ($operations as $operation) {
      $result = $operation->execute();
      if ($result->isFailed()) {
        throw new \Exception($result->getMessage());
      }
    }
  }

  /**
   * Initializes the config object.
   *
   * We need to initialize the config object for backward compatibility, as the
   * constructor is accepting the arguments in 1.1.3 and below versions, but it
   * will cause an error in 1.2.0 and above versions.
   *
   * @todo Remove this method in 1.2.0 and above versions, as the constructor
   *   will not accept any arguments.
   *
   * @param string $drupal_root
   *   The path to drupal webroot directory.
   * @param string $site
   *   The drupal site machine_name. Ex: site1, site2 etc.
   */
  private function initializeConfig(string $drupal_root, string $site = "default"): void {
    $config = new DefaultConfig($drupal_root);
    $config->set('site', $site);
    $config_initializer = new ConfigInitializer($config);
    $config_initializer->setSite($site);
    $config = $config_initializer->initialize()->loadAllConfig()->processConfig();
    $this->setConfig($config);
  }

  /**
   * Load default operations.
   *
   * @return array[]
   *   An array of default operations.
   */
  private function loadDefaultOperations(): array {
    return [
      "\${docroot}/sites/\${site}/settings.php" => "\${docroot}/sites/default/default.settings.php",
      "\${docroot}/sites/settings/default.global.settings.php" => "\${drs.root}/settings/global/default.global.settings.php",
      "\${docroot}/sites/\${site}/settings/default.includes.settings.php" => "\${drs.root}/settings/site/default.includes.settings.php",
      "\${docroot}/sites/\${site}/settings/default.local.settings.php" => "\${drs.root}/settings/site/default.local.settings.php",
      "\${docroot}/sites/\${site}/settings/local.settings.php" => [
        "copy" => [
          "path" => "\${drs.root}/settings/site/default.local.settings.php",
          "with-placeholder" => TRUE,
        ],
      ],
    ];
  }

  /**
   * Combine project operations with default operations.
   *
   * The project operations will be defined in the
   * `extra.drupal-recommended-settings.operations` key in the `composer.json`
   * file.
   *
   * @param array[] $operations
   *   An array of default operations.
   *
   * @return array[]
   *   An array of combined operations.
   *
   * @throws \JsonException
   *   If the JSON is invalid.
   */
  private function combineProjectOperations(array $operations): array {
    $project = $this->config->get('repo.root');
    assert(
      is_string($project),
      "The project root must be a string.",
    );
    $project_json_file = $project . '/composer.json';
    $json = $this->readJsonFile($project_json_file);
    $project_operations = $json['extra']['drupal-recommended-settings']['operations'] ?? [];
    if (!$project_operations) {
      $project_operations_file = $json['extra']['drupal-recommended-settings']['operations-file'] ?? '';
      if ($project_operations_file) {
        $project_operations = $this->readJsonFile($project . DIRECTORY_SEPARATOR . $project_operations_file);
      }
    }
    return array_merge($operations, $project_operations);
  }

  /**
   * Add the DRS require line to settings.php file.
   *
   * @param array[] $operations
   *   An array of operations.
   *
   * @return array[]
   *   An array of operations with the DRS require line added to the
   *   settings.php file.
   *
   * @throws \AssertionError
   *   If the `settings.php` file operation is not included in the operations.
   */
  private function addDrsSettings(array $operations): array {
    $settings_key = "\${docroot}/sites/\${site}/settings.php";
    $settings_payload = $operations[$settings_key] ?? NULL;
    assert(
      array_key_exists($settings_key, $operations) && (is_string($settings_payload) || is_array($settings_payload) && !empty($settings_payload)),
      "The `settings.php` file operation must be included in the operations."
    );
    if (is_string($settings_payload)) {
      $operations[$settings_key] = [
        "copy" => $settings_payload,
      ];
    }
    $operations[$settings_key]["append"][] = [
      "content" => PHP_EOL . $this->drsRequireLine . PHP_EOL,
    ];
    $operations[$settings_key]["append"][] = [
      "content" => $this->settingsWarning . PHP_EOL,
    ];
    return $operations;
  }

  /**
   * Reads a JSON file and returns its contents as an associative array.
   *
   * @param string $path
   *   The path to the JSON file.
   *
   * @return array
   *   The contents of the JSON file as an associative array.
   *
   * @throws \AssertionError
   *   If the file does not exist or is not readable.
   * @throws \JsonException
   *   If the JSON is invalid.
   */
  private function readJsonFile(string $path): array {
    assert(
      file_exists($path) && is_readable($path),
      sprintf("The JSON file must exist and be readable. Path: '%s'.", $path),
    );
    return json_decode(
      file_get_contents($path),
      TRUE,
      512,
      JSON_THROW_ON_ERROR,
    );
  }

}
