<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Drush\Config\DrushConfig;

/**
 * The configuration for settings.
 */
class DefaultDrushConfig extends DrushConfig {

  /**
   * Config Constructor.
   *
   * @param \Drush\Config\DrushConfig $config
   *   The drush config object.
   */
  public function __construct(?DrushConfig $config = NULL) {
    parent::__construct();
    if ($config) {
      $this->set('repo.root', $config->get("runtime.project"));
      $this->set('docroot', $config->get("options.root"));
      $this->set('composer.bin', $config->get("drush.vendor-dir") . '/bin');
      if ($config->get("options.ansi") !== NULL) {
        $this->set('drush.ansi', $config->get("options.ansi"));
      }
      $this->set('drush.bin', $config->get("runtime.drush-script"));
      $this->setDefault('drush.alias', "self");
      $this->setDefault('drush.uri', $config->get('options.uri'));
      $this->combine($config->export());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function combine($data) {
    $this->getContext(self::PROCESS_CONTEXT)->combine($data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function replace($data) {
    $this->getContext(self::PROCESS_CONTEXT)->replace($data);
    return $this;
  }

}
