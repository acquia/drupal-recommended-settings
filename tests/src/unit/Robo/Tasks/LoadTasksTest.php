<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Robo\Tasks;

use Acquia\Drupal\RecommendedSettings\Robo\Tasks\DrushTask;
use Acquia\Drupal\RecommendedSettings\Robo\Tasks\LoadTasks;
use Acquia\Drupal\RecommendedSettings\Tests\CommandsTestBase;
use Robo\Collection\CollectionBuilder;
use Robo\LoadAllTasks;

class LoadTasksTest extends CommandsTestBase {
  use LoadAllTasks;
  use LoadTasks;

  public function testLoadTask(): void {
    $this->assertInstanceOf(CollectionBuilder::class, $this->taskDrush());
    $this->assertEquals(DrushTask::class, $this->drushTaskClass);
  }

}
