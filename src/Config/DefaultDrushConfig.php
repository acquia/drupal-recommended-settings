<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Consolidation\Config\Config;
use Consolidation\Config\ConfigInterface;

/**
 * The configuration for settings.
 */
class DefaultDrushConfig extends Config {

  /**
   * Config Constructor.
   *
   * @param \Consolidation\Config\ConfigInterface $config
   *   The config object.
   */
  public function __construct(ConfigInterface $config) {
    $config->set('repo.root', $config->get("runtime.project"));
    $config->set('docroot', $config->get("options.root"));
    $config->set('composer.bin', $config->get("drush.vendor-dir") . '/bin');
    if ($config->get("options.ansi")) {
      $config->set('drush.ansi', $config->get("options.ansi"));
    }
    $config->set('drush.bin', $config->get("runtime.drush-script"));
    $config->setDefault('drush.alias', "self");
    parent::__construct($config->export());
  }

}
