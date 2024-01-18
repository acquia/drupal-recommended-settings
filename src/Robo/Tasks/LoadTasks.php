<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Tasks;

/**
 * Load Settings's custom Robo tasks.
 */
trait LoadTasks {

  /**
   * Task drush.
   *
   * @return \Acquia\Drupal\RecommendedSettings\Robo\Tasks\DrushTask
   *   Drush task.
   */
  protected function taskDrush() {
    /** @var \Acquia\Blt\Robo\Tasks\DrushTask $task */
    $task = $this->task(DrushTask::class);
    /** @var \Symfony\Component\Console\Output\OutputInterface $output */
    $output = $this->output();
    $task->setVerbosityThreshold($output->getVerbosity());

    return $task;
  }

}
