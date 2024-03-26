<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Config;

use Acquia\Drupal\RecommendedSettings\Config\YamlConfigProcessor;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;

/**
 * Functional test for the YamlConfigProcessor class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Config\YamlConfigProcessor
 */
class YamlConfigProcessorTest extends FunctionalTestBase {

  /**
   * Tests the preprocess() method.
   */
  public function testPreprocess(): void {
    $config_processor = new YamlConfigProcessor();
    $config = [
      "drupal.db.database" => "drupal",
      "drupal.db.username" => "drupal",
      "drupal.db.password" => "drupal",
    ];
    $method = $this->getReflectionMethod(YamlConfigProcessor::class, 'preprocess');
    $data = $method->invokeArgs($config_processor, [$config]);
    $this->assertEquals([
      "drupal" => [
        "db" => [
          "database" => "drupal",
          "username" => "drupal",
          "password" => "drupal",
        ],
      ],
    ], $data);
  }

}
