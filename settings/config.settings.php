<?php

/**
 * @file
 * Controls configuration management settings.
 */

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;

/**
 * Override config directories.
 *
 * Acquia Drupal Recommended Settings makes the assumption that,
 * if using multisite, the default configuration
 * directory should be shared between all multi-sites, and each multisite will
 * override this selectively using configuration splits. However, some
 * applications may prefer to manage the configuration for each multisite
 * completely separately. If this is the case, set
 * $drs_override_config_directories & $drs_override_site_studio_sync_directories
 * to FALSE and along with value for config & site studio sync directory in
 * settings.php as follows:
 * $settings['config_sync_directory'] = $dir . "/config/$site_dir"
 * $settings['site_studio_sync'] =  $dir . "/sitestudio/$site_dir"
 * then it will not overwrite it.
 */
// phpcs:ignore
$drs_override_config_directories = $drs_override_config_directories ?? TRUE;
$drs_override_site_studio_sync_directories = $drs_override_site_studio_sync_directories ?? TRUE;

/**
 * Site path.
 *
 * @var string $site_path
 * This is always set and exposed by the Drupal Kernel.
 */
// phpcs:ignore
$site_name = EnvironmentDetector::getSiteName($site_path);

// phpcs:ignore
// If $drs_override_config_directories is set to FALSE and user has specified
// the value of $overridden_config_sync_dir then set config sync directory as
// value of $overridden_config_sync_dir. If user has only set
// $drs_override_config_directories is set to FALSE and not specified the value
// of $overridden_config_sync_dir then it will fallback to the settings file
// provided by FilePaths::ahSettingsFile().
if (!$drs_override_config_directories) {
  if (isset($overridden_config_sync_dir)) {
    $settings['config_sync_directory'] = $overridden_config_sync_dir;
  }
}

// phpcs:ignore
// Config sync settings.
// if $drs_override_config_directories is TRUE or not specified at all
// then set sync directory to ../config/default.
else {
  $settings['config_sync_directory'] = "../config/default";
}

// Site Studio sync settings.
// if $settings['site_studio_sync'] isn't set by users, then the DRS set it to
// "../sitestudio/default" and if $drs_override_site_studio_sync_directories is
// set to FALSE then user needs to provide the value of
// $settings['site_studio_sync'] =  "../sitestudio/$site_dir" in settings file;
if ($drs_override_site_studio_sync_directories) {
  $settings['site_studio_sync'] = "../sitestudio/default";
} else {
  if (isset($overridden_site_studio_sync_dir)) {
    $settings['site_studio_sync'] = $overridden_site_studio_sync_dir;
  } else {
    $settings['site_studio_sync'] = "../sitestudio/$site_name";
  }
}

$split_filename_prefix = 'config_split.config_split';

/**
 * Set environment splits.
 */
$split_envs = EnvironmentDetector::getEnvironments();
foreach ($split_envs as $split_env => $status) {
  $config["$split_filename_prefix.$split_env"]['status'] = $status;
}

// phpcs:ignore
$config["$split_filename_prefix.$site_name"]['status'] = TRUE;

/**
 * Set multisite split.
 */
// Set acsf site split if explicit global exists.
global $_acsf_site_name;
if (isset($_acsf_site_name)) {
  $config["$split_filename_prefix.$_acsf_site_name"]['status'] = TRUE;
}
