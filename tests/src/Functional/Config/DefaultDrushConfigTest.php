<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Config;

use Acquia\Drupal\RecommendedSettings\Config\DefaultDrushConfig;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;
use Drush\Config\DrushConfig;

/**
 * Functional test for the DefaultDrushConfig class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Config\DefaultDrushConfig
 */
class DefaultDrushConfigTest extends FunctionalTestBase {

  /**
   * Tests the default config data.
   */
  public function testDefaultDrushConfigData(): void {
    $drupal_root = $this->getDrupalRoot();
    $project_root = $this->getProjectRoot();

    $drushConfig = new DrushConfig();
    $drushConfig->set("runtime.project", $project_root);
    $drushConfig->set("options.root", $drupal_root);
    $drushConfig->set("drush.vendor-dir", $project_root . "/vendor");
    $drushConfig->set("options.ansi", TRUE);
    $drushConfig->set('drush.uri', '/var/www/html/acms.prod/vendor/bin');
    $drushConfig->set("runtime.drush-script", $project_root . "/vendor/bin/drush");

    $default_drush_config = new DefaultDrushConfig($drushConfig);
    $actual = $default_drush_config->export();
    $this->assertEquals($actual, [
      "drush" => [
        "alias" => "self",
        "uri" => '/var/www/html/acms.prod/vendor/bin',
        "ansi" => TRUE,
        "bin" => $project_root . "/vendor/bin/drush",
        "vendor-dir" => $project_root . "/vendor",
      ],
      "runtime" => [
        "project" => $project_root,
        "drush-script" => $project_root . "/vendor/bin/drush",
      ],
      "options" => [
        "root" => $drupal_root,
        "ansi" => TRUE,
      ],
      "repo" => [
        "root" => $project_root,
      ],
      "docroot" => $drupal_root,
      "composer" => [
        "bin" => $project_root . "/vendor/bin",
      ],
    ]);
  }

}
