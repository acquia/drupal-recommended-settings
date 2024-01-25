<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Consolidation\Config\Config;
use Consolidation\Config\ConfigInterface;
use Consolidation\Config\Loader\YamlConfigLoader;

/**
 * Config init.
 */
class ConfigInitializer {

  const CONFIG_FILE_PATH = "/config/build.yml";

  /**
   * Config.
   */
  protected Config $config;

  /**
   * Loader.
   */
  protected YamlConfigLoader $loader;

  /**
   * Processor.
   */
  protected YamlConfigProcessor $processor;

  /**
   * Site.
   */
  protected string $site;

  /**
   * ConfigInitializer constructor.
   *
   * @param string $site
   *   Drupal site uri. Ex: site1, site2 etc.
   */
  public function __construct(ConfigInterface $config) {
    $this->config = $config;
    $this->loader = new YamlConfigLoader();
    $this->processor = new YamlConfigProcessor();
    $this->initialize();
  }

  /**
   * Initialize.
   */
  public function initialize(): ConfigInitializer {
    $environment = $this->determineEnvironment();
    $this->config->set('environment', $environment);
    return $this;
  }

  /**
   * Load config.
   */
  public function loadAllConfig(): ConfigInitializer {
    $this->loadDefaultConfig();
    $this->loadProjectConfig();
    return $this;
  }

  /**
   * Load config.
   */
  protected function loadDefaultConfig(): ConfigInitializer {
    $this->addConfig($this->config->export());
    $drsDirectory = dirname(__FILE__, 3);
    $this->processor->extend($this->loader->load($drsDirectory . self::CONFIG_FILE_PATH));
    return $this;
  }

  /**
   * Load config.
   *
   * @return $this
   *   Config.
   */
  public function loadProjectConfig(): ConfigInitializer {
    $this->processor->extend($this->loader->load($this->config->get('repo.root') . self::CONFIG_FILE_PATH));
    return $this;
  }

  /**
   * Add/Overrides the config data.
   *
   * @param string[] $data
   *   An array of data.
   */
  public function addConfig(array $data): Config {
    $this->processor->add($data);
    return $this->config;
  }

  /**
   * Determine env.
   *
   * @throws \ReflectionException
   */
  public function determineEnvironment(): string {
    if ($this->config->get('environment')) {
      return $this->config->get('environment');
    }
    if (EnvironmentDetector::isCiEnv()) {
      return 'ci';
    }
    return 'local';
  }

  /**
   * Process config.
   */
  public function processConfig(): Config {
    $this->config->replace($this->processor->export());
    return $this->config;
  }

}
