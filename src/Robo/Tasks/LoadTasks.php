<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Tasks;

use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Load Settings's custom Robo tasks.
 */
trait LoadTasks {

  /**
    * An instance of drush task class.
    */
  protected string $drushTaskClass = DrushTask::class;

  /**
   * Task drush.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   Drush task.
   */
  protected function taskDrush(): CollectionBuilder {
    /** @var \Acquia\Drupal\RecommendedSettings\Robo\Tasks\DrushTask $task */
    $task = $this->task($this->drushTaskClass);

    // We can't directly use $this->output() as it throws error with PHPUnit 10
    // because it defines output() method with a different method signature.
    /** @var \Symfony\Component\Console\Output\OutputInterface $output */
    $output = ($this->output() instanceof OutputInterface) ? $this->output() : $this->getOutput();
    $task->setVerbosityThreshold($output->getVerbosity());

    return $task;
  }

}
