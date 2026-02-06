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

    // Check basic structure is correct.
    $config_export = $config->export();
    $this->assertEquals("default", $config_export['site']);
    $this->assertEquals("default", $config_export['drush']['uri']);
    $this->assertEquals("local", $config_export['environment']);
    $this->assertEquals($project_root, $config_export['repo']['root']);
    $this->assertArrayHasKey('drupal', $config_export);
    $this->assertArrayHasKey('db', $config_export['drupal']);
    $this->assertArrayHasKey('multisites', $config_export);
    $this->assertContains('acms', $config_export['multisites']);

    $config = new DefaultDrushConfig();
    $config->set("repo.root", $project_root);
    $config->set("docroot", $this->getDrupalRoot());
    $config_initializer = new ConfigInitializer($config);
    $config_initializer = $config_initializer->initialize()->loadAllConfig();

    // Check structure with docroot.
    $config_export = $config_initializer->processConfig()->export();
    $this->assertEquals("default", $config_export['site']);
    $this->assertEquals("default", $config_export['drush']['uri']);
    $this->assertEquals("local", $config_export['environment']);
    $this->assertEquals($project_root, $config_export['repo']['root']);
    $this->assertEquals($this->getDrupalRoot(), $config_export['docroot']);
    $this->assertArrayHasKey('drupal', $config_export);
    $this->assertArrayHasKey('db', $config_export['drupal']);
    $this->assertArrayHasKey('multisites', $config_export);
    $this->assertContains('acms', $config_export['multisites']);

    $config_initializer->addConfig([
      "drupal" => [
        "db" => [
          "database" => "override",
        ],
      ],
    ]);

    // Check override works correctly.
    $config_export = $config_initializer->processConfig()->export();
    $this->assertEquals("override", $config_export['drupal']['db']['database']);
    $this->assertEquals($drupal_root, $config_export['docroot']);
    $this->assertContains('acms', $config_export['multisites']);

  }

}
