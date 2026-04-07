<?php

//phpcs:disable
namespace Acquia\Drupal\RecommendedSettings\Config;

use Consolidation\Config\Config;

/**
 * The configuration for settings.
 *
 * @deprecated in acquia/drupal-recommended-settings:1.2.0 and will be removed from acquia/drupal-recommended-settings:1.3.0.
 * Use ConfigResolver::resolve() to resolve array values instead.
 */
class SettingsConfig extends Config {

    /**
     * The config replacer.
     */
    private ConfigResolver $resolver;

    /**
     * Config Constructor.
     *
     * @param string[] $data
     *   Data array, if available.
     */
    public function __construct(array $data = []) {
        trigger_error("The " . __CLASS__ . " class is deprecated in acquia/drupal-recommended-settings:1.2.0 and will be removed from acquia/drupal-recommended-settings:1.3.0.", E_USER_DEPRECATED);
        parent::__construct($data);
        $this->resolver = new ConfigResolver($this);
    }

    /**
     * Replace YAML placeholders in a given file, using config object.
     *
     * @param string $filename
     *   The file in which placeholders should be expanded.
     */
    public function replaceFileVariables(string $filename): void {
        trigger_error("The " . __CLASS__ . "::replaceFileVariables is deprecated in acquia/drupal-recommended-settings:1.2.0 and will be removed in acquia/drupal-recommended-settings:1.3.0. Use ConfigResolver::resolve() instead.", E_USER_DEPRECATED);
        $data = ['content' => file_get_contents($filename)];
        $replaced = $this->resolver->resolve($data);
        file_put_contents($filename, $replaced['content']);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value) {
        $processed = $this->resolver->resolve(['value' => $value]);
        return parent::set($key, $processed['value']);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultFallback = NULL) {
        $value = parent::get($key, $defaultFallback);
        $processed = $this->resolver->resolve(['value' => $value]);
        return $processed['value'];
    }

}