<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Tasks;

use Robo\Collection\CollectionBuilder;

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
    /** @var \Symfony\Component\Console\Output\OutputInterface $output */
    $output = $this->output();
    $task->setVerbosityThreshold($output->getVerbosity());

    return $task;
  }

}
