<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Traits;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Custom OutputAwareTrait defined by the DRS without defining
 * method output() as it throws error with PHPUnit 10.
 *
 * @see \Robo\Common\OutputAwareTrait
 */
trait OutputAwareTrait {

  /**
   * Holds Console output object.
   */
  protected OutputInterface $output;

  /**
   *
   * @return $this
   *
   * @see \Robo\Contract\OutputAwareInterface::setOutput()
   */
  public function setOutput(OutputInterface $output): static {
    $this->output = $output;
    return $this;
  }

  /**
   */
  protected function stderr(): OutputInterface {
    $output = $this->getOutput();
    if ($output instanceof ConsoleOutputInterface) {
      $output = $output->getErrorOutput();
    }
    return $output;
  }

  /**
   * Returns an instance of OutputInterface object.
   * This method is deprecated in Robo IO.php, hence defined here.
   */
  protected function getOutput(): OutputInterface {
    if (!isset($this->output)) {
      $this->setOutput(new NullOutput());
    }
    return $this->output;
  }

  /**
   * We are using our own IO (DrsIO) instead of the IO trait provided by Robo.
   * The PHPUnit 9 doesn't have output() method, so when DrsIO's logConfig
   * method calls output(), it throws a "Call to undefined method" error.
   * We are handling that case here.
   *
   * @todo: Remove the method below once support for the PHPUnit 9 is dropped.
   *
   * @param string name
   *   The method name to call.
   * @param array<string> $arguments
   *   An array of arguments to pass to method.
   */
  public function __call(string $name, array $arguments): OutputInterface {
    if ($name == "output") {
      return $this->getOutput();
    }
    throw new \BadMethodCallException("Call to undefined method " . __NAMESPACE__ . "::$name()");
  }

}
