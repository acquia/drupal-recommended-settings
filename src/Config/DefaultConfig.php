<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

/**
 * The configuration for settings.
 */
class DefaultConfig extends DefaultDrushConfig {

  /**
   * Config Constructor.
   *
   * @param string $drupal_root
   *   The path to Drupal webroot.
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
