<?php

namespace Acquia\Drupal\RecommendedSettings\Helpers;

use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Config\DefaultConfig;
use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use loophp\phposinfo\OsInfo;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Attempts to detect various properties about the current hosting environment.
 *
 * @package Acquia\Drupal\RecommendedSettings\Helpers
 */
class EnvironmentDetector extends AcquiaDrupalEnvironmentDetector {

  /**
   * Get CI env name.
   *
   * In the case of multiple environment detectors declaring a CI env name, the
   * first one wins.
   */
  public static function getCiEnv(): string|bool {
    if (getenv('PIPELINE_ENV')) {
      return 'pipelines';
    }
    if (getenv('GITLAB_CI_TOKEN')) {
      return 'codestudio';
    }
    return FALSE;
  }

  /**
   * Is CI.
   */
  public static function isCiEnv(): bool {
    return self::getCiEnv() || getenv('CI');
  }

  /**
   * Get the settings file include for the current CI environment.
   *
   * This may be provided by Acquia Drupal Recommended Settings,
   * or via a Composer package that has provided
   * its own environment detector. In the case of multiple detectors providing a
   * settings file, the first one wins.
   *
   * @return string
   *   Settings file full path and filename.
   */
  public static function getCiSettingsFile(): string {
    return sprintf("%s/vendor/acquia/drupal-recommended-settings/settings/%s.settings.php", dirname(DRUPAL_ROOT), self::getCiEnv());
  }

  /**
   * Is Pantheon.
   */
  public static function isPantheonEnv(): bool {
    return (bool) getenv('PANTHEON_ENVIRONMENT');
  }

  /**
   * Get Pantheon.
   */
  public static function getPantheonEnv(): array|string|false {
    return getenv('PANTHEON_ENVIRONMENT');
  }

  /**
   * Is Pantheon.
   */
  public static function isPantheonDevEnv(): bool {
    return self::getPantheonEnv() === 'dev';
  }

  /**
   * Is Pantheon.
   */
  public static function isPantheonStageEnv(): bool {
    return self::getPantheonEnv() === 'test';
  }

  /**
   * Is Pantheon.
   */
  public static function isPantheonProdEnv(): bool {
    return self::getPantheonEnv() === 'live';
  }

  /**
   * Is local.
   */
  public static function isLocalEnv(): bool {
    return parent::isLocalEnv() && !self::isPantheonEnv() && !self::isCiEnv();
  }

  /**
   * Is dev.
   */
  public static function isDevEnv(): bool {
    return self::isAhDevEnv() || self::isPantheonDevEnv();
  }

  /**
   * Is stage.
   */
  public static function isStageEnv(): bool {
    return self::isAhStageEnv() || self::isPantheonStageEnv();
  }

  /**
   * Is prod.
   */
  public static function isProdEnv(): bool {
    return self::isAhProdEnv() || self::isPantheonProdEnv();
  }

  /**
   * Is ACSF.
   */
  public static function isAcsfInited(): bool {
    return file_exists(DRUPAL_ROOT . "/sites/g");
  }

  /**
   * OS name.
   *
   * @return string
   *   The OS name.
   */
  public static function getOsName(): string {
    return OsInfo::os();
  }

  /**
   * OS version.
   *
   * @return string
   *   The OS version.
   */
  public static function getOsVersion(): string {
    return OsInfo::version();
  }

  /**
   * OS is Darwin.
   */
  public static function isDarwin(): bool {
    return OsInfo::isApple();
  }

  /**
   * Get a standardized site / db name.
   *
   * On ACE or simple multisite installs, this is the site directory under
   * 'docroot/sites'.
   *
   * On ACSF, this is the ACSF db name.
   *
   * @param string $site_path
   *   Directory site path.
   *
   * @return string|null
   *   Site name.
   */
  public static function getSiteName(string $site_path): ?string {
    if (self::isAcsfEnv()) {
      return self::getAcsfDbName();
    }
    if (self::isAcsfInited() && self::isLocalEnv()) {
      global $argv;

      // When developing locally, we use the host name to determine which site
      // factory site is active. The hostname must have a corresponding entry
      // under the multisites key.
      $input = new ArgvInput($argv);
      $config = new DefaultConfig(self::getDrupalRoot());
      $config_initializer = new ConfigInitializer($config, $input);
      $drs_config = $config_initializer->initialize()->loadAllConfig()->processConfig();
      // The hostname must match the pattern local.[site-name].com, where
      // [site-name] is a value in the multisites array.
      $domain_fragments = explode('.', getenv('HTTP_HOST'));
      if (isset($domain_fragments[1]) && $drs_config->has('multisites')) {
        $name = $domain_fragments[1];
        $acsf_sites = $drs_config->get('multisites');
        if (in_array($name, $acsf_sites, TRUE)) {
          return $name;
        }
      }
    }

    return str_replace('sites/', '', $site_path);
  }

  /**
   * Find the repo root.
   *
   * This isn't as trivial as it sounds, since a simple relative path
   * (__DIR__ . '/../../../../../../') won't work if this package is symlinked
   * using a Composer path repository, and this file can be invoked from both
   * web requests and Acquia Drupal Recommended Settings CLI calls.
   *
   * @return string
   *   The repo root as an absolute path.
   */
  public static function getRepoRoot(): string {
    return self::getDrupalRoot() ? dirname(self::getDrupalRoot()) : "";
  }

  /**
   * Returns the Drupal root path.
   */
  public static function getDrupalRoot(): string {
    return defined('DRUPAL_ROOT') ? DRUPAL_ROOT : "";
  }

  /**
   * List detectable environments and whether they are currently active.
   *
   * @return string[]
   *   Returns an array of environments.
   */
  public static function getEnvironments(): array {
    return [
      'local' => self::isLocalEnv(),
      'dev' => self::isDevEnv(),
      'stage' => self::isStageEnv(),
      'prod' => self::isProdEnv(),
      'ci' => self::isCiEnv(),
      'ode' => self::isAhOdeEnv(),
      'ah_other' => self::isAhEnv() && !self::isDevEnv() && !self::isStageEnv() && !self::isAhOdeEnv() && !self::isProdEnv(),
    ];
  }

}
