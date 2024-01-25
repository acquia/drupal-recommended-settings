<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Consolidation\Config\Config;

/**
 * The configuration for settings.
 */
class DefaultConfig extends Config {

  /**
   * Config Constructor.
   *
   * @param string[] $data
   *   Data array, if available.
   */
  public function __construct(string $drupal_root) {
    parent::__construct();
    $repo_root = dirname($drupal_root);
    $this->set('repo.root', $repo_root);
    $this->set('docroot', $drupal_root);
    $this->set('composer.bin', $repo_root . '/vendor/bin');
    $this->set('site', 'default');
    $this->set('tmp.dir', sys_get_temp_dir());
  }

}
