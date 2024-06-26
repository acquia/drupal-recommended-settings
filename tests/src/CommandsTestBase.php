<?php

namespace Acquia\Drupal\RecommendedSettings\Tests;

use Acquia\Drupal\RecommendedSettings\Robo\Config\ConfigAwareTrait;
use Acquia\Drupal\RecommendedSettings\Tests\Helpers\NullLogOutputStylers;
use Acquia\Drupal\RecommendedSettings\Tests\Traits\OutputAwareTrait;
use Consolidation\Log\Logger;
use Drush\Config\DrushConfig;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Common\BuilderAwareTrait;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Base commands to test drush commands/tasks.
 */
abstract class CommandsTestBase extends TestCase {
  use BuilderAwareTrait;
  use OutputAwareTrait;
  use ConfigAwareTrait;

  /**
   * Stores an instance of container object.
   */
  protected ContainerInterface $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createContainer();
  }

  /**
   * Initialize the Container.
   *
   * @param ContainerInterface|null $container
   *   An instance of container object or NULL.
   */
  protected function createContainer(?ContainerInterface $container = NULL): void {
    if (!$container) {
      $container = new Container();
      $output = new NullOutput();
      $this->setOutput($output);

      $config = new DrushConfig();
      $this->setConfig($config);
      $logger = new Logger($this->getOutput());
      $null_log_output = new NullLogOutputStylers;
      $logger->setLogOutputStyler($null_log_output);
      $container->add("logger", $logger);

      $app = Robo::createDefaultApplication("acquia/drupal-recommended-settings", "1.0.0");
      Robo::configureContainer($container, $app, $this->getConfig());

      $tasks = new Tasks();
      $builder = CollectionBuilder::create($container, $tasks);
      $this->setBuilder($builder);
      $container->add("builder", $builder);
    }
    $this->container = $container;
  }

  /**
   * Returns an instance of container object.
   */
  protected function getContainer(): ContainerInterface {
    return $this->container;
  }

}
