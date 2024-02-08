<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Config;

use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;
use Consolidation\Config\Config;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;

class ConfigInitializerTest extends FunctionalBaseTest {

  public function testSetSite(): void {
    $config = new Config();
    $config_initializer = new ConfigInitializer($config);

    $reflectionClass = $this->getReflectionClass($config_initializer::class);
    $method = $this->getReflectionMethod($reflectionClass, "setSite");
    $result = $method->invokeArgs($config_initializer, ['site1']);
    $this->assertNull($result);

    $property = $this->getReflectionProperty($reflectionClass, 'config');
    $value = $property->getValue($config_initializer);

    $this->assertEquals($value->export(), [
      "site" => "site1",
      "drush" => [
        "uri" => "site1",
      ],
    ]);
  }

  public function testDetermineSite(): void {
    $config = new Config();
    $config_initializer = new ConfigInitializer($config);
    $reflectionClass = $this->getReflectionClass($config_initializer::class);
    $method = $this->getReflectionMethod($reflectionClass, "determineSite");
    $result = $method->invoke($config_initializer);

    $this->assertSame("default", $result);

    $input = new StringInput("");
    $inputOption = new InputOption("uri", "l", InputOption::VALUE_OPTIONAL);
    $inputDefinition = new InputDefinition([$inputOption]);
    $input->bind($inputDefinition);

    $input->setOption("uri", "site1");

    $config_initializer = new ConfigInitializer($config, $input);
    $reflectionClass = $this->getReflectionClass($config_initializer::class);
    $method = $this->getReflectionMethod($reflectionClass, "determineSite");
    $result = $method->invoke($config_initializer);

    $this->assertSame("site1", $result);
  }

  public function testDetermineEnvironment(): void {
    putenv("CI=");
    $config = new Config();
    $config_initializer = new ConfigInitializer($config);
    $reflectionClass = $this->getReflectionClass($config_initializer::class);
    $method = $this->getReflectionMethod($reflectionClass, "determineEnvironment");
    $result = $method->invoke($config_initializer);
    $this->assertSame("local", $result);

    putenv("CI=true");
    $result = $method->invoke($config_initializer);
    $this->assertSame("ci", $result);
    putenv("CI=");

    $config = new Config();
    $config->set("environment", "dev");
    $config_initializer = new ConfigInitializer($config);
    $reflectionClass = $this->getReflectionClass($config_initializer::class);
    $method = $this->getReflectionMethod($reflectionClass, "determineEnvironment");
    $result = $method->invoke($config_initializer);
    $this->assertSame("dev", $result);
  }

  public function testInitialize(): void {
    putenv("CI=");
    $config = new Config();
    $configInitializer = new ConfigInitializer($config);
    $configInitializer->initialize();
    $this->assertEquals($configInitializer->processConfig()->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
    ]);

    $configInitializer = new ConfigInitializer($config);
    $configInitializer->setSite("site1");
    $configInitializer->initialize();

    $this->assertEquals($configInitializer->processConfig()->export(), [
      "site" => "site1",
      "drush" => [
        "uri" => "site1",
      ],
      "environment" => "local",
    ]);

    putenv("CI=true");
    $config = new Config();
    $configInitializer = new ConfigInitializer($config);
    $configInitializer->initialize();

    $this->assertEquals($configInitializer->processConfig()->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "ci",
    ]);
    putenv("CI=");
  }

  public function testLoadAllConfig(): void {
    putenv("CI=");
    $config = new Config();
    $configInitializer = new ConfigInitializer($config);
    $config = $configInitializer->initialize()->loadAllConfig()->processConfig();
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

    $config = new Config();
    $projectDir = $this->getFixtureDirectory() . "/project";
    $config->set("repo.root", $projectDir);
    $configInitializer = new ConfigInitializer($config);
    $config = $configInitializer->initialize()->loadAllConfig()->processConfig();

    $this->assertEquals($config->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
      "repo" => [
        "root" => $projectDir,
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
    ]);

    $config = new Config();
    $projectDir = $this->getFixtureDirectory() . "/project";
    $config->set("repo.root", $projectDir);
    $config->set("docroot", "$projectDir/docroot");
    $configInitializer = new ConfigInitializer($config);
    $configInitializer = $configInitializer->initialize()->loadAllConfig();

    $this->assertEquals($configInitializer->processConfig()->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
      "repo" => [
        "root" => $projectDir,
      ],
      "docroot" => "$projectDir/docroot",
      "drupal" => [
        "db" => [
          "database" => "default",
          "username" => "root",
          "password" => "root",
          "host" => "127.0.0.1",
          "port" => 3306,
        ],
      ],
    ]);

    $configInitializer->addConfig([
      "drupal" => [
        "db" => [
          "database" => "override",
        ],
      ],
    ]);

    $this->assertEquals($configInitializer->processConfig()->export(), [
      "site" => "default",
      "drush" => [
        "uri" => "default",
      ],
      "environment" => "local",
      "repo" => [
        "root" => $projectDir,
      ],
      "docroot" => "$projectDir/docroot",
      "drupal" => [
        "db" => [
          "database" => "override",
          "username" => "root",
          "password" => "root",
          "host" => "127.0.0.1",
          "port" => 3306,
        ],
      ],
    ]);

  }

}
