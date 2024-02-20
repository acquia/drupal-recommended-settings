<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Config;

use Robo\Common\ConfigAwareTrait as RoboConfigAwareTrait;

/**
 * Adds custom methods to RoboConfigAwareTrait.
 */
trait ConfigAwareTrait {

  use RoboConfigAwareTrait;

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
