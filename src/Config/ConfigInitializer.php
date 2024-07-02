<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Consolidation\Config\ConfigInterface;
use Consolidation\Config\Loader\YamlConfigLoader;
use Drush\Config\DrushConfig;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Config init.
 */
class ConfigInitializer {

  const CONFIG_FILE_PATH = "/config/build.yml";

  /**
   * Config.
   */
  protected DrushConfig $config;

  /**
   * Loader.
   */
  protected YamlConfigLoader $loader;

  /**
   * Processor.
   */
  protected YamlConfigProcessor $processor;

  /**
   * Input.
   */
  protected ?InputInterface $input;

  /**
   * Site.
   */
  protected string $site = "";

  /**
   * ConfigInitializer constructor.
   *
   * @param \Consolidation\Config\ConfigInterface|\Drush\Config\DrushConfig $config
   *   The config object.
   * @param \Symfony\Component\Console\Input\InputInterface|null $input
   *   An input object or null.
   */
  public function __construct(ConfigInterface $config, ?InputInterface $input = NULL) {
    $this->config = $config;
    $this->loader = new YamlConfigLoader();
    $this->processor = new YamlConfigProcessor();
    $this->input = $input;
  }

  /**
   * Set site.
   *
   * @param string $site
   *   Site.
   */
  public function setSite(string $site): void {
    $this->site = $site;
    $this->config->set("site", $site);
    $this->config->set("drush.uri", $site);
  }

  /**
   * Determine site.
   */
  protected function determineSite(): string {
    // If input parameter has site option, then use that.
    if ($this->input instanceof InputInterface && $this->input->hasOption("uri") && $this->input->getOption("uri")) {
      return $this->input->getOption("uri");
    }

    return 'default';
  }

  /**
   * Initialize.
   */
  public function initialize(): ConfigInitializer {
    if (!$this->site) {
      $this->site = $this->determineSite();
      $this->setSite($this->site);
    }
    $environment = $this->determineEnvironment();
    $this->config->set('environment', $environment);

    $this->processor->add($this->config->export());
    return $this;
  }

  /**
   * Load config.
   */
  public function loadAllConfig(): ConfigInitializer {
    $this->loadDefaultConfig();
    $this->loadProjectConfig();
    $this->loadSiteConfig();
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
  protected function loadProjectConfig(): ConfigInitializer {
    $this->processor->extend($this->loader->load($this->config->get('repo.root') . "/drs/config.yml"));
    return $this;
  }

  /**
   * Load config.
   *
   * @return $this
   *   Config.
   */
  protected function loadSiteConfig(): ConfigInitializer {
    if ($this->site) {
      // Since docroot can change in the project, we need to respect that here.
      $this->config->replace($this->processor->export());
      $this->processor->extend($this->loader->load($this->config->get('docroot') . "/sites/{$this->site}/drs/config.yml"));
    }

    return $this;
  }

  /**
   * Add/Overrides the config data.
   *
   * @param string[] $data
   *   An array of data.
   */
  public function addConfig(array $data): DrushConfig {
    $this->processor->add($data);
    return $this->config;
  }

  /**
   * Determine env.
   *
   * @throws \ReflectionException
   */
  protected function determineEnvironment(): string {
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
  public function processConfig(): DrushConfig {
    $this->config->replace($this->processor->export());
    return $this->config;
  }

}
