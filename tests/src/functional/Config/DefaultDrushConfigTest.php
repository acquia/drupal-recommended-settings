<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Config;

use Acquia\Drupal\RecommendedSettings\Config\DefaultDrushConfig;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;
use Drush\Config\DrushConfig;

class DefaultDrushConfigTest extends FunctionalBaseTest {

  public function testDefaultDrushConfigData(): void {
    $drupal_root = $this->getFixtureDirectory() . "/project/docroot";
    $project_root = dirname($drupal_root);

    $drushConfig = new DrushConfig();
    $drushConfig->set("runtime.project", $project_root);
    $drushConfig->set("options.root", $drupal_root);
    $drushConfig->set("drush.vendor-dir", $project_root . "/vendor");
    $drushConfig->set("options.ansi", TRUE);
    $drushConfig->set("runtime.drush-script", $project_root . "/vendor/bin/drush");

    $default_drush_config = new DefaultDrushConfig($drushConfig);
    $actual = $default_drush_config->export();
    $this->assertEquals($actual, [
      "drush" => [
        "alias" => "self",
        "vendor-dir" => $project_root . "/vendor",
        "ansi" => TRUE,
        "bin" => $project_root . "/vendor/bin/drush",
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
