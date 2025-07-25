<?php

/**
 * @file
 * Setup Acquia Drupal Recommended Settings utility variables.
 *
 * Includes required settings files.
 */

use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Acquia\DrupalEnvironmentDetector\FilePaths;

/**
 * Detect environments, sites, and hostnames.
 */

// If trusted_reverse_proxy_ips is not defined, fail gracefully.
// phpcs:ignore
$trusted_reverse_proxy_ips = isset($trusted_reverse_proxy_ips) ? $trusted_reverse_proxy_ips : '';
if (!is_array($trusted_reverse_proxy_ips)) {
  $trusted_reverse_proxy_ips = [];
}

// Tell Drupal whether the client arrived via HTTPS. Ensure the
// request is coming from our load balancers by checking the IP address.
if (getenv('HTTP_X_FORWARDED_PROTO') === 'https'
    && getenv('REMOTE_ADDR')
    && in_array(getenv('REMOTE_ADDR'), $trusted_reverse_proxy_ips, TRUE)) {
  putenv("HTTPS=on");
}
$x_ips = getenv('HTTP_X_FORWARDED_FOR') ? explode(',', getenv('HTTP_X_FORWARDED_FOR')) : [];
$x_ips = array_map('trim', $x_ips);

// Add REMOTE_ADDR to the X-Forwarded-For in case it's an internal AWS address.
if (getenv('REMOTE_ADDR')) {
  $x_ips[] = getenv('REMOTE_ADDR');
}

// Check firstly for the bal and then check for an internal IP immediately.
$settings['reverse_proxy_addresses'] = $settings['reverse_proxy_addresses'] ?? [];
$ip = array_pop($x_ips);
if ($ip) {
  if (in_array($ip, $trusted_reverse_proxy_ips)) {
    if (!in_array($ip, $settings['reverse_proxy_addresses'])) {
      $settings['reverse_proxy_addresses'][] = $ip;
    }
    // We have a reverse proxy so turn the setting on.
    $settings['reverse_proxy'] = TRUE;

    // Get the next IP to test if it is internal.
    $ip = array_pop($x_ips);
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
      if (!in_array($ip, $settings['reverse_proxy_addresses'])) {
        $settings['reverse_proxy_addresses'][] = $ip;
      }
    }
  }
}

/**
 * Include additional settings files.
 *
 * Settings are included in a very particular order to ensure that they always
 * go from the most general (default global settings) to the most specific
 * (local custom site-specific settings). Each step in the cascade also includes
 * a global (all sites) and site-specific component. The entire order is:
 *
 * 1. Acquia Cloud settings (including secret settings)
 * 2. Default general settings (provided by Acquia Drupal Recommended Settings)
 * 3. Custom general settings (provided by the project)
 * 4. Default CI settings (provided by Acquia Drupal Recommended Settings)
 * 5. Custom CI settings (provided by the project)
 * 6. Local settings (provided by the project)
 */

$settings_files = [];

// Get overridden config & site studio sync directory path set from settings.php file.
$overridden_config_sync_dir = $settings['config_sync_directory'] ?? NULL;
$overridden_site_studio_sync_dir = $settings['site_studio_sync'] ?? NULL;

/**
 * Site path.
 *
 * @var string $site_path
 * This is always set and exposed by the Drupal Kernel.
 */
// phpcs:ignore
$site_name = EnvironmentDetector::getSiteName($site_path);

// Acquia Cloud settings.
if (EnvironmentDetector::isAhEnv()) {
  try {
    // Acquia platform settings includes a require line
    // that opens database connection, hence the mysql57 settings
    // file should be added before platform require line.
    // @see: https://www.drupal.org/project/mysql57
    // @todo: Remove this line once acquia platform start supporting mysql 8.0
    if(!EnvironmentDetector::isAhIdeEnv()) {
      $settings_files[] = __DIR__ . "/mysql57.settings.php";
    }
    if (!EnvironmentDetector::isAcsfEnv()) {
      $settings_files[] = FilePaths::ahSettingsFile(EnvironmentDetector::getAhGroup(), $site_name);
    }
    // Acquia Cloud IDE settings have $databases variable defined hence
    // the mysql57 setting file should be added after platform require line.
    // @todo: Remove this line once acquia platform start supporting mysql 8.0
    if(EnvironmentDetector::isAhIdeEnv()) {
      $settings_files[] = __DIR__ . "/mysql57.settings.php";
    }
  }
  catch (SettingsException $e) {
    trigger_error($e->getMessage(), E_USER_WARNING);
  }

  // Store API Keys and things outside of version control.
  // @see settings/sample-secrets.settings.php for sample code.
  // @see https://docs.acquia.com/resource/secrets/#secrets-settings-php-file
  $settings_files[] = EnvironmentDetector::getAhFilesRoot() . '/secrets.settings.php';
  $settings_files[] = EnvironmentDetector::getAhFilesRoot() . "/$site_name/secrets.settings.php";
}
// Default global settings.
$acquia_settings_files = [
  'cache',
  'config',
  'logging',
  'filesystem',
  'misc',
];
foreach ($acquia_settings_files as $recommended_settings_file) {
  $settings_files[] = __DIR__ . "/$recommended_settings_file.settings.php";
}

// Custom global and site-specific settings.
$settings_files[] = DRUPAL_ROOT . '/sites/settings/global.settings.php';
$settings_files[] = DRUPAL_ROOT . "/sites/$site_name/settings/includes.settings.php";

if (EnvironmentDetector::isCiEnv()) {
  // Default CI settings.
  $settings_files[] = __DIR__ . '/ci.settings.php';
  $settings_files[] = EnvironmentDetector::getCiSettingsFile();
  // Custom global and site-specific CI settings.
  $settings_files[] = DRUPAL_ROOT . "/sites/settings/ci.settings.php";
  $settings_files[] = DRUPAL_ROOT . "/sites/$site_name/settings/ci.settings.php";
}

// Local global and site-specific settings.
if (EnvironmentDetector::isLocalEnv()) {
  $settings_files[] = DRUPAL_ROOT . '/sites/settings/local.settings.php';
  $settings_files[] = DRUPAL_ROOT . "/sites/$site_name/settings/local.settings.php";
}

foreach ($settings_files as $settings_file) {
  if (file_exists($settings_file)) {
    // phpcs:ignore
    require $settings_file;
  }
}
