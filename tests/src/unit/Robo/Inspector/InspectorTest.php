<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Robo\Inspector;

use Acquia\Drupal\RecommendedSettings\Common\Executor;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Robo\Inspector\Inspector;
use Acquia\Drupal\RecommendedSettings\Tests\CommandsTestBase;
use Psr\Log\LoggerInterface;
use Robo\Common\ProcessExecutor;
use Robo\ResultData;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Unit test for the ConfigAwareTrait trait.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Robo\Inspector\Inspector
 */
class InspectorTest extends CommandsTestBase {

  /**
   * An instance of inspector class object.
   */
  protected Inspector $inspector;

  /**
   * An array of files to delete.
   *
   * @var array<string>
   */
  protected array $files;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $executor = new Executor($this->getBuilder());
    $this->inspector = new Inspector($executor);
    $repo_root = $this->getProjectRoot();
    $drupal_root = $this->getDrupalRoot();
    $this->config->set('repo.root', $repo_root);
    $this->config->set('docroot', $drupal_root);
    $this->config->set('drush.uri', "pqr");
    $this->config->set('composer.bin', $repo_root . "/vendor/bin");
    $this->config->set('drush.alias', "self");
    $this->inspector->setConfig($this->config);
    $this->files = [];
    $logger = $this->createMock(LoggerInterface::class);
    $this->inspector->setLogger($logger);
  }

  /**
   * Tests the getFs() method.
   */
  public function testGetFs(): void {
    $this->assertInstanceOf(Filesystem::class, $this->inspector->getFs());
  }

  /**
   * Tests the clearState() method.
   */
  public function testClearState(): void {
    $this->assertNull($this->inspector->clearState());
  }

  /**
   * Tests the isRepoRootPresent() & isDocrootPresent() method.
   */
  public function testIsRootPresent(): void {
    $this->assertTrue($this->inspector->isRepoRootPresent());
    $this->assertTrue($this->inspector->isDocrootPresent());
  }

  /**
   * Tests the multiple method to ensure files exist.
   */
  public function testFilesPresent(): void {
    $docroot = $this->getConfig()->get("docroot");
    $repo_root = $this->getConfig()->get("repo.root");

    mkdir("$docroot/sites/pqr/settings", 0777, TRUE);

    $settings_file = "$docroot/sites/pqr/settings.php";
    $local_settings_file = "$docroot/sites/pqr/settings/local.settings.php";
    $hash_salt_file = "$repo_root/salt.txt";

    $this->files[] = $settings_file;
    $this->assertTrue(touch($settings_file));
    $this->assertTrue($this->inspector->isDrupalSettingsFilePresent());

    $this->files[] = $hash_salt_file;
    $this->assertTrue(touch($hash_salt_file));
    $this->assertTrue($this->inspector->isHashSaltPresent());

    $this->files[] = $local_settings_file;
    $this->assertTrue(touch($local_settings_file));
    $this->assertTrue($this->inspector->isDrupalLocalSettingsFilePresent());
    $this->files[] = "$docroot/sites/pqr/settings";
    $this->files[] = "$docroot/sites/pqr";
  }

  /**
   * Tests the isDrupalSettingsFileValid() method.
   */
  public function testIsDrupalSettingsFileValid(): void {
    $docroot = $this->getConfig()->get("docroot");
    mkdir("$docroot/sites/pqr", 0777, TRUE);
    $settings_file = "$docroot/sites/pqr/settings.php";
    $this->files[] = $settings_file;
    $this->files[] = "$docroot/sites/pqr";
    $this->assertTrue(touch($settings_file));
    $settings_file_contents = <<<SETTINGS
<?php
require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";
SETTINGS;
    $this->assertNotFalse(file_put_contents($settings_file, $settings_file_contents));
    $this->assertTrue($this->inspector->isDrupalSettingsFileValid());

    $this->assertNotFalse(file_put_contents($settings_file, "<?php"));
    $this->assertFalse($this->inspector->isDrupalSettingsFileValid());
  }

  /**
   * Tests the filesExist() method.
   */
  public function testFilesExist(): void {
    $repo_root = $this->getConfigValue("repo.root");
    $random_file = $repo_root . "/random-file.txt";
    $this->files[] = $random_file;
    $this->assertTrue(touch($random_file));
    $this->assertTrue($this->inspector->filesExist([$random_file]));
    $this->assertFalse($this->inspector->filesExist([$repo_root . "/random-file-not-exist.txt"]));
  }

  /**
   * Tests the issueEnvironmentWarnings() method.
   */
  public function testIssueEnvironmentWarnings(): void {
    $this->assertNull($this->inspector->issueEnvironmentWarnings());
  }

  /**
   * Tests the warnIfPhpOutdated() method.
   */
  public function testWarnIfPhpOutdated(): void {
    // We are setting greater minimum php version, so that we can expect
    // exception.
    $this->inspector->minPhpVersion = "9.0";
    $this->expectException(SettingsException::class);
    $this->expectExceptionMessageMatches("/DRS requires PHP 9.0 or greater. You are using*/");
    $this->inspector->warnIfPhpOutdated();
  }

  /**
   * Tests the isActiveConfigIdentical() method.
   */
  public function testIsActiveConfigIdenticalTrue(): void {
    $process_executor = $this->createMock(ProcessExecutor::class);
    $process_executor->method("run")->willReturn(new ResultData(0, ""));

    $executor = $this->createMock(Executor::class);
    $executor->method("drush")->willReturn($process_executor);

    $logger = $this->createMock(LoggerInterface::class);
    $executor->setLogger($logger);

    $inspector = new Inspector($executor);
    $inspector->setLogger($logger);
    $this->assertTrue($inspector->isActiveConfigIdentical());
  }

  /**
   * Tests the isActiveConfigIdentical() method when returning FALSE.
   */
  public function testIsActiveConfigIdenticalFalse(): void {
    $process_executor = $this->createMock(ProcessExecutor::class);
    $process_executor->method("run")->willReturn(new ResultData(0, "system.site   different"));

    $executor = $this->createMock(Executor::class);
    $executor->method("drush")->willReturn($process_executor);

    $logger = $this->createMock(LoggerInterface::class);
    $executor->setLogger($logger);

    $inspector = new Inspector($executor);
    $inspector->setLogger($logger);
    $this->assertFalse($inspector->isActiveConfigIdentical());
  }

  /**
   * Tests the various commands.
   */
  public function testCommands(): void {
    $executor = $this->createMock(Executor::class);

    $executor->method("executeShell")->willReturnCallback(function ($command) {
      $commands = explode(" ", $command);
      $process = $this->getMockBuilder(Process::class)
        ->setConstructorArgs([$commands])
        ->getMock();
      $process_executor = $this->getMockBuilder(ProcessExecutor::class)
        ->setConstructorArgs([$process])
        ->getMock();
      $result_data = match ($command) {
        "git config user.name" => new ResultData(ResultData::EXITCODE_OK, "acquia"),
        "git config user.email" => new ResultData(ResultData::EXITCODE_OK, "noreply@acquia.com"),
        "git --version | cut -d' ' -f3" => new ResultData(ResultData::EXITCODE_OK, "2.39.2"),
        "command -v git >/dev/null 2>&1" => new ResultData(ResultData::EXITCODE_OK, ""),
        "composer --version | cut -d' ' -f3" => new ResultData(ResultData::EXITCODE_OK, "2.4"),
        default => "",
      };
      $result_data->provideOutputdata();
      $process_executor->method("run")->willReturn($result_data);
      return $process_executor;
    });

    $process_executor = function ($commands) {
      $process = $this->getMockBuilder(Process::class)
        ->setConstructorArgs([$commands])
        ->getMock();
      $process_executor = $this->getMockBuilder(ProcessExecutor::class)
        ->setConstructorArgs([$process])
        ->getMock();
      $process_executor->method("interactive")->willReturnSelf();
      $process_executor->method("silent")->willReturnSelf();
      $command = implode(" ", $commands);
      $drush_status = json_encode([
        "bootstrap" => "Successful",
        "php-conf" => [
          "/usr/local/etc/php/8.3/php.ini",
        ],
      ]);
      $result_data = match ($command) {
        "composer --version" => new ResultData(ResultData::EXITCODE_OK, "2.5.2"),
        "sqlq .tables",
        "sqlq SHOW DATABASES",
        "sqlq \"SHOW DATABASES\"",
        "site:alias @site1 --format=json" => new ResultData(ResultData::EXITCODE_OK, "success"),
        "status --format=json --fields=* --root=" . $this->getConfigValue("docroot") => new ResultData(ResultData::EXITCODE_OK, $drush_status),
        default => "",
      };
      $result_data->provideOutputdata();
      $process_executor->method("run")->willReturn($result_data);
      return $process_executor;
    };
    $executor->method('execute')->willReturnCallback($process_executor);
    $executor->method('drush')->willReturnCallback($process_executor);

    $inspector = new Inspector($executor);
    $logger = $this->createMock(LoggerInterface::class);
    $inspector->setLogger($logger);
    $this->assertTrue($inspector->isGitUserSet());
    $this->assertTrue($inspector->isGitMinimumVersionSatisfied("2.39.2"));
    $this->assertFalse($inspector->isGitMinimumVersionSatisfied("2.40"));
    $this->assertTrue($inspector->commandExists("git"));
    $this->assertFalse($inspector->isComposerMinimumVersionSatisfied("2.5.2"));
    $this->assertTrue($inspector->isComposerMinimumVersionSatisfied("2.2.1"));

    $this->assertEquals("2.5.2", $inspector->getComposerVersion());
    $this->assertTrue($inspector->getSqliteAvailable());
    $this->assertTrue($inspector->isSqliteAvailable());
    $this->assertTrue($inspector->getPostgreSqlAvailable());
    $this->assertTrue($inspector->isPostgreSqlAvailable());
    $this->assertTrue($inspector->getMySqlAvailable());
    $this->assertTrue($inspector->isMySqlAvailable());
    $inspector->setConfig($this->getConfig());
    $this->assertTrue($inspector->isDrupalInstalled());
    $this->assertIsArray($inspector->getStatus());
    $this->assertTrue($inspector->isDrushAliasValid("site1"));
  }

  /**
   * Tests the isLandoConfigPresent() method.
   */
  public function testIsLandoConfigPresent(): void {
    $this->assertFalse($this->inspector->isLandoConfigPresent());
    $this->files[] = $this->getConfigValue('repo.root') . '/.lando.yml';
    $this->assertTrue(touch($this->getConfigValue('repo.root') . '/.lando.yml'));
    $this->assertTrue($this->inspector->isLandoConfigPresent());
  }

  /**
   * Tests the isDatabaseAvailable() method.
   */
  public function testIsDatabaseAvailable(): void {
    $executor = $this->createMock(Executor::class);
    $inspector = new Inspector($executor);
    $logger = $this->createMock(LoggerInterface::class);
    $inspector->setLogger($logger);
    $inspector->setConfig($this->getConfig());
    $process_executor = function ($commands) {
      static $i = 0;
      $process = $this->getMockBuilder(Process::class)
        ->setConstructorArgs([$commands])
        ->getMock();
      $process_executor = $this->getMockBuilder(ProcessExecutor::class)
        ->setConstructorArgs([$process])
        ->getMock();
      $process_executor->method("interactive")->willReturnSelf();
      $process_executor->method("silent")->willReturnSelf();
      $result_data = match ($i) {
        0, 1 => new ResultData(ResultData::EXITCODE_OK, json_encode(["db-driver" => "mysql"])),
        2, 3 => new ResultData(ResultData::EXITCODE_OK, json_encode(["db-driver" => "pgsql"])),
        4, 5 => new ResultData(ResultData::EXITCODE_OK, json_encode(["db-driver" => "sqlite"])),
        6, 7 => new ResultData(ResultData::EXITCODE_OK, json_encode(["db-driver" => "mssql"])),
        default => "",
      };
      $result_data->provideOutputdata();
      $process_executor->method("run")->willReturn($result_data);
      $i++;
      return $process_executor;
    };
    $executor->method('drush')->willReturnCallback($process_executor);
    $this->assertTrue($inspector->isDatabaseAvailable());
    $this->assertTrue($inspector->isDatabaseAvailable());
    $this->assertTrue($inspector->isDatabaseAvailable());
    $this->assertFalse($inspector->isDatabaseAvailable());
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    foreach ($this->files as $file) {
      if (is_dir($file)) {
        @rmdir($file);
      }
      else {
        @unlink($file);
      }
    }
  }

}
