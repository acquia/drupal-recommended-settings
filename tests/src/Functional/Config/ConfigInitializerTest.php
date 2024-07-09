<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Config;

use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Config\DefaultDrushConfig;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;

/**
 * Functional test for the ConfigInitializer class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer
 */
class ConfigInitializerTest extends FunctionalTestBase {

  /**
   * Tests setSite() method.
   *
   * @throws \ReflectionException
   */
  public function testSetSite(): void {
    $config = new DefaultDrushConfig();
    $config_initializer = new ConfigInitializer($config);

    $method = $this->getReflectionMethod($config_initializer::class, "setSite");
    $result = $method->invokeArgs($config_initializer, ['site1']);
    $this->assertNull($result);

    $property = $this->getReflectionProperty($config_initializer::class, 'config');
    $value = $property->getValue($config_initializer);

    $this->assertEquals($value->export(), [
      "site" => "site1",
      "drush" => [
        "uri" => "site1",
      ],
    ]);
  }

  /**
   * Tests determineSite() method.
   *
   * @throws \ReflectionException
   */
  public function testDetermineSite(): void {
    $config = new DefaultDrushConfig();
    $config_initializer = new ConfigInitializer($config);
    $method = $this->getReflectionMethod($config_initializer::class, "determineSite");
    $result = $method->invoke($config_initializer);

    $this->assertSame("default", $result);

    $input = new StringInput("");
    $input_option = new InputOption("uri", "l", InputOption::VALUE_OPTIONAL);
    $input_definition = new InputDefinition([$input_option]);
    $input->bind($input_definition);

    $input->setOption("uri", "site1");

    $config_initializer = new ConfigInitializer($config, $input);
    $method = $this->getReflectionMethod($config_initializer::class, "determineSite");
    $result = $method->invoke($config_initializer);

    $this->assertSame("site1", $result);
  }

  /**
   * Tests determineEnvironment() method.
   *
   * @throws \ReflectionException
   */
  public function testDetermineEnvironment(): void {
    putenv("CI=");
    $config = new DefaultDrushConfig();
    $config_initializer = new ConfigInitializer($config);
    $method = $this->getReflectionMethod($config_initializer::class, "determineEnvironment");
    $result = $method->invoke($config_initializer);
    $this->assertSame("local", $result);

    putenv("CI=true");
    $result = $method->invoke($config_initializer);
    $this->assertSame("ci", $result);
    putenv("CI=");

    $config = new DefaultDrushConfig();
    $config->set("environment", "dev");
    $config_initializer = new ConfigInitializer($config);
    $method = $this->getReflectionMethod($config_initializer::class, "determineEnvironment");
    $result = $method->invoke($config_initializer);
    $this->assertSame("dev", $result);
  }

  /**
   * Tests the initialize() method.
   */
  public function testInitialize(): void {
    putenv("CI=");
    $config = new DefaultDrushConfig();
    $config_initializer = new ConfigInitializer($config);
    $config_initializer->initialize();
    $this->assertEquals($config_initializer->processConfig()->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
    ]);

    $config_initializer = new ConfigInitializer($config);
    $config_initializer->setSite("site1");
    $config_initializer->initialize();

    $this->assertEquals($config_initializer->processConfig()->export(), [
      "site" => "site1",
      "drush" => [
        "uri" => "site1",
      ],
      "environment" => "local",
    ]);

    putenv("CI=true");
    $config = new DefaultDrushConfig();
    $config_initializer = new ConfigInitializer($config);
    $config_initializer->initialize();

    $this->assertEquals($config_initializer->processConfig()->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "ci",
    ]);
    putenv("CI=");
  }

  /**
   * Tests the loadAllConfig() method.
   */
  public function testLoadAllConfig(): void {
    putenv("CI=");
    $config = new DefaultDrushConfig();
    $config_initializer = new ConfigInitializer($config);
    $config = $config_initializer->initialize()->loadAllConfig()->processConfig();
    $this->assertEquals($config->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
      "drupal" => [
        "db" => [
          "database" => "drupal",
          "username" => "drupal",
          "password" => "drupal",
          "host" => "localhost",
          "port" => 3306,
        ],
      ],
    ]);

    $config = new DefaultDrushConfig();
    $project_root = $this->getProjectRoot();
    $drupal_root = $this->getDrupalRoot();
    $config->set("repo.root", $project_root);
    $config_initializer = new ConfigInitializer($config);
    $config = $config_initializer->initialize()->loadAllConfig()->processConfig();

    $this->assertEquals($config->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
      "repo" => [
        "root" => $project_root,
      ],
      "drupal" => [
        "db" => [
          "database" => "mydatabase",
          "username" => "drupal",
          "password" => "drupal",
          "host" => "localhost",
          "port" => 3306,
        ],
      ],
      "multisites" => [
        "acms",
      ],
    ]);

    $config = new DefaultDrushConfig();
    $config->set("repo.root", $project_root);
    $config->set("docroot", $this->getDrupalRoot());
    $config_initializer = new ConfigInitializer($config);
    $config_initializer = $config_initializer->initialize()->loadAllConfig();

    $this->assertEquals($config_initializer->processConfig()->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
      "repo" => [
        "root" => $project_root,
      ],
      "docroot" => $this->getDrupalRoot(),
      "drupal" => [
        "db" => [
          "database" => "default",
          "username" => "root",
          "password" => "root",
          "host" => "127.0.0.1",
          "port" => 3306,
        ],
      ],
      "multisites" => [
        "acms",
      ],
    ]);

    $config_initializer->addConfig([
      "drupal" => [
        "db" => [
          "database" => "override",
        ],
      ],
    ]);

    $this->assertEquals($config_initializer->processConfig()->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
      "repo" => [
        "root" => $project_root,
      ],
      "docroot" => $drupal_root,
      "drupal" => [
        "db" => [
          "database" => "override",
          "username" => "root",
          "password" => "root",
          "host" => "127.0.0.1",
          "port" => 3306,
        ],
      ],
      "multisites" => [
        "acms",
      ],
    ]);

  }

}
