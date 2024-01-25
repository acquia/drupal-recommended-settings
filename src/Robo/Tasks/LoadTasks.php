<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Tasks;

use Robo\Collection\CollectionBuilder;

/**
 * Load Settings's custom Robo tasks.
 */
trait LoadTasks {

  /**
   * Task drush.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   Drush task.
   */
  protected function taskDrush(): CollectionBuilder {
    /** @var \Acquia\Drupal\RecommendedSettings\Robo\Tasks\DrushTask $task */
    $task = $this->task(DrushTask::class);
    /** @var \Symfony\Component\Console\Output\OutputInterface $output */
    $output = $this->output();
    $task->setVerbosityThreshold($output->getVerbosity());

    return $task;
  }

}
