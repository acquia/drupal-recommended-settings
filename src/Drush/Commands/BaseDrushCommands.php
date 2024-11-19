<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Common\IO;
use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Config\DefaultDrushConfig;
use Acquia\Drupal\RecommendedSettings\Robo\Config\ConfigAwareTrait;
use Acquia\Drupal\RecommendedSettings\Robo\Tasks\LoadTasks;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as Cli;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\LoadAllTasks;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * A Base Drush command.
 */
class BaseDrushCommands extends DrushCommands implements ConfigAwareInterface, LoggerAwareInterface, BuilderAwareInterface, IOAwareInterface, ContainerAwareInterface {

  use ContainerAwareTrait;
  use LoadAllTasks;
  use ConfigAwareTrait;
  use LoadTasks;
  use IO;

  /**
   * {@inheritdoc}
   */
  #[CLI\Hook(type: HookManager::INITIALIZE)]
  public function init(): void {
    $this->initializeConfig();
  }

  /**
   * Invokes an array of Drush commands.
   *
   * @param string[] $commands
   *   An array of Symfony commands to invoke, e.g., 'tests:behat:run'.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  protected function invokeCommands(array $commands): void {
    foreach ($commands as $key => $value) {
      if (is_numeric($key)) {
        $command = $value;
        $args = [];
      }
      else {
        $command = $key;
        $args = $value;
      }
      $this->invokeCommand($command, $args);
    }
  }

  /**
   * Invokes a single Drush command.
   *
   * @param string $command_name
   *   The name of the command, e.g., 'status'.
   * @param string[] $args
   *   An array of arguments to pass to the command.
   * @param string[] $options
   *   An array of options to pass to the command.
   * @param bool $display_command
   *   Decides if command should be displayed on terminal or not. Default is TRUE.
   */
  protected function invokeCommand(string $command_name, array $args = [], array $options = [], bool $display_command = TRUE): void {
    $process = Drush::drush(Drush::aliasManager()->getSelf(), $command_name, $args, $options);
    $output = $this->output();
    if ($display_command) {
      $output->writeln("<comment> > " . $command_name . "</comment>");
    }
    $process->setTty(Process::isTtySupported());
    $process->run(static function ($type, $buffer) use ($output): void {
      if (Process::ERR === $type) {
        $output->getErrorOutput()->write($buffer, FALSE, OutputInterface::OUTPUT_NORMAL);
      }
      else {
        $output->write($buffer, FALSE, OutputInterface::OUTPUT_NORMAL);
      }
    });
  }

  /**
   * Sets multisite context by settings site-specific config values.
   *
   * @param string $site_name
   *   The name of a multisite, e.g., if docroot/sites/example.com is the site,
   *   $site_name would be example.com.
   */
  public function switchSiteContext(string $site_name): void {
    $this->logger->debug("Switching site context to <comment>$site_name</comment>.");
    $this->initializeConfig($site_name);
  }

  /**
   * Initialize the configuration.
   *
   * @param string $site_name
   *   Given site name.
   */
  protected function initializeConfig(string $site_name = ""): void {
    $config = new DefaultDrushConfig($this->getConfig());
    $configInitializer = new ConfigInitializer($config);
    if ($site_name) {
      $configInitializer->setSite($site_name);
    }
    $config = $configInitializer->initialize()->loadAllConfig()->processConfig();
    $this->setConfig($config);
  }

}
