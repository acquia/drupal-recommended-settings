<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Inspector;

// Use Acquia\Blt\Robo\Common\ArrayManipulator;
// use Acquia\Blt\Robo\Common\IO;
// use Acquia\Blt\Robo\Config\BltConfig;
// use Acquia\Blt\Robo\Config\YamlConfigProcessor;
// use Acquia\Blt\Robo\Exceptions\BltException;.
use Acquia\Drupal\RecommendedSettings\Common\ArrayManipulator;
use Acquia\Drupal\RecommendedSettings\Common\Executor;
use Acquia\Drupal\RecommendedSettings\Common\IO;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Robo\Config\ConfigAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\BuilderAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\ResultData;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Inspects various details about the current project.
 *
 * @package Acquia\Drupal\RecommendedSettings\Common
 */
class Inspector implements BuilderAwareInterface, ConfigAwareInterface, ContainerAwareInterface, LoggerAwareInterface {

  use BuilderAwareTrait;
  use ConfigAwareTrait;
  use ContainerAwareTrait;
  use LoggerAwareTrait;
  use IO;

  /**
   * Process executor.
   */
  protected Executor $executor;

  /**
   * Is MYSQL available.
   */
  protected bool|NULL $isMySqlAvailable = NULL;

  /**
   * Is PostgreSQL available.
   */
  protected bool|NULL $isPostgreSqlAvailable = NULL;

  /**
   * Is Sqlite available.
   */
  protected bool|NULL $isSqliteAvailable = NULL;


  /**
   * Filesystem.
   */
  protected Filesystem $fs;

  /**
   * Warnings were issued.
   */
  protected bool $warningsIssued = FALSE;

  /**
   * Defines the minimum PHP version.
   */
  public string $minPhpVersion = "8.1";

  /**
   * The constructor.
   *
   * @param \Acquia\Drupal\RecommendedSettings\Common\Executor $executor
   *   Process executor.
   */
  public function __construct(Executor $executor) {
    $this->executor = $executor;
    $this->fs = new Filesystem();
  }

  /**
   * Get filesystem.
   *
   * @return \Symfony\Component\Filesystem\Filesystem
   *   Filesystem.
   */
  public function getFs(): Filesystem {
    return $this->fs;
  }

  /**
   * Clear state.
   */
  public function clearState(): void {
    $this->isMySqlAvailable = NULL;
    $this->isPostgreSqlAvailable = NULL;
    $this->isSqliteAvailable = NULL;
  }

  /**
   * Determines if the repository root directory exists.
   *
   * @return bool
   *   TRUE if file exists.
   */
  public function isRepoRootPresent(): bool {
    return file_exists($this->getConfigValue('repo.root'));
  }

  /**
   * Determines if the Drupal docroot directory exists.
   *
   * @return bool
   *   TRUE if file exists.
   */
  public function isDocrootPresent(): bool {
    return file_exists($this->getConfigValue('docroot'));
  }

  /**
   * Determines if Drupal settings.php file exists.
   *
   * @return bool
   *   TRUE if file exists.
   */
  public function isDrupalSettingsFilePresent(): bool {
    return file_exists($this->getDrupalSettingsPath());
  }

  /**
   * Returns the path to settings.php file.
   *
   * @return string
   *   Returns file path.
   */
  public function getDrupalSettingsPath(): string {
    $uri = $this->getConfigValue("drush.uri", "default");
    $docroot = $this->getConfigValue('docroot');
    return "$docroot/sites/$uri/settings.php";
  }

  /**
   * Determines if salt.txt file exists.
   *
   * @return bool
   *   TRUE if file exists.
   */
  public function isHashSaltPresent(): bool {
    return file_exists($this->getConfigValue('repo.root') . '/salt.txt');
  }

  /**
   * Determines if Drupal local.settings.php file exists.
   *
   * @return bool
   *   TRUE if file exists.
   */
  public function isDrupalLocalSettingsFilePresent(): bool {
    $uri = $this->getConfigValue("drush.uri", "default");
    $docroot = $this->getConfigValue('docroot');
    return file_exists("$docroot/sites/$uri/settings/local.settings.php");
  }

  /**
   * Determines if Drupal settings.php contains required DRS includes.
   *
   * @return bool
   *   TRUE if settings.php is valid for DRS usage.
   */
  public function isDrupalSettingsFileValid(): bool {
    $settings_file_contents = file_get_contents($this->getDrupalSettingsPath());
    if (!strstr($settings_file_contents,
      '/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php')
    ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks that Drupal is installed, caches result.
   *
   * This method caches its result in $this->drupalIsInstalled.
   *
   * @return bool
   *   TRUE if Drupal is installed.
   */
  public function isDrupalInstalled(): bool {
    $this->logger->debug("Verifying that Drupal is installed...");
    $output = $this->getDrushStatus()['bootstrap'] ?? '';
    $installed = $output === 'Successful';
    $this->logger->debug("Drupal bootstrap results: $output");

    return $installed;
  }

  /**
   * Gets the result of `drush status`.
   *
   * @return string[]
   *   The result of `drush status`.
   */
  public function getDrushStatus(): array {
    $docroot = $this->getConfigValue('docroot');
    $status_info = (array) json_decode($this->executor->drush([
      'status',
      '--format=json',
      '--fields=*',
      "--root=$docroot",
    ])->run()->getMessage(), TRUE);

    return $status_info;
  }

  /**
   * Get status.
   *
   * @return mixed
   *   Status.
   */
  public function getStatus(): mixed {
    $status = $this->getDrushStatus();
    if (array_key_exists('php-conf', $status)) {
      foreach ($status['php-conf'] as $key => $conf) {
        unset($status['php-conf'][$key]);
        $status['php-conf'][] = $conf;
      }
    }

    $defaults = [
      'root' => $this->getConfigValue('docroot'),
      'uri' => $this->getConfigValue('drush.uri'),
    ];

    $status['composer-version'] = $this->getComposerVersion();
    // $status['blt-version'] = Blt::getVersion();
    $status = ArrayManipulator::arrayMergeRecursiveDistinct($defaults, $status);
    ksort($status);

    return $status;
  }

  /**
   * Validates a drush alias.
   *
   * Note that this runs in the context of the _configured_ Drush alias, but
   * validates the _passed_ Drush alias. So the generated command might be:
   * `drush @self site:alias @self --format=json`
   *
   * @param string $alias
   *   Drush alias.
   *
   * @return bool
   *   TRUE if alias is valid.
   */
  public function isDrushAliasValid(string $alias): bool {
    return $this->executor->drush([
      "site:alias",
      "@$alias",
      "--format=json",
    ])->run()->wasSuccessful();
  }

  /**
   * Determines if database is available, caches result.
   *
   * This method caches its result in $this->isDatabaseAvailable.
   *
   * @return bool
   *   TRUE if MySQL is available.
   */
  public function isDatabaseAvailable(): bool {
    $db = $this->getDrushStatus()['db-driver'];
    switch ($db) {
      case 'mysql':
        return $this->isMySqlAvailable();

      case 'pgsql':
        return $this->isPostgreSqlAvailable();

      case 'sqlite':
        return $this->isSqliteAvailable();
    }
    return FALSE;
  }

  /**
   * Determines if MySQL is available, caches result.
   *
   * This method caches its result in $this->isMySqlAvailable.
   *
   * @return bool
   *   TRUE if MySQL is available.
   */
  public function isMySqlAvailable(): bool {
    if (is_null($this->isMySqlAvailable)) {
      $this->isMySqlAvailable = $this->getMySqlAvailable();
    }

    return $this->isMySqlAvailable;
  }

  /**
   * Determines if MySQL is available. Uses MySQL credentials from Drush.
   *
   * This method does not cache its result.
   *
   * @return bool
   *   TRUE if MySQL is available.
   */
  public function getMySqlAvailable(): bool {
    $this->logger->debug("Verifying that MySQL is available...");
    /** @var \Robo\Result $result */
    $result = $this->executor->drush(["sqlq", "SHOW DATABASES"])
      ->run();

    return $result->wasSuccessful();
  }

  /**
   * Determines if PostgreSQL is available, caches result.
   *
   * This method caches its result in $this->isPostgreSqlAvailable.
   *
   * @return bool
   *   TRUE if MySQL is available.
   */
  public function isPostgreSqlAvailable(): bool {
    if (is_null($this->isPostgreSqlAvailable)) {
      $this->isPostgreSqlAvailable = $this->getPostgreSqlAvailable();
    }

    return $this->isPostgreSqlAvailable;
  }

  /**
   * Determines if PostgreSQL is available. Uses credentials from Drush.
   *
   * This method does not cache its result.
   *
   * @return bool
   *   TRUE if PostgreSQL is available.
   */
  public function getPostgreSqlAvailable(): bool {
    $this->logger->debug("Verifying that PostgreSQL is available...");
    /** @var \Robo\Result $result */
    $result = $this->executor->drush(["sqlq \"SHOW DATABASES\""])
      ->run();

    return $result->wasSuccessful();
  }

  /**
   * Determines if Sqlite is available, caches result.
   *
   * This method caches its result in $this->isSqliteAvailable.
   *
   * @return bool
   *   TRUE if Sqlite is available.
   */
  public function isSqliteAvailable(): bool {
    if (is_null($this->isSqliteAvailable)) {
      $this->isSqliteAvailable = $this->getSqliteAvailable();
    }

    return $this->isSqliteAvailable;
  }

  /**
   * Determines if Sqlite is available. Uses credentials from Drush.
   *
   * This method does not cache its result.
   *
   * @return bool
   *   TRUE if Sqlite is available.
   */
  public function getSqliteAvailable(): bool {
    $this->logger->debug("Verifying that Sqlite is available...");
    /** @var \Robo\Result $result */
    $result = $this->executor->drush(["sqlq", ".tables"])
      ->run();

    return $result->wasSuccessful();
  }

  /**
   * Determines if Lando configuration exists in the project.
   *
   * @return bool
   *   TRUE if Lando configuration exists.
   */
  public function isLandoConfigPresent(): bool {
    return file_exists($this->getConfigValue('repo.root') . '/.lando.yml');
  }

  /**
   * Gets Composer version.
   *
   * @return string
   *   The version of Composer.
   */
  public function getComposerVersion(): string {
    return $this->executor->execute(["composer", "--version"])
      ->interactive(FALSE)
      ->silent(TRUE)
      ->run()
      ->getMessage();
  }

  /**
   * Verifies that installed minimum Composer version is met.
   *
   * @param string $minimum_version
   *   The minimum Composer version that is required.
   *
   * @return bool
   *   TRUE if minimum version is satisfied.
   */
  public function isComposerMinimumVersionSatisfied(string $minimum_version): bool {
    $output = $this->executor->executeShell("composer --version | cut -d' ' -f3")->run();
    if (version_compare($output->getOutputData(), $minimum_version, '>=')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if a given command exists on the system.
   *
   * @param string $command
   *   The command binary only, e.g., "drush" or "php".
   *
   * @return bool
   *   TRUE if the command exists, otherwise FALSE.
   */
  public function commandExists(string $command): bool {
    $output = $this->executor->executeShell("command -v $command >/dev/null 2>&1")->run();
    return $output->getExitCode() == ResultData::EXITCODE_OK;
  }

  /**
   * Verifies that installed minimum git version is met.
   *
   * @param string $minimum_version
   *   The minimum git version that is required.
   *
   * @return bool
   *   TRUE if minimum version is satisfied.
   */
  public function isGitMinimumVersionSatisfied(string $minimum_version): bool {
    $output = $this->executor->executeShell("git --version | cut -d' ' -f3")->run();
    if (version_compare($output->getOutputData(), $minimum_version, '>=')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Verifies that Git user is configured.
   *
   * @return bool
   *   TRUE if configured, FALSE otherwise.
   */
  public function isGitUserSet(): bool {
    $name_data = $this->executor->executeShell("git config user.name")->run();
    $email_data = $this->executor->executeShell("git config user.email")->run();
    return ($name_data->getOutputData() || $email_data->getOutputData());
  }

  /**
   * Determines if all file in a given array exist.
   *
   * @param string[] $files
   *   An array of files.
   *
   * @return bool
   *   TRUE if all files exist.
   */
  public function filesExist(array $files): bool {
    foreach ($files as $file) {
      if (!file_exists($file)) {
        $this->logger->warning("Required file $file does not exist.");
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Issues warnings to user if their local environment is mis-configured.
   */
  public function issueEnvironmentWarnings(): void {
    if (!$this->warningsIssued) {
      $this->warnIfPhpOutdated();
      $this->warningsIssued = TRUE;
    }
  }

  /**
   * Throws an exception if the minimum PHP version is not met.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  public function warnIfPhpOutdated(): void {
    $current_php_version = phpversion();
    if (version_compare($current_php_version, $this->minPhpVersion, "<=")) {
      throw new SettingsException("DRS requires PHP $this->minPhpVersion or greater. You are using $current_php_version.");
    }
  }

  /**
   * Determines if the active config is identical to sync directory.
   *
   * @return bool
   *   TRUE if config is identical.
   */
  public function isActiveConfigIdentical(): bool {
    $result = $this->executor->drush(["config:status"])->run();
    $message = trim($result->getMessage());
    $this->logger->debug("Config status check results:");
    $this->logger->debug($message);

    // A successful test here results in "no message" so check for null.
    if ($message == NULL) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
