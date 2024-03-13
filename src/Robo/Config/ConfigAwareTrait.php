<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Config;

use Drush\Config\ConfigAwareTrait as DrushConfigAwareTrait;

/**
 * Adds custom methods to DrushConfigAwareTrait.
 */
trait ConfigAwareTrait {

  use DrushConfigAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function getConfigValue($key, $default = NULL): mixed {
    if (!$this->getConfig()) {
      return $default;
    }
    return $this->getConfig()->get($key, $default);
  }

  // @todo add hasConfigValue().
}
