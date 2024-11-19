<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Tasks;

use Acquia\Drupal\RecommendedSettings\Robo\Config\ConfigAwareTrait;
use Robo\Common\CommandArguments;
use Robo\Exception\TaskException;
use Robo\Task\CommandStack;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs Drush commands in stack.
 *
 * You can use `stopOnFail()` to point that stack
 * should be terminated on first fail.
 *
 * ``` php
 * $this->taskDrush()
 *     ->drush('updb')
 *     ->drush('cr')
 *     ->run();
 * ```
 */
class DrushTask extends CommandStack {

  use ConfigAwareTrait;
  use CommandArguments {
    option as traitOption;
  }

  /**
   * Site alias to prepend to each command.
   */
  protected string|NULL $alias;

  /**
   * Directory to execute the command from.
   *
   * @see ExecTrait::$workingDirectory
   */
  protected string|NULL $dir;

  /**
   * Site uri to append uri option to each command.
   */
  protected string|NULL $uri;

  /**
   * Indicates if the command output should be verbose.
   */
  protected bool|int $verbose = FALSE;

  /**
   * Indicates if the command output should be very verbose.
   */
  protected bool $veryVerbose = FALSE;

  /**
   * Indicates if the command output should be debug verbosity.
   */
  protected bool $debug = FALSE;

  /**
   * Defaults init.
   */
  protected bool $defaultsInitialized = FALSE;

  /**
   * Additional directory paths to search for drush commands.
   */
  protected string|NULL $include = NULL;

  /**
   * Add or not the --ansi option.
   */
  protected bool|NULL $ansi = NULL;

  /**
   * Drush commands to execute when task is run.
   *
   * @var string[]
   */
  protected array $commands;

  /**
   * Options for each drush command.
   *
   * @var string[]
   */
  protected array $options;

  /**
   * Adds the given drush command to a stack.
   *
   * @param string|array<string> $commands
   *   The drush command to execute. Do NOT include "drush" prefix.
   *
   * @return $this
   */
  public function drush(string|array $commands) {
    // Clear out options associated with previous drush command.
    $this->setOptionsForLastCommand();

    if (!$this->defaultsInitialized) {
      $this->init();
    }

    if (is_array($commands)) {
      $commands = implode(' ', array_filter($commands));
    }

    $this->commands[] = trim($commands);
    return $this;
  }

  /**
   * Sets the site alias to be used for each command.
   *
   * @param string $alias
   *   The drush alias to use. Do NOT include "@" prefix.
   *
   * @return $this
   */
  public function alias(string $alias) {
    $this->alias = $alias;
    return $this;
  }

  /**
   * Sets the site uri to be used for each command.
   *
   * @param string $uri
   *   The URI to pass to drush's --uri option.
   *
   * @return $this
   */
  public function uri(string $uri) {
    $this->uri = $uri;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function dir($dir) {
    $this->dir = $dir;
    parent::dir($dir);

    return $this;
  }

  /**
   * Indicates if the command output should be verbose.
   *
   * @param string|bool $verbose
   *   Verbose.
   *
   * @return $this
   */
  public function verbose(string|bool|int $verbose) {
    $this->verbose = $this->mixedToBool($verbose);
    return $this;
  }

  /**
   * Indicates if the command output should be very verbose.
   *
   * @param string|bool $verbose
   *   Verbose.
   *
   * @return $this
   */
  public function veryVerbose(string|bool $verbose) {
    $this->veryVerbose = $this->mixedToBool($verbose);
    return $this;
  }

  /**
   * Indicates if the command output should be debug verbosity.
   *
   * @param string|bool $verbose
   *   Verbose.
   *
   * @return $this
   */
  public function debug(string|bool $verbose) {
    $this->debug = $this->mixedToBool($verbose);
    return $this;
  }

  /**
   * Include additional directory paths to search for drush commands.
   *
   * @param string $path
   *   The filepath for the --include option.
   *
   * @return $this
   */
  public function includePath(string $path) {
    $this->include = $path;
    return $this;
  }

  /**
   * Include or not the --ansi option for drush commands.
   *
   * @param string|bool $ansi
   *   The flag for including --ansi option.
   *
   * @return $this
   */
  public function ansi(string|bool $ansi) {
    $this->ansi = $ansi;
    return $this;
  }

  /**
   * Sets up drush defaults using config.
   */
  protected function init(): void {
    if ($this->getConfig()->get('drush.bin')) {
      $this->executable = str_replace(' ', '\\ ', $this->getConfig()->get('drush.bin'));
    }
    else {
      $this->executable = 'drush';
    }

    if (!isset($this->dir)) {
      $this->dir($this->getConfig()->get('drush.dir'));
    }
    if (!isset($this->uri)) {
      $this->uri = $this->getConfig()->get('drush.uri');
    }
    if (!isset($this->alias)) {
      $this->alias($this->getConfig()->get('drush.alias'));
    }
    if (!isset($this->interactive)) {
      $this->interactive(FALSE);
    }
    if (!isset($this->ansi)) {
      $this->ansi($this->getConfig()->get('drush.ansi', FALSE));
    }

    $this->defaultsInitialized = TRUE;
  }

  /**
   * Helper function to get the boolean equivalent of a variable.
   *
   * @param mixed $mixedVar
   *   Mixed.
   *
   * @return int|bool
   *   TRUE/FALSE as per PHP's cast to boolean ruleset, with the exception that
   *   a string value not equal to 'yes' or 'true' will evaluate to FALSE.
   */
  protected function mixedToBool(mixed $mixedVar): int|bool {
    if (is_string($mixedVar)) {
      $boolVar = ($mixedVar === 'yes' || $mixedVar === 'true');
    }
    elseif (is_int($mixedVar)) {
      $boolVar = $mixedVar;
    }
    else {
      $boolVar = (bool) $mixedVar;
    }
    return $boolVar;
  }

  /**
   * Associates arguments with their corresponding drush command.
   */
  protected function setOptionsForLastCommand(): void {
    if (isset($this->commands)) {
      $numberOfCommands = count($this->commands);
      $correspondingCommand = $numberOfCommands - 1;
      $this->options[$correspondingCommand] = $this->arguments;
      $this->arguments = '';
    }
    elseif (isset($this->arguments) && !empty($this->arguments)) {
      throw new TaskException($this, "A drush command must be added to the stack before setting arguments: {$this->arguments}");
    }
  }

  /**
   * Set the options to be used for each drush command in the stack.
   */
  protected function setGlobalOptions(): void {
    if (isset($this->uri) && !empty($this->uri)) {
      $this->option('uri', $this->uri);
    }

    if (!$this->interactive) {
      $this->option('no-interaction');
    }

    if ($this->verbose !== FALSE || ($this->verbosityThreshold() >= OutputInterface::VERBOSITY_VERBOSE)) {
      $verbosity_threshold = ($this->verbose !== FALSE) ? $this->verbose : $this->verbosityThreshold();
      switch ($verbosity_threshold) {
        case OutputInterface::VERBOSITY_VERBOSE:
          $this->verbose(TRUE);
          break;

        case OutputInterface::VERBOSITY_VERY_VERBOSE:
          $this->veryVerbose(TRUE);
          break;

        case OutputInterface::VERBOSITY_DEBUG:
          $this->debug(TRUE);
          break;

      }
    }

    if (($this->debug || $this->getConfig()->get('drush.debug'))
      && $this->getConfig()->get('drush.debug') !== FALSE) {
      $this->option('-vvv');
    }
    elseif (($this->veryVerbose || $this->getConfig()->get('drush.veryVerbose'))
      && $this->getConfig()->get('drush.veryVerbose') !== FALSE) {
      $this->option('-vv');
    }
    elseif (($this->verbose || $this->getConfig()->get('drush.verbose'))
      && $this->getConfig()->get('drush.verbose') !== FALSE) {
      $this->option('-v');
    }

    if ($this->include) {
      $this->option('include', $this->include);
    }

    if ($this->ansi) {
      $this->option("ansi");
    }
  }

  /**
   * Overriding CommandArguments::option to default option separator to '='.
   *
   * @param string $option
   *   An option key.
   * @param string $value
   *   Value for option.
   * @param string $separator
   *   The separator for options.
   *
   * @return $this
   */
  public function option(string $option, string $value = NULL, string $separator = '=') {
    return $this->traitOption($option, $value, $separator);
  }

  /**
   * Overriding parent::run() method to remove printTaskInfo() calls.
   *
   * Make note that if stopOnFail() is TRUE, then result data isn't returned!
   * Maybe this should be changed.
   *
   * {@inheritdoc}
   */
  public function run() {
    $this->setupExecution();

    // If 'stopOnFail' is not set, or if there is only one command to run,
    // then execute the single command to run.
    if (!$this->stopOnFail || (count($this->exec) == 1)) {
      return $this->executeCommand($this->getCommand());
    }

    // When executing multiple commands in 'stopOnFail' mode, run them
    // one at a time so that the result will have the exact command
    // that failed available to the caller. This is at the expense of
    // losing the output from all successful commands.
    $data = [];
    $message = '';
    $result = NULL;
    foreach ($this->exec as $command) {
      $result = $this->executeCommand($command);
      $result->accumulateExecutionTime($data);
      $message = $result->accumulateMessage($message);
      $data = $result->mergeData($data);
      if (!$result->wasSuccessful()) {
        return $result;
      }
    }

    return $result;
  }

  /**
   * Adds drush commands with their corresponding options to stack.
   */
  protected function setupExecution(): void {
    $this->setOptionsForLastCommand();
    $this->setGlobalOptions();

    $globalOptions = $this->arguments;

    foreach ($this->commands as $commandNumber => $command) {
      if ($this->alias) {
        $command = "@{$this->alias} {$command}";
      }

      $options = $this->options[$commandNumber] ?? '';

      // Add in global options, as well as those set via option method.
      $command = $command . $options . $globalOptions;

      $this->exec($command)
        ->dir($this->dir);
    }
  }

}
