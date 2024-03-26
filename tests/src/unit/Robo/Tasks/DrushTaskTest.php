<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Robo\Tasks;

use Acquia\Drupal\RecommendedSettings\Robo\Tasks\LoadTasks;
use Acquia\Drupal\RecommendedSettings\Tests\CommandsTestBase;
use Acquia\Drupal\RecommendedSettings\Tests\Helpers\TestDrushTask;
use Robo\Exception\TaskException;
use Robo\LoadAllTasks;
use Robo\ResultData;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Unit test for the ConfigAwareTrait trait.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Robo\Tasks\DrushTask
 */
class DrushTaskTest extends CommandsTestBase {
  use LoadTasks;
  use LoadAllTasks;

  protected function setUp(): void {
    parent::setUp();
    $this->getConfig()->set("drush.alias", "self");
    $this->getConfig()->set("drush.bin", "./vendor/bin/drush");
    $this->drushTaskClass = TestDrushTask::class;
  }

  /**
   * Tests the basic Robo Drush task commands.
   */
  public function testBasicDrushTaskCommands(): void {
    $result = $this->taskDrush()
      ->drush(["--version"])
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self --version --no-interaction",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->drush(["site:install", "minimal"])
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self site:install minimal --no-interaction",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->includePath('/var/www/html/project/drush')
      ->drush("status")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self status --no-interaction --include=/var/www/html/project/drush",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->ansi(TRUE)
      ->drush("status")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self status --no-interaction --ansi",
       $result->getMessage(),
    );

    $this->getConfig()->set("drush.bin", "");
    $result = $this->taskDrush()
      ->ansi("true")
      ->drush("status")
      ->run();
    $this->assertEquals(
      "drush @self status --no-interaction --ansi",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->stopOnFail()
      ->drush("updb")
      ->drush("cr")
      ->run();
    $this->assertEquals(
      "drush @self cr --no-interaction\ndrush @self updb --no-interaction\n",
      $result->getMessage(),
    );
  }

  /**
   * Tests the Robo Drush commands with uri parameter.
   */
  public function testDrushTaskCommandsWithUri(): void {
    $result = $this->taskDrush()
      ->uri("site1")
      ->drush(["site:install", "minimal"])
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self site:install minimal --uri=site1 --no-interaction",
      $result->getMessage(),
    );

    $this->expectException(TaskException::class);
    $this->taskDrush()
      ->option("uri", "site1")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self site:install minimal --uri=site1 --no-interaction",
      $result->getMessage(),
    );
  }

  /**
   * Tests the Robo Drush commands with verbose.
   */
  public function testDrushTaskCommandsWithVerbose(): void {
    $result = $this->taskDrush()
      ->verbose(TRUE)
      ->drush("status")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self status --no-interaction -v",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->verbose("yes")
      ->drush("status")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self status --no-interaction -v",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->veryVerbose("yes")
      ->drush("status")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self status --no-interaction -vv",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->debug('true')
      ->drush("status")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self status --no-interaction -vvv",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->verbose(OutputInterface::VERBOSITY_VERBOSE)
      ->drush("cr")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self cr --no-interaction -v",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->verbose(OutputInterface::VERBOSITY_VERY_VERBOSE)
      ->drush("cr")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self cr --no-interaction -vv",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->verbose(OutputInterface::VERBOSITY_DEBUG)
      ->drush("cr")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self cr --no-interaction -vvv",
      $result->getMessage(),
    );

    $result = $this->taskDrush()
      ->setVerbosityThreshold(OutputInterface::VERBOSITY_DEBUG)
      ->drush("cr")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self cr --no-interaction -vvv",
      $result->getMessage(),
    );
  }

  /**
    * Tests the Robo Drush command exception.
    */
  public function testDrushTaskCommandException(): void {
    $result = $this->taskDrush()
      ->stopOnFail()
      ->drush("cr")
      ->drush("--error")
      ->run();
    $this->assertEquals(
      "./vendor/bin/drush @self --error --no-interaction\n./vendor/bin/drush @self cr --no-interaction\n",
      $result->getMessage(),
    );
    $this->assertEquals(ResultData::EXITCODE_ERROR, $result->getExitCode());
  }

}
