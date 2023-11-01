<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Consolidation\Config\Config;
use Consolidation\Config\Loader\YamlConfigLoader;

/**
 * Config init.
 */
class ConfigInitializer {

  const DEFAULT_CONFIG_FILE_PATH = "/config/build.yml";

  /**
   * Config.
   *
   * @var \Consolidation\Config\Config
   */
  protected $config;

  /**
   * Loader.
   *
   * @var \Consolidation\Config\Loader\YamlConfigLoader
   */
  protected $loader;

  /**
   * Processor.
   *
   * @var \Acquia\Drupal\RecommendedSettings\Config\YamlConfigProcessor
   */
  protected $processor;

  /**
   * Site.
   *
   * @var string
   */
  protected $site;

  /**
   * ConfigInitializer constructor.
   *
   * @param string $site
   *   Drupal site uri. Ex: site1, site2 etc.
   */
  public function __construct(string $site = "default") {
    $this->config = new Config();
    $this->loader = new YamlConfigLoader();
    $this->processor = new YamlConfigProcessor();
    $this->setSite($site);
    $this->initialize();
  }

  /**
   * Set site.
   *
   * @param string $site
   *   Given Site.
   */
  public function setSite(string $site): void {
    $this->site = $site;
    $this->config->set('site', $site);
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
    return $this;
  }

  /**
   * Load config.
   */
  protected function loadDefaultConfig(): ConfigInitializer {
    $this->addConfig($this->config->export());
    $drsDirectory = dirname(__FILE__, 3);
    $this->processor->extend($this->loader->load($drsDirectory . self::DEFAULT_CONFIG_FILE_PATH));
    return $this;
  }

  /**
   * Add/Overrides the config data.
   *
   * @param array $data
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
