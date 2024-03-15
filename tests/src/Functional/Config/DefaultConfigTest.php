<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Config;

use Acquia\Drupal\RecommendedSettings\Config\DefaultConfig;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;

/**
 * Functional test for the DefaultConfig class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Config\DefaultConfig
 */
class DefaultConfigTest extends FunctionalTestBase {

  /**
   * Tests the default config data.
   */
  public function testDefaultConfigData(): void {
    $drupal_root = $this->getDrupalRoot();
    $project_root = $this->getProjectRoot();

    $default_drush_config = new DefaultConfig($drupal_root);
    $actual = $default_drush_config->export();
    $this->assertArrayHasKey("tmp", $actual);
    $this->assertArrayHasKey("dir", $actual['tmp']);
    unset($actual['tmp']);
    $this->assertEquals($actual, [
      "repo" => [
        "root" => $project_root,
      ],
      "docroot" => $drupal_root,
      "composer" => [
        "bin" => $project_root . "/vendor/bin",
      ],
      "site" => "default",
    ]);
  }

}
