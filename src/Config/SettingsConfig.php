<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Consolidation\Config\Config;
use Grasmash\YamlExpander\YamlExpander;
use Psr\Log\NullLogger;

/**
 * The configuration for settings.
 */
class SettingsConfig extends Config {

  /**
   * Holds the YamlExpander class object.
   */
  protected YamlExpander $expander;

  /**
   * Config Constructor.
   *
   * @param string[] $data
   *   Data array, if available.
   */
  public function __construct(array $data = []) {
    parent::__construct($data);
    $logger = new NullLogger();
    $this->expander = new YamlExpander($logger);
  }

  /**
   * Replace YAML placeholders in a given file, using config object.
   *
   * @param string $filename
   *   The file in which placeholders should be expanded.
   */
  public function replaceFileVariables(string $filename): void {
    $expanded_contents = $this->expander->expandArrayProperties(file($filename), $this->export());
    file_put_contents($filename, implode("", $expanded_contents));
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    if ($value === 'false') {
      $value = FALSE;
    }
    elseif ($value === 'true') {
      $value = TRUE;
    }

    // Expand properties in string. We do this here so that one can pass
    // -D drush.alias=${drush.ci.aliases} at runtime and still expand
    // properties.
    if (is_string($value) && strstr($value, '$')) {
      $expanded = $this->expander->expandArrayProperties([$value], $this->export());
      $value = $expanded[0];
    }

    return parent::set($key, $value);

  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $defaultOverride = NULL) {
    $value = parent::get($key, $defaultOverride);

    // Last ditch effort to expand properties that may not have been processed.
    if (is_string($value) && strstr($value, '$')) {
      $expanded = $this->expander->expandArrayProperties([$value], $this->export());
      $value = $expanded[0];
    }

    return $value;
  }

}
