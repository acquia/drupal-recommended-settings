<?php

namespace Acquia\Drupal\RecommendedSettings\Common;

use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Robo\Config\ConfigAwareTrait;
use Consolidation\SiteProcess\ProcessManager;
use Consolidation\SiteProcess\ProcessManagerAwareInterface;
use Consolidation\SiteProcess\ProcessManagerAwareTrait;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\ProcessExecutor;
use Robo\Contract\ConfigAwareInterface;
use Robo\Robo;

/**
 * A class for executing commands.
 *
 * This allows non-Robo-command classes to execute commands easily.
 */
class Executor implements ConfigAwareInterface, LoggerAwareInterface, ProcessManagerAwareInterface {

  use ConfigAwareTrait;
  use IO;
  use LoggerAwareTrait;
  use ProcessManagerAwareTrait;

  /**
   * A copy of the Robo builder.
   */
  protected CollectionBuilder $builder;

  /**
   * An instance of GuzzleClient.
   */
  protected ClientInterface $client;

  /**
   * Executor constructor.
   *
   * @param \Robo\Collection\CollectionBuilder $builder
   *   This is a copy of the collection builder, required for calling various
   *   Robo tasks from non-command files.
   * @param \GuzzleHttp\ClientInterface|null $client
   *   The client interface object.
   */
  public function __construct(CollectionBuilder $builder, ?ClientInterface $client = NULL) {
    $this->builder = $builder;
    $this->client = $client ?? new Client();
    $this->processManager = new ProcessManager();
  }

  /**
   * Returns $this->builder.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The builder.
   */
  public function getBuilder(): CollectionBuilder {
    return $this->builder;
  }

  /**
   * Wrapper for taskExec().
   *
   * @param string $command
   *   The command string|array.
   *   Warning: symfony/process 5.x expects an array.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The task. You must call run() on this to execute it!
   */
  public function taskExec(string $command): CollectionBuilder {
    return $this->builder->taskExec($command);
  }

  /**
   * Executes a drush command.
   *
   * @param mixed $command
   *   The command to execute, without "drush" prefix.
   *
   * @return \Robo\Common\ProcessExecutor
   *   The unexecuted process.
   */
  public function drush(mixed $command): ProcessExecutor {
    $drush_array = [];
    // @todo Set to silent if verbosity is less than very verbose.
    $drush_array[] = $this->getConfigValue('composer.bin') . DIRECTORY_SEPARATOR . "drush";
    $drush_array[] = "@" . $this->getConfigValue('drush.alias');

    // URIs do not work on remote drush aliases in Drush 9. Instead, it is
    // expected that the alias define the uri in its configuration.
    if ($this->getConfigValue('drush.uri') !== 'default') {
      $drush_array[] = '--uri=' . $this->getConfigValue('drush.uri');
    }

    if (is_array($command)) {
      $command_array = array_merge($drush_array, $command);
      $this->logger->info("Running command " . implode(" ", $command_array));
      $process_executor = $this->execute($command_array);
    }
    else {
      $drush_string = implode (" ", $drush_array);
      $this->logger->info("$drush_string $command");
      $process_executor = $this->executeShell("$drush_string $command");
    }
    return $process_executor;
  }

  /**
   * Executes a command.
   *
   * @param array<string> $commands
   *   An array of commands.
   *
   * @return \Robo\Common\ProcessExecutor
   *   The unexecuted command.
   */
  public function execute(array $commands): ProcessExecutor {
    /** @var \Robo\Common\ProcessExecutor $process_executor */
    $process_executor = Robo::process($this->processManager->process($commands));
    return $process_executor->dir($this->getConfigValue('repo.root'))
      ->printOutput(FALSE)
      ->printMetadata(FALSE)
      ->interactive(FALSE);
  }

  /**
   * Executes a shell command.
   *
   * @param string $command
   *   The shell command string.
   *
   * @return \Robo\Common\ProcessExecutor
   *   The unexecuted command.
   */
  public function executeShell(string $command): ProcessExecutor {
    $process_executor = Robo::process($this->processManager->shell($command));
    return $process_executor->dir($this->getConfigValue('repo.root'))
      ->printOutput(FALSE)
      ->printMetadata(FALSE)
      ->interactive(FALSE);
  }

  /**
   * Kills all system processes that are using a particular port.
   *
   * @param string $port
   *   The port number.
   */
  public function killProcessByPort(string $port): void {
    $this->logger->info("Killing all processes on port '$port'...");
    // This is allowed to fail.
    $this->processManager->shell("command -v lsof && lsof -ti tcp:$port | xargs kill l 2>&1")->run();
    $this->processManager->shell("pkill -f $port 2>&1")->run();
  }

  /**
   * Kills all system processes containing a particular string.
   *
   * @param string $name
   *   The name of the process.
   */
  public function killProcessByName(string $name): void {
    $this->logger->info("Killing all processing containing string '$name'...");
    // This is allowed to fail.
    $this->processManager->shell("ps aux | grep -i $name | grep -v grep | awk '{print $2}' | xargs kill -9 2>&1")->run();
    // exec("ps aux | awk '/$name/ {print $2}' 2>&1 | xargs kill -9");.
  }

  /**
   * Waits until a given URL responds with a non-50x response.
   *
   * This does have a maximum timeout, defined in wait().
   *
   * @param string $url
   *   The URL to wait for.
   */
  public function waitForUrlAvailable(string $url): void {
    $this->wait([$this, 'checkUrl'], [$url], "Waiting for non-50x response from $url...");
  }

  /**
   * Waits until a given callable returns TRUE.
   *
   * This does have a maximum timeout.
   *
   * @param callable $callable
   *   The method/function to wait for a TRUE response from.
   * @param string[] $args
   *   Arguments to pass to $callable.
   * @param string $message
   *   The message to display when this function is called.
   * @param string $maxWait
   *   The maximum time to wait. Default is 1 min.
   *
   * @return bool
   *   TRUE if callable returns TRUE.
   *
   * @throws \Exception
   */
  public function wait(callable $callable, array $args, string $message = '', int $maxWait = 60 * 1000): bool {
    $checkEvery = 1 * 1000;
    $start = microtime(TRUE) * 1000;
    $end = $start + $maxWait;

    if (!$message) {
      $method_name = is_array($callable) ? $callable[1] : $callable;
      $message = "Waiting for $method_name() to return true.";
    }

    // For some reason we can't reuse $start here.
    while (microtime(TRUE) * 1000 < $end) {
      $this->logger->info($message);
      try {
        if (call_user_func_array($callable, $args)) {
          return TRUE;
        }
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
      usleep($checkEvery * 1000);
    }

    throw new SettingsException("Timed out.");
  }

  /**
   * Checks a URL for a non-50x response.
   *
   * @param string $url
   *   The URL to check.
   *
   * @return bool
   *   TRUE if URL responded with a non-50x response.
   */
  public function checkUrl(string $url): bool {
    try {
      $res = $this->client->request('GET', $url, [
        'connection_timeout' => 2,
        'timeout' => 2,
        'http_errors' => FALSE,
      ]);
      if ($res->getStatusCode() && substr($res->getStatusCode(), 0, 1) != '5') {
        return TRUE;
      }
      else {
        $this->logger->info("Response code: " . $res->getStatusCode());
        $this->logger->debug($res->getBody());
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $this->logger->debug($e->getMessage());
    }
    return FALSE;
  }

}
