<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Consolidation\Config\Config;
use Drush\Config\DrushConfig;
use Grasmash\YamlExpander\YamlExpander;
use Psr\Log\NullLogger;

/**
 * The configuration for settings.
 */
class DefaultDrushConfig extends Config {

  /**
   * Config Constructor.
   *
   * @param string[] $data
   *   Data array, if available.
   */
  public function __construct(DrushConfig $config) {
    $uri = $config->get("options.uri") ?? "default";
    $config->set('repo.root', $config->get("runtime.project"));
    $config->set('docroot', $config->get("options.root"));
    $config->set('composer.bin', $config->get("drush.vendor-dir") . '/bin');
    $config->set('drush.uri', $config->get("options.uri"));
    $config->set('site',  $config->get("options.uri"));
    if ($config->get("options.ansi")) {
      $config->set('drush.ansi', $config->get("options.ansi"));
    }
    $config->set('drush.bin', $config->get("runtime.drush-script"));
    $config->setDefault('drush.alias', "self");
    parent::__construct($config->export());
  }

}
