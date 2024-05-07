<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Drush\Commands\SettingsDrushCommands;
use Acquia\Drupal\RecommendedSettings\Tests\CommandsTestBase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Functional test for the SettingsDrushCommands class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Drush\Commands\SettingsDrushCommands
 */
class SettingsDrushCommandsTest extends CommandsTestBase {

  /**
   * Holds an instance of Drush Command object.
   */
  protected SettingsDrushCommands $command;

  /**
   * An array of files to delete.
   *
   * @var array<string>
   */
  protected array $fileList = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->command = new SettingsDrushCommands();
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $this->getDrupalRoot());
    }
    $docroot = $this->getDrupalRoot();
    $this->getConfig()->set("drush.uri", "abcd");
    $this->getConfig()->set("docroot", $docroot);
    $this->command->setConfig($this->getConfig());
    if (!file_exists("$docroot/sites/default/default.settings.php")) {
      $this->assertEquals(file_put_contents("$docroot/sites/default/default.settings.php", "<?php"), 5);
      $this->fileList[] = "$docroot/sites/default/default.settings.php";
    }
    if (!file_exists($docroot . "/../config")) {
      $this->fileList[] = $docroot . "/../config";
    }
  }

  /**
   * Tests the initSettings() method.
   */
  public function testInitSettings(): void {
    $docroot = $this->getDrupalRoot();
    $output = $this->createMock(OutputInterface::class);
    $output->method("writeln")->willReturnCallback(function ($message): void {
      $this->assertEquals(" <fg=white;bg=green;options=bold>[success]</fg=white;bg=green;options=bold> Settings generated successfully for site 'abcd'.", $message);
    });
    $this->command->setOutput($output);
    $this->assertEquals($this->command->initSettings(), 0);
    $this->assertFileExists("$docroot/sites/abcd/settings.php");
    $this->assertDirectoryExists("$docroot/sites/abcd/settings");
    $this->assertFileExists("$docroot/sites/abcd/settings/default.includes.settings.php");
    $this->assertFileExists("$docroot/sites/abcd/settings/default.local.settings.php");
    $this->assertFileExists("$docroot/sites/abcd/settings/local.settings.php");
    $this->assertDirectoryExists($docroot . "/../config/abcd");

    $this->getConfig()->set("docroot", "/some-random");
    $this->command->setConfig($this->getConfig());
    $output = $this->createMock(OutputInterface::class);
    $output->method("isQuiet")->willReturn(FALSE);
    $output->method("isVerbose")->willReturn(TRUE);
    $output->method("writeln")->willReturnCallback(function ($message): void {
      static $i = 0;
      switch ($i) {
        case 0:
          $this->assertStringStartsWith(" <fg=white;bg=red;options=bold>[error]</fg=white;bg=red;options=bold>", $message);
          break;

        case 1:
          $this->assertStringStartsWith(" <fg=white;bg=cyan;options=bold>[notice]</fg=white;bg=cyan;options=bold>", $message);
          break;
      }
      $i++;
    });
    $this->command->setOutput($output);
    $this->assertEquals($this->command->initSettings(), 1);
  }

  /**
   * Tests the hashSalt() method.
   */
  public function testHashSalt(): void {
    $this->command->setBuilder($this->getBuilder());
    $project_dir = $this->getProjectRoot();
    $this->assertTrue(mkdir($project_dir . "/test-project"));
    $this->command->getConfig()->set("repo.root", $project_dir . "/test-project");
    $output = $this->createMock(OutputInterface::class);
    $output->method("writeln")->willReturnCallback(function ($message): void {
      static $i = 0;
      switch ($i) {
        case 0:
          $this->assertEquals("Generating hash salt...", $message);
          break;

        case 1:
            $this->assertEquals(" <fg=white;bg=cyan;options=bold>[notice]</fg=white;bg=cyan;options=bold> Hash salt already exists.", $message);
          break;
      }
      $i++;
    });
    $this->command->setOutput($output);
    $this->assertEquals($this->command->hashSalt(), 0);
    $this->assertFileExists($project_dir . "/test-project/salt.txt");
    $this->assertEquals($this->command->hashSalt(), 0);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    $docroot = $this->getDrupalRoot();
    $project_dir = $this->getProjectRoot();
    @unlink("$docroot/sites/abcd/settings.php");
    @unlink("$docroot/sites/abcd/settings/default.includes.settings.php");
    @unlink("$docroot/sites/abcd/settings/default.local.settings.php");
    @unlink("$docroot/sites/abcd/settings/local.settings.php");
    @rmdir("$docroot/sites/abcd/settings");
    @rmdir("$docroot/sites/abcd");
    @rmdir($docroot . "/../config/abcd");
    foreach ($this->fileList as $file) {
      if (is_file($file)) {
        @unlink($file);
      }
      else {
        @rmdir($file);
      }
    }
    @unlink("$project_dir/test-project/salt.txt");
    @rmdir("$project_dir/test-project");
  }

}
