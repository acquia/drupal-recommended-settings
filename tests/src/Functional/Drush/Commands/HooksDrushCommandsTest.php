<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Drush\Commands\HooksDrushCommands;
use Acquia\Drupal\RecommendedSettings\Tests\CommandsTestBase;
use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Functional test for the HooksDrushCommands class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Drush\Commands\HooksDrushCommands
 */
class HooksDrushCommandsTest extends CommandsTestBase {

  /**
   * Holds an instance of Drush Command object.
   */
  protected HooksDrushCommands $command;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->command = new HooksDrushCommands();
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $this->getDrupalRoot());
    }
  }

  /**
   * Tests the doGenerateSettings() method.
   */
  public function testDoGenerateSettings(): void {
    putenv("AH_SITE_ENVIRONMENT=ode");
    $annotation_data = $this->createMock(AnnotationData::class);
    $input = $this->createMock(InputInterface::class);
    $input->method("getOption")->willReturnCallback(function ($option) {
      return match ($option) {
        "uri", "sites-subdir" => "abcd",
        default => "abcd",
      };
    });
    $output = $this->createMock(OutputInterface::class);
    $command_data = $this->getMockBuilder(CommandData::class)
      ->setConstructorArgs([$annotation_data, $input, $output])
      ->getMock();
    $command_data->method("input")->willReturn($input);
    $this->assertFalse($this->command->doGenerateSettings($command_data));

    putenv("AH_SITE_ENVIRONMENT=");
    $this->assertTrue($this->command->doGenerateSettings($command_data));

    mkdir($this->getDrupalRoot() . "/sites/abcd");
    $this->assertTrue(touch($this->getDrupalRoot() . "/sites/abcd/settings.php"));
    $this->assertFalse($this->command->doGenerateSettings($command_data));
    putenv("AH_SITE_ENVIRONMENT=");
  }

  /**
   * Tests the doGenerateSettings() method returning FALSE.
   */
  public function testDoNotGenerateSettings(): void {
    $annotation_data = $this->createMock(AnnotationData::class);
    $input = $this->createMock(InputInterface::class);
    $input->method("getOption")->willReturnCallback(function ($option) {
      return match ($option) {
        "uri" => "default",
        "sites-subdir" => "abcd",
      };
    });
    $output = $this->createMock(OutputInterface::class);
    $command_data = $this->getMockBuilder(CommandData::class)
      ->setConstructorArgs([$annotation_data, $input, $output])
      ->getMock();
    $command_data->method("input")->willReturn($input);
    $this->assertFalse($this->command->doGenerateSettings($command_data));

    putenv("AH_SITE_ENVIRONMENT=ode");
    $this->assertFalse($this->command->doGenerateSettings($command_data));
    putenv("AH_SITE_ENVIRONMENT=");
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    @unlink($this->getDrupalRoot() . "/sites/abcd/settings.php");
    @rmdir($this->getDrupalRoot() . "/sites/abcd");
  }

}
