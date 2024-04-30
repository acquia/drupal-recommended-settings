<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Drush\Commands\BaseDrushCommands;
use Acquia\Drupal\RecommendedSettings\Tests\CommandsTestBase;
use Consolidation\SiteAlias\SiteAliasManager;
use Consolidation\SiteProcess\SiteProcess;
use Consolidation\SiteProcess\Transport\TransportInterface;
use Drush\Config\DrushConfig;
use Drush\SiteAlias\ProcessManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;

/**
 * Functional test for the BaseDrushCommands class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Drush\Commands\BaseDrushCommands
 */
class BaseDrushCommandsTest extends CommandsTestBase {

  /**
   * Holds an instance of Drush Command object.
   */
  protected BaseDrushCommands $command;

  /**
   * Holds default config array.
   *
   * @var array<string>
   */
  protected array $defaultConfig = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->command = new BaseDrushCommands();
    $drupal_root = $this->getDrupalRoot();
    $project_root = $this->getProjectRoot();
    $drushConfig = new DrushConfig();
    $drushConfig->set("runtime.project", $project_root);
    $drushConfig->set("options.root", $drupal_root);
    $drushConfig->set("drush.vendor-dir", $project_root . "/vendor");
    $drushConfig->set("options.ansi", TRUE);
    $drushConfig->set("runtime.drush-script", $project_root . "/vendor/bin/drush");

    $this->command->setConfig($drushConfig);
    $this->command->setInput($this->getContainer()->get("input"));
    $this->command->setOutput($this->getContainer()->get("output"));
    $this->command->setBuilder($this->getContainer()->get("builder"));
    $this->command->setLogger($this->getContainer()->get("logger"));
    $this->defaultConfig = [
      "drush" => [
        "alias" => "self",
        "vendor-dir" => "$project_root/vendor",
        "ansi" => TRUE,
        "bin" => "$project_root/vendor/bin/drush",
        "uri" => "default",
      ],
      "runtime" => [
        "project" => $project_root,
        "drush-script" => "$project_root/vendor/bin/drush",
      ],
      "options" => [
        "root" => $drupal_root,
        "ansi" => TRUE,
      ],
      "repo" => [
        "root" => $project_root,
      ],
      "docroot" => $drupal_root,
      "composer" => [
        "bin" => "$project_root/vendor/bin",
      ],
      "site" => "default",
      "environment" => "local",
      "drupal" => [
        "db" => [
          "database" => "default",
          "username" => "root",
          "password" => "root",
          "host" => "127.0.0.1",
          "port" => 3306,
        ],
      ],
      "multisites" => ["acms"],
    ];
  }

  /**
   * Tests the init() method.
   */
  public function testInit(): void {
    $this->command->init();
    $this->assertEquals($this->defaultConfig, $this->command->getConfig()->export());
  }

  /**
   * Tests the switchSiteContext() method.
   */
  public function testSwitchSiteContext(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->method("debug")->willReturnCallback(function ($message): void {
      $this->assertEquals("Switching site context to <comment>site1</comment>.", $message);
    });
    $this->command->setLogger($logger);
    $this->command->switchSiteContext("site1");
    $this->defaultConfig["drush"]["uri"] = "site1";
    $this->defaultConfig["site"] = "site1";
    $this->defaultConfig["drupal"]["db"]["database"] = "mydatabase";
    $this->defaultConfig["drupal"]["db"]["username"] = "drupal";
    $this->defaultConfig["drupal"]["db"]["password"] = "drupal";
    $this->defaultConfig["drupal"]["db"]["host"] = "localhost";
    $this->assertEquals($this->defaultConfig, $this->command->getConfig()->export());
  }

  /**
   * Tests the invokeCommand() and invokeCommands() methods.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  public function testInvokeCommands(): void {
    $process_manager = $this->createMock(ProcessManager::class);
    $output = $this->createMock(ConsoleOutput::class);
    $output->method("write")->willReturnCallback(function ($message): void {
      static $i = 0;
      match ($i) {
        0, 2, 4 => $this->assertEquals("This is error.", $message),
        1, 3, 5 => $this->assertEquals("This is normal output.", $message),
      };
      $i++;
    });

    $output->method("writeln")->willReturnCallback(function ($message): void {
      static $i = 0;
      match ($i) {
        0, 1 => $this->assertEquals("<comment> > site:install</comment>", $message),
        2 => $this->assertEquals("<comment> > pm:enable</comment>", $message),
      };
      $i++;
    });

    $output->method("getErrorOutput")->willReturnSelf();
    $this->command->setOutput($output);
    $process_manager->method("drush")->willReturnCallback(function ($siteAlias, string $command, array $args, array $options) {
      $commands = [$command];
      array_unshift($commands, "./vendor/bin/drush");
      $transport = $this->createMock(TransportInterface::class);
      $process = $this->getMockBuilder(SiteProcess::class)
        ->setConstructorArgs([$siteAlias, $transport, $commands, $options])
        ->getMock();
      $process->method("run")->willReturnCallback(function ($data) {
        $data(Process::ERR, "This is error.");
        $data(Process::OUT, "This is normal output.");
        return 1;
      });
      return $process;
    });
    $container = $this->getContainer();
    $container->add("site.alias.manager", SiteAliasManager::class);
    $container->add("process.manager", $process_manager);
    $this->command->invokeCommand("site:install", ["minimal"], ["--yes", "--uri=site1"]);
    $this->command->invokeCommands(["site:install", "pm:enable" => ["acquia_cms_search"]]);
  }

}
