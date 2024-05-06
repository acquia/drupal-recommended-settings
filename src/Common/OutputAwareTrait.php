<?php

namespace Acquia\Drupal\RecommendedSettings\Common;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait OutputAwareTrait {

  /**
   *
   * @return $this
   *
   * @see \Robo\Contract\OutputAwareInterface::setOutput()
   */
  public function setOutput(OutputInterface $output) {
    $this->output = $output;

    return $this;
  }

  /**
   */
  public function getOutput(): OutputInterface {
    if (!isset($this->output)) {
      $this->setOutput(new NullOutput());
    }
    return $this->output;
  }

  /**
   */
  protected function stderr(): OutputInterface {
    $output = $this->output();
    if ($output instanceof ConsoleOutputInterface) {
      $output = $output->getErrorOutput();
    }
    return $output;
  }

}
