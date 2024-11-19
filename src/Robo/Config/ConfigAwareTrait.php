<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Config;

use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Config\DefaultDrushConfig;
use Drush\Config\ConfigAwareTrait as DrushConfigAwareTrait;
use Drush\Config\DrushConfig;

/**
 * Adds custom methods to DrushConfigAwareTrait.
 */
trait ConfigAwareTrait {

  use DrushConfigAwareTrait {
    DrushConfigAwareTrait::getConfig as parentDrushGetConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(): DrushConfig {
    if (!$this->config instanceof DefaultDrushConfig) {
      $this->config = new DefaultDrushConfig($this->parentDrushGetConfig());
    }
    return $this->config;
  }

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
